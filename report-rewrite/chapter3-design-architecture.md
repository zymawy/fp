# Chapter 3: System Design and Architecture

## 3.1 System Overview

The Enaam charitable donation platform employs a **service-oriented monorepo** architecture organised within a single repository. It is important to state at the outset what this architecture is not: it is not a microservices architecture. All three applications share a single PostgreSQL database, are orchestrated through a unified Docker Compose configuration, and communicate via standard REST APIs rather than through message queues or service meshes. A single database instance eliminates the need for distributed transaction coordination, data consistency protocols, or inter-service communication patterns such as sagas. This design choice is appropriate for the project's scale and reduces operational complexity while retaining a clean separation of concerns at the application level.

The system comprises three principal applications:

1. **Donor-Facing Single-Page Application (SPA)**: A React 18 application written in TypeScript, built with Vite, and styled using shadcn/ui (which composes Radix UI primitives with Tailwind CSS). This application serves as the primary interface through which donors browse charitable causes, make donations, track their contribution history, and earn achievements.

2. **REST API Server**: A Laravel 12 application running on PHP 8.2, serving as the authoritative backend for all business logic, data persistence, authentication, payment processing, and real-time event broadcasting. The API layer is built on the Dingo API package, which provides versioned routing under the `/api/v1/` namespace, and uses Fractal Transformers (via the `laravel-responder` package) to format all outbound responses consistently.

3. **Administrative Dashboard**: A Vue 3 application written in TypeScript, using Pinia for state management, Headless UI for accessible component primitives, Tailwind CSS for styling, and Chart.js for data visualisation. This dashboard enables administrators to manage causes, categories, partners, users, donations, financial reports, and platform-wide analytics.

The React SPA and Vue admin panel each maintain their own HTTP client that issues requests to the versioned API endpoints. There is no direct communication between the two frontend applications; all data flows through the centralised backend. Supporting infrastructure services are co-located in the Docker Compose environment: PostgreSQL (primary database), Redis (caching and session storage), Elasticsearch (full-text search indexing), Centrifugo (WebSocket server for real-time donation updates), Adminer (database management interface), and Mailhog (email testing).

### System Architecture Diagram

The following textual description represents the high-level system architecture. A formal diagram should accompany this description in the final submission.

```
[React 18 SPA]  <--- REST/JSON --->  [Laravel 12 API]  <--- REST/JSON --->  [Vue 3 Admin]
   (Vite)                                   |                                  (Vite)
                                            |
                              +-------------+-------------+
                              |             |             |
                        [PostgreSQL]    [Redis]    [Elasticsearch]
                              |
                        [Centrifugo] --- WebSocket ---> [Connected Clients]
                              |
                    [MyFatoorah Gateway]
                      (external service)
```


## 3.2 Database Design

### 3.2.1 Primary Key Strategy

All domain models use Universally Unique Identifiers (UUIDs) as primary keys, implemented through Laravel's `HasUuids` trait. UUIDs prevent enumeration attacks by eliminating sequential identifiers, provide collision-free key generation suitable for future horizontal scaling, and simplify data migration and backup restoration.

### 3.2.2 Entity Model

The database schema is organised around the following core entities:

**User** represents both donors and administrators, extending Laravel's `Authenticatable` base class and implementing the `JWTSubject` interface for token-based authentication. Key attributes include `name`, `email`, `password` (hashed), `first_name`, `last_name`, `avatar_url`, and `phone_number`.

**Role** defines permission levels within the platform, currently supporting Admin and Donor. Roles relate to Users through a many-to-many pivot table (`role_user`) and store a `privileges` attribute cast to JSON.

**Cause** represents a charitable initiative that accepts donations. It belongs to one Category and optionally one Partner, and has many Donations and CauseUpdates. Monetary fields (`goal_amount`, `raised_amount`) are cast to `decimal:2` for precision.

**Category** provides taxonomic classification for Causes, with `name` and `slug` attributes.

**Donation** records an individual financial contribution, linking a User to a Cause. Key attributes include `amount`, `total_amount`, `processing_fee`, `currency_code`, `payment_status`, and flags for anonymous and gift donations. A Donation has one Transaction.

**Transaction** captures payment-level detail for a Donation, including the external payment provider reference, payment method, and gateway response data stored as a JSON array.

**Partner** represents an organisational partner associated with charitable causes and has many Causes.

**FinancialReport** stores periodic financial reporting data, belonging to one Cause.

**Achievement** and **AchievementType** support the gamification system, tracking milestones earned by donors upon successful donation completion.

**PaymentMethod** stores configuration for available payment methods from the MyFatoorah gateway, including service charges, display ordering, and active status.

### 3.2.3 Relationship Summary

The following table summarises the principal entity relationships:

| Relationship | Type | Pivot/Foreign Key |
|---|---|---|
| User hasMany Donations | One-to-Many | `donations.user_id` |
| User hasMany Achievements | One-to-Many | `achievements.user_id` |
| User belongsToMany Roles | Many-to-Many | `role_user` pivot table |
| Cause belongsTo Category | Many-to-One | `causes.category_id` |
| Cause belongsTo Partner | Many-to-One | `causes.partner_id` |
| Cause hasMany Donations | One-to-Many | `donations.cause_id` |
| Cause hasMany CauseUpdates | One-to-Many | `cause_updates.cause_id` |
| Donation belongsTo User | Many-to-One | `donations.user_id` |
| Donation belongsTo Cause | Many-to-One | `donations.cause_id` |
| Donation hasOne Transaction | One-to-One | `transactions.donation_id` |
| Achievement belongsTo User | Many-to-One | `achievements.user_id` |
| Achievement belongsTo AchievementType | Many-to-One | `achievements.achievement_type_id` |
| FinancialReport belongsTo Cause | Many-to-One | `financial_reports.cause_id` |
| Partner hasMany Causes | One-to-Many | `causes.partner_id` |


## 3.3 API Design

### 3.3.1 Routing Architecture

The API is built on the Dingo API package, which provides a dedicated router with built-in support for API versioning, rate limiting, and content negotiation. All routes are defined under the `/api/v1/` URL prefix (configured via the `API_PREFIX=api` and `API_VERSION=v1` environment variables), with all controllers residing in the `App\Http\Controllers\Api` namespace.

### 3.3.2 Route Organisation

Routes are organised into three concentric groups based on access level:

| Route Group | Authentication | Route Count | Scope |
|---|---|---|---|
| Public | None | 18 | Registration, login, cause/category/partner listing, payment initiation and callbacks |
| Authenticated | Valid JWT (`api.auth`) | 16 | Profile management, donation CRUD, transaction history, WebSocket token generation |
| Admin | JWT + Admin role (`api.auth` + `admin`) | 14 | Dashboard statistics and trends, full CRUD for categories/causes/partners/users, financial reports |

### 3.3.3 Response Formatting

All API responses are formatted through Fractal Transformers, integrated via the `laravel-responder` package. Each domain model declares a corresponding transformer class (e.g., `Donation` declares `DonationTransformer`). The transformer layer decouples the internal database schema from the public API contract, enforces a uniform response envelope structure across all endpoints, supports optional relationship inclusion (enabling clients to request nested data without additional round trips), and excludes sensitive internal fields by design. The project defines fourteen transformers covering all domain entities.


## 3.4 Authentication and Authorisation Design

### 3.4.1 JWT Authentication Flow

The platform implements stateless token-based authentication using JSON Web Tokens (JWT) through the `php-open-source-saver/jwt-auth` package. The User model implements the `JWTSubject` interface, returning the user's UUID as the JWT identifier.

Upon registration or login, the server generates a signed JWT returned alongside user data; the response includes the token, its type (`bearer`), and its expiry duration. Subsequent API requests include the token in the `Authorization: Bearer <token>` header, where the `api.auth` middleware validates the signature and checks expiration. The API supports token refresh (invalidating the current token and issuing a new one for long-lived sessions) and logout (blacklisting the token to prevent further use). Password reset follows Laravel's standard `Password` facade flow, sending a reset link via email and validating the reset token upon submission.

### 3.4.2 Role-Based Access Control

Authorisation is implemented through a Role-Based Access Control (RBAC) model with two roles: **Admin** and **Donor**. Users and Roles share a many-to-many relationship through the `role_user` pivot table. The `User::isAdmin()` method iterates through the user's loaded roles and returns `true` if any role satisfies the Admin condition.

### 3.4.3 Admin Middleware

Administrative route protection is enforced through a dedicated `AdminMiddleware` class registered under the `admin` alias and applied within the `api.auth` group. This nesting ensures two-layer protection: the JWT middleware verifies that the request originates from an authenticated user (rejecting with 401 on failure), and the admin middleware verifies that the authenticated user holds an administrator role (rejecting with 403 on failure). Implementation details are discussed in Chapter 4.


## 3.5 Payment Flow Design

### 3.5.1 Payment Gateway Integration

The platform integrates with the MyFatoorah payment gateway (via the `myfatoorah/laravel-package`) to process financial transactions. MyFatoorah is a regional payment service provider that supports multiple payment methods across the Middle East and North Africa, making it appropriate for the platform's target market. The payment service layer is encapsulated in `MyFatoorahService`, which is injected into the `PaymentController` via constructor dependency injection.

### 3.5.2 Payment Processing Flow

The end-to-end payment flow proceeds through five stages. First, the donor completes the donation form on the React SPA, specifying the cause, amount, currency, and optional processing fee coverage. Second, the SPA submits a POST request to the payment endpoint, where the server atomically creates a Donation and Transaction record within a database transaction, initiates payment with MyFatoorah, receives the hosted payment page URL, and returns it to the client. If any exception occurs, the database transaction is rolled back to prevent orphaned records.

Third, the donor is redirected to the MyFatoorah hosted payment page, which handles card details in a PCI-DSS compliant environment managed entirely by MyFatoorah. Fourth, upon payment completion, a synchronous callback retrieves the payment status, updates the Donation and Transaction records, and triggers the `AchievementService` to evaluate and award any milestone achievements if the payment succeeded. The donor is then redirected to a success or failure page on the SPA.

Fifth, an asynchronous webhook provides redundancy by independently verifying payment status through a timing-safe HMAC signature comparison, ensuring database consistency even if the donor closes their browser before the callback completes. Implementation details for each stage are discussed in Chapter 4.


## 3.6 Real-Time Architecture

### 3.6.1 WebSocket Infrastructure

Real-time functionality is delivered through Centrifugo, an open-source real-time messaging server that runs as a standalone Docker container within the project's Docker Compose environment. Centrifugo handles WebSocket connection management, channel subscription, message broadcasting, and client authentication, offloading these responsibilities from the Laravel application server.

### 3.6.2 Broadcasting and Client Integration

The Laravel backend publishes events to Centrifugo via the `CentrifugoService` class, which exposes methods for publishing messages to namespaced channels and generating connection and subscription tokens. The platform defines two broadcast events: `DonationCreated` (dispatched when a new donation is recorded) and `DonationUpdated` (dispatched when a donation's status changes). Both events broadcast to a public cause-specific channel for frontend consumption and a private admin channel for administrative notifications.

On the React SPA, the `useCauseRealtime` hook subscribes to donation updates for a specific cause on mount and unsubscribes on unmount, exposing `isConnected`, `isSubscribed`, `latestUpdate`, and `error` to consuming components. WebSocket authentication uses server-issued JWT tokens: the client requests connection and subscription tokens from authenticated API endpoints, and Centrifugo validates these against its shared secret.


## 3.7 Docker Compose Infrastructure

### 3.7.1 Service Topology

The project's infrastructure is defined in a root-level `docker-compose.yml` file that orchestrates the following services:

| Service | Purpose | Port Mapping |
|---|---|---|
| `ui` | React SPA with Vite dev server | `${ENAAM_UI_PORT}:80`, `5173:5173` |
| `postgres` | Primary relational database | `${POSTGRES_PORT}:5432` |
| `redis` | Cache and session store | `${REDIS_PORT}:6379` |
| `elasticsearch` | Full-text search engine | `${ELASTICSEARCH_HOST_HTTP_PORT}:9200` |
| `centrifugo` | WebSocket real-time messaging | `${CENTRIFUGO_PORT}:8000` |
| `adminer` | Database administration interface | `${ADM_PORT}:8080` |
| `mailhog` | Email testing (SMTP capture) | `1025:1025`, `8025:8025` |

The Laravel backend runs outside Docker during development (via `php artisan serve`), communicating with the containerised services through the exposed ports. This arrangement simplifies the development feedback loop for PHP code changes while keeping infrastructure services consistent and reproducible. All service configuration is externalised through environment variables defined in a root `.env` file, including database credentials, Redis authentication, Centrifugo secrets, and port mappings.


## 3.8 Internationalisation Design

### 3.8.1 Translation Framework

The React SPA implements internationalisation using the `react-i18next` library with two bundled locales: English (`en`) and Arabic (`ar`). Language detection checks `localStorage` first, falling back to the browser's `navigator.language` setting, with English as the default fallback when a translation key is missing. Arabic right-to-left (RTL) layout support is handled by dynamically setting the document's `dir` attribute and toggling an `rtl` CSS class, with Tailwind CSS's RTL plugin automatically generating the correct directional utility variants. Language switching is immediate and entirely client-side, requiring no server-side rendering or locale-specific API endpoints; the backend returns data in a locale-agnostic format, and all user-facing string translation is handled within the React SPA.

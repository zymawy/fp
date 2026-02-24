# Chapter 4: Implementation

This chapter presents the implementation of the Enaam charitable donation platform, detailing the technology choices and engineering decisions that translate the system design described in Chapter 3 into a working product.

## 4.1 Technology Stack

The technology stack was selected to satisfy the requirements identified during the analysis phase: a performant, multilingual single-page application for donors; a robust and extensible REST API; a dedicated administrative interface; real-time donation progress updates; and a containerised development and deployment environment.

Table 4.1 summarises every major dependency, its version, and its role within the platform.

| Layer | Technology | Version | Role |
|---|---|---|---|
| **Frontend (Donor SPA)** | React | 18.3 | Component-based UI rendering |
| | TypeScript | 5.5 | Static type checking across the frontend |
| | Vite | 5.4 | Development server and production bundler |
| | shadcn/ui (Radix + Tailwind) | latest | Accessible, composable UI primitives |
| | Tailwind CSS | 3.4 | Utility-first CSS framework |
| | react-i18next | 15.4 | Internationalisation (English and Arabic) |
| | React Router | 6.22 | Client-side routing |
| | Zod | 3.23 | Runtime schema validation for form data |
| | centrifuge (JS client) | 5.0 | WebSocket client for real-time updates |
| **Backend (REST API)** | Laravel | 12.0 | PHP application framework |
| | PHP | 8.2+ | Server-side language |
| | Dingo API | 4.3 | Versioned REST API routing and response formatting |
| | Fractal Transformers | via php-open-source-saver | API response serialisation layer |
| | laravel-responder | 3.4 | Consistent JSON response formatting |
| | php-open-source-saver/jwt-auth | 2.7 | JWT authentication |
| | myfatoorah/laravel-package | 2.2 | Payment gateway integration |
| | laravel-centrifugo-broadcaster | 2.3 | Server-side Centrifugo broadcasting |
| **Admin Panel** | Vue 3 | 3.5 | Composition API-based admin dashboard |
| | TypeScript | 5.5 | Static type checking |
| | Pinia | 2.1 | Centralised state management |
| | Chart.js + vue-chartjs | 4.4 / 5.3 | Dashboard analytics visualisations |
| | Headless UI | 1.7 | Accessible, unstyled Vue components |
| | Tailwind CSS | 3.4 | Utility-first CSS framework |
| **Database** | PostgreSQL | 16 | Relational database with UUID support |
| **Caching / Queues** | Redis | latest | Queue backend and cache store |
| **Search** | Elasticsearch | 8.x | Full-text search and indexing |
| **Real-Time** | Centrifugo | latest | WebSocket server for pub/sub messaging |
| **Email (Dev)** | Mailhog | latest | SMTP testing server |
| **DB Admin** | Adminer | latest | Browser-based database management |
| **Testing** | Pest | 3.7 | Expressive PHP testing framework |
| | PHPUnit | 11.0 | Underlying test runner |
| **Containerisation** | Docker Compose | latest | Multi-service orchestration |

### 4.1.1 Justification of Key Choices

**Laravel over Express.js.** Laravel provides a mature ecosystem for the features central to Enaam -- database migrations with UUID support, form request validation, event broadcasting, and queue management are all first-party concerns. The Dingo API package offers versioned routing and Fractal-based response transformation, which would require substantial custom middleware in Express.

**React over Vue for the donor frontend.** React 18 was selected for the donor-facing application because of its larger ecosystem of accessible UI component libraries, specifically shadcn/ui, and more mature internationalisation tooling through react-i18next for English and Arabic with full RTL support.

**Vue 3 for the admin panel.** Vue 3 with the Composition API was selected for the admin panel because its template-based syntax accelerates development of data-heavy CRUD interfaces. Pinia provides lightweight, type-safe state management that maps naturally to the admin panel's domain stores.

**Pest over PHPUnit directly.** Pest builds on PHPUnit while offering a closure-based syntax that reduces boilerplate. Its `it()` and `expect()` API produces test files that read as behavioural specifications, improving maintainability without sacrificing test runner compatibility.

**JWT over Sanctum.** Sanctum is optimised for cookie-based SPA authentication within the same domain, but Enaam's architecture separates the frontend, backend, and admin panel as independent applications potentially deployed on different origins. JWT tokens are stateless, transportable across origins, and compatible with the Dingo API middleware pipeline.

## 4.2 Backend Implementation

The backend is a Laravel 12 application that exposes a versioned REST API through the Dingo API package. This section examines the routing structure, authentication middleware, payment processing, donation flow, and real-time broadcasting.

### 4.2.1 API Routing and Middleware

All API routes are defined using Dingo's versioned routing API, organised into three tiers: public routes accessible without authentication, protected routes requiring a valid JWT token, and admin-only routes restricted by role. This three-tier structure enforces the principle of least privilege. Public endpoints such as cause listings and payment webhooks are accessible without credentials; the `api.auth` middleware group requires a valid JWT token; and the inner `admin` middleware group further restricts access to administrators. Response serialisation is handled by Fractal Transformers, which decouple the internal Eloquent model structure from the JSON representation sent to clients, ensuring a stable API schema regardless of how the underlying database columns evolve.

### 4.2.2 JWT Authentication

Authentication is implemented using the `php-open-source-saver/jwt-auth` package, a community-maintained fork supporting Laravel 12 and PHP 8.2. The `User` model implements the `JWTSubject` interface, providing its UUID as the JWT identifier. Registration and login both return a JWT access token alongside the user object. Role-based access control is enforced by the `AdminMiddleware`, which intercepts requests to admin-only route groups and verifies that the authenticated user holds the administrator role, throwing an `AccessDeniedHttpException` (serialised as a 403 response) otherwise. This keeps authorisation logic separate from controller actions, following the Single Responsibility Principle.

### 4.2.3 Payment Processing with MyFatoorah

Payment processing is handled through the MyFatoorah gateway, a regional payment provider widely used in the Middle East and North Africa. The integration follows a redirect-based flow: the backend initiates a payment session within a database transaction to ensure atomicity, MyFatoorah redirects the donor to its hosted checkout page, and the result is communicated back through both a browser redirect callback and a server-to-server webhook.

Webhook security is enforced through HMAC-SHA256 signature verification. When MyFatoorah sends a server-to-server notification, the webhook handler verifies the request's authenticity before processing:

```php
// app/Http/Controllers/Api/PaymentController.php - webhook method
public function webhook(Request $request)
{
    try {
        // Verify webhook signature from MyFatoorah
        $webhookSecretKey = config('myfatoorah.webhook_secret_key');
        $signatureHeader = $request->header('MyFatoorah-Signature');

        if (!empty($webhookSecretKey)) {
            if (empty($signatureHeader)) {
                Log::warning('Webhook received without signature header');
                return $this->response->error('Missing webhook signature', 403);
            }

            $computedSignature = hash_hmac('sha256', $request->getContent(), $webhookSecretKey);
            if (!hash_equals($computedSignature, $signatureHeader)) {
                Log::warning('Webhook signature verification failed');
                return $this->response->error('Invalid webhook signature', 403);
            }
        }

        // Process webhook payload...
        $data = $request->all();
        $eventType = $data['EventType'] ?? '';
        $resourceId = $data['ResourceId'] ?? '';

        $transaction = Transaction::where('transaction_id', $resourceId)->first();

        switch ($eventType) {
            case 'PaymentSucceeded':
                $transaction->update(['payment_status' => 'completed']);
                $transaction->donation->update(['payment_status' => 'completed']);
                break;
            case 'PaymentFailed':
                $transaction->update(['payment_status' => 'failed']);
                $transaction->donation->update(['payment_status' => 'failed']);
                break;
        }

        return $this->response->array(['success' => true]);
    } catch (\Exception $e) {
        Log::error('Webhook processing error: ' . $e->getMessage());
        return $this->response->error('Webhook processing failed', 500);
    }
}
```

The `hash_equals()` function is used rather than the `===` operator for signature comparison to prevent timing attacks, a security best practice when comparing cryptographic digests.

### 4.2.4 Donation Flow with Database Transactions

When a payment succeeds, the system must atomically update the associated cause's `raised_amount`. Concurrent donations to the same cause could produce a race condition if handled naively. The payment callback method addresses this with pessimistic locking:

```php
// app/Http/Controllers/Api/DonationController.php - paymentCallback method
public function paymentCallback(Request $request)
{
    $paymentId = $request->input('paymentId');

    try {
        $paymentStatus = $this->myFatoorahService->getPaymentStatus($paymentId);
        $donation = Donation::where('payment_id', $paymentId)->firstOrFail();

        // Update donation status
        $donation->update([
            'payment_status' => $paymentStatus['IsSuccess'] ? 'completed' : 'failed',
        ]);

        // If payment was successful, update cause raised amount atomically
        if ($paymentStatus['IsSuccess']) {
            DB::transaction(function () use ($donation) {
                $cause = \App\Models\Cause::lockForUpdate()->findOrFail($donation->cause_id);
                $cause->raised_amount += $donation->amount;
                $cause->save();
            });

            // Fire the donation created event for real-time broadcasting
            $donation->load(['user', 'cause']);
            event(new DonationCreated($donation));

            // Send real-time update for this cause
            $this->sendRealTimeUpdate($donation->cause_id);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'donation_id'    => $donation->id,
                'payment_status' => $donation->payment_status,
            ],
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error processing payment callback: ' . $e->getMessage(),
        ], 500);
    }
}
```

The `lockForUpdate()` call acquires a row-level exclusive lock on the cause record within the transaction, preventing concurrent transactions from reading or modifying the `raised_amount` until the lock is released on commit. This guarantees that simultaneous donations to the same cause are serialised correctly. Following the successful update, a `DonationCreated` event is fired and a real-time broadcast is triggered to connected clients.

### 4.2.5 Real-Time Broadcasting via Centrifugo

Real-time donation progress updates are delivered through Centrifugo, an open-source WebSocket server. The backend communicates with Centrifugo through the `opekunov/laravel-centrifugo-broadcaster` package, abstracted behind a `CentrifugoService` class that handles channel namespace prefixing, message publishing, and token generation. Each cause has its own channel (e.g., `donations:cause_abc123`), and when a donation completes, the backend publishes an update containing the new `raised_amount` and `progress_percentage`. Connection and subscription tokens are generated server-side and provided to the frontend via a dedicated API endpoint, ensuring that only authenticated users can subscribe to real-time channels.

## 4.3 Frontend Implementation

The donor-facing frontend is a React 18 single-page application built with TypeScript, bundled by Vite, and styled with Tailwind CSS through the shadcn/ui component library.

### 4.3.1 Project Structure and Configuration

Vite serves as both the development server and production bundler, with the path alias `@/` configured to map to the `src/` directory for simplified imports throughout the codebase.

### 4.3.2 API Client

All HTTP communication with the backend is routed through a typed API client that handles JSON serialisation, JWT token injection from `localStorage`, request timeouts, and error normalisation. The client exposes typed methods (`api.get`, `api.post`, `api.upload`) and domain-specific endpoints (`api.auth.signIn`, `api.causes.list`, `api.donations.create`) that encapsulate serialisation logic and response unwrapping.

### 4.3.3 Custom Hooks

Business logic is encapsulated in custom React hooks that abstract data fetching, state management, and side effects, including `useAuth` for authentication, `useCauses` for cause listings, `useDonations` for donation management, and `useCauseRealtime` for live progress updates.

The `useCauseRealtime` hook demonstrates the real-time subscription pattern:

```typescript
// src/hooks/useCauseRealtime.ts
export function useCauseRealtime(causeId: string) {
  const { isConnected } = useCentrifugo();
  const [latestUpdate, setLatestUpdate] = useState<DonationUpdateData | null>(null);
  const [isSubscribed, setIsSubscribed] = useState(false);

  useEffect(() => {
    centrifugoService.subscribeToCause(
      causeId,
      (data: DonationUpdateData) => {
        setLatestUpdate(data);
        setIsSubscribed(true);
      },
      (err: Error) => {
        setIsSubscribed(false);
      }
    );

    return () => {
      centrifugoService.unsubscribeFromCause(causeId);
    };
  }, [causeId]);

  return { isConnected, isSubscribed, latestUpdate };
}
```

The hook automatically subscribes when the component mounts and unsubscribes on unmount, preventing memory leaks and stale subscriptions.

### 4.3.4 Internationalisation and Input Validation

The application supports English and Arabic through `react-i18next`, with automatic locale detection and RTL layout adjustment. Client-side form validation uses Zod schemas integrated with `react-hook-form`, providing runtime type checking that mirrors the server-side Laravel Form Request rules for immediate user feedback before any network request.

## 4.4 Admin Panel Implementation

The admin panel is a separate Vue 3 application that communicates with the same backend API using JWT tokens, built with the Composition API and TypeScript, styled with Tailwind CSS and Headless UI. Pinia stores provide centralised state management for each domain entity (causes, donations, partners, users, reports, transactions), each encapsulating API calls, loading states, error handling, and data caching. The admin dashboard renders summary statistics and trend visualisations using Chart.js through the `vue-chartjs` wrapper, consuming dedicated API endpoints for aggregate statistics, time-series trends, activity feeds, and user growth data. Full CRUD interfaces for all entities follow a consistent pattern of paginated tables with sorting and filtering, detail views with related entities, and modal-based forms with delete confirmation.

## 4.5 Testing Implementation

The test suite uses Pest 3.7, which provides an expressive closure-based syntax while executing on the PHPUnit 11 runner. The suite comprises 78 tests producing 163 assertions across six test files covering the application's critical paths.

### 4.5.1 Test Organisation

| Test File | Tests | Focus Areas |
|---|---|---|
| `AuthTest.php` | 15 | Registration, login, profile access, logout, token refresh |
| `DonationTest.php` | 14 | Listing, filtering, showing, creating, validation |
| `CauseTest.php` | 13 | Listing, filtering, search, show detail |
| `AdminTest.php` | 11 | Dashboard stats, CRUD operations, RBAC enforcement |
| `PaymentTest.php` | 9 | Payment processing, callback handling, webhook verification |
| `ModelTest.php` | 19 | UUID generation, relationships, attribute casts |
| **Total** | **78** | **163 assertions** |

The tests cover the full request lifecycle including HTTP routing, form validation, entity creation, JWT token generation, JSON response serialisation, and database side effects. Authenticated test requests use a shared helper function that generates a valid JWT token for a given user, avoiding the overhead of a full login request in every test. Model tests verify that all entities generate valid UUID primary keys, a critical invariant for the system's data integrity.

## 4.6 Security Implementation

Security is addressed at multiple layers following a defence-in-depth strategy. JWT tokens provide stateless authentication with configurable expiry and refresh (Section 4.2.2). Role-based access control is enforced at the route group level through the `AdminMiddleware`. Webhook payloads from MyFatoorah are verified using HMAC-SHA256 with constant-time comparison via `hash_equals()` (Section 4.2.3). Server-side input validation uses Laravel Form Requests to validate and sanitise incoming data before it reaches controller logic, enforcing UUID format for foreign keys, minimum amounts to prevent zero-value donations, and conditional requirements for gift donations. Pessimistic database locking prevents race conditions on concurrent donations (Section 4.2.4). Additional measures include CSRF protection, bcrypt password hashing, API rate limiting, and exclusion of sensitive fields from JSON responses via the model's `$hidden` array.

## 4.7 Docker Compose Deployment

The containerised deployment environment was described in Chapter 3 (Section 3.6). The Docker Compose configuration defines eight services across two isolated networks, with named volumes for data persistence and externalised environment configuration following the twelve-factor app methodology.

## 4.8 Summary

This chapter has presented the implementation of the Enaam platform across its three application layers. Key engineering decisions -- HMAC-SHA256 webhook verification, pessimistic database locking for concurrent donations, and Centrifugo-based real-time broadcasting -- have been examined with supporting code evidence. The 78-test Pest suite provides coverage of authentication flows, donation lifecycle management, payment processing, and model integrity.

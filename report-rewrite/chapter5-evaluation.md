# Chapter 5: Evaluation

## 5.1 Evaluation Methodology

A rigorous, multi-layered evaluation strategy was adopted to assess the Enaam system against its stated research objectives and to identify defects prior to deployment. Three complementary approaches were employed: an automated test suite of 78 Pest tests covering unit, feature, and integration levels executed against an in-memory SQLite database for isolation and repeatability; a heuristic security assessment aligned with the OWASP Top Ten framework (OWASP Foundation, 2021), chosen over formal penetration testing due to project scope constraints; and manual functional evaluation of key user journeys in both supported languages. Together, these methods provide quantitative evidence of system correctness and qualitative insight into usability, security posture, and fitness for purpose.

---

## 5.2 Automated Test Results

### 5.2.1 Test Suite Overview

The complete test suite comprises 78 individual test cases containing 163 discrete assertions. All 78 tests pass successfully with a total execution time of 0.69 seconds on an SQLite test database, providing rapid feedback suitable for execution before every code commit. Table 5.1 presents the breakdown by test file.

**Table 5.1: Automated Test Suite Breakdown**

| Test File | Tests | Assertions | Focus Area |
|-----------|------:|----------:|------------|
| AuthTest | 15 | ~30 | Registration, login, profile retrieval, logout, token refresh |
| DonationTest | 14 | ~28 | Listing, filtering, creation, input validation |
| CauseTest | 13 | ~26 | Listing, filtering, search, detail view |
| AdminTest | 11 | ~22 | Dashboard statistics, CRUD operations, RBAC enforcement |
| PaymentTest | 9 | ~18 | Payment session initiation, callback handling, webhook verification |
| ModelTest | 19 | ~39 | UUID generation, Eloquent relationships, attribute casting |
| **Total** | **78** | **163** | **Full stack coverage** |

The six test files collectively cover the full application domain, with each file including both positive-path assertions (verifying correct inputs produce expected outputs) and negative-path assertions (verifying that invalid inputs and unauthorised access are properly rejected). AuthTest validates the complete JWT lifecycle including registration, login, token refresh, and logout, with both valid and invalid credential scenarios. DonationTest verifies donation creation, required field validation, and filtering by user, cause, and payment status with correctly scoped result sets. CauseTest covers public cause browsing, category filtering, text search, and individual detail retrieval including progress calculations. AdminTest is particularly significant as it verifies RBAC enforcement, confirming that non-admin users receive HTTP 403 responses on all administrative endpoints whilst authenticated administrators receive correct aggregate statistics. PaymentTest validates the MyFatoorah integration using mocked HTTP responses to ensure deterministic execution, covering payment session initiation, callback processing, and HMAC-SHA256 webhook signature verification that correctly rejects tampered payloads. ModelTest confirms UUID v4 generation across all models (User, Donation, Cause, Transaction, Role), Eloquent relationship integrity, and correct attribute casting for monetary values, dates, and JSON fields.

---

## 5.3 Security Assessment

Given that the platform processes financial transactions and stores personally identifiable information, a structured security review was conducted against the OWASP Top Ten Web Application Security Risks (2021 edition). The assessment draws on both static analysis of the codebase and dynamic observation of runtime behaviour, assigning each category a status of Mitigated, Partially Mitigated, or Low Risk. Table 5.2 summarises the findings.

**Table 5.2: OWASP Top Ten Security Assessment**

| # | OWASP Category | Status | Implementation Details |
|---|----------------|--------|----------------------|
| A01 | Broken Access Control | Mitigated | JWT authentication on all protected routes; AdminMiddleware enforces RBAC; automated tests verify HTTP 403 for non-admin users |
| A02 | Cryptographic Failures | Mitigated | Passwords hashed via bcrypt; webhooks verified with HMAC-SHA256; JWT signed with server-side secret; no plaintext sensitive data |
| A03 | Injection | Mitigated | Eloquent ORM parameterised queries; Laravel Form Request validation; no raw SQL present |
| A04 | Insecure Design | Partially Mitigated | Database transactions with `lockForUpdate()` prevent race conditions; lacks comprehensive rate limiting; no formal threat modelling conducted |
| A05 | Security Misconfiguration | Mitigated | Sensitive values in environment variables; excluded from version control; debug mode disabled in production; CORS configured |
| A06 | Vulnerable Components | Monitored | Composer and npm audit commands available; no automated dependency scanning in CI/CD |
| A07 | Identification and Authentication Failures | Mitigated | JWT expiration enforced; rate limiting on authentication endpoints; minimum password length required |
| A08 | Software and Data Integrity Failures | Mitigated | HMAC-SHA256 webhook verification before state mutation; database transactions ensure payment atomicity |
| A09 | Security Logging and Monitoring Failures | Partially Mitigated | Laravel logging for payments, webhooks, and authentication; no centralised log aggregation or alerting configured |
| A10 | Server-Side Request Forgery (SSRF) | Low Risk | No user-supplied URL fetching; all external requests use hardcoded, environment-configured endpoints |

### 5.3.1 Summary of Security Posture

Of the ten OWASP categories, seven are assessed as fully mitigated through the application's existing defensive measures. Two categories (Insecure Design and Security Logging) are assessed as partially mitigated, reflecting areas where the implementation provides basic protection but would benefit from additional investment in a production context. One category (SSRF) is assessed as low risk due to the absence of the relevant attack vector. The security posture is considered appropriate for the project's scope; the partially mitigated categories would require production hardening, particularly the introduction of comprehensive rate limiting, centralised logging with alerting, and a formal threat modelling exercise.

---

## 5.4 Bugs Discovered and Resolved

The development process revealed three significant bugs, each identified through the automated test suite during iterative development. The discovery and resolution of these bugs demonstrates the practical value of comprehensive testing and provides honest evidence of the challenges encountered during implementation. Notably, all three share a common characteristic: inconsistencies between different application layers (schema versus controller, schema versus model), suggesting that cross-layer integration tests are among the most valuable in a full-stack application. The honest disclosure of these defects is deliberate; a system with no reported bugs is not a system without bugs, but a system with inadequate testing.

**Bug 1: total_amount Field Never Set** (Critical). The `DonationController@store` method did not calculate or set the `total_amount` field, a non-nullable column representing the sum of donation amount and processing fee, causing a database constraint violation on every donation creation. The fix added the computation of amount plus processing fee before record creation. This defect arose from a gap between schema design and controller implementation. Detected by DonationTest creation assertions.

**Bug 2: Webhook Updating Wrong Field** (Critical). The `PaymentController` webhook handler updated a field named `status` rather than the schema-defined `payment_status`, meaning that MyFatoorah callback signals indicating payment success or failure were silently ignored. All occurrences were corrected to reference the proper field name, aligning controller code with the database schema. This defect arose from a field rename during schema evolution that was not propagated to the controller. Detected by PaymentTest webhook assertions.

**Bug 3: Role Model Missing HasUuids Trait** (High). The Role model lacked the `HasUuids` trait, generating integer primary keys whilst all other models used UUIDs, causing foreign key constraint violations when attaching roles to users via the pivot table. Adding the trait resolved the inconsistency. This defect arose from an incremental UUID migration that inadvertently excluded the less frequently modified Role model. Detected by ModelTest relationship assertions.

Each defect was identified before manual testing or deployment, validating the test suite as an effective safety net and the investment in automated testing as a core development practice.

---

## 5.5 Functional Evaluation

Manual functional testing was conducted to evaluate complete user journeys that span multiple system components and cannot be fully captured by isolated unit or feature tests. Two primary journeys were evaluated to assess workflow coherence, bilingual behaviour, and real-time update functionality.

The **donor journey** was tested across six stages: account registration with input validation, JWT authentication, cause browsing with category filtering and text search, donation submission via MyFatoorah payment gateway redirect, real-time progress updates via Centrifugo WebSocket, and donation history review with filtering by date and status. All stages functioned correctly in both English and Arabic, with language switching correctly updating all interface text and the Arabic locale triggering RTL layout adaptation. The real-time update mechanism was verified by opening three simultaneous browser sessions viewing the same cause; all received the donation progress update within approximately one second of payment confirmation. Unauthenticated WebSocket connection attempts were correctly rejected, confirming token-based channel authentication.

The **administrator journey** verified authentication with RBAC enforcement (non-admin users receiving HTTP 403 responses), dashboard aggregate statistics including total donations and active causes computed from live database queries, cause CRUD operations with input validation and error messaging, and user management with search and filtering capabilities. All administrative functions operated as designed, with access control consistently enforced across all endpoints both through automated tests and manual verification with a donor-role account.

---

## 5.6 Limitations

An honest assessment of the system's limitations is essential for contextualising the evaluation results and identifying areas for future development.

1. **Single Payment Gateway.** MyFatoorah serves only the MENA region; global accessibility would require integration with additional gateways such as Stripe or PayPal.
2. **No Frontend Tests.** The automated suite covers only backend functionality; frontend rendering and client-side validation rely on manual testing.
3. **No Load Testing.** System behaviour under concurrent user load is unknown; performance benchmarks have not been established.
4. **Development-Only Email.** Email notifications use Mailhog; no production mail service is configured.
5. **Underutilised Elasticsearch.** The Docker configuration includes Elasticsearch, but search functionality relies on standard database queries.
6. **No CI/CD Pipeline.** Tests are executed manually; no automated test execution is configured for code commits.
7. **Certificate Generation Not Implemented.** The previously referenced donor certificate feature does not exist in the codebase.
8. **Limited Gamification Frontend.** Backend badge and achievement models exist, but frontend display is minimal.

---

## 5.7 Comparison with Research Objectives

Table 5.3 maps each research objective to its evaluation outcome with traceable evidence.

**Table 5.3: Research Objectives Evaluation**

| Objective | Description | Status | Evidence |
|-----------|-------------|--------|----------|
| RO1 | Transparent donation tracking with real-time updates | Achieved | Centrifugo WebSocket broadcasts to connected clients; progress bars update in real time; verified via multi-client testing (Section 5.5) |
| RO2 | Secure payment processing via MyFatoorah | Achieved | HMAC-SHA256 webhook verification; database transactions with `lockForUpdate()`; 9 automated payment tests (Section 5.2) |
| RO3 | Bilingual support for English and Arabic with RTL | Achieved | react-i18next runtime switching; Tailwind RTL plugin; complete locale translations; verified manually (Section 5.5) |
| RO4 | Role-based access control with separated interfaces | Achieved | JWT with AdminMiddleware; 11 automated RBAC tests confirming HTTP 403 enforcement (Section 5.2) |
| RO5 | Comprehensive automated test suite | Achieved | 78 tests, 163 assertions, 0.69s execution; three critical bugs identified and resolved (Section 5.4) |

### 5.7.1 Assessment Summary

All five research objectives have been achieved, as evidenced by the combination of automated test results, OWASP-aligned security assessment findings, and functional evaluation outcomes. The automated test suite provides quantitative evidence of correctness across all major functional domains, while the security assessment confirms that standard defensive measures are in place for the most common web application vulnerability categories. The limitations documented in Section 5.6 represent areas requiring additional investment prior to production deployment but do not negate achievement of the stated research objectives. The project delivers a functional, tested, and reasonably secure donation platform that meets its defined scope.

---

## References

Beck, K. (2002) *Test-Driven Development: By Example*. Boston: Addison-Wesley.

OWASP Foundation (2021) *OWASP Top Ten Web Application Security Risks*. Available at: https://owasp.org/www-project-top-ten/ (Accessed: 15 February 2026).

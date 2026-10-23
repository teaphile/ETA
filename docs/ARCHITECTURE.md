# Architecture

Modular monolith built on Laravel 13. Request flow:

```
Blade views ──▶ Controllers (thin, validation in Form Requests)
                     │
                     ├──▶ Services  (CentreSearchService, JourneyPlannerService,
                     │               ChatbotService, AssistantContextBuilder,
                     │               IntegrationHealthService, AccountDeletionService,
                     │               SearchLogger)
                     │
                     └──▶ Contracts (interface) ──▶ Integrations (provider implementations)
                                                    └── config/services.php chooses which one
```

## Provider boundaries

All external/vendor behaviour sits behind a contract in `app/Contracts`, bound in
`app/Providers/IntegrationServiceProvider.php`. Controllers and services depend only on the
interface, so a missing credential degrades to a documented fallback and swapping a vendor is
a configuration change.

| Contract | Default implementation | Configured alternative | Fallback behaviour |
|---|---|---|---|
| `PlacesProvider` | `CuratedPlacesProvider` (admin listings) | `GooglePlacesProvider` | Shows curated listings + an in-page notice |
| `DirectionsProvider` | `GoogleDirectionsProvider` | — | Keyless Google Maps deep links + text address |
| `TransportProvider` | `EstimatedTransportProvider` | (a licensed partner API) | Disclosed distance-based estimates + official links |
| `AIProvider` | `GroundedAssistantProvider` (DB + FAQ) | `OpenAiProvider` | Falls back to the grounded provider on any failure |
| `CentreDataImporter` | `CsvCentreDataImporter` | — | Validate → preview → approve; rejects bad headers |
| `NotificationProvider` | `LogNotificationProvider` | `MailNotificationProvider` | Writes to the log instead of sending mail |

`config/services.php` is the **single** source of truth for integration credentials. (It
replaced a duplicated `config/Services.php` that collided on case-insensitive filesystems.)

## Data-honesty model

`app/Integrations/Support/DataStatus` is a backed enum with one controlled vocabulary used by
the UI, the assistant and the admin screens:

`officially_verified` · `admin_verified` · `third_party` · `estimated` · `unverified` ·
`stale` · `unavailable`.

- Centres/places carry `verification_status` + `source_name` + `source_url` +
  `last_verified_at` + `is_demo`, and render a badge accordingly.
- Travel options carry `price_status` and, when estimated, an `estimateMethod` string that is
  shown beside the price. Flight pricing is `unavailable` by design.
- `data:flag-stale` marks verified records past their freshness window as `stale`.

## Cross-cutting concerns

- **AuthZ** — the `role` middleware alias (`EnsureRole`) plus per-record ownership checks
  (itineraries). Moderators are deliberately excluded from admin-only sub-areas.
- **Email verification** — the `verified` alias maps to `EnsureEmailVerified`, a *conditional*
  guard that is a no-op unless `REQUIRE_EMAIL_VERIFICATION=true`.
- **Audit** — `AuditLog::record(...)` on sensitive admin/auth actions; append-only.
- **Rate limiting** — named limiters in `AppServiceProvider` protect login, registration,
  password reset, the assistant (paid), journey planning, places, community writes, reports
  and admin import.
- **Security headers** — `SecurityHeaders` middleware on every web response.
- **Localisation** — `SetLocale` resolves the request locale (session → user preference → app
  default). Student-facing strings live in `lang/{en,hi}/messages.php`. Admin screens are
  English-only by design.
- **Strictness** — `Model::shouldBeStrict(!production)` prevents silent N+1 queries and access
  to non-existent attributes outside production.
- **Timezone** — `Asia/Kolkata`; money is stored in decimal columns and displayed in INR.

## Front end

Blade templates extend `resources/views/layouts/app.blade.php`. Styling is a hand-written,
dependency-free design system in `public/css/app.css` (355 lines); the mobile navigation is
~15 lines of vanilla JS with progressive enhancement. **No build step is required to run the
app.** A Tailwind/Vite pipeline exists in the repository but is not wired into the layout; see
`IMPLEMENTATION_STATUS.md`.

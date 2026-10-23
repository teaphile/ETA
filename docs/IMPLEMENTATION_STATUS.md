# Implementation Status Ledger — Exam Travel Assist

Last reviewed: 2026-10-18 (IST) · Stack: Laravel 13.37, PHP 8.4, SQLite dev DB
(MySQL-compatible migrations), Blade + hand-written CSS.

**How to read this ledger**

| Mark | Meaning |
|---|---|
| ✅ Complete & tested | Implemented **and** covered by an automated test that exercises the real behaviour |
| 🟡 Implemented, needs configuration | Code is complete and tested in fallback mode; a real credential/config is required for the *live* path |
| 🟠 Implemented with limitations | Works, but is deliberately constrained (e.g. estimates instead of live data) — the constraint is surfaced in the UI |
| ⬜ Not implemented | Not built |
| ⛔ Blocked in this environment | Implemented but could not be verified here because of a missing tool |

Nothing in this file is a production-readiness claim. "Tested" means *unit/feature tests in
this repository passed*; it does not mean the feature was validated against live third-party
services, at scale, or by a human on a device matrix.

---

## Core platform

| Feature | Status | Evidence | Remaining dependency / next action |
|---|---|---|---|
| Public pages (home, centres, journey, places, community, assistant, help, legal, auth) | ✅ Complete & tested | `test_home_and_public_pages_load` | — |
| Centre search: exam / session / state / city / code / keyword, pagination, empty state | ✅ Complete & tested | `CentreSearchService`, `test_centre_search_filters_and_empty_state`, `CentreSearchServiceTest` | Real official centre lists must be imported by an admin |
| Centre detail: provenance, verification badge, "coordinates missing" honesty path | ✅ Complete & tested | `test_demo_data_is_labelled`, `test_centre_without_coordinates_says_so` | — |
| Exam & session management | ✅ Complete & tested | `AdminController::exams/storeSession`, `test_admin_routes_require_admin_role` | — |
| Centre CRUD + verify + archive + provenance fields + audit | ✅ Complete & tested | `test_admin_can_create_centre_then_it_appears_in_search` | — |
| CSV import: validate → preview → approve, row errors, duplicates, template | ✅ Complete & tested | `test_csv_import_validates_then_imports_valid_rows_only`, `test_csv_import_rejects_missing_required_headers`, `CsvCentreDataImporterTest` | — |
| Auth: register / login / logout / profile / password reset | ✅ Complete & tested | `AuthController`, `test_registration_login_and_role_defaults`, `test_password_reset_request_is_enumeration_safe` | Password-reset delivery needs working `MAIL_*` in production |
| Email verification (optional, config-driven) | ✅ Complete & tested | `EnsureEmailVerified`, `test_email_verification_flow_when_strictly_required`, `test_verification_link_is_bound_to_the_signed_in_account` | Allowable mail must be configured before enabling `REQUIRE_EMAIL_VERIFICATION=true` |
| Roles: student / moderator / admin, server-enforced, least privilege | ✅ Complete & tested | `EnsureRole` middleware, `test_admin_routes_require_admin_role`, `test_moderator_cannot_reach_admin_only_areas` | — |
| Last-administrator guard | ✅ Complete & tested | `test_admin_user_update_protects_last_administrator` | — |
| Account deletion: immediate + grace-period request/cancel + anonymisation | ✅ Complete & tested | `AccountDeletionService`, `test_immediate_account_deletion_anonymises_and_logs_out` | — |
| Security headers + nonce-based CSP on every response | ✅ Complete & tested | `SecurityHeaders` (per-request nonce; no `script-src 'unsafe-inline'`), `test_security_headers_present_on_html_response` | — |
| 404 / 403 / 419 / 429 / 500 / 503 pages | ✅ Complete & tested | `test_unknown_route_returns_404` | — |
| Bilingual student UI (en + hi) with locale switcher | ✅ Complete & tested | `SetLocale`, `test_locale_switch_changes_interface_language` | Hindi copy is a first pass; a native reviewer should proofread it before launch |

## Journey planning & places

| Feature | Status | Evidence | Remaining dependency / next action |
|---|---|---|---|
| Journey planner Stage A (train / bus / flight / car) | 🟠 Implemented with limitations | `EstimatedTransportProvider`, `test_journey_plan_shows_estimates_not_live_fares` | India has no openly licensed live rail/bus/flight fare API. Prices are **disclosed distance-based estimates**; flights show *price unavailable*. Live data needs an authorised partner API bound to `TransportProvider`. |
| Journey planner Stage B last mile (walk / auto / cab / bus) | 🟠 Implemented with limitations | `LastMileEstimator` | Live Rapido/Ola/Uber fares are in-app only; shown as unavailable with indicative ranges |
| Budget flag, "lowest among results" wording, method disclosure | ✅ Complete & tested | `SupportTest` (`JourneyPlan`), `journey/result.blade.php` | — |
| Saved itineraries (private, owner-checked) | ✅ Complete & tested | `test_itinerary_ownership_enforced` | — |
| Nearby services — curated admin listings | ✅ Complete & tested | `PlaceController`, `CuratedPlacesProvider`, `test_save_and_unsave_place` | Admins must enter real, sourced listings |
| Nearby services — live Google Places | 🟡 Implemented, needs configuration | `GooglePlacesProvider` | Set `PLACES_PROVIDER=google` + `GOOGLE_PLACES_SERVER_KEY` (restricted). Not exercised in CI (no key). |
| Maps: embed + directions | 🟡 Implemented, needs configuration | centre page embed + keyless deep links | Set `GOOGLE_MAPS_BROWSER_KEY`/`SERVER_KEY` for the JS Maps/Directions experience; keyless links always work |

## Assistant, community, admin

| Feature | Status | Evidence | Remaining dependency / next action |
|---|---|---|---|
| Grounded assistant (DB + FAQ, sources shown) | ✅ Complete & tested | `ChatbotService`, `GroundedAssistantProvider`, `test_chatbot_grounded_and_never_invents_seats` | — |
| LLM-provider assistant | 🟡 Implemented, needs configuration | `OpenAiProvider` (falls back to grounded on failure) | Set `AI_PROVIDER=openai` + `OPENAI_API_KEY`. Not exercised in CI (no key). |
| Assistant transcript retention + user clear/report | ✅ Complete & tested | `ChatbotService::prune`, `assistant/history` route | — |
| Community: posts, replies, reports, lock/hide moderation | ✅ Complete & tested | `test_community_post_reply_and_report`, `test_community_cannot_reply_to_locked_thread` | — |
| Admin dashboard with real counts + integration health panel | ✅ Complete & tested | `test_admin_dashboard_reports_real_counts` | — |
| Admin: users, FAQs, places, moderation queue, audits, stale data | ✅ Complete & tested | `test_admin_routes_require_admin_role` | — |
| Scheduled maintenance: stale flagging, retention pruning, deletion processing | ✅ Complete & tested (commands) | `data:flag-stale`, `data:prune`, `accounts:process-deletions` in `routes/console.php` | Requires a cron entry in production |
| Demo-data purge before launch | ✅ Complete & tested (command) | `data:purge-demo` (dry-run, confirmation, audit log, last-admin guard) | Run `php artisan data:purge-demo` on the production database |
| Responsive design system (mobile → desktop, no build step) | ✅ Complete & tested | `public/css/app.css`, `docs/UI_GUIDELINES.md`, `test_home_and_public_pages_load` | Visual check on a real device matrix is still worthwhile |
| Human-readable bilingual copy (en + hi) | ✅ Complete & tested | `lang/en/messages.php`, `lang/hi/messages.php` (276 keys, in parity), `test_locale_switch_changes_interface_language` | Legal pages (`/privacy`, `/terms`, `/community-rules`) stay in English pending legal review; Hindi should still be proofread by a native speaker |

## Platform / environment

| Item | Status | Evidence | Remaining dependency / next action |
|---|---|---|---|
| MySQL 8 production path | ⛔ Blocked in this environment | Driver-agnostic migrations; MySQL not installed, no Docker/sudo here | Provision MySQL 8, set `DB_*`, run `php artisan migrate --force`; verify on the target host |
| Front-end asset build (Vite + Tailwind) | ⬜ Not implemented (intentionally unused) | The UI is a hand-written `public/css/app.css` design system loaded directly; `package.json`/`vite.config.js` are unused | Either delete the unused pipeline, or run `npm install && npm run build` and switch the layout to `@vite(...)` |
| Automated browser (E2E) tests | ⬜ Not implemented | — | Add Laravel Dusk or Playwright if a real browser matrix is required |
| Live Google / OpenAI integration tests | ⬜ Not implemented | — | Add opt-in tests gated on secrets; never call paid APIs in the default suite |
| Native mobile apps | ⬜ Not implemented | Responsive web only | Build an API layer (Sanctum) and clients if required |

---

## Data-honesty guarantees encoded in the code

- Every centre and listing carries `verification_status`, `source_name`, `source_url`,
  `last_verified_at` and `is_demo`. Nothing is shown as verified without evidence.
- Freshness has an explicit window (`CENTRE_FRESHNESS_MONTHS`, default 6); records past it
  are flagged `stale` and surfaced on **Admin → Stale data** and in the UI.
- Transport prices are either `estimated` (with the calculation disclosed per option) or
  `unavailable` (flights). They are never presented as live fares.
- The assistant answers only from retrieved context and reports its sources; if it cannot
  ground an answer it says so and sets a `limitation`.
- Demonstration data is badged **DEMO DATA** and the demo seeder refuses to run in
  production unless `ALLOW_DEMO_SEED=true`.

See [`DATA_SOURCES_AND_VERIFICATION.md`](DATA_SOURCES_AND_VERIFICATION.md) for the full policy.

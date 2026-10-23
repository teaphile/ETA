# Requirements Traceability

Maps each business requirement to its implementation and the evidence that it works.

| # | Requirement | Implementation | Status / Evidence |
|---|---|---|---|
| 1 | Search exam centres by state / city / code / examination; filters + empty state | `CentreController`, `CentreSearchService`, `search_logs` | ✅ `test_centre_search_filters_and_empty_state`, `CentreSearchServiceTest` |
| 2 | Student registration (name / email / phone / location) with clear consent | `AuthController::register`, `RegisterRequest` | ✅ `test_registration_login_and_role_defaults`, `test_registration_requires_terms_acceptance` |
| 3 | Travel planning: rail / bus / flight / car + comparison, with budget and official links | `JourneyPlannerService`, `EstimatedTransportProvider`, `TravelOption`/`JourneyPlan` | 🟠 `test_journey_plan_shows_estimates_not_live_fares` — **estimated** ranges with the method disclosed, official links; live fares need a licensed API |
| 4 | Local navigation: taxi / auto / Rapido / bus, cost and directions | `LastMileEstimator` + Google Maps deep links | 🟠 Live ride-hailing fares are unavailable by design and labelled so (`DATA_SOURCES_AND_VERIFICATION.md`) |
| 5 | Nearby hotels / lodges / lounges / food / stationery | `PlaceController`, `CuratedPlacesProvider` (+ optional `GooglePlacesProvider`) | ✅ curated (`test_save_and_unsave_place`); 🟡 live needs a restricted Places key |
| 6 | Community for students | `CommunityController`, posts / replies / reports / moderation | ✅ `test_community_post_reply_and_report`, `test_community_cannot_reply_to_locked_thread` |
| 7 | AI chatbot answering travel questions | `ChatbotService`, `GroundedAssistantProvider` (+ optional `OpenAiProvider`) | ✅ grounded + honest limitation (`test_chatbot_grounded_and_never_invents_seats`); 🟡 optional LLM needs a key |
| 8 | Admin dashboard: centres / users / reports / analytics / content | `Admin\AdminController` + 13 admin screens | ✅ `test_admin_routes_require_admin_role`, `test_admin_dashboard_reports_real_counts` |
| 9 | Responsive UI, secure auth, Google Maps, Laravel, MySQL | Blade + `public/css/app.css`; auth + `SecurityHeaders`; Maps embed/deep links; Laravel 13; driver-agnostic migrations | ✅ responsive & secure (tests); 🟡 Maps JS needs a key; ⛔ MySQL path not executed here (no MySQL/Docker) |
| 10 | Testing, deployment docs, mobile-ready | PHPUnit suite; `docs/DEPLOYMENT.md`; responsive web | ✅ `docs/TEST_REPORT.md`; native apps are future work (⬜) |

## Non-functional requirements

| Requirement | How it is met |
|---|---|
| Data honesty | `DataStatus` vocabulary; provenance columns; estimates labelled; demo badged (`IMPLEMENTATION_STATUS.md`) |
| No fabricated data | Flights priced *unavailable*; ride-hailing *unavailable*; centres only via verified import; assistant grounded |
| Security | Role middleware, ownership checks, rate limiting, CSRF, security headers, audit log (`SECURITY_AND_PRIVACY.md`) |
| Privacy | Minimal PII, identifier-free analytics, retention windows, self-service deletion |
| Accessibility | Skip link, landmarks, labelled controls, `aria-live` regions, focus-visible styles, keyboard-operable nav |
| Localisation | en + hi student UI via `SetLocale` and `lang/{en,hi}/messages.php` |
| Operability | Scheduled maintenance commands, integration health telemetry, audit log, `/up` health endpoint |
| Cost control | Zero-key default; every paid integration has a documented kill switch (`COST_AND_API_DEPENDENCIES.md`) |

## Deliberate exclusions (v1)

- **Private direct messaging** between users — omitted for safety and moderation load.
- **In-platform booking/payments** — the platform only links to official portals.
- **Native mobile apps** — responsive web first.

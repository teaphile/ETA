# Cost & API Dependencies

## Core platform cost

**₹0 in API fees.** Centre search, journey planning (estimates + official links), the grounded
assistant, the community and the whole admin area work with **no external keys**. The only
running cost of a default deployment is hosting and a MySQL database.

## Optional cost drivers

| Integration | Billing unit | Controls baked into the app |
|---|---|---|
| Google Maps Platform | Per map load / Places / Directions / Geocoding request | Referrer- and API-restricted keys, `PLACES_MAX_RESULTS` cap, caching within Google terms (`PLACES_CACHE_SECONDS`), per-user `places` rate limiter |
| LLM provider (OpenAI-compatible) | Per token | `assistant` rate limiter (12/min per user), 1,000-character question cap, FAQ/DB grounding first (fewer LLM calls), `AI_MAX_TOKENS` cap |
| Licensed transport API | Vendor-specific | Single `TransportProvider` binding; per-user `journey` limiter |
| Transactional email | Per message | `password-reset` limiter, verification resend limiter, log provider for local/dev |
| Hosting | Per server | Node build step is **not required**; a 1 vCPU / 2 GB VPS is a reasonable start |

Check current pricing with each vendor; it changes. Nothing here is a quote.

## Kill switches

Set a provider to its fallback to remove all external spend instantly:

- `AI_PROVIDER=faq` — no LLM calls.
- `PLACES_PROVIDER=curated` — no Places calls.
- Leave `GOOGLE_MAPS_*` empty — keyless links only.
- `NOTIFICATION_PROVIDER=log` — no outbound mail.

## Cost-observability

`integration_events` records per-call success, HTTP status and latency (never payloads), and
the admin **Integration health** screen aggregates call counts — enough to see whether a paid
integration is being exercised more than expected.

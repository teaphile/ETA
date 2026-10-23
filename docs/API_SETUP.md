# API Setup

Every integration is optional: the application runs with **zero keys** and degrades to a
documented, clearly-labelled fallback. All credential reads happen in a single file,
[`config/services.php`](../config/services.php). Never commit real `.env` values.

## Google Maps Platform

| Env var | Key type | Used for |
|---|---|---|
| `GOOGLE_MAPS_BROWSER_KEY` | HTTP-referrer-restricted (browser) | Maps JS embed on centre pages |
| `GOOGLE_MAPS_SERVER_KEY` | IP/API-restricted (server) | Geocoding / Directions |
| `GOOGLE_PLACES_SERVER_KEY` | Server | Google Places Nearby/Details (falls back to `GOOGLE_MAPS_SERVER_KEY`) |

1. Google Cloud Console → **Maps Platform** → enable *Maps JavaScript API*, *Places API*,
   *Directions API*, *Geocoding API*.
2. Create the browser key (restrict by HTTP referrer) and a server key (restrict by IP and by
   API). Set quotas and budget alerts.
3. Set `PLACES_PROVIDER=google` to enable live place search (default is `curated`).

**When missing:** centre pages show a keyless "open in Google Maps" link and the text address;
place search shows curated admin listings with an in-page notice.

## AI assistant

| Env var | Value |
|---|---|
| `AI_PROVIDER` | `faq` (default, no external call) or `openai` |
| `OPENAI_API_KEY` | key for an OpenAI-compatible endpoint |
| `OPENAI_MODEL` | e.g. `gpt-4o-mini` |
| `OPENAI_BASE_URL` | override for a compatible provider |

The LLM receives only the user's question plus retrieved platform context (no personal data
beyond the question text). It may only phrase grounded facts; on error it falls back to the
grounded provider. Requests are throttled (`assistant` limiter: 12/min per user, 100/hour
per IP).

**When missing:** the grounded FAQ/database assistant answers — fully functional.

## Transport data

| Env var | Value |
|---|---|
| `TRANSPORT_PROVIDER` | `estimate` (default) or the name of a licensed partner provider |
| `TRANSPORT_API_KEY`, `TRANSPORT_API_BASE_URL` | credentials for that provider |

There is **no public, openly licensed** live rail/bus/flight fare API in India. A live provider
requires a commercial/partnership agreement; until one is bound, the platform returns disclosed
distance-based estimates and official booking links.

## Transactional email

| Env var | Value |
|---|---|
| `NOTIFICATION_PROVIDER` | `mail` or `log` |
| `MAIL_*` | any Laravel-supported mailer (SMTP/SES/Postmark/Resend) |

**When missing:** notifications are written to the application log (safe local default). Enable
`REQUIRE_EMAIL_VERIFICATION=true` only after mail is working.

## Testing integrations safely

- Use restricted, quota-limited keys and never a shared production key.
- Keep paid integrations behind the rate limiters already configured.
- Add opt-in tests gated on the presence of secrets; the default test suite must pass with no
  keys and no network access.

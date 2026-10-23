# Security & Privacy

This document describes the controls actually present in the code. It is **not** a legal
compliance claim; a pre-launch review with counsel is still required (see the checklist).

## Authentication & accounts

- Passwords are hashed with Laravel's default hasher (bcrypt). Production enforces a stronger
  policy: minimum 10 characters, mixed case, letters + numbers, and `uncompromised` (checked
  against a breach corpus). Local/dev uses a lighter minimum of 8 for iteration speed.
- Login regenerates the session; logout invalidates it and regenerates the CSRF token.
- Suspended accounts are blocked at login; `deleted` accounts cannot sign in.
- Password reset uses Laravel's broker with an **enumeration-safe** response: the same message
  is returned whether or not the address exists (proven by `test_password_reset_request_is_enumeration_safe`).
- Email verification is available but **off by default** (`REQUIRE_EMAIL_VERIFICATION`). When
  enabled, the signed link is bound to the signed-in account, so a forwarded link cannot verify
  a different user (proven by `test_verification_link_is_bound_to_the_signed_in_account`).
- Account deletion supports an immediate path (password + typed confirmation) and a
  grace-period request that can be cancelled; the immediate path anonymises the profile and
  removes owned data.

## Authorisation

- A `role` middleware (`EnsureRole`) enforces `admin`/`moderator` server-side. Admin-only
  sub-areas exclude moderators (least privilege).
- Per-record ownership is enforced for itineraries (403 to non-owners).
- The **last active administrator** cannot be demoted or suspended, and an admin cannot
  demote or suspend themselves.
- Every sensitive admin/auth action is appended to `audit_logs`.

## Request hygiene

- CSRF protection on all state-changing forms (Laravel middleware).
- Named rate limiters: `login` (10/min/IP), `register` (6/min/IP), `password-reset`
  (4/min/IP), `assistant` (12/min/user + 100/hour/IP), `journey` (20/min), `places` (30/min),
  `community-write` (5/min), `reports` (5/min), `admin-import` (5/min).
- All input is validated through Form Requests or inline `validate()`; centre search input is
  normalised and bounded (`CentreSearchService` strips control characters and caps length).
- CSV uploads are validated as `mimes:csv,txt, max:5120` (5 MB) and parsed defensively; a bad
  header row is rejected before any write.
- Mass assignment is controlled with `$fillable`.
- Blade escapes output by default.

## Response hardening

`SecurityHeaders` middleware sets `X-Content-Type-Options: nosniff`,
`X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`,
`Cross-Origin-Opener-Policy: same-origin` and a restrictive `Permissions-Policy`
(asserted by `test_security_headers_present_on_html_response`). It also sends a
Content-Security-Policy that:

- allows scripts only from this origin **or carrying the per-request nonce** (there is no
  `'unsafe-inline'` for scripts, so an injected inline `<script>` will not run);
- permits Google Maps origins for the embedded centre map;
- keeps `style-src 'unsafe-inline'` because a handful of computed inline style attributes
  (for example a meter width) cannot be covered by a nonce;
- blocks `object-src`, and forbids framing by other origins (`frame-ancestors 'self'`).

Production forces HTTPS (`URL::forceScheme('https')`) and honours `X-Forwarded-*` only
from `TRUSTED_PROXIES`. HSTS is sent once the request is secure.

## Data minimisation & privacy

- PII is deliberately minimal: name, email, optional phone, **city-level** location only — no
  precise address or GPS tracking.
- `search_logs` stores search terms **without user identifiers**, for no-result analytics.
- `integration_events` stores provider, operation, success, HTTP status and latency — **never**
  API keys, request bodies or personal data.
- Chat transcripts are retained only for `CHAT_RETENTION_DAYS` (default 90) and a user can clear
  their own history. Only the question text plus retrieved platform context is sent to an LLM
  provider — no account identifiers.
- Integration secrets live only in the environment; nothing sensitive is committed.

## Retention (enforced by `data:prune`)

Chat transcripts (90 d), search logs (180 d), integration events (90 d), import rows (365 d);
account-deletion grace is 30 days. See `DATABASE_SCHEMA.md`.

## Pre-launch checklist (not yet done)

- [ ] Serve over HTTPS only; set `APP_DEBUG=false`, `APP_ENV=production`, real `APP_URL`.
- [ ] Configure MySQL 8 and back up nightly; test a restore.
- [ ] Configure `MAIL_*`, then decide on `REQUIRE_EMAIL_VERIFICATION`.
- [ ] Remove demo accounts and demo data (`php artisan data:purge-demo`); import only lawfully-obtained verified centre data.
- [ ] Rotate all keys; keep the Google keys restricted and quotas/budget alerts on.
- [ ] Run `composer audit`; keep dependencies patched.
- [ ] Legal review for DPDP Act 2023 and publish the privacy/terms pages with counsel's copy.
- [ ] Add a cron entry for `php artisan schedule:run`.

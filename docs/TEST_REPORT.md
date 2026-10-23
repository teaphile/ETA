# Test Report — Exam Travel Assist

Date: 2026-10-18 (IST)
Runtime: PHP 8.4.8 · PHPUnit 12.5.40 · Laravel 13.37.0 · SQLite (in-memory for tests)

## Command and result

```
$ php vendor/bin/phpunit
OK (50 tests, 172 assertions)
```

Machine-readable summary (as emitted by the project's test runner):

```json
{"tool":"phpunit","result":"passed","tests":50,"passed":50,"assertions":172,"duration_ms":2730}
```

Style check:

```
$ php vendor/bin/pint --test
{"tool":"pint","result":"passed"}
```

Fresh install check (throwaway SQLite file):

```
$ DB_DATABASE=/tmp/…/t2.sqlite php artisan migrate:fresh --seed --force
  0001_01_01_000000_create_users_table .................... DONE
  0001_01_01_000001_create_cache_table .................... DONE
  0001_01_01_000002_create_jobs_table ..................... DONE
  2026_01_01_000001_create_exam_travel_tables ............. DONE
  2026_10_18_000001_add_provenance_and_health_tables ...... DONE
  ReferenceDataSeeder ..................................... DONE
  DemoDataSeeder .......................................... DONE
Seeded 9 demo centres (all flagged is_demo) and their demo listings.
```

27 tables are created; `php artisan about` reports the expected drivers, and `php artisan
route:list` reports 77 routes.

## Full test list (testdox)

**Unit — Centre search service**
- normalise trims and collapses whitespace
- normalise strips control characters and bounds length
- normalise defaults and validates sort
- normalise coerces positive integers only
- empty values become null

**Unit — CSV importer**
- preview rejects missing required headers without writing
- preview reports an unreadable file
- preview rejects an empty file
- required and optional column contract is stable

**Unit — Support DTOs**
- data status vocabulary and labels
- city directory resolution is conservative
- road distance uses disclosed detour factor
- haversine is symmetric and zero for same point
- travel option round trips without inventing a price
- journey plan lowest price ignores unpriced options
- journey plan unavailable is explicit
- journey plan and travel option round trip

**Feature — smoke**
- the application returns a successful response
- home and public pages load

**Feature — centre search & data honesty**
- centre search filters and empty state
- demo data is labelled
- centre without coordinates says so

**Feature — auth & accounts**
- registration login and role defaults
- registration requires terms acceptance
- password reset request is enumeration safe
- email verification is optional by default
- email verification flow when strictly required
- verification link is bound to the signed in account
- immediate account deletion anonymises and logs out
- registration welcome notification is stored not emailed

**Feature — journey & places**
- journey plan shows estimates not live fares
- journey plan requires at least one mode
- itinerary ownership enforced
- save and unsave place

**Feature — admin & moderation**
- admin routes require admin role
- moderator cannot reach admin only areas
- admin can create centre then it appears in search
- admin dashboard reports real counts
- admin user update protects last administrator
- csv import validates then imports valid rows only
- csv import rejects missing required headers

**Feature — assistant & community**
- chatbot grounded and never invents seats
- community post reply and report
- community cannot reply to locked thread
- faq page shows seeded faqs
- locale switch changes interface language

**Feature — platform**
- unknown route returns 404
- security headers present on html response
- content security policy uses a per request nonce
- demo purge removes only demo rows

## What the tests actually prove

- **Authorisation is enforced server-side.** A student is refused `/admin`; a moderator is
  refused admin-only sub-areas; a locked thread rejects replies.
- **Data honesty holds at the edges.** Flight prices render as *unavailable*; train/bus
  prices render as *estimated* with the IRCTC link; demo rows carry the DEMO DATA badge; a
  centre without coordinates explains why no map is shown.
- **The assistant does not invent facts.** It reports `provider=grounded_db`, never claims
  seat availability, and sets `limitation=live_provider_data_required` when asked for live
  fares.
- **The importer is safe.** Bad headers are rejected before any write; only valid rows are
  imported, and imported rows are marked `unverified`.
- **Account lifecycle works.** Immediate deletion removes the account and logs the user out;
  the last administrator cannot be demoted.
- **Security headers** (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`) are
  present on HTML responses, and the CSP that accompanies them gates scripts behind a
  per-request nonce — the rendered inline script carries the same nonce, and there is no
  `'unsafe-inline'` for scripts.
- **Demo data can be removed cleanly.** `data:purge-demo` deletes only rows flagged `is_demo`
  (plus the known demo accounts) and leaves real, imported centres untouched.

## Not covered (honest gaps)

- **Live third-party calls.** No test hits Google Maps/Places or OpenAI. Those providers are
  exercised only through their fallback paths; the live paths need secrets and are opt-in by
  design.
- **MySQL.** The suite runs on SQLite. Migrations are written to be driver-agnostic, but the
  MySQL path has **not** been executed in this environment (no MySQL/Docker/sudo available).
- **Browser/E2E and accessibility automation.** The responsive UI, keyboard navigation and
  screen-reader behaviour are built to good practice but are **not** covered by an automated
  browser test.
- **Scale/performance.** No load, concurrency or query-count budget tests were run.
- **Email delivery.** The suite asserts notifications are recorded by the log provider; it
  does not assert real SMTP delivery.

# 🎓 Exam Travel Assist — Student Examination Travel Assistance Platform (India)

Helps students travel to exam centres in unfamiliar cities: find centres, compare **honestly
labelled** travel options, plan the last mile, discover stay/food near the centre, ask a
grounded assistant, and learn from a moderated student community.

> **Independent platform.** Not affiliated with NTA, SSC, UPSC, IRCTC, any examination
> authority, transport operator or government body. It is **not** an official source of
> centre allotment, timetables, fares or availability.

The guiding rule of this codebase is **data honesty**: it never invents centres, fares,
timetables, hotel prices, vacancies, live ride-hailing availability, or "verified" status.
Anything that is a calculation is labelled *estimated*; anything unknown is labelled
*unavailable*; anything not yet checked is labelled *unverified*; demonstration rows are
stamped **DEMO DATA**. See [`docs/DATA_SOURCES_AND_VERIFICATION.md`](docs/DATA_SOURCES_AND_VERIFICATION.md).

---

## Stack

| Layer | Choice |
|---|---|
| Framework | Laravel 13.37 (PHP 8.4) |
| Front end | Blade templates + a small hand-written CSS design system (`public/css/app.css`) and ~15 lines of vanilla JS. **No bundler is required to run the app.** |
| Database | SQLite for local dev; MySQL 8 for production (driver-agnostic migrations) |
| Mail / notifications | Laravel mail, wrapped behind a `NotificationProvider` contract (log sink by default) |
| Tests | PHPUnit 12 |
| Style | Laravel Pint |

The repository *also* contains a Tailwind v4 + Vite pipeline (`package.json`,
`vite.config.js`, `resources/css/app.css`, `resources/js/app.js`). It is **not wired into the
layout** and is not needed to run the app — see
[Known issues](docs/IMPLEMENTATION_STATUS.md). Delete it or adopt it deliberately.

---

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed      # creates reference data + clearly-badged DEMO data
php artisan serve               # http://127.0.0.1:8000
php artisan test                # 48 tests
```

Demo logins (local/non-production only): `admin@example.com`, `moderator@example.com`,
`student@example.com` — password `password`. **There is no admin seeder for production**;
create the first administrator with `php artisan app:create-admin you@example.com`.

---

## Features

- 🔍 **Centre search** by examination / session / state / city / centre code / keyword
  (open to guests), with provenance (source name, source URL, last-verified date) and a
  verification badge on every record.
- 🧭 **Journey planner** — Stage A intercity (train / bus / flight / car) and Stage B last
  mile (walk / auto / cab / bus), distance-based **disclosed estimates**, official booking
  links (IRCTC, State RTC, airlines), budget flags, and saveable private itineraries.
- 🏨 **Nearby services** (hotel / lodge / lounge / food / breakfast / stationery) — curated
  administrator listings by default; Google Places when a key is configured.
- 🤖 **Grounded assistant** — answers from the platform's own centre/FAQ data first; an
  optional OpenAI-compatible provider may phrase the long tail. It states its sources and
  refuses to invent facts.
- 💬 **Moderated community** — posts, replies, content reports, lock/hide moderation.
- 🛠️ **Admin** — examinations, sessions, centres (CRUD + verify + archive), CSV import
  (validate → preview → approve), users & roles, FAQs, places, moderation queue, integration
  health, stale-data review, and an append-only audit log.
- 🌐 **Bilingual student UI** (English + हिन्दी) via a locale switcher. Admin screens are
  intentionally English-only.
- 🎨 **Responsive, accessible** mobile-first layout: skip link, landmarks, focus-visible
  styles, `aria-live` status regions, keyboard-operable mobile nav.

---

## Configuration

The app **runs with zero API keys**. Every integration is bound behind a contract and
degrades to a documented, clearly-labelled fallback. All credentials are read from the
environment in a single file, [`config/services.php`](config/services.php).

| Env var(s) | Purpose | When missing |
|---|---|---|
| `PLACES_PROVIDER` (`curated`\|`google`), `GOOGLE_PLACES_SERVER_KEY` | Live nearby-place search | Curated admin listings only, with an in-page notice |
| `GOOGLE_MAPS_BROWSER_KEY`, `GOOGLE_MAPS_SERVER_KEY` | Maps embed + server-side directions | Keyless Google Maps deep links + text address |
| `AI_PROVIDER` (`faq`\|`openai`), `OPENAI_API_KEY`, `OPENAI_MODEL`, `OPENAI_BASE_URL` | LLM phrasing over grounded context | Fully working grounded FAQ/DB assistant |
| `TRANSPORT_PROVIDER`, `TRANSPORT_API_KEY`, `TRANSPORT_API_BASE_URL` | Live transport data (requires a licensed partner) | Disclosed distance-based estimates + official links |
| `NOTIFICATION_PROVIDER`, `MAIL_*` | Transactional email | Notifications written to the log |
| `REQUIRE_EMAIL_VERIFICATION` | Block unverified accounts from student features | Off by default |
| `DB_*` | Database | SQLite file locally |

See [`docs/API_SETUP.md`](docs/API_SETUP.md) and
[`docs/COST_AND_API_DEPENDENCIES.md`](docs/COST_AND_API_DEPENDENCIES.md).

---

## Documentation

| Document | Contents |
|---|---|
| [`docs/IMPLEMENTATION_STATUS.md`](docs/IMPLEMENTATION_STATUS.md) | Honest feature ledger (what works, what is partial, what is blocked) |
| [`docs/REQUIREMENTS.md`](docs/REQUIREMENTS.md) | Requirement → implementation traceability |
| [`docs/ARCHITECTURE.md`](docs/ARCHITECTURE.md) | Layers, provider boundaries, data-honesty model |
| [`docs/DATABASE_SCHEMA.md`](docs/DATABASE_SCHEMA.md) | Tables, relationships, retention |
| [`docs/DATA_SOURCES_AND_VERIFICATION.md`](docs/DATA_SOURCES_AND_VERIFICATION.md) | Where every kind of data may lawfully come from |
| [`docs/API_SETUP.md`](docs/API_SETUP.md) | How to obtain and safely restrict each key |
| [`docs/SECURITY_AND_PRIVACY.md`](docs/SECURITY_AND_PRIVACY.md) | Controls, privacy posture, pre-launch checklist |
| [`docs/COST_AND_API_DEPENDENCIES.md`](docs/COST_AND_API_DEPENDENCIES.md) | Cost drivers and kill switches |
| [`docs/TEST_REPORT.md`](docs/TEST_REPORT.md) | Test evidence and what is not covered |
| [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md) | Local, MySQL, Docker and production checklist |
| [`docs/GO_LIVE.md`](docs/GO_LIVE.md) | Step-by-step launch runbook (four tracks) |
| [`docs/UI_GUIDELINES.md`](docs/UI_GUIDELINES.md) | Design system: classes, spacing, responsive and accessibility rules |

---

## Going to production

1. Set the MySQL 8 `DB_*` values, `APP_ENV=production`, `APP_DEBUG=false`, a real `APP_URL`,
   and **HTTPS only**.
2. `php artisan migrate --force` — **do not seed demo data**. Create the first admin with
   `php artisan app:create-admin`.
3. Import only lawfully-obtained, verified centre lists via **Admin → CSV Import**, and mark
   them verified only when you hold a source URL.
4. Configure `MAIL_*`, then optionally turn on `REQUIRE_EMAIL_VERIFICATION=true`.
5. Run the scheduler (`php artisan schedule:run` every minute via cron) for stale-data
   flagging, retention pruning and account-deletion processing.

Full checklist: [`docs/DEPLOYMENT.md`](docs/DEPLOYMENT.md).

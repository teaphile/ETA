# Database Schema

Defined by two application migrations:

- `2026_01_01_000001_create_exam_travel_tables.php` — the domain tables (+ widened `users`).
- `2026_10_18_000001_add_provenance_and_health_tables.php` — provenance, health telemetry,
  indexes and the `notifications` table.

Migrations are written to be **driver-agnostic** (SQLite for local dev, MySQL 8 for
production). Creating a fresh database yields 27 tables, of which 20 are application tables:

| Table | Purpose |
|---|---|
| `users` | Accounts. Added columns: `role`, `phone`, `current_city`, `state`, `preferred_language`, `account_status`, `deletion_requested_at` (plus Laravel's `last_login_at`/`terms_accepted_at`) |
| `examinations` | Examination catalogue (name, slug, conducting authority, official URL, status) |
| `exam_sessions` | Sessions/years for an examination |
| `exam_centres` | Centres. FK to examination/session; indexed `centre_code`, `name`, `city`, `state`, `status`; provenance: `verification_status`, `source_name`, `source_url`, `last_verified_at`, `is_demo`, `latitude`/`longitude` (nullable) |
| `import_batches` | A CSV upload: status (`awaiting_approval`/`imported`/`rejected`), row counts, uploader |
| `import_rows` | Per-row parse/validation result and error message |
| `itineraries` | A saved journey plan (owner, origin/destination, `options_snapshot` JSON) |
| `local_services` | Curated nearby listings (FK to centre; category; source + `last_verified_at`; `is_demo`) |
| `saved_places` | Unique (user, local_service) pairs |
| `faqs` | Help/FAQ content, ordered, optionally published |
| `community_posts` | Threads (author, title, body, category, status, `is_locked`) |
| `community_replies` | Replies to a post |
| `content_reports` | Polymorphic reports (`reportable_type`/`reportable_id`) with status |
| `chat_conversations` | One assistant conversation per user |
| `chat_messages` | Transcript turns (role, content, provider, category, `sources` JSON, `limitation`) |
| `audit_logs` | Append-only record of sensitive actions |
| `search_logs` | Centre-search analytics (term-level, **no user identifiers**) |
| `integration_events` | External-call telemetry: provider, operation, success, HTTP status, latency, error category — **never** keys or payloads |
| `notifications` | Laravel database notifications (e.g. the verification mail record) |
| `password_reset_tokens` | Laravel password-reset tokens |

Framework tables (`cache`, `cache_locks`, `sessions`, `jobs`, `job_batches`, `failed_jobs`,
`migrations`) are Laravel defaults.

## Relationships

```
Examination 1─n ExamSession 1─n ExamCentre 1─n LocalService
User        1─n Itinerary / CommunityPost / ChatConversation
CommunityPost 1─n CommunityReply
ContentReport ─polymorphic─▶ CommunityPost | CommunityReply
ImportBatch 1─n ImportRow
User        1─n SavedPlace n─1 LocalService
```

## Provenance & freshness

- `verification_status` uses the `DataStatus` vocabulary (see `ARCHITECTURE.md`).
- Freshness windows come from `config/exam_travel.php` (`CENTRE_FRESHNESS_MONTHS`,
  `SERVICE_FRESHNESS_MONTHS`, default 6). `data:flag-stale` marks records past the window
  as `stale`. Missing verification is never treated as fresh.
- `is_demo` marks demonstration rows; the UI always badges them.

## Retention

Windows are configuration-driven and enforced by `data:prune` (see `SECURITY_AND_PRIVACY.md`):

| Data | Env var | Default |
|---|---|---|
| Chat transcripts | `CHAT_RETENTION_DAYS` | 90 days |
| Search logs | `SEARCH_LOG_RETENTION_DAYS` | 180 days |
| Integration events | `INTEGRATION_EVENT_RETENTION_DAYS` | 90 days |
| Deleted-account grace | `ACCOUNT_DELETION_GRACE_DAYS` | 30 days |
| Import rows | `IMPORT_ROW_RETENTION_DAYS` | 365 days |

Archived centres are retained with `status = archived`; the UI never hard-deletes them.

## Scheduler

`routes/console.php` registers three daily commands (repeatable, support `--dry-run`):

- `02:15` `data:prune`
- `02:45` `data:flag-stale`
- `03:15` `accounts:process-deletions`

Requires one cron entry: `* * * * * php artisan schedule:run`.

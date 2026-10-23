# Deployment

## Prerequisites

- PHP 8.2+ (tested on 8.4) with `pdo_mysql`, `mbstring`, `curl`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`.
- Composer 2.
- MySQL 8.0+ for production (SQLite is fine for local dev).
- A web server whose document root is the `public/` directory.
- (Optional) Node 20+ **only** if you choose to switch to the Vite/Tailwind pipeline. Not required to run the app.

## Local development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed          # reference data + badged DEMO data
php artisan serve                   # http://127.0.0.1:8000
php artisan test
```

Demo logins (non-production): `admin@example.com`, `moderator@example.com`,
`student@example.com` / `password`.

## Docker (development convenience)

`docker-compose.yml` brings up an app container (PHP 8.4 built-in server) and MySQL 8.4.

```bash
docker compose up --build
```

It is a **development convenience, not a production recipe**: it does not run
`composer install`, `key:generate`, or migrations for you, and it uses PHP's built-in server.
Inside the app container run:

```bash
composer install
php artisan key:generate
php artisan migrate --seed
```

## Production (MySQL 8)

1. **Create the database and a least-privilege user.**
2. **Configure the environment** (never commit `.env`):
   ```
   APP_NAME="Exam Travel Assist"
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://your-domain.example
   APP_KEY=            # php artisan key:generate writes this
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=examtravel
   DB_USERNAME=exam
   DB_PASSWORD=…
   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database
   MAIL_MAILER=smtp
   MAIL_* …
   REQUIRE_EMAIL_VERIFICATION=false   # turn on once mail works
   ```
3. **Install dependencies and build caches.**
   ```bash
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force          # do NOT seed demo data
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
4. **Create the first administrator** (there is no admin seeder in production):
   ```bash
   php artisan app:create-admin you@example.com --name="Your Name"
   ```
5. **Point the web server at `public/`.** Example (nginx):
   ```
   root /var/www/exam-travel-assist/public;
   location / { try_files $uri $uri/ /index.php?$query_string; }
   ```
   Ensure `storage/` and `bootstrap/cache/` are writable by the web user.
6. **HTTPS** via Let's Encrypt (or your provider). The app forces HTTPS in production.
7. **Scheduler** — one cron entry:
   ```
   * * * * * cd /var/www/exam-travel-assist && php artisan schedule:run >> /dev/null 2>&1
   ```
   This drives stale-flagging, retention pruning and account-deletion processing.
8. **Queue worker** (if you enable queued mail/notifications):
   ```
   php artisan queue:work --tries=3
   ```
   Managed by Supervisor/systemd.

## Health, logs, backups

- Health endpoint: `GET /up`.
- Logs: `storage/logs/` (configure rotation).
- Backup: nightly `mysqldump` of the database plus a sync of `storage/app`. **Test a restore.**

## Rollback

- Deploy by tag/commit; roll back with `git checkout <previous-tag>`.
- Migrations are reversible where the migration defines a `down()`; otherwise restore the
  database backup taken before the deploy. Take the backup **before** running new migrations.

## Post-deploy checklist

- [ ] Demo data and demo accounts removed.
- [ ] Only lawfully-obtained, verified centre data imported; sources recorded.
- [ ] Integration keys set and restricted (or left empty for keyless operation).
- [ ] `REQUIRE_EMAIL_VERIFICATION` decided and mail delivery confirmed.
- [ ] Cron and queue workers running.
- [ ] Calendar reminder to review stale-data reports monthly.

## Caveats for this repository

- The **MySQL production path has not been executed in the development sandbox** (no MySQL,
  Docker or sudo available there). Migrations are driver-agnostic and were validated on SQLite;
  run `php artisan migrate --force` on the target host and verify.
- The default deployment does **not** need a Node/build step; `public/css/app.css` is served
  directly. The Vite pipeline in the repository is unused.

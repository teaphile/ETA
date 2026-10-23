# Go Live — Exam Travel Assist

This runbook takes the app from this repository to a public URL. Pick **one** of the
tracks below. Tracks A–C are real production; Track D is a temporary public demo.

> **Before any public exposure**, read [`SECURITY_AND_PRIVACY.md`](SECURITY_AND_PRIVACY.md)
> and complete its pre-launch checklist. In particular: `APP_DEBUG=false`, a real
> `APP_KEY`, HTTPS, and **no demo accounts with a known password**.

---

## Track A — Your own VPS with nginx + PHP-FPM + MySQL (recommended)

Requirements: an Ubuntu 22.04/24.04 server with a public IP, root SSH access, and a domain
whose DNS A-record points at the server.

```bash
# 1. On the server: install the base stack
sudo bash deploy/bootstrap-server.sh

# 2. On the server: create the database (use a strong password)
sudo mysql -e "CREATE DATABASE examtravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'exam'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';"
sudo mysql -e "GRANT ALL PRIVILEGES ON examtravel.* TO 'exam'@'localhost'; FLUSH PRIVILEGES;"

# 3. From your workstation: push the code
DEPLOY_HOST=user@YOUR_SERVER_IP \
DEPLOY_PATH=/var/www/exam-travel-assist \
bash deploy/deploy.sh

# 4. On the server: configure the environment
cd /var/www/exam-travel-assist
cp .env.production.example .env
nano .env                      # APP_URL, DB_*, MAIL_*
php artisan key:generate
php artisan migrate --force
php artisan app:create-admin you@example.com --name="Your Name"

# 5. On the server: nginx + TLS
sudo cp deploy/nginx.conf /etc/nginx/sites-available/exam-travel-assist
sudo nano /etc/nginx/sites-available/exam-travel-assist   # set server_name + PHP socket
sudo ln -s /etc/nginx/sites-available/exam-travel-assist /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d your-domain.example

# 6. On the server: scheduler (and queue worker if using queued mail)
sudo crontab -u www-data -e            # add the line from deploy/crontab
sudo cp deploy/supervisor-worker.conf /etc/supervisor/conf.d/
sudo supervisorctl reread && sudo supervisorctl update

# 7. Verify
curl -fsS https://your-domain.example/up && echo OK
```

Then log in as the admin you created. If this database ever held demo data, remove
it first — `php artisan data:purge-demo` prints exactly what it would delete and
asks before doing it. Then import real, lawfully-obtained centre data via
**Admin → CSV import**. The app ships with **no production data** by design.

---

## Track B — A managed Laravel platform (least ops)

Options such as **Laravel Cloud**, **Laravel Forge** (provisions the VPS for you), **Ploi**,
**RunCloud**, **Railway**, **Render** or **Fly.io**.

- Connect the Git repository, set the **root/web directory to `public/`**, and add the
  environment variables from `.env.production.example`.
- Run the release commands: `composer install --no-dev --optimize-autoloader`,
  `php artisan migrate --force`, and the cache commands in `deploy/deploy.sh`.
- These platforms give you a domain and TLS automatically; add your own for production.

I cannot complete this from here because it requires your account and authentication, but the
repository is ready for it (no build step, standard Laravel layout, env-based config).

---

## Track C — Shared hosting (cPanel)

1. Upload the code **above** the web root (e.g. `/home/user/exam-travel-assist`).
2. Point `public_html` at the app's `public/` directory (or move `public/` contents up and
   adjust `public/index.php`'s `require` paths).
3. Create a MySQL database + user in cPanel; fill in `.env`.
4. Set the PHP version to 8.2+ and run from cPanel Terminal:
   `composer install --no-dev --optimize-autoloader && php artisan key:generate && php artisan migrate --force && php artisan app:create-admin you@example.com`
5. Enable the cron entry from `deploy/crontab` in cPanel.

---

## Track D — Temporary public URL from this sandbox (demo only)

A Cloudflare quick tunnel can expose a local server with no account:

```bash
bash deploy/preview.sh
```

This prints a `https://….trycloudflare.com` URL. **It is not production:** it runs PHP's
built-in server over SQLite, dies when the process/sandbox stops, and the URL is random and
ephemeral. Use it only to *show* the app. If you use it, do **not** leave demo admin
credentials active.

---

## Go-live checklist (production)

- [ ] `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`, fresh `APP_KEY`.
- [ ] MySQL 8 configured; `php artisan migrate --force` succeeded.
- [ ] `php artisan app:create-admin` run; **demo accounts removed**.
- [ ] `php artisan data:purge-demo --dry-run` reports nothing left to delete.
- [ ] Only lawfully-obtained centre data imported, with source URLs recorded.
- [ ] `MAIL_*` configured; decide `REQUIRE_EMAIL_VERIFICATION`.
- [ ] HTTPS enforced (certbot / platform TLS); HSTS considered.
- [ ] Scheduler cron installed; queue worker if needed.
- [ ] Nightly DB backup scheduled **and** a restore tested.
- [ ] Integration keys set and restricted, or left empty for keyless operation.
- [ ] `curl https://…/up` returns healthy.

#!/usr/bin/env bash
# =============================================================================
# Exam Travel Assist — zero-downtime-ish deploy from a workstation.
# -----------------------------------------------------------------------------
# Syncs the app to a server over rsync and runs the Laravel release steps.
# Idempotent and safe to re-run. It does NOT touch the server's .env or the DB
# data; it only runs `migrate --force` (additive migrations).
#
# Usage:
#   DEPLOY_HOST=user@1.2.3.4 \
#   DEPLOY_PATH=/var/www/exam-travel-assist \
#   []=www-data \
#   bash deploy/deploy.sh
#
# Optional env:
#   DEPLOY_KEY=/path/to/private_key   # ssh identity file
#   DEPLOY_BRANCH=(current checkout)  # informational only
# =============================================================================
set -euo pipefail

: "${DEPLOY_HOST:?Set DEPLOY_HOST, e.g. user@1.2.3.4}"
: "${DEPLOY_PATH:?Set DEPLOY_PATH, e.g. /var/www/exam-travel-assist}"
DEPLOY_USER="${DEPLOY_USER:-www-data}"
DEPLOY_KEY="${DEPLOY_KEY:-}"
SSH_OPTS=(-o StrictHostKeyChecking=accept-new)
[[ -n "$DEPLOY_KEY" ]] && SSH_OPTS+=(-i "$DEPLOY_KEY")

LOCAL_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

echo "==> Deploying $LOCAL_DIR → $DEPLOY_HOST:$DEPLOY_PATH"

echo "==> [1/5] Uploading files (excluding local/dev artefacts)"
rsync -az --delete \
  -e "ssh ${SSH_OPTS[*]}" \
  --exclude '.git' \
  --exclude '.env' \
  --exclude '.env.*' \
  --exclude 'node_modules' \
  --exclude 'storage/logs/*' \
  --exclude 'storage/framework/cache/data/*' \
  --exclude 'storage/framework/sessions/*' \
  --exclude 'storage/framework/views/*' \
  --exclude 'database/*.sqlite' \
  --exclude 'tests' \
  --exclude 'deploy' \
  "$LOCAL_DIR/" "$DEPLOY_HOST:$DEPLOY_PATH/"

echo "==> [2/5] Installing production dependencies"
ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" bash -lc "'
  cd \"$DEPLOY_PATH\" &&
  composer install --no-dev --optimize-autoloader --no-interaction
'"

echo "==> [3/5] Running migrations"
ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" bash -lc "'
  cd \"$DEPLOY_PATH\" &&
  php artisan migrate --force
'"

echo "==> [4/5] Rebuilding caches"
ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" bash -lc "'
  cd \"$DEPLOY_PATH\" &&
  php artisan config:cache &&
  php artisan route:cache &&
  php artisan view:cache
'"

echo "==> [5/5] Fixing permissions and reloading PHP-FPM"
ssh "${SSH_OPTS[@]}" "$DEPLOY_HOST" bash -lc "'
  cd \"$DEPLOY_PATH\" &&
  chown -R ${DEPLOY_USER}:${DEPLOY_USER} storage bootstrap/cache &&
  chmod -R ug+rwX storage bootstrap/cache &&
  (sudo systemctl reload php8.4-fpm 2>/dev/null || true)
'"

echo "==> Done. Health check:  curl -fsS https://YOUR_DOMAIN/up  && echo OK"

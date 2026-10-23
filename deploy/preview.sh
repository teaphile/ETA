#!/usr/bin/env bash
# =============================================================================
# Exam Travel Assist — temporary public demo via a Cloudflare quick tunnel.
# -----------------------------------------------------------------------------
# DEMO ONLY. It serves PHP's built-in server over SQLite on a random, ephemeral
# *.trycloudflare.com URL. Nothing here is production-grade.
#
# Safety measures applied automatically:
#   * APP_DEBUG=false for the tunnel process (overrides .env).
#   * Demo account passwords are randomised and printed once, so the well-known
#     "password" credential is not exposed.
#
# Usage:   bash deploy/preview.sh
# Stop:    Ctrl-C
# =============================================================================
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$APP_DIR"

PORT="${PORT:-8000}"
BIN_DIR="${BIN_DIR:-/tmp/opencode/bin}"
CF="$BIN_DIR/cloudflared"

# --- locate a PHP binary ------------------------------------------------------
PHP_BIN="$(command -v php || true)"
[[ -z "$PHP_BIN" && -x /tmp/opencode/php/php ]] && PHP_BIN=/tmp/opencode/php/php
[[ -z "$PHP_BIN" ]] && { echo "No PHP binary found." >&2; exit 1; }

# --- fetch cloudflared if missing --------------------------------------------
if [[ ! -x "$CF" ]]; then
  echo "==> Downloading cloudflared"
  mkdir -p "$BIN_DIR"
  curl -fsSL -o "$CF" \
    https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64
  chmod +x "$CF"
fi

# --- randomise demo credentials so the tunnel is not trivially compromised ----
echo "==> Randomising demo account passwords"
DEMO_PW="$("$PHP_BIN" -r '
require "vendor/autoload.php";
$app = require "bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$pw = bin2hex(random_bytes(6));
foreach (["admin@example.com","moderator@example.com","student@example.com"] as $e) {
    $u = App\Models\User::where("email", $e)->first();
    if ($u) { $u->forceFill(["password" => $pw])->save(); }
}
echo $pw;
')"
echo "    Temporary password for demo accounts: $DEMO_PW"
echo "    admin@example.com / moderator@example.com / student@example.com"

# --- start the app (debug off) -----------------------------------------------
echo "==> Starting PHP server on 127.0.0.1:$PORT (APP_DEBUG=false)"
APP_DEBUG=false APP_ENV=local \
  "$PHP_BIN" -S "127.0.0.1:$PORT" -t public >/tmp/opencode/preview-server.log 2>&1 &
APP_PID=$!
trap 'kill $APP_PID 2>/dev/null || true' EXIT

sleep 1

# --- start the tunnel ---------------------------------------------------------
echo "==> Opening Cloudflare quick tunnel (look for the https://…trycloudflare.com URL)"
echo
"$CF" tunnel --no-autoupdate --url "http://127.0.0.1:$PORT"

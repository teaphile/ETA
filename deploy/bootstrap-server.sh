#!/usr/bin/env bash
# =============================================================================
# Exam Travel Assist — one-time server bootstrap (Ubuntu 22.04/24.04, run as root)
# -----------------------------------------------------------------------------
# Installs PHP 8.4 + extensions, nginx, MySQL 8, composer, and prepares the
# directory. It does NOT create the database, the .env, or deploy code — see
# docs/GO_LIVE.md for the full sequence.
#
# Usage (on a fresh server):   sudo bash deploy/bootstrap-server.sh
# =============================================================================
set -euo pipefail

if [[ "$(id -u)" -ne 0 ]]; then echo "Run as root (sudo)." >&2; exit 1; fi

export DEBIAN_FRONTEND=noninteractive

echo "==> Updating packages"
apt-get update -y && apt-get upgrade -y

echo "==> Installing PHP 8.4 and extensions"
apt-get install -y software-properties-common
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y \
  php8.4-fpm php8.4-cli php8.4-mysql php8.4-mbstring php8.4-xml \
  php8.4-curl php8.4-zip php8.4-bcmath php8.4-intl php8.4-gd

echo "==> Installing nginx and MySQL 8"
apt-get install -y nginx mysql-server unzip git

echo "==> Installing Composer"
curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
rm -f /tmp/composer-setup.php

echo "==> Creating application directory"
APP_DIR=/var/www/exam-travel-assist
mkdir -p "$APP_DIR"
chown -R www-data:www-data /var/www

cat <<'NEXT'

--------------------------------------------------------------------------
Server base ready. Next steps (see docs/GO_LIVE.md):

  1. Create the database and user:
       sudo mysql -e "CREATE DATABASE examtravel CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
       sudo mysql -e "CREATE USER 'exam'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';"
       sudo mysql -e "GRANT ALL PRIVILEGES ON examtravel.* TO 'exam'@'localhost'; FLUSH PRIVILEGES;"

  2. Deploy the code (from your workstation):
       DEPLOY_HOST=user@SERVER DEPLOY_PATH=/var/www/exam-travel-assist bash deploy/deploy.sh

  3. On the server, create .env from .env.production.example and:
       php artisan key:generate
       php artisan migrate --force
       php artisan app:create-admin you@example.com

  4. Install the nginx site (deploy/nginx.conf) and get a certificate:
       sudo certbot --nginx -d your-domain.example
--------------------------------------------------------------------------
NEXT

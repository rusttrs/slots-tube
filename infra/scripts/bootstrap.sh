#!/usr/bin/env bash
# Slots.tube — Ubuntu 24.04 server bootstrap (Phase 0)
# Run as root on a fresh DigitalOcean Droplet:
#   curl -fsSL ... | bash
#   OR: bash bootstrap.sh
set -euo pipefail

APP_USER="${APP_USER:-deploy}"
APP_DIR="${APP_DIR:-/var/www/slots.tube}"
DOMAIN="${DOMAIN:-slots.tube}"
PHP_VERSION="${PHP_VERSION:-8.4}"
POSTGRES_DB="${POSTGRES_DB:-slotstube}"
POSTGRES_USER="${POSTGRES_USER:-slotstube}"
# Pass POSTGRES_PASSWORD via env; generated if empty
POSTGRES_PASSWORD="${POSTGRES_PASSWORD:-}"

export DEBIAN_FRONTEND=noninteractive

echo "==> Updating system"
apt-get update -y
apt-get upgrade -y

echo "==> Installing base packages"
apt-get install -y \
  curl ca-certificates gnupg lsb-release software-properties-common \
  ufw fail2ban unzip git redis-server nginx \
  build-essential

echo "==> Firewall (22/80/443)"
ufw default deny incoming
ufw default allow outgoing
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

echo "==> PHP ${PHP_VERSION}"
add-apt-repository -y ppa:ondrej/php
apt-get update -y
apt-get install -y \
  "php${PHP_VERSION}-fpm" \
  "php${PHP_VERSION}-cli" \
  "php${PHP_VERSION}-common" \
  "php${PHP_VERSION}-pgsql" \
  "php${PHP_VERSION}-redis" \
  "php${PHP_VERSION}-mbstring" \
  "php${PHP_VERSION}-xml" \
  "php${PHP_VERSION}-curl" \
  "php${PHP_VERSION}-zip" \
  "php${PHP_VERSION}-gd" \
  "php${PHP_VERSION}-intl" \
  "php${PHP_VERSION}-bcmath" \
  "php${PHP_VERSION}-tokenizer" \
  "php${PHP_VERSION}-opcache"

echo "==> Composer"
if ! command -v composer >/dev/null 2>&1; then
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

echo "==> PostgreSQL 16"
apt-get install -y postgresql postgresql-contrib
systemctl enable --now postgresql

if [[ -z "${POSTGRES_PASSWORD}" ]]; then
  POSTGRES_PASSWORD="$(openssl rand -base64 32 | tr -d '=+/' | cut -c1-32)"
fi

sudo -u postgres psql -tc "SELECT 1 FROM pg_roles WHERE rolname='${POSTGRES_USER}'" | grep -q 1 \
  || sudo -u postgres psql -c "CREATE USER ${POSTGRES_USER} WITH PASSWORD '${POSTGRES_PASSWORD}';"
sudo -u postgres psql -tc "SELECT 1 FROM pg_database WHERE datname='${POSTGRES_DB}'" | grep -q 1 \
  || sudo -u postgres psql -c "CREATE DATABASE ${POSTGRES_DB} OWNER ${POSTGRES_USER};"
sudo -u postgres psql -c "GRANT ALL PRIVILEGES ON DATABASE ${POSTGRES_DB} TO ${POSTGRES_USER};"
sudo -u postgres psql -d "${POSTGRES_DB}" -c "GRANT ALL ON SCHEMA public TO ${POSTGRES_USER};"

echo "==> Redis"
systemctl enable --now redis-server
# bind localhost only (default on Ubuntu is fine)
sed -i 's/^supervised no/supervised systemd/' /etc/redis/redis.conf || true
systemctl restart redis-server

echo "==> App user: ${APP_USER}"
if ! id -u "${APP_USER}" >/dev/null 2>&1; then
  adduser --disabled-password --gecos "" "${APP_USER}"
  usermod -aG www-data "${APP_USER}"
fi
mkdir -p "${APP_DIR}"
chown -R "${APP_USER}:www-data" "${APP_DIR}"

echo "==> Node.js 22 (assets build on server optional)"
if ! command -v node >/dev/null 2>&1; then
  curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
  apt-get install -y nodejs
fi

echo "==> Nginx site placeholder (Coming Soon until Laravel cutover)"
mkdir -p /var/www/coming-soon
cat >/var/www/coming-soon/index.html <<'HTML'
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Coming Soon | slots.tube</title>
  <meta name="robots" content="noindex, follow" />
</head>
<body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#0a0614;color:#fff;font-family:system-ui,sans-serif">
  <main style="text-align:center">
    <p style="letter-spacing:.12em;text-transform:uppercase;opacity:.7">slots.tube</p>
    <h1>Coming Soon</h1>
  </main>
</body>
</html>
HTML

cat >/etc/nginx/sites-available/slots.tube <<NGINX
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN} www.${DOMAIN};

    root /var/www/coming-soon;
    index index.html;

    # Cloudflare real IP (will be refined after CF is in front)
    real_ip_header CF-Connecting-IP;

    location / {
        try_files \$uri \$uri/ =404;
    }

    location ~ /\. {
        deny all;
    }

    add_header X-Content-Type-Options nosniff always;
    add_header Referrer-Policy strict-origin-when-cross-origin always;
}
NGINX

ln -sfn /etc/nginx/sites-available/slots.tube /etc/nginx/sites-enabled/slots.tube
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

CRED_FILE="/root/slots-tube-credentials.txt"
cat >"${CRED_FILE}" <<EOF
# Slots.tube bootstrap credentials — store securely and delete this file
Generated: $(date -u +%Y-%m-%dT%H:%M:%SZ)
Domain: ${DOMAIN}
App dir: ${APP_DIR}
App user: ${APP_USER}

PostgreSQL:
  host: 127.0.0.1
  port: 5432
  database: ${POSTGRES_DB}
  username: ${POSTGRES_USER}
  password: ${POSTGRES_PASSWORD}

Redis:
  host: 127.0.0.1
  port: 6379

PHP: ${PHP_VERSION}-fpm
Nginx: coming-soon on :80 (Cloudflare SSL Full later)
EOF
chmod 600 "${CRED_FILE}"

echo ""
echo "==> Bootstrap complete"
echo "Credentials written to ${CRED_FILE}"
echo "Next: point Cloudflare A record to this server IP (proxied), SSL Full (strict) after origin cert."
echo "Then deploy Laravel to ${APP_DIR} and switch nginx root to public/."

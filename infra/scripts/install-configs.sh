#!/usr/bin/env bash
# Раскладывает конфиги из infra/ по системе. Идемпотентен: можно запускать повторно
# после любых правок в infra/ (nginx, cron, logrotate, bin-скрипты).
#
#   sudo bash /var/www/slots.tube/infra/scripts/install-configs.sh
set -euo pipefail

INFRA="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
APP_USER="${APP_USER:-deploy}"

[[ $EUID -eq 0 ]] || { echo "Запусти от root"; exit 1; }

echo "==> bin: deploy, slots-backup-db → /usr/local/bin"
install -m 755 "$INFRA/bin/deploy"          /usr/local/bin/deploy
install -m 755 "$INFRA/bin/slots-backup-db" /usr/local/bin/slots-backup-db
install -d /var/lib/slots-deploy

echo "==> nginx site"
install -m 644 "$INFRA/nginx/slots.tube.conf" /etc/nginx/sites-available/slots.tube
ln -sfn /etc/nginx/sites-available/slots.tube /etc/nginx/sites-enabled/slots.tube
rm -f /etc/nginx/sites-enabled/default
if [[ -f /etc/ssl/cloudflare/origin.pem && -f /etc/ssl/cloudflare/origin.key ]]; then
  nginx -t && systemctl reload nginx
else
  echo "!! Нет /etc/ssl/cloudflare/origin.{pem,key}: nginx не перезагружен (см. docs/from-scratch.md, шаг «SSL»)."
fi

echo "==> cron: scheduler (crontab $APP_USER) + бэкап (cron.d)"
crontab -u "$APP_USER" "$INFRA/cron/deploy.crontab"
install -m 644 "$INFRA/cron/slots-tube-backup" /etc/cron.d/slots-tube-backup

echo "==> logrotate"
install -m 644 "$INFRA/logrotate/slots-tube" /etc/logrotate.d/slots-tube
logrotate -d /etc/logrotate.d/slots-tube >/dev/null 2>&1 || echo "!! logrotate -d сообщил об ошибке"

echo "✅ Конфиги установлены"

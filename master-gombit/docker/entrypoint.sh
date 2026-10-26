#!/bin/sh
# =============================================================================
# Master Gombit entrypoint - lokal eğitim ortamı
# 1) .env varsa export et (compose zaten geçirir; CLI/test için yedek)
# 2) php-fpm + nginx başlat
# NOT: Bu betik internete çıkmaz, uzaktan kod indirmez.
# =============================================================================
set -eu

if [ -f /var/www/html/.env ]; then
  set -a; . /var/www/html/.env; set +a
fi

: "${APP_ENV:=local}"
export APP_ENV

echo "[mg] starting php-fpm (env=${APP_ENV})"
php-fpm &

echo "[mg] starting nginx"
exec nginx -g "daemon off;"

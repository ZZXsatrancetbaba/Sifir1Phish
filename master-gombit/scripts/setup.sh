#!/usr/bin/env bash
# Master Gombit - kurulum (yalnızca lokal). İnternet gerektirmez (vendor zaten Docker build'de).
set -euo pipefail
cd "$(dirname "$0")/.."

[ -f .env ] || { cp .env.example .env; echo "[i] .env oluşturuldu — DB_PASS/DB_ROOT_PASS/APP_KEY değerlerini güncelleyin."; }
if grep -q '^APP_KEY=$' .env; then
  KEY=$(head -c 32 /dev/urandom | od -An -tx1 | tr -d ' \n')
  sed -i "s/^APP_KEY=.*/APP_KEY=${KEY}/" .env
  echo "[i] APP_KEY üretildi."
fi

docker compose up --build -d
echo "[✓] http://127.0.0.1:8080 açın. health: http://127.0.0.1:8080/health.php"

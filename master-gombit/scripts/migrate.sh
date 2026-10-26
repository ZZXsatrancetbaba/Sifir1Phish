#!/usr/bin/env bash
# Şemayı mevcut container DB'sine yeniden uygular (idempotent; initdb ilk açılışta çalışır).
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose exec -T db sh -c 'mysql -u"root" -p"$MARIADB_ROOT_PASSWORD" < /docker-entrypoint-initdb.d/01_schema.sql' mg_lab \
  && echo "[✓] schema uygulandı"

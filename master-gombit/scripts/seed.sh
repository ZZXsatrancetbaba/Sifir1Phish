#!/usr/bin/env bash
# Sentetik seed verisini yeniden uygular (ON DUPLICATE ile idempotent).
set -euo pipefail
cd "$(dirname "$0")/.."
docker compose exec -T db sh -c 'mysql -u"root" -p"$MARIADB_ROOT_PASSWORD" mg_lab < /docker-entrypoint-initdb.d/02_seed.sql' \
  && echo "[✓] seed uygulandı"

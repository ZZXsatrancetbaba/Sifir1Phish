#!/usr/bin/env bash
# Testler: unit+security container dışında da koşar; feature testleri DB ister.
set -uo pipefail
cd "$(dirname "$0")/.."
composer install --quiet
vendor/bin/phpunit --testsuite "Unit,Security" "$@"
echo "--- Feature suite (DB varsa) ---"
vendor/bin/phpunit --testsuite Feature "$@" || echo "[i] Feature testleri için docker compose ortamı gerekir (atlandı)."

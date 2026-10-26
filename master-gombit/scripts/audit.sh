#!/usr/bin/env bash
# Savunma odaklı lokal denetim betiği — YALNIZCA kendi repomuzda çalışır.
# Dış hedef taraması/saldırı aracı DEĞİLDİR.
set -uo pipefail
cd "$(dirname "$0")/.."
fail=0

echo "== 1) SQL concatenation tespiti =="
if grep -RnE '(->(query|exec)\([^)]*\.\s*\$|"[^"]*"\s*\.\s*\$_)' app/ public/ ; then
  echo "BULGU: parametresiz SQL birleştirme bulundu!"; fail=1
else echo "temiz"; fi

echo "== 2) Zayıf hash kullanımı =="
if grep -RnE '\b(md5|sha1)\s*\(\s*\$' app/ ; then echo "BULGU: md5/sha1 değişken hash'i"; fail=1; else echo "temiz"; fi

echo "== 3) Ham kısa etiket (encode edilmemiş view çıktısı) =="
HITS=$(grep -RnE '<\?=\s*\$[a-zA-Z_]+(\[[^]]+\])?\s*\?>' app/Views/ || true)
if [ -n "$HITS" ]; then echo "$HITS"; echo "UYARI: bu satırlarda E::escHtml kontrol edin"; fi

echo "== 4) Tehlikeli fonksiyonlar =="
grep -RnE '\b(eval|assert|extract\s*\(\s*\$_|unserialize\s*\(|shell_exec|proc_open|create_function)\s*\(' app/ public/ || echo "temiz"

echo "== 5) Secret taraması (regex fallback; CI'da gitleaks asıldır) =="
grep -RnEi '(api[_-]?key|secret|password)\s*[:=]\s*["'"'"'][^"'"'"']{8,}' --include='*.php' --include='*.yml' app/ config/ docker-compose.yml | grep -v 'example\|change-me\|REDACTED\|DB_PASS=?\|getenv' || echo "temiz"

echo "== 6) Composer audit =="
composer audit 2>/dev/null || echo "[i] composer audit çalıştırılamadı (offline olabilir)"

exit $fail

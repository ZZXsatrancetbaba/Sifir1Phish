# Lab 09 — Secret Yönetimi & Tedarik Zinciri

**OWASP:** A06 (Vulnerable & Outdated Components) + A05 karışık · **Zorluk:** medium · **Puan:** 20
**Öğrenme hedefi:** Secret sızıntısını önleme, bağımlılık hijyeni, SBOM ve CI kontrolleri.

## 🔐 Secret yönetimi kuralları (repoda uygulanan)
1. Yalnız `.env.example` repoda; `.env` gitignore'da.
2. Örnek dosyada placeholder değerler + "sentetik" uyarısı.
3. `APP_KEY = openssl rand -hex 32`; DB parolaları compose ile env olarak geçer (`:?` zorunlu tutarak eksikse ayağa kalkmaz).
4. Kod içine gömülü secret taraması: CI'da `gitleaks` adımı (workflow örneği).
5. Docker secrets (opsiyonel) / Vault (ileri seviye) notu RUNBOOK'da.

## 📦 Composer hijyeni
- `composer.lock` commit edilir → deterministik build (supply chain temeli).
- `composer install --no-dev` prod imajda; `--audit` ile bilinen advisory kontrolü.
- `allow-plugins: false`, minimum-stability stable.
- Bağımlılık yüzeyi küçültme: projede tek runtime paketi (Monolog); framework yok — bilinçli eğitim tercihi.

## 🧾 SBOM örneği
```bash
composer require --dev cyclonedx/cyclonedx-php-composer -W   # lokal geliştirme aracı
vendor/bin/cyclonedx --format json --output sbom.json
```
SBOM'u artifact olarak saklayın; CVE duyurusunda "var mı?" sorusuna saniyeler içinde cevap verirsiniz.

## ✅ Tespit Checklist'i
- [ ] `git grep -niE '(api[_-]?key|secret|password)\s*[:=]'` çalıştırıldığında .env.example dışı sonuç veriyor mu?
- [ ] composer.lock eksik/GITIGNORE'da mı? (lock'suz build = belirsiz sürüm)
- [ ] CI'da audit + secret-scan adımları var mı?
- [ ] Güncel olmayan paket: `composer outdated` haftalık koşuluyor mu?

## 🛠️ Düzeltme Adımları
Sızıntı olduysa: secret'ı İPTAL et → yenile → geçmişi temizle (`filter-repo`) → neden geçtiğini kök-neden analiziyle kapat (pre-commit hook ekle).

## 🧪 Test Senaryosu
`scripts/audit.sh` lokal çalıştırılır: gitleaks yoksa regex fallback ile şüpheli desenleri raporlar (CI ile aynı mantık).

## 🚩 Bayrak
`MG{l0ckf1l3s-4nd-sc4nned-s3cr3ts}`

## ⚠️ Uyarı
Secret scanning yalnızca KENDİ repolarınızda çalıştırılır. Başka kurumların repo/host taranması bu eğitimin kapsamı dışıdır ve hukuka aykırıdır.

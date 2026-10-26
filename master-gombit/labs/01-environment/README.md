# Lab 01 — Ortam & Threat Modeling

**OWASP:** A05 (Security Misconfiguration) + süreç olarak A00 destekli
**Zorluk:** easy · **Puan:** 10
**Öğrenme hedefi:** Güvenli laboratuva ortamını izole kurmak, kapsam (scope) belirlemek ve STRIDE ile tehdit modellemek.

## 🧭 Kapsam ve Yasal Çerçeve
- Bu lab YALNIZCA kendi Docker/localhost ortamınızdaki sentetik uygulamayı inceler.
- İzinsiz hiçbir sistem taranamaz, test edilemez. Eğitimde bile "scope" yazılı onay gerektirir (şablon: `docs/THREAT_MODEL.md` içindeki Authorization Form).

## 🏗️ Ortam Kurulumu (savunmacı alıştırmalar)
1. `docker compose up --build` ile ayağa kaldırın; `http://127.0.0.1:8080/health.php` → `{"status":"ok"}` bekleyin.
2. Doğrulama görevleri:
   - [ ] `docker-compose.yml` içinde DB servisinin **port yayınlamadığını** bulun (neden? → saldırı yüzeyini azaltma).
   - [ ] Tüm port bind'lerinin `127.0.0.1` olduğunu doğrulayın.
   - [ ] `nginx/default.conf` içinde `/storage/` ve `/.env` erişiminin nasıl kapatıldığını okuyun.
   - [ ] `php/conf.d/security.ini` dosyasında `expose_php`, `display_errors`, `disable_functions` ayarlarını açıklayın.

## 🎯 Varlık Envanteri (Asset Inventory)
| Varlık | Gizlilik | Bütünlük | Kullanılabilirlik |
|---|---|---|---|
| users tablosu (parola hash'leri) | Yüksek | Yüksek | Orta |
| audit_logs | Orta | Çok yüksek (adli) | Orta |
| eğitim bayrakları | Düşük | Orta | Düşük |
| .env / APP_KEY | Yüksek | Yüksek | Orta |

## 🔐 Trust Boundaries (Güven Sınırları)
```
[Browser] --HTTP--> [Nginx] --FastCGI--> [PHP App] --PDO/TCP--> [MariaDB]
             ^boundary1        ^boundary2          ^boundary3
[User upload] --> [storage/ webroot dışı]  ^boundary4 (dosya içeriği güvenilmez)
```
Her sınırda sorulacak soru: "Bu veriyi doğruluyor muyum, encode ediyor muyum, yetkilendiriyor muyum?"

## 📝 STRIDE Tehdit Modeli Örneği (uygulama geneli)
| Harf | Tehdit | Bu lab'da örnek | Karşı önlem (kodda nerede?) |
|---|---|---|---|
| S | Spoofing | Başkasının hesabıyla login | Argon2id + throttling + lockout (`AuthService`) |
| T | Tampering | Not içeriğini bozma | Prepared statement + output encoding (`LabService`, `OutputEncoder`) |
| R | Repudiation | "Ben yapmadım" | Audit log, IP hash (`AuditLogger`) |
| I | Information disclosure | Hata sayfasında stack trace | Jenerik hata view'ı, debug yalnız lokal (`App::renderError`) |
| D | DoS | Login brute-force / ağır payload | RateLimiter, upload boyut limiti, max_execution_time |
| E | Elevation of privilege | Kayıtta kendini admin yapma | Rol client'tan ALINMAZ (`AuthService::register`) |

## ✅ Tespit Checklist'i
- [ ] Varsayılan parolalar değiştirildi mi?
- [ ] Gereksiz port/host yayınları kapalı mı?
- [ ] Hata sayfaları bilgi sızdırıyor mu? (`/olmayan-yol` deneyin)
- [ ] Response header'larında sunucu sürümü var mı? (`curl -sI`)

## 🚩 Bayrak
Bu README'nin kendisi zararsız bir eğitim bayrağı içermez; bayrak 02+ modüllerde analiz karşılığıdır. Lab 01 bayrağı kurulum kanıtıdır: health.php çıktısını raporlayın → eğitmen onayı ile puan.
*(Sistem bayrağı: bu modül için flag tanımlı değildir — tamamlama eğitmen onaylıdır.)*

## ⚠️ Yasal/Uyarı Kutusu
> Master Gombit savunma eğitimidir. Bu modüldeki hiçbir teknik adım üçüncü taraf sistemlere uygulanamaz.

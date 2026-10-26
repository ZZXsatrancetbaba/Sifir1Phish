# Lab 08 — Loglama, İzleme & Tespit

**OWASP:** A09 (Security Logging & Monitoring Failures) · **Zorluk:** easy · **Puan:** 10
**Öğrenme hedefi:** Denetim logu tasarımı, hassas veri redaksiyonu, tespit kuralları ve retention.

## ✅ Repodaki audit tasarımı (`AuditLogger`)
- Olaylar: `login_ok/login_fail/login_blocked/login_locked/logout/flag_submit/upload/admin_action`
- Alanlar: user_id, event_type, **ip_hash / ua_hash (peppered sha256)**, target, result, metadata_json
- Redaksiyon: password/token/csrf/flag/answer/secret anahtarları `[REDACTED]`
- Yazı hatası uygulamayı çökertmez (fail-open logging + error_log) — ama SIEM entegrasyonunda fail-alarm tercih edilir (not).

## 🕵️ Anomali tespiti fikirleri (savunma analizi)
| Sinyal | Kural örneği | Yanlış pozitif azaltma |
|---|---|---|
| Credential stuffing | Aynı ip_hash'ten çok farklı kullanıcıya login_fail | NAT ofis IP'lerini allow-listle |
| Bayrak brute-force | flag_submit failure oranı > eşik | Kullanıcı bazlı cooldown |
| Admin keşfi | admin.* 403 fırtınası | Rol değişimi olaylarıyla korelasyon |
| Gece toplu indirme | files download hızı z-score | Bilinen batch job etiketi |

## 🗄️ Retention & gizlilik
- `LOG_RETENTION_DAYS=30`: ham erişim logları kısa; audit (adli) daha uzun tutulabilir.
- Pepper rotasyonu planlanmalı (pepper değişirse eski hash'ler yeniden hesaplanamaz — tasarım kararı notu).

## ✅ Tespit Checklist'i
- [ ] Loglarda parola/API anahtarı/oturum token'ı basılıyor mu? (`grep -RnE 'error_log.*(pass|token)' app/`)
- [ ] Başarısız login loglanıyor mu? (yalnızca başarı yetmez)
- [ ] Zaman damgası UTC mi, saat dilimi tutarlı mı?
- [ ] Log silme/yazma yetkisi uygulama hesabında var mı? (OLMAMALI — append-only akış önerilir)

## 🧪 Test Senaryosu
`tests/Security/AuditRedactionTest.php`: metadata içindeki `password` alanı DB'ye `[REDACTED]` olarak düşer; ip_hash deterministik ama geri döndürülemez.

## 🚩 Bayrak
`MG{str1ict-structur3d-4udit-l0gs}`

## ⚠️ Uyarı
Bu lab izleme altyapısı kurmayı öğretir; saldırı trafiği simülasyonu ÜRETMEYİ değil. Gürültü testleri yalnızca kendi lokal container'ınızda, kendi sentetik hesabınızla yapılır.

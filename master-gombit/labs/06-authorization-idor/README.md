# Lab 06 — Yetkilendirme & IDOR Önleme

**OWASP:** A01 (Broken Access Control) · **Zorluk:** hard · **Puan:** 30
**Öğrenme hedefi:** RBAC, route-level ve object-level yetki ayrımını kavramak; IDOR'un yapısal sebebini anlamak ve sahiplik kontrolüyle kapatmak.

## 🧱 İki seviyeli kontrol (ikisi de zorunlu)
1. **Route-level:** `/admin/*` → `admin.audit` permission (Router etiketi + App middleware).
2. **Object-level:** "Bu kaynağa erişebilir *misin*?" — her kayıt erişiminde SQL içinde owner filtresi:
```php
'SELECT * FROM files WHERE id = ? AND user_id = ? LIMIT 1'   // getOwnFile()
```
Sadece route kontrolü IDOR'u durdurmaz: `student`, `file.download.own` iznine sahiptir; ama **başkasının** dosya ID'sini yazarsa ikinci katman gerekir.

## 🔍 IDOR'un yapısı
Dış referans (sıralı `files.id`) + eksik sahiplik kontrolü = yatay privilege ihlali. Azaltmalar:
- Owner filtreli sorgular (repodaki desen) ✔
- Rastgele/unversal ID (UUIDv7) — tahmin edilemezlik *yetki yerine geçmez*, yalnız gürültü azaltır
- Denetim: `submissions.user_id` gibi alanlarda client value ASLA kabul edilmez; session'dan alınır ✔

## ⬆️ Dikey privilege örnekleri ve kapatılması
| Hata | Kapanış (repoda) |
|---|---|
| Kayıt formunda `role=admin` göndermek | Rol sunucuda sabit: `register()` student atar |
| Admin panelini URL bilmek | Flag + permission + `isAdmin()` üçlü kontrol |
| `UPDATE users SET is_active=0 WHERE id=?` herkese uygulansın | `AND role_id <> 1` + kendi kendini koruma |

## 🛡️ Güvenli API endpoint tasarımı kuralları
- Her mutasyon: authn + authz + CSRF + validasyon + audit (beşi birden).
- Yanıt jeneriği: "bulunamadı" vs "senin değil" ayrımı yapma (varlık sızıntısı).
- Liste uçlarında scope: `WHERE user_id = :me` default; admin scope ayrı parametre + izin.

## ✅ Tespit Checklist'i
- [ ] `$_GET['id']` → doğrudan sorgu; owner filtresi YOK mu?
- [ ] Permission kontrolü controller'da unutulabilir mi? (Merkezi middleware daha güvenli — repodaki tercih)
- [ ] Nesne kimlikleri sıralı ve tahmin edilebilir mi?
- [ ] Rol bilgisi client'tan geliyor mu? (session snapshot kullanılmalı)
- [ ] 404/403 mesajları varlık var/yok bilgisini veriyor mu?

## 🧪 Test Senaryosu
`tests/Feature/IdorPreventionTest.php`: user B, user A'nın notunu listeyemez (SQL owner filtresi boş döner); dosya indirme 404 jenerik döner.

## 🚩 Bayrak
`MG{0bj3ct-l3v3l-ch3cks-st0p-1d0r}`

## ⚠️ Uyarı
IDOR eğitimi, başka sistemlerde ID dolaştırma rehberi DEĞİLDİR. Laboratuvarda iki sentetik hesapla (student/instructor) kendi lokal uygulamanızda savunma testesi yapın: B hesabıyla A'nın kaynak ID'sini çağırıp 404/jenerik yanıtı DOĞRULAYIN.

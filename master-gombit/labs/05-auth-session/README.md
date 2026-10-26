# Lab 05 — Kimlik Doğrulama & Oturum Yönetimi

**OWASP:** A07 (Identification & Authentication Failures) · **Zorluk:** medium · **Puan:** 20
**Öğrenme hedefi:** Güvenli login akışı, parola saklama, throttling/lockout, oturum fixation önleme, güvenli remember-me ve şifre sıfırlama deseni; MFA mimarisi.

## 🔑 Parola saklama
- Varsayılan: **Argon2id** (`memory_cost=64MB, time=4, threads=3`), fallback bcrypt cost 12.
- Seed'deki bcrypt hash'ler ilk başarılı login'de otomatik Argon2id'ye **yükseltilir** (`PasswordHasher::needsRehash`).
- Parola uzunluğu 10–190; bcrypt 72-byte kesme tuzağına karşı not README'de.

## 🚪 Login akışı (AuthService — repoda gerçek)
1. Rate limit: kullanıcı bucket + IP bucket (credential stuffing yavaşlatma).
2. Prepared statement ile kullanıcı yükleme.
3. Kullanıcı yoksa **dummy verify** → zamanlama farkıyla kullanıcı enumeration azaltma.
4. Tek jenerik mesaj: "Kullanıcı adı veya parola hatalı." (hangi alanın yanlış olduğu SIZMAZ).
5. 5 hatada `locked_until = NOW()+15dk`; başarılı login sayaçları sıfırlar.
6. Başarı → `session_regenerate_id(true)` + rol snapshot + audit log.

## 🍪 Oturum sertleştirme (SessionGuard + security.ini)
| Ayar | Değer | Neden |
|---|---|---|
| use_strict_mode | 1 | Sabitlenmiş SID reddedilir |
| cookie httponly/samesite | 1 / Lax | JS çalınamaz, cross-site taşımaz |
| sid_length/bits | 48/6 | ≥288 bit entropy |
| idle timeout | 30 dk | Boşta oturum kapanır |
| absolute TTL | 8 saat | Uzun yaşam riski sınırlı |

## ☑️ Remember-me güvenli tasarım (teori + desen)
Selector/Verifier çifti: `selector` DB'de, `verifier = sha256(random)` yalnız istemcide; doğrulama sabit zamanlı; her kullanımda rotate (çalıntı token bir sonraki kullanımda tespit edilir). Bu repoda kasıtlı olarak *uygulanmadı* — eğitim sorusu: neden düz `user+hash` cookie güvenlidir/değildir?

## 🔁 Şifre sıfırlama (güvenli versiyon)
- Token: `random_bytes(32)`, DB'de yalnızca **hash** saklanır, TTL ≤ 15 dk, tek kullanım.
- Zamanlama sabitleme: kullanıcı varsa da yoksa da aynı yanıt.
- Reset sonrası: tüm oturumlar iptal + e-posta bildirimi (audit).

## 📱 MFA placeholder
`users.mfa_secret_enc` sütunu ve `FEATURE_MFA_PLACEHOLDER` bayrağı mimari yer tutucusudur. Gerçek TOTP entegrasyonu için RFC 6238 tabanlı kütüphane + **recovery code** akışı eklenmelidir (düşünce: MFA'yı atlatan "SMS kodu tekrar gönder" butonu tuzaktır).

## ✅ Tespit Checklist'i
- [ ] Parola GET/query/log/cookie'de görünüyor mu? (repoda hayır — koruyun)
- [ ] `md5/sha1(password)` kullanımı? (`grep -RnE 'md5|sha1' app/`)
- [ ] Login成功后 regenerate var mı? Yoksa fixation mümkün.
- [ ] Başarısız giriş messages farklı detayda mı? (enumeration)
- [ ] Lockout sadece kullanıcı adını kalıcı kilitleyebilir mi? (DoS'a açık — süreli kilit şart)

## 🧪 Test Senaryosu
`tests/Security/AuthFlowTest.php`: zayıf parola reddi; throttle eşiği; kilit süresi; rehash yükseltmesi.

## 🚩 Bayrak
`MG{r3g3n3r4t3-s3ss10n-0n-l0gin}`

## ⚠️ Uyarı
Bu modül parola kırma, sözlük/hashing aracı veya kimlik avı senaryosu içermez. Eğitim hesapları tamamen sentetiktir; parolalar README'de verilmiştir çünkü bunlar **gerçek gizlilik taşımayan laboratuva girdileridir**.

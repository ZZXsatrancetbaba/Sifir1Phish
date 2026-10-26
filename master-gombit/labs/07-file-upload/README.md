# Lab 07 — Güvenli Dosya Yükleme

**OWASP:** A04 (Insecure Design) + A05 karışık · **Zorluk:** medium · **Puan:** 20
**Öğrenme hedefi:** Upload zincirinin her halkasını (client→sunucu→disk→servis) savunmacı tasarlama.

## 🧠 Tehdit yüzeyi (kavramsal)
Dosya yüklemesinde risk: çalıştırılabilir içerik, path manipülasyonu, depolama yeri ifşası, MIME taklidi, kaynak tüketimi. Savunma katmanları (hepsi birlikte):

## ✅ Repodaki güvenli implementasyon (`LabService::storeUpload`)
1. **Boyut limiti** (env 2 MB) — hem PHP ini hem uygulama kontrolü.
2. **Uzantı WHITELIST**: png,jpg,gif,txt,pdf,md (blacklist asla yetmez).
3. **İçerik/magic-byte kontrolü**: `finfo` ile gerçek MIME; uzantı-MIME uyuşması zorunlu (spoofing'e karşı).
4. **Rastgele dosya adı**: `random_bytes(16)` hex; orijinal ad yalnızca sanitize edilip KAYITTA tutulur, yolda kullanılmaz → path traversal imkânsız.
5. **Webroot DIŞI depolama**: `storage/uploads/` nginx'de `deny all`; indirme yalnız kontrollü controller üzerinden.
6. **Checksum SHA-256**: bütünlük kanıtı.
7. **Controlled delivery** (`downloadFile`): `Content-Disposition: attachment`, `nosniff`, owner-check SQL, inline render yok.
8. **Virus-scan hook placeholder**: gerçek projede ClamAV/vendor SDK buraya çağrılır (zararsız yer tutucu).

## ❌ Anti-pattern (pasif eğitim kodu)
```php
move_uploaded_file($tmp, '/var/www/html/uploads/' . $_FILES['f']['name']); // orijinal ad + webroot içi
// -> çakışma/traversal riski + .php yüklenirse remote execution açığı
```

## ✅ Tespit Checklist'i
- [ ] Whitelist mi blacklist mi? (blacklist = bulgu)
- [ ] `$_FILES['name']`dosya yoluna giriyor mu? (girmemeli)
- [ ] Upload dizini webroot içinde mi / script çalıştırabiliyor mu?
- [ ] Sunulan dosyalarda `Content-Type` kullanıcı verisinden mi geliyor? (bizde DB'deki doğrulanmış MIME)
- [ ] Boyut limiti SADECE client-side `<input>`'ta mı? (sunucu limiti olmalı)

## 🛠️ Düzeltme Diff
```diff
- $dest = '/var/www/html/uploads/' . $_FILES['doc']['name'];
+ $rand = bin2hex(random_bytes(16));
+ $ext  = in_array($ext, $whitelist, true) ? $ext : throw_invalid();
+ $dest = '/var/app/private/uploads/' . $rand . '.' . $ext;
```

## 🧪 Test Senaryosu
`tests/Feature/UploadValidationTest.php`: `.php` uzantılı PNG taklidi reddedilir; 3 MB reddedilir; whitelist + magic byte uyumlusu kabul; stored_path random.

## 🚩 Bayrak
`MG{wh1t3l1st-r4nd0m-n4m3s-0utside-w3br00t}`

## ⚠️ Uyarı
Bu lab "nasıl webshell yüklenir" öğretMEZ. Yükleyeceğiniz tek şey zararsız eğitim dosyasıdır (ör. kendi notunuz .md). Webshell üretmek bu projenin kapsamı dışı ve hukuka aykırıdır.

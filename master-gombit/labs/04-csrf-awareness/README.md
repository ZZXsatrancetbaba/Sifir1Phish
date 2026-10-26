# Lab 04 — CSRF Koruması

**OWASP:** A01 (Broken Access Control – CSRF alt türü) · **Zorluk:** medium · **Puan:** 20
**Öğrenme hedefi:** CSRF'nin nedenini (kimlik taşınması: tarayıcının cookie'yi otomatik eklemesi) anlamak; synchronizer token + SameSite kombinasyonunu kurmak.

## 🧠 Tehdit modeli (soyut)
Bir oturum çerezleri otomatik gönderildiği için, üçüncü bir sayfadaki form POST'u, kullanıcı "farkında olmadan" durum değiştirebilir. Savunma katmanları:
1. **Synchronizer Token** (durum içeren her istekte zorunlu)
2. **SameSite=Lax/Strict** (tarayıcı seviyesi azaltım)
3. **State-changing işlemler GET ile YAPILMAZ** (idempotent GET)
4. **HttpOnly cookie** (token çalınsa bile JS okuyamaz — ikinci katman)

## ✅ Repodaki güvenli akış
- `CsrfTokenManager`: `random_bytes(32)` → oturumda tutulur; formlarda hidden `csrf_token`; AJAX'ta `X-CSRF-Token` header.
- `App::run()`: `GET/HEAD` dışı HER istek `validateOrFail()` görmek zorunda — controller'ın unutması mümkün değil (middleware seviyesi).
- Karşılaştırma `hash_equals` (sabit zamanlı).
- Login sonrası `SessionGuard::loginUser()` → `session_regenerate_id(true)` + token rotasyona hazır.

## ❌ Anti-pattern (pasif eğitim kodu)
```php
// Durum değiştirme GET'te: prefetch/spider/clickbait tetikleyebilir
$app->get('/user/delete', fn() => deleteUser());     // YANLIŞ method semantiği
// Token'sız POST:
$('#save').click(() => fetch('/notes', {method:'POST'}));  // CSRF başlıksız
```

## 🔁 Token rotasyonu stratejisi
- Her login/logout → yeni token.
- Kritik işlem (rol değişikliği) sonrası rotate önerilir; stateless JWT mimarisinde double-submit cookie + Origin kontrolü gerekir (not).

## 📡 AJAX CSRF Header örneği (public/assets/js/app.js içinde gerçek)
```js
fetch('/labs/7/notes', {
  method: 'POST',
  credentials: 'same-origin',
  headers: {'Content-Type':'application/json','X-CSRF-Token': token},
  body: JSON.stringify({content: note})
});
```

## ✅ Tespit Checklist'i
- [ ] Tüm POST/PUT/PATCH/DELETE uçları token doğruluyor mu? (tek merkezi middleware var mı?)
- [ ] Token `random_bytes` ile mi üretiliyor? (`mt_rand`/`time()` tuzak)
- [ ] Karşılaştırma `==` yerine `hash_equals` mi?
- [ ] SameSite ve HttpOnly set mi? (`curl -sI .../login | grep -i set-cookie`)
- [ ] Login öncesi formlarda (login/register) de token var mı? (session-fixation + CSRF birlikte)

## 🛠️ Düzeltme Diff
```diff
- if ($_POST['csrf_token'] == $_SESSION['csrf']) { ... }   // timing + null uyuşması riski
+ if (hash_equals((string)($_SESSION['csrf'] ?? ''), (string)($_POST['csrf_token'] ?? ''))) { ... }
```

## 🧪 Test Senaryosu
`tests/Security/CsrfTokenManagerTest.php`: tokensuz POST reddedilir; yanlış token reddedilir; doğru token geçer; rotate sonrası eski token geçmez.

## 🚩 Bayrak
`MG{s4m3s1t3-4nd-synchr0niz3r-t0k3n}`

## ⚠️ Uyarı
Bu lab, başka uygulamalara karşı CSRF formu/poC ÜRETMEK İÇİN KULLANILAMAZ. Eğitimde yalnızca kendi lokal laboratuva uygulamanız üzerinde gözlem yapabilirsiniz; o da defense-test amaçlıdır (ör. token'sız isteğin 419 dönüp dönmediğini curl ile kontrol).

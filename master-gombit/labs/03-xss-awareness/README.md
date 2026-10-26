# Lab 03 — XSS & Output Encoding

**OWASP:** A03 (Injection – XSS alt türü) · **Zorluk:** medium · **Puan:** 20
**Öğrenme hedefi:** Stored/Reflected/DOM XSS farklarını anlamak, bağlama duyarlı (context-aware) encode yapmayı öğrenmek, CSP politikası kurmak.

## 🧠 Türler (savunma bakışı)
| Tür | Nerede doğar | Temel karşı önlem |
|---|---|---|
| Reflected | Sorgu parametresi → HTML yanıt | Çıktıda HTML encode |
| Stored | Kullanıcı verisi DB → başkalarının sayfası | Girdide beyaz liste + çıktıda encode |
| DOM | JS içinde `innerHTML` vb. sink'ler | `textContent`, güvenli API, CSP |

XSS'in kökü: **veri, kod muamelesi görür.** Çözüm: veriyi koddan ayıran bağlamsal dönüşüm.

## ❌ Anti-pattern (pasif eğitim kodu)
```php
echo "<p>Mesaj: " . $_GET['msg'] . "</p>";        // ham çıktı: bağlam HTML body
div.innerHTML = userInput;                          // DOM sink
<a href="<?= $url ?>">                              // $url javascript: olabilir → proto kontrolü şart
```

## ✅ Güvenli implementasyon (repoda gerçekten var)
```php
use MG\Security\OutputEncoder as E;
echo '<p>Mesaj: ' . E::escHtml($msg) . '</p>';      // htmlspecialchars ENT_QUOTES|SUBSTITUTE, UTF-8
<div class="note"><?= E::escHtml($note) ?></div>    // view'ların tamamı bu deseni kullanır
<script>window.CONFIG = <?= E::escJs($config) ?>;</script>  // JSON_HEX_* -> script breakout engeli
```
URL bağlamı: `E::safeLink()` yalnız `https://` kabul eder (`javascript:`/`data:` reddedilir).

## 🧱 CSP (defense-in-depth)
`app/Security/SecurityHeaders.php` + `nginx/default.conf`:
```
default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:;
frame-ancestors 'none'; base-uri 'self'; form-action 'self'; object-src 'none'
```
- Inline script YOK (harici `app.js`), `'unsafe-inline'` style'da bile kaldırıldı.
- `form-action 'self'`: forma dışarı veri gönderme engellenir.

## Sanitization vs Encoding
- **Encoding**: her veriyi metne çevirir — güvenli varsayılan.
- **Sanitization (HTML paste istersen)**: allow-list tag/attribute gerekir; karmaşıktır; bu lab bilinçli olarak sanitiser KULLANMIYOR, encoding öğütüyor.

## ✅ Tespit Checklist'i
- [ ] View'larda `<?= $x ?>` ham basım var mı? (`grep -RnE '<\?=\s*\$[a-zA-Z]' app/Views/` → hepsi E:: ile sarılı olmalı)
- [ ] `echo`/`print` içinde `$_GET/$_POST` var mı?
- [ ] JS tarafında `innerHTML`/`document.write`/`eval` var mı? (repoda YOK — koruyun)
- [ ] Event handler attribute'ları (`onclick="<?= ... ?>"`) encode ediliyor mu?
- [ ] CSP header gerçekten gönderiliyor mu? (`curl -sI http://127.0.0.1:8080/ | grep -i content-security`)

## 🛠️ Düzeltme Diff Örneği
```diff
- <td><?= $log['target'] ?></td>
+ <td><?= \MG\Security\OutputEncoder::escHtml((string)$log['target']) ?></td>
```

## 🧪 Test Senaryosu
`tests/Security/OutputEncoderTest.php`: `<script>` girdisi `&lt;script&gt;` olur; alıntı karakterleri attribute'u kıramaz; escJs `</script>` dizisini kırpar.

## 🚩 Bayrak
Checklist'i raporlayıp aşağıdaki zararsız anahtarı forma yazın:
`MG{c0nt3xt-4w4r3-3nc0d1ng}`

## ⚠️ Uyarı
Bu modül payload koleksiyonu, encoded attack string listesi veya "şu etiketi şu siteye bas" senaryosu İÇERMEZ. Başka bir sitenin formuna kendi notunuzu yazmanız dahi izinsiz test kapsamına girer — yasaktır.

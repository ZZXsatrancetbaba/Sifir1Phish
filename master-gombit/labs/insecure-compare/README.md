# Master Gombit — Ek Lab: Zafiyetli vs Güvenli Karşılaştırma Demosu

> ⚠️ **Bu modül VARSAYILAN OLARAK KAPALIDIR.** Yalnızca `FEATURE_INSECURE_DEMOS=true`
> ve istek localhost/Docker iç bind üzerinden geliyorsa açılır. Açıldığında her
> sayfada büyük uyarı banner'ı görünür. İçerik **pasif kod incelemesidir**; çalışan
> zafiyetli uç nokta, exploit arayüzü veya payload üretici BULUNMAZ.

## Neden bu lab var?
Geliştiriciler zafiyeti en iyi, *güvenli ve güvensiz kodu yan yana okuyarak* öğrenir.
Bu modülde güvensiz desenler yalnızca yorum/kod listing olarak gösterilir ve hemen
altında repoda gerçekten kullanılan güvenli karşılığı yer alır.

## Karşılaştırma setleri

### 1) SQL: concatenation vs prepared statement
```php
// ❌ PASİF ÖRNEK (çalıştırılmaz): veri SQL sözdizimine karışır
$sql = "SELECT id FROM users WHERE username = '" . $_POST['username'] . "'";

// ✅ REPODA ÇALIŞAN (User::findByUsername): parametre ayrı kanaldan gider
$stmt = $pdo->prepare('SELECT ... WHERE username = ? LIMIT 1');
$stmt->execute([$username]);
```
Tespit: `" . $`, `query("...$var")` desenleri → PR'da bloklayan grep kuralı CI'da mevcut.

### 2) XSS: ham echo vs context encoding
```php
// ❌ PASİF ÖRNEK
echo "<div>" . $_GET['q'] . "</div>";

// ✅ REPODA ÇALIŞAN (tüm view'lar)
echo '<div>' . OutputEncoder::escHtml($q) . '</div>';
```

### 3) Parola: md5 vs Argon2id
```php
// ❌ PASİF ÖRNEK: hızlı hash + salt yok -> offline crack'e açık tasarım
$stored = md5($password);

// ✅ REPODA ÇALIŞAN
$stored = password_hash($password, PASSWORD_ARGON2ID, [...]);
```

### 4) Oturum: fixation'a açık login vs regenerate
```php
// ❌ PASİF ÖRNEK: SID sabit kalır -> önceden sabitlenen SID geçerli kalabilir
$_SESSION['user'] = $id;

// ✅ REPODA ÇALIŞAN
session_regenerate_id(true); $_SESSION['user_id'] = $id;
```

## Eğitim görevleri
1. Her pasif örnekte hatayı ve kök nedeni bir cümleyle yaz (rapor şablonu Lab 10).
2. Repoda güvenli karşılığın hangi dosyada olduğunu bul; satır numarasıyla referansla.
3. CI'daki grep tespit kurallarından birini kendin için genişlet (ör. `md5(` yasağı).

## Bu modülde NELER YOK (ve neden?)
- Çalışan zafiyetli endpoint: öğrencinin dış sistemlere karşı yanlışlıkla kullanmasını engeller.
- Payload/exploit listesi: savunma eğitimi için gerekli değil; suiistimal riski yüksek.
- Otomatik tarayıcı/brute-force aracı: yasal sınır ihlali potansiyeli.

**Bayrak:** Bu modül sistem bayrağı taşımaz; tamamlama eğitmen onaylı raporladır.

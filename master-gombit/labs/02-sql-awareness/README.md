# Lab 02 — SQL Farkındalığı & Prepared Statements

**OWASP:** A03 (Injection) · **Zorluk:** medium · **Puan:** 20
**Öğrenme hedefi:** SQL enjeksiyonun *neden* oluştuğunu kavramak, PDO prepared statements ile düzeltmek, tespit ve hardening yapmak.

## 🧠 Kavram (saldırı rehberi DEĞİL, savunma mantığı)
SQL enjeksiyon, kullanıcı verisinin sorgu *sözdiziminin parçası* muamele görmesidir. Veri/sorgu ayrımı bozulduğunda uygulamanın niyeti değişebilir. Biz burada saldırı string'i üretmiyoruz; **kod inceleyerek zafiyeti buluyoruz**.

## ❌ Anti-pattern (yalnızca PASİF eğitim kodu — çalıştırılmaz, yorumdur)
```php
// KOD İNCELEME ALIŞTIRMASI: Bu satırda hangi iki hata var? (cevabı rapora yazın)
$sql = "SELECT * FROM users WHERE username = '" . $_POST['username'] . "'";
$result = $pdo->query($sql);
// HATA 1: Veri, SQL metnine birleştirildi (string concatenation).
// HATA 2: Ham $_POST kullanıldı (doğrulama yok).
```

## ✅ Güvenli implementasyon (bu repoda gerçekten kullanılan kod)
```php
$stmt = $pdo->prepare('SELECT id, username FROM users WHERE username = ? LIMIT 1');
$stmt->execute([$username]);           // veri HER ZAMAN parametre olarak gider
```
- `App::boot` içinde `PDO::ATTR_EMULATE_PREPARES => false` → gerçek prepared statement.
- `ERRMODE_EXCEPTION` + jenerik hata sayfası → DB hatası kullanıcıya sızmaz.

## 🔁 Dinamik parçalar (tablo/kolon/sıra) prepare EDİLEMEZ → WHITELIST
```php
$allowed = ['created_at','score','username'];            // whitelist
$col = in_array($_GET['sort'], $allowed, true) ? $_GET['sort'] : 'created_at';
$sql = "SELECT ... ORDER BY {$col} DESC";                // yalnızca whitelist'ten gelen kimlik
```
Bkz. `InputValidator::oneOf()` ve `AuditLog::recent()` (LIMIT int cast+clamp örneği).

## 🛡️ En az yetki (DB seviyesi)
Uygulama hesabı `mg_user`: yalnızca `SELECT,INSERT,UPDATE,DELETE`. DDL/DROP/GRANT yok — şema dosyasında açıklanmıştır. Sızıntı durumunda hasar sınırlanır.

## ✅ Tespit Checklist'i (kod incelemesi)
- [ ] `" . $` veya `"{$var}"` ile SQL birleştirme var mı? (`grep -RnE 'query\(.*(\\$_|\. )' app/`)
- [ ] Her `query()` çağrısı sabit SQL mi? Değilse `prepare` nerede?
- [ ] ORDER BY / tablo adı dinamikse whitelist var mı?
- [ ] `ERRMODE_SILENT/WARNING` kullanılmış mı? (sessiz başarısızlık riski)
- [ ] Hata mesajları kullanıcıya gösteriliyor mu? (`PDOException` mesajı = tablo adı sızıntısı)

## 🛠️ Düzeltme Adımları (fix-it challenge)
Aşağıdaki eksik fonksiyonu güvenli hale getirin ve test edin:
```php
function searchLabs(PDO $pdo, string $term): array {
    // GÖREV: LIKE aramasını PARAMETRELİ yapın; term'i InputValidator::answer benzeri doğrulayın;
    // wildcard karakterlerini ESCAPE edin (\, %, _).
    // Çözüm iskeleti:
    // $stmt = $pdo->prepare('SELECT slug,title FROM lab_modules WHERE title LIKE :t ESCAPE "\\"');
    // $stmt->execute([':t' => '%' . addcslashes($safeTerm, '%_\\') . '%']);
}
```

## 🧪 Test Senaryosu (tests/Security/SqlSafetyTest.php çalıştırın)
- Doğru parametreli sorgu sonuc döndürür.
- Tuhaf girdiler (tek tırnak, unicode) sorguyu BOZMAZ — yalnızca veri muamelesi görür.

## 🚩 Bayrak
Raportaki analiz sorularının cevabı derlenmiş zararsız anahtar kelimelerden üretilir:
`MG{pr3p4r3d-st4t3m3nts-4lw4ys}`
Bayrağı bu lab'in formundan gönderin. Açıklama: bayrak, yukarıdaki checklist'in 5 maddesini de raporunuza yazdıktan SONRA anlamlıdır.

## 📄 Rapor Şablonu (Lab 10'a bağlanır)
`Bulgu: Concatenated SQL in X dosyası | Etki: Yetkisiz veri erişimi | OWASP A03 | Öneri: prepare+param | Düzenleme: diff ekle`

## ⚠️ Uyarı
Bu modül hiçbir sömürü payload'ı, dump tekniği veya harici araç komutu içermez ve içeremez. Amaç: kodu doğru yazmak ve yanlış yazılmış kodu *gözle* tanımak.

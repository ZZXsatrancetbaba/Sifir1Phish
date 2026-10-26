<?php
/**
 * Merkezi hata görünümü. Değişkenler: $status, $safeMsg, $safePath, $debug.
 * GÜVENLİK: stack trace/yol/SQL detayı YALNIZCA debug+lokal modda; her durumda
 * çıktı htmlspecialchars ile encode edilir (error page XSS önleme).
 */
use MG\Security\OutputEncoder as E;
$titles = [
    401 => 'Giriş gerekli',
    403 => 'Erişim yasak',
    404 => 'Bulunamadı',
    419 => 'Oturum zaman aşımı',
    422 => 'Geçersiz girdi',
    429 => 'Çok fazla deneme',
    500 => 'Sunucu hatası',
];
$t = $titles[$status] ?? 'Hata';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= E::escHtml($t) ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container error-page">
  <h1><?= (int)$status ?> — <?= E::escHtml($t) ?></h1>
  <p><?= E::escHtml($safeMsg) ?></p>
  <?php if (!empty($debug)): ?>
    <details><summary>Detay (yalnız lokal debug)</summary>
      <p>Path: <code><?= E::escHtml($safePath) ?></code></p>
    </details>
  <?php endif; ?>
  <p><a href="/">← Ana sayfaya dön</a></p>
</main>
</body>
</html>

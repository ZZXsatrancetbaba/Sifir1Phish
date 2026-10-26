<?php
/** @var string $title */
use MG\Security\OutputEncoder as E;
?>
<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= E::escHtml($title ?? 'Master Gombit') ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="topbar" role="banner">
  <a class="brand" href="/">🛡️ Master&nbsp;Gombit</a>
  <nav aria-label="Ana menü">
    <a href="/">Lab'ler</a>
    <a href="/dashboard">Panel</a>
    <?php if (!empty($user)): ?>
      <form method="post" action="/logout" class="inline">
        <input type="hidden" name="csrf_token" value="<?= E::escAttr($csrf ?? '') ?>">
        <button type="submit" class="linkbtn">Çıkış (<?= E::escHtml($user->username) ?>)</button>
      </form>
    <?php else: ?>
      <a href="/login">Giriş</a>
    <?php endif; ?>
  </nav>
</header>

<?php if (!empty($insecureBanner)): ?>
<div class="banner-warn" role="alert">
  ⚠️ <strong>EĞİTİM MODU AÇIK:</strong> Zafiyetli-karşılaştırma demosu etkin.
  Bu mod YALNIZCA lokal Docker ortamında, savunma eğitimi içindir. Dış sistemlere
  karşı kullanılamaz; içerikte saldırı aracı bulunmaz.
</div>
<?php endif; ?>

<?php if (!empty($_SESSION['_mg_flash'])): ?>
  <div class="flash"><?= E::escHtml((string)$_SESSION['_mg_flash']) ?></div>
  <?php unset($_SESSION['_mg_flash']); ?>
<?php endif; ?>

<main class="container" role="main">

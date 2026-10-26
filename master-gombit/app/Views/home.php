<?php
/** Ana sayfa: lab kataloğu. Tüm dinamik çıktılar E::escHtml ile encode edilir. */
use MG\Security\OutputEncoder as E;
require __DIR__ . '/layouts/header.php';
?>
<section class="hero">
  <h1><?= E::escHtml($appName ?? 'Master Gombit') ?></h1>
  <p>Secure PHP + MySQL Education Lab — güvenli kodlama, zafiyet farkındalığı, tespit ve düzeltme eğitimi.</p>
  <p class="muted">Yalnızca sentetik veri • yalnızca localhost/Docker • savunma odaklı</p>
</section>

<section aria-label="Lab listesi">
  <h2>Lab Modülleri</h2>
  <ul class="lablist">
  <?php foreach ($labs as $lab): ?>
    <li class="labcard">
      <a href="/labs/<?= E::escAttr(rawurlencode((string)$lab['slug'])) ?>">
        <strong><?= E::escHtml((string)$lab['title']) ?></strong>
      </a>
      <p><?= E::escHtml(mb_substr((string)$lab['description'], 0, 160)) ?>…</p>
      <span class="tag">OWASP <?= E::escHtml((string)$lab['owasp_category']) ?></span>
      <span class="tag diff-<?= E::escAttr((string)$lab['difficulty']) ?>"><?= E::escHtml((string)$lab['difficulty']) ?></span>
    </li>
  <?php endforeach; ?>
  </ul>
</section>
<?php require __DIR__ . '/layouts/footer.php'; ?>

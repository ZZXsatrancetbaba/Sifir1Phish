<?php
use MG\Security\OutputEncoder as E;
require __DIR__ . '/../layouts/header.php';
?>
<article class="lab-detail">
  <h1><?= E::escHtml((string)$lab['title']) ?></h1>
  <p><span class="tag">OWASP <?= E::escHtml((string)$lab['owasp_category']) ?></span>
     <span class="tag"><?= E::escHtml((string)$lab['difficulty']) ?></span></p>

  <div class="guide markdown-safe">
    <?php /* Rehber içeriği repodaki statik Markdown dosyasıdır (kullanıcı verisi DEĞİL).
             Yine de encode edilip <pre> içinde gösterilir: stored-XSS girdisi olmaz. */ ?>
    <pre><?= E::escHtml($guide) ?></pre>
  </div>

  <?php if ($user !== null): ?>
  <section aria-label="Bayrak gönderimi">
    <h2>Bayrak Gönder</h2>
    <form method="post" action="/labs/<?= (int)$lab['id'] ?>/submit">
      <input type="hidden" name="csrf_token" value="<?= E::escAttr($csrf) ?>">
      <label for="answer">Eğitim bayrağı (MG{...})</label>
      <input id="answer" name="answer" required maxlength="128" autocomplete="off">
      <button type="submit">Gönder</button>
    </form>
  </section>

  <section aria-label="Not ekle">
    <h2>Notum</h2>
    <form method="post" action="/labs/<?= (int)$lab['id'] ?>/notes">
      <input type="hidden" name="csrf_token" value="<?= E::escAttr($csrf) ?>">
      <label for="content">Kişisel not (sunucu tarafında encode edilerek saklanır/gösterilir)</label>
      <textarea id="content" name="content" rows="4" maxlength="10000"></textarea>
      <button type="submit">Kaydet</button>
    </form>
    <ul>
      <?php foreach ($notes as $n): ?>
        <li><time><?= E::escHtml((string)$n['created_at']) ?></time>: <?= E::escHtml((string)$n['content_safe']) ?></li>
      <?php endforeach; ?>
    </ul>
  </section>

  <section aria-label="Dosya yükleme lab'ı">
    <h2>Dosya Yükle (Lab 07)</h2>
    <form method="post" enctype="multipart/form-data" action="/labs/<?= (int)$lab['id'] ?>/upload">
      <input type="hidden" name="csrf_token" value="<?= E::escAttr($csrf) ?>">
      <label for="document">İzinli türler: png, jpg, gif, txt, pdf, md — maks 2 MB</label>
      <input id="document" name="document" type="file" accept=".png,.jpg,.gif,.txt,.pdf,.md">
      <button type="submit">Yükle</button>
    </form>
  </section>
  <?php else: ?>
    <p class="muted">Bayrak göndermek ve not almak için <a href="/login">giriş yapın</a>.</p>
  <?php endif; ?>
</article>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

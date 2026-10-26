<?php
use MG\Security\OutputEncoder as E;
require __DIR__ . '/layouts/header.php';
?>
<section class="dashboard">
  <div class="profile-card">
    <h1>Merhaba, <?= E::escHtml($user->username) ?></h1>
    <p>Rol: <strong><?= E::escHtml($user->roleName) ?></strong></p>
    <p>Toplam puan: <strong><?= (int)$progress['total_score'] ?></strong></p>
  </div>

  <div class="badges" aria-label="Kazanılan rozetler">
    <h2>Rozetler</h2>
    <?php if ($progress['badges'] === []): ?><p class="muted">Henüz rozet yok — lab'leri çöz!</p><?php endif; ?>
    <?php foreach ($progress['badges'] as $b): ?>
      <span class="badge">🏅 <?= E::escHtml((string)$b['label']) ?></span>
    <?php endforeach; ?>
  </div>

  <div aria-label="OWASP ilerleme">
    <h2>OWASP Kategori İlerlemesi</h2>
    <table>
      <thead><tr><th>Kategori</th><th>Puan</th></tr></thead>
      <tbody>
      <?php foreach ($progress['by_owasp'] as $cat => $score): ?>
        <tr><td><?= E::escHtml((string)$cat) ?></td><td><?= (int)$score ?></td></tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php if ($progress['weak_areas'] !== []): ?>
      <p>Önerilen çalışma alanları:
      <?php foreach ($progress['weak_areas'] as $w): ?>
        <span class="tag warn"><?= E::escHtml((string)$w) ?></span>
      <?php endforeach; ?></p>
    <?php endif; ?>
  </div>

  <div>
    <h2>Rapor</h2>
    <a class="btn" href="/report.json">JSON Raporu</a>
    <a class="btn" href="/report.csv">CSV İndir</a>
  </div>

  <div>
    <h2>Güvenli Kod Checklist'leri</h2>
    <p>docs/SECURE_CODING_CHECKLIST.md — PR öncesi 15 maddelik kontrol listesi.</p>
  </div>
</section>
<?php require __DIR__ . '/layouts/footer.php'; ?>

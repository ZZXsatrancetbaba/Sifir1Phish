<?php
use MG\Security\OutputEncoder as E;
require __DIR__ . '/../layouts/header.php';
?>
<section class="admin">
  <h1>Yönetici Paneli</h1>
  <p class="muted">Bu panel FEATURE_ADMIN_PANEL=true olduğunda erişilebilir (çift kapı: flag + admin rolü).</p>
  <h2>Son Denetim Kayıtları</h2>
  <table>
    <thead><tr><th>Zaman</th><th>Olay</th><th>Hedef</th><th>Sonuç</th><th>IP(hash)</th></tr></thead>
    <tbody>
    <?php foreach ($logs as $l): ?>
      <tr>
        <td><?= E::escHtml((string)$l['created_at']) ?></td>
        <td><?= E::escHtml((string)$l['event_type']) ?></td>
        <td><?= E::escHtml((string)$l['target']) ?></td>
        <td><?= E::escHtml((string)$l['result']) ?></td>
        <td><code><?= E::escHtml(substr((string)$l['ip_hash'], 0, 12)) ?>…</code></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</section>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

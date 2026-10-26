<?php
use MG\Security\OutputEncoder as E;
require __DIR__ . '/../layouts/header.php';
?>
<section class="auth-card">
  <h1>Giriş Yap</h1>
  <p class="muted">Eğitim hesapları (sentetik): student / instructor / admin — parolalar README'de.</p>
  <!-- CSRF: synchronizer token hidden alan; POST-only; autocomplete off parola dışında -->
  <form method="post" action="/login" novalidate>
    <input type="hidden" name="csrf_token" value="<?= E::escAttr($csrf) ?>">
    <label for="username">Kullanıcı adı</label>
    <input id="username" name="username" required maxlength="64" pattern="[a-z0-9._-]{3,64}" autocomplete="username">
    <label for="password">Parola</label>
    <input id="password" name="password" type="password" required maxlength="190" autocomplete="current-password">
    <button type="submit">Giriş</button>
  </form>

  <h2>Kayıt (yalnızca öğrenci rolü verilir)</h2>
  <form method="post" action="/register" novalidate>
    <input type="hidden" name="csrf_token" value="<?= E::escAttr($csrf) ?>">
    <label for="r-username">Kullanıcı adı</label>
    <input id="r-username" name="username" required maxlength="64" autocomplete="username">
    <label for="r-email">E-posta (isteğe bağlı, sentetik)</label>
    <input id="r-email" name="email" type="email" maxlength="254" autocomplete="email">
    <label for="r-password">Parola (min 10 kar., 3 sınıf)</label>
    <input id="r-password" name="password" type="password" required maxlength="190" autocomplete="new-password">
    <button type="submit">Kayıt ol</button>
  </form>
</section>
<?php require __DIR__ . '/../layouts/footer.php'; ?>

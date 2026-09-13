<?php require __DIR__ . '/_header.php'; ?>
<div class="card">
  <h1>Configuration initiale</h1>
  <p class="muted">Premier lancement : créez le nom du site et le compte administrateur.</p>
  <form method="post" action="index.php?a=setup">
    <label for="site_name">Nom du site</label>
    <input type="text" id="site_name" name="site_name" value="Parto-preno" required>

    <label for="login">Identifiant administrateur</label>
    <input type="text" id="login" name="login" required>

    <label for="password">Mot de passe administrateur</label>
    <input type="password" id="password" name="password" required>

    <p><button type="submit">Configurer</button></p>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>

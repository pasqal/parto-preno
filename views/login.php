<?php require __DIR__ . '/_header.php'; ?>
<div class="card">
  <h1>Connexion</h1>
  <form method="post" action="index.php?a=login">
    <label for="login">Identifiant</label>
    <input type="text" id="login" name="login" required autofocus>

    <label for="password">Mot de passe</label>
    <input type="password" id="password" name="password" required>

    <p><button type="submit">Se connecter</button>
       <a class="btn secondary" href="index.php?a=register">Créer un compte</a></p>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>

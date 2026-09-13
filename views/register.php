<?php require __DIR__ . '/_header.php'; ?>
<div class="card">
  <h1>Créer un compte</h1>
  <p class="muted">Compte utilisateur (lecteur) : vous pourrez vous inscrire aux listes avec un pseudo.</p>
  <form method="post" action="index.php?a=register">
    <label for="login">Identifiant</label>
    <input type="text" id="login" name="login" required>

    <label for="pseudo">Pseudo affiché (optionnel)</label>
    <input type="text" id="pseudo" name="pseudo" placeholder="Pseudo public">

    <label for="password">Mot de passe</label>
    <input type="password" id="password" name="password" required>

    <p><button type="submit">Créer le compte</button>
       <a class="btn secondary" href="index.php?a=login">J'ai déjà un compte</a></p>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>

<?php
// Partial : en-tête + flash. Variables disponibles : $settings, $user
$siteName = $settings['site_name'] ?? 'Parto-preno';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($siteName) ?></title>
  <link rel="stylesheet" href="assets/style.css">
  <link rel="icon" type="image/svg+xml" href="assets/logo.svg">
</head>
<body>
<header class="top">
  <div class="brand"><a href="index.php"><img src="assets/logo.svg" alt="" class="brand-logo"><?= htmlspecialchars($siteName) ?></a></div>
  <nav>
    <?php if (Auth::check()): ?>
      <?php if (Auth::isAdmin()): ?>
        <a href="index.php?admin=settings">Paramètres</a>
        <a href="index.php?admin=users">Utilisateurs</a>
      <?php endif; ?>
      <?php if (Auth::isMod()): ?>
        <a href="index.php?a=import">Importer</a>
      <?php endif; ?>
      <a href="index.php?a=logout">Déconnexion</a>
    <?php else: ?>
      <a href="index.php?a=login">Connexion</a>
      <a href="index.php?a=register">Créer un compte</a>
    <?php endif; ?>
  </nav>
</header>
<main>
<?php if ($flash_error): ?>
  <div class="flash error"><?= htmlspecialchars($flash_error) ?></div>
<?php endif; ?>
<?php if ($flash_ok): ?>
  <div class="flash ok"><?= htmlspecialchars($flash_ok) ?></div>
<?php endif; ?>

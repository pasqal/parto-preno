<?php require __DIR__ . '/_header.php'; ?>
<h1>Paramètres du site</h1>
<div class="card">
  <form method="post" action="index.php?admin=settings">
    <label for="site_name">Nom du site</label>
    <input type="text" id="site_name" name="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? 'Parto-preno') ?>" required>
    <p><button type="submit">Enregistrer</button></p>
  </form>
</div>
<?php require __DIR__ . '/_footer.php'; ?>

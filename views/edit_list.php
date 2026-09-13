<?php require __DIR__ . '/_header.php'; ?>
<h1>Modifier la liste</h1>
<form method="post" action="index.php?a=edit_list">
  <input type="hidden" name="id" value="<?= htmlspecialchars($list['id']) ?>">
  <div class="card">
    <label for="title">Titre</label>
    <input type="text" id="title" name="title" value="<?= htmlspecialchars($list['title']) ?>" required>

    <label>Lignes d'inscription (une par champ ; laissez vide pour supprimer)</label>
    <div id="slots">
      <?php foreach ($list['slots'] ?? [] as $i => $slot): ?>
        <input type="text" name="slots[]" value="<?= htmlspecialchars($slot) ?>" style="margin-bottom:6px">
      <?php endforeach; ?>
    </div>
    <p><button type="button" class="btn secondary" onclick="addSlot()">+ Ajouter une ligne</button></p>

    <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
    <input type="password" id="password" name="password">
    <label><input type="checkbox" name="remove_password" value="1"> Retirer la protection</label>

    <label><input type="checkbox" name="one_per_user" value="1" <?= !empty($list['one_per_user']) ? 'checked' : '' ?>> Une seule inscription par personne par liste</label>

    <p>
      <button type="submit">Enregistrer</button>
      <a class="btn secondary" href="index.php?a=list&id=<?= urlencode($list['id']) ?>">Annuler</a>
    </p>
  </div>
</form>

<form method="post" action="index.php?a=delete_list" onsubmit="return confirm('Effacer définitivement cette liste ?')">
  <input type="hidden" name="id" value="<?= htmlspecialchars($list['id']) ?>">
  <p><button type="submit" class="btn danger">Effacer la liste</button></p>
</form>

<script>
function addSlot() {
  var d = document.getElementById('slots');
  var i = document.createElement('input');
  i.type = 'text';
  i.name = 'slots[]';
  i.style.marginBottom = '6px';
  d.appendChild(i);
  i.focus();
}
</script>
<?php require __DIR__ . '/_footer.php'; ?>

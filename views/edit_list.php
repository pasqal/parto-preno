<?php require __DIR__ . '/_header.php'; ?>
<h1>Modifier la liste</h1>
<form method="post" action="index.php?a=edit_list">
  <input type="hidden" name="id" value="<?= htmlspecialchars($list['id']) ?>">
  <div class="card">
    <label for="title">Nom de la liste</label>
    <input type="text" id="title" name="title" value="<?= htmlspecialchars($list['title']) ?>" required>
    <label for="description">Description</label>
    <textarea id="description" name="description" style="min-height:70px"><?= htmlspecialchars($list['description'] ?? '') ?></textarea>
    <label>Points d'inscription sans groupe (un par champ ; vide = supprimé)</label>
    <div id="slots">
      <?php $ungrouped = $list['slots'] ?? [];
      $groupedSlots = [];
      foreach (($list['groups'] ?? []) as $g) {
          $slots = $g['slots'] ?? ($g ?? []);
          foreach ($slots as $s) $groupedSlots[] = $s;
      }
      foreach ($ungrouped as $i => $slot): if (in_array($slot, $groupedSlots, true)) continue; ?>
        <input type="text" name="slots[]" value="<?= htmlspecialchars($slot) ?>" style="margin-bottom:6px">
      <?php endforeach; ?>
    </div>
    <p><button type="button" class="btn secondary" onclick="addSlot()">+ Ajouter un point</button></p>
    <label>Groupes (chaque groupe : liste de points d'inscription)</label>
    <div id="groups">
      <?php foreach (($list['groups'] ?? []) as $gi => $g): ?>
        <div class="group-edit" data-group>
          <?php if (!isset($g['title'])): $g = ['title' => '', 'slots' => $g]; endif; ?>
          <input type="text" class="group-title" name="group_titles[<?= $gi ?>]" placeholder="Titre du groupe" value="<?= htmlspecialchars($g['title']) ?>" style="margin-bottom:6px">
          <?php foreach ($g['slots'] as $s): ?>
            <input type="text" name="groups[<?= $gi ?>][]" value="<?= htmlspecialchars($s) ?>" style="margin-bottom:6px">
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <p><button type="button" class="btn secondary" onclick="addGroup()">+ Ajouter un groupe</button></p>
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
  i.type = 'text'; i.name = 'slots[]'; i.style.marginBottom = '6px';
  d.appendChild(i); i.focus();
}
function addGroup() {
  var d = document.getElementById('groups');
  var idx = d.children.length;
  var g = document.createElement('div');
  g.className = 'group-edit';
  g.setAttribute('data-group', '');
  var t = document.createElement('input');
  t.type = 'text'; t.className = 'group-title'; t.name = 'group_titles[' + idx + ']';
  t.placeholder = 'Titre du groupe';
  t.style.marginBottom = '6px';
  g.appendChild(t);
  var s = document.createElement('input');
  s.type = 'text'; s.name = 'groups[' + idx + '][]';
  s.placeholder = 'Point d\'inscription';
  s.style.marginBottom = '6px';
  g.appendChild(s);
  d.appendChild(g); t.focus();
}
document.getElementById('groups').addEventListener('input', function (e) {
  var g = e.target.closest('[data-group]');
  if (!g) return;
  var idx = Array.prototype.indexOf.call(g.parentNode.children, g);
  if (e.target.classList.contains('group-title')) {
    e.target.name = 'group_titles[' + idx + ']';
    g.querySelectorAll('input:not(.group-title)').forEach(function (inp) {
      inp.name = 'groups[' + idx + '][]';
    });
  } else if (e.target.name === '' || e.target.name === '[]' || !e.target.name.startsWith('groups[')) {
    e.target.name = 'groups[' + idx + '][]';
  }
  // Mettre à jour le name du titre aussi
  var titleInput = g.querySelector('.group-title');
  if (titleInput) {
    titleInput.name = 'group_titles[' + idx + ']';
  }
});
</script>
<?php require __DIR__ . '/_footer.php'; ?>

<?php require __DIR__ . '/_header.php'; ?>
<h1>Listes d'inscription</h1>
<p class="muted">Inscrivez-vous aux lignes d'une liste, consultez les autres inscrits.</p>

<?php if (empty($lists)): ?>
  <div class="card">
    <p class="muted">Aucune liste pour le moment.</p>
    <?php if (Auth::isMod()): ?>
      <p><a class="btn" href="index.php?a=import">Importer une liste</a></p>
    <?php endif; ?>
  </div>
<?php else: ?>
  <div class="lists-grid">
  <?php foreach ($lists as $l):
      $count = 0;
      foreach (($l['signups'] ?? []) as $s) { $count++; }
      $locked = !empty($l['password']);
  ?>
    <div class="card">
      <h2 style="margin-top:0">
        <a href="index.php?a=list&id=<?= urlencode($l['id']) ?>"><?= htmlspecialchars($l['title']) ?></a>
        <?php if ($locked): ?> <span class="tag lock">protégée</span> <?php endif; ?>
      </h2>
      <div class="list-meta">
        <?= count($l['slots'] ?? []) ?> ligne(s) · <?= $count ?> inscription(s)
        · créée le <?= date('d/m/Y', strtotime($l['created'] ?? 'now')) ?>
      </div>
      <p>
        <a class="btn" href="index.php?a=list&id=<?= urlencode($l['id']) ?>">Ouvrir</a>
        <?php if (Auth::isMod() && (Auth::isAdmin() || ($l['owner_id'] ?? '') === ($user['id'] ?? ''))): ?>
          <a class="btn secondary" href="index.php?a=edit_list&id=<?= urlencode($l['id']) ?>">Modifier</a>
        <?php endif; ?>
      </p>
    </div>
  <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>

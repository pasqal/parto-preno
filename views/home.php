<?php require __DIR__ . '/_header.php'; ?>

<h1>Listes à participer</h1>
<p class="muted">Toutes les listes disponibles. Cliquez sur une liste pour voir ses points d'inscription et vous inscrire.</p>

<?php if (empty($lists)): ?>
  <div class="card">
    <p class="muted">Aucune liste pour le moment.</p>
    <?php if (Auth::isMod()): ?>
      <p><a class="btn" href="index.php?a=import">Importer une liste</a></p>
    <?php endif; ?>
  </div>
<?php else: ?>
  <?php if (!Auth::check()): ?>
    <div class="card">
      <p class="muted">Connectez-vous pour vous inscrire. Vous pouvez consulter les listes publiques et leurs inscrits sans compte.</p>
      <p>
        <a class="btn" href="index.php?a=login">Connexion</a>
        <a class="btn secondary" href="index.php?a=register">Créer un compte</a>
      </p>
    </div>
  <?php endif; ?>

  <div class="card">
    <table>
      <thead>
        <tr>
          <th>Liste</th>
          <th>Inscriptions</th>
          <th>Créée le</th>
          <th>Accès</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($lists as $l):
            $locked = !empty($l['password']);
            $canEdit = Auth::isMod() && (Auth::isAdmin() || ($l['owner_id'] ?? '') === ($user['id'] ?? ''));
        ?>
          <tr>
            <td>
              <a href="index.php?a=list&id=<?= urlencode($l['id']) ?>"><strong><?= htmlspecialchars($l['title']) ?></strong></a>
              <?php if (!empty($l['description'])): ?>
                <div class="muted small"><?= htmlspecialchars($l['description']) ?></div>
              <?php endif; ?>
            </td>
            <td><?= count($l['signups'] ?? []) ?></td>
            <td><?= date('d/m/Y', strtotime($l['created'] ?? 'now')) ?></td>
            <td>
              <?php if ($locked): ?>
                <span class="tag lock">protégée</span>
              <?php else: ?>
                <span class="tag">publique</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn secondary" href="index.php?a=list&id=<?= urlencode($l['id']) ?>">Ouvrir</a>
              <?php if ($canEdit): ?>
                <a href="index.php?a=edit_list&id=<?= urlencode($l['id']) ?>">Modifier</a>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/_footer.php'; ?>

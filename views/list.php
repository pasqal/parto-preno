<?php require __DIR__ . '/_header.php'; ?>
<h1><?= htmlspecialchars($list['title']) ?></h1>
<div class="list-meta">
  <?php if (!empty($list['password'])): ?> <span class="tag lock">protégée</span> · <?php endif; ?>
  <?= count($list['slots'] ?? []) ?> ligne(s) · <?= count($list['signups'] ?? []) ?> inscription(s)
</div>

<?php if (Auth::isMod()): ?>
  <p class="small">
    <a href="index.php?a=export&id=<?= urlencode($list['id']) ?>&format=csv">Exporter CSV</a> ·
    <a href="index.php?a=export&id=<?= urlencode($list['id']) ?>&format=md">Exporter Markdown</a>
    <?php if (Auth::isAdmin() || ($list['owner_id'] ?? '') === ($user['id'] ?? '')): ?>
      · <a href="index.php?a=edit_list&id=<?= urlencode($list['id']) ?>">Modifier</a>
    <?php endif; ?>
  </p>
<?php endif; ?>

<?php if (!$unlocked): ?>
  <div class="card">
    <h2 style="margin-top:0">Liste protégée par mot de passe</h2>
    <form method="post" action="index.php?a=list&id=<?= urlencode($list['id']) ?>">
      <input type="hidden" name="do" value="unlock">
      <label for="password">Mot de passe de la liste</label>
      <input type="password" id="password" name="password" required>
      <p><button type="submit">Déverrouiller</button></p>
    </form>
  </div>
<?php else: ?>

  <?php if (!Auth::check()): ?>
    <div class="card">
      <p class="muted">Connectez-vous pour vous inscrire. Vous pouvez toujours consulter les inscrits ci-dessous.</p>
      <p><a class="btn" href="index.php?a=login">Connexion</a></p>
    </div>
  <?php endif; ?>

  <div class="card">
    <table>
      <thead>
        <tr><th style="width:34%">Ligne</th><th>Inscrits</th><th style="width:130px"></th></tr>
      </thead>
      <tbody>
      <?php foreach ($list['slots'] ?? [] as $slotName):
          $people = $bySlot[$slotName] ?? [];
          $myHere = false;
          foreach ($people as $p) {
              if (($p['user_id'] ?? '') === ($user['id'] ?? null)) { $myHere = true; break; }
          }
      ?>
        <tr>
          <td><strong><?= htmlspecialchars($slotName) ?></strong></td>
          <td class="who">
            <?php if (empty($people)): ?>
              <span class="muted">—</span>
            <?php else: foreach ($people as $p): ?>
              <div class="<?= ($p['user_id'] ?? '') === ($user['id'] ?? null) ? 'me' : '' ?>">
                <?= htmlspecialchars(App::displayName($p)) ?>
              </div>
            <?php endforeach; endif; ?>
          </td>
          <td>
            <?php if (Auth::check()): ?>
              <?php if ($myHere): ?>
                <form method="post" action="index.php?a=signup" style="display:inline">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($list['id']) ?>">
                  <input type="hidden" name="slot" value="<?= htmlspecialchars($slotName) ?>">
                  <input type="hidden" name="do" value="remove">
                  <button type="submit" class="btn secondary">Se retirer</button>
                </form>
              <?php else: ?>
                <form method="post" action="index.php?a=signup" style="display:inline">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($list['id']) ?>">
                  <input type="hidden" name="slot" value="<?= htmlspecialchars($slotName) ?>">
                  <input type="text" name="pseudo" placeholder="Pseudo" class="small" style="width:90px; padding:5px 7px; margin-bottom:4px" value="<?= htmlspecialchars($user['pseudo'] ?? '') ?>">
                  <button type="submit" class="btn">S'inscrire</button>
                </form>
              <?php endif; ?>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>

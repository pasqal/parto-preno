<?php require __DIR__ . '/_header.php'; ?>
<h1>Listes d'inscription</h1>
<p class="muted">Cliquez sur un point d'une liste pour vous inscrire ou vous désinscrire.</p>
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
      <p class="muted">Connectez-vous pour vous inscrire. Vous pouvez consulter les inscrits ci-dessous.</p>
      <p><a class="btn" href="index.php?a=login">Connexion</a></p>
    </div>
  <?php endif; ?>
  <?php foreach ($lists as $l):
      $locked = !empty($l['password']);
      $bySlot = [];
      foreach (($l['signups'] ?? []) as $s) { $bySlot[$s['slot']][] = $s; }
      $canEdit = Auth::isMod() && (Auth::isAdmin() || ($l['owner_id'] ?? '') === ($user['id'] ?? ''));
  ?>
    <div class="card list-block" data-list="<?= urlencode($l['id']) ?>" data-locked="<?= $locked ? '1' : '0' ?>">
      <h2 style="margin-top:0">
        <?= htmlspecialchars($l['title']) ?>
        <?php if ($locked): ?> <span class="tag lock">protégée</span> <?php endif; ?>
      </h2>
      <?php if (!empty($l['description'])): ?>
        <p class="muted"><?= htmlspecialchars($l['description']) ?></p>
      <?php endif; ?>
      <div class="list-meta">
        <?= count($l['slots'] ?? []) ?> point(s) · <?= count($l['signups'] ?? []) ?> inscription(s)
        · créée le <?= date('d/m/Y', strtotime($l['created'] ?? 'now')) ?>
        <?php if ($canEdit): ?>
          · <a href="index.php?a=edit_list&id=<?= urlencode($l['id']) ?>">Modifier</a>
        <?php endif; ?>
      </div>
      <?php if ($locked): ?>
        <details class="unlock-box">
          <summary>Déverrouiller la liste (mot de passe requis)</summary>
          <form method="post" action="index.php?a=list&id=<?= urlencode($l['id']) ?>" class="row">
            <input type="hidden" name="do" value="unlock">
            <input type="hidden" name="back" value="home">
            <input type="password" name="password" placeholder="Mot de passe" required style="max-width:220px">
            <button type="submit">Déverrouiller</button>
          </form>
        </details>
      <?php else: ?>
        <?php
        $groups = $l['groups'] ?? [];
        $ungrouped = [];
        if (empty($groups)) {
            $ungrouped = $l['slots'] ?? [];
        } else {
            $groupedSlots = [];
            foreach ($groups as $g) {
                if (isset($g['title'])) {
                    foreach ($g['slots'] as $s) $groupedSlots[] = $s;
                } else {
                    foreach ($g as $s) $groupedSlots[] = $s;
                }
            }
            foreach (($l['slots'] ?? []) as $s) {
                if (!in_array($s, $groupedSlots, true)) $ungrouped[] = $s;
            }
        }
        ?>
        <?php if (!empty($groups)): ?>
          <?php foreach ($groups as $g):
              $gTitle = $g['title'] ?? '';
              $gSlots = $g['slots'] ?? $g;
          ?>
            <h3 class="group-title"><?= htmlspecialchars($gTitle) ?></h3>
            <div class="slots-row">
              <?php foreach ($gSlots as $slotName): ?>
                <?= App::renderSlot($l, $slotName, $bySlot[$slotName] ?? [], $user) ?>
              <?php endforeach; ?>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
        <?php if (!empty($ungrouped)): ?>
          <?php if (!empty($groups)): ?><h3 class="group-title">Autres points</h3><?php endif; ?>
          <div class="slots-row">
            <?php foreach ($ungrouped as $slotName): ?>
              <?= App::renderSlot($l, $slotName, $bySlot[$slotName] ?? [], $user) ?>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>

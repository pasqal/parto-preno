<?php require __DIR__ . '/_header.php'; ?>
<h1><?= htmlspecialchars($list['title']) ?></h1>
<?php if (!empty($list['description'])): ?>
  <p class="muted"><?= htmlspecialchars($list['description']) ?></p>
<?php endif; ?>
<div class="list-meta">
  <?php if (!empty($list['password'])): ?> <span class="tag lock">protégée</span> · <?php endif; ?>
  <?= count($list['slots'] ?? []) ?> point(s) · <?= count($list['signups'] ?? []) ?> inscription(s)
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
    <div class="slots-container">
      <?php
      $groups = $list['groups'] ?? [];
      $ungrouped = [];
      if (empty($groups)) {
          $ungrouped = $list['slots'] ?? [];
      } else {
          $groupedSlots = [];
          foreach ($groups as $g) {
              if (isset($g['title'])) {
                  foreach ($g['slots'] as $s) $groupedSlots[] = $s;
              } else {
                  foreach ($g as $s) $groupedSlots[] = $s;
              }
          }
          foreach (($list['slots'] ?? []) as $s) {
              if (!in_array($s, $groupedSlots, true)) $ungrouped[] = $s;
          }
      }
      
      // Display groups
      foreach ($groups as $g):
          $gTitle = $g['title'] ?? '';
          $gSlots = $g['slots'] ?? $g;
      ?>
        <?php if (!empty($gTitle)): ?>
          <div class="chapter-header">
            <h3 class="chapter-title"><?= htmlspecialchars($gTitle) ?></h3>
          </div>
        <?php endif; ?>
        <?php foreach ($gSlots as $slotName): ?>
          <?php
          $slotNameEsc = htmlspecialchars($slotName);
          $people = $bySlot[$slotName] ?? [];
          $count = count($people);
          $myHere = false;
          foreach ($people as $p) {
              if (($p['user_id'] ?? '') === ($user['id'] ?? null)) { $myHere = true; break; }
          }
          
          // Build attendees HTML
          $attendeesHtml = '';
          if (empty($people)) {
              $attendeesHtml = '<span class="bubble empty">—</span>';
          } else {
              foreach ($people as $p) {
                  $displayName = htmlspecialchars(App::displayName($p));
                  $color = abs(crc32(App::displayName($p))) % 6;
                  $isMe = ($p['user_id'] ?? '') === ($user['id'] ?? null);
                  $attendeesHtml .= '<span class="bubble p' . $color . ($isMe ? ' me' : '') . '" title="' . $displayName . '">' . $displayName . '</span>';
              }
          }
          ?>
          <div class="slot-row">
            <div class="slot-name-cell">
              <?= $slotNameEsc ?>
              <span class="slot-count">(<?= $count ?>)</span>
            </div>
            <div class="slot-people-cell">
              <?= $attendeesHtml ?>
            </div>
            <?php if (Auth::check()): ?>
              <div class="slot-action-cell">
                <form method="post" action="index.php?a=signup" class="slot-form-compact">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($list['id']) ?>">
                  <input type="hidden" name="slot" value="<?= $slotNameEsc ?>">
                  <input type="hidden" name="do" value="<?= $myHere ? 'remove' : 'add' ?>">
                  <button type="submit" class="btn-compact <?= $myHere ? 'btn-remove' : 'btn-join' ?>" 
                          title="<?= $myHere ? 'Désinscrire' : 'S\'inscrire' ?>">
                    <?= $myHere ? '×' : '+' ?>
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endforeach; ?>
      
      <?php if (!empty($ungrouped)): ?>
        <?php if (!empty($groups)): ?>
          <div class="chapter-header">
            <h3 class="chapter-title">Autres points</h3>
          </div>
        <?php endif; ?>
        <?php foreach ($ungrouped as $slotName): ?>
          <?php
          $slotNameEsc = htmlspecialchars($slotName);
          $people = $bySlot[$slotName] ?? [];
          $count = count($people);
          $myHere = false;
          foreach ($people as $p) {
              if (($p['user_id'] ?? '') === ($user['id'] ?? null)) { $myHere = true; break; }
          }
          
          $attendeesHtml = '';
          if (empty($people)) {
              $attendeesHtml = '<span class="bubble empty">—</span>';
          } else {
              foreach ($people as $p) {
                  $displayName = htmlspecialchars(App::displayName($p));
                  $color = abs(crc32(App::displayName($p))) % 6;
                  $isMe = ($p['user_id'] ?? '') === ($user['id'] ?? null);
                  $attendeesHtml .= '<span class="bubble p' . $color . ($isMe ? ' me' : '') . '" title="' . $displayName . '">' . $displayName . '</span>';
              }
          }
          ?>
          <div class="slot-row">
            <div class="slot-name-cell">
              <?= $slotNameEsc ?>
              <span class="slot-count">(<?= $count ?>)</span>
            </div>
            <div class="slot-people-cell">
              <?= $attendeesHtml ?>
            </div>
            <?php if (Auth::check()): ?>
              <div class="slot-action-cell">
                <form method="post" action="index.php?a=signup" class="slot-form-compact">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($list['id']) ?>">
                  <input type="hidden" name="slot" value="<?= $slotNameEsc ?>">
                  <input type="hidden" name="do" value="<?= $myHere ? 'remove' : 'add' ?>">
                  <button type="submit" class="btn-compact <?= $myHere ? 'btn-remove' : 'btn-join' ?>" 
                          title="<?= $myHere ? 'Désinscrire' : 'S\'inscrire' ?>">
                    <?= $myHere ? '×' : '+' ?>
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>

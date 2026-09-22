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
    <table class="list-table">
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
      
      function renderBubbles($slotName, $bySlot, $user) {
          $people = $bySlot[$slotName] ?? [];
          if (empty($people)) {
              return '<span class="bubble empty">—</span>';
          }
          $html = '';
          foreach ($people as $p) {
              $displayName = htmlspecialchars(App::displayName($p));
              $color = abs(crc32(App::displayName($p))) % 6;
              $isMe = ($p['user_id'] ?? '') === ($user['id'] ?? null);
              $html .= '<span class="bubble p' . $color . ($isMe ? ' me' : '') . '" title="' . $displayName . '">' . $displayName . '</span>';
          }
          return $html;
      }
      
      function renderSlotRow($slotName, $list, $bySlot, $user) {
          $slotNameEsc = htmlspecialchars($slotName);
          $people = $bySlot[$slotName] ?? [];
          $myHere = false;
          foreach ($people as $p) {
              if (($p['user_id'] ?? '') === ($user['id'] ?? null)) { $myHere = true; break; }
          }
          
          if (Auth::check()):
              $do = $myHere ? 'remove' : 'add';
              $title = $myHere ? 'Cliquez pour vous désinscrire' : 'Cliquez pour vous inscrire';
              $classes = 'slot-cell ' . ($myHere ? 'mine' : '');
              $html = '<td class="' . $classes . '">' .
                  '<form method="post" action="index.php?a=signup" class="slot-form-inline">' .
                  '<input type="hidden" name="id" value="' . htmlspecialchars($list['id']) . '">' .
                  '<input type="hidden" name="slot" value="' . $slotNameEsc . '">' .
                  '<input type="hidden" name="do" value="' . $do . '">' .
                  '<button type="submit" class="slot-link" title="' . htmlspecialchars($title) . '">' . $slotNameEsc . '</button>' .
                  '</form>' .
                  '</td>' .
                  '<td class="people-cell">' . renderBubbles($slotName, $bySlot, $user) . '</td>';
          else:
              $html = '<td class="slot-cell">' . $slotNameEsc . '</td>' .
                     '<td class="people-cell">' . renderBubbles($slotName, $bySlot, $user) . '</td>';
          endif;
          return $html;
      }
      
      // Affiche les groupes
      foreach ($groups as $g):
          $gTitle = $g['title'] ?? '';
          $gSlots = $g['slots'] ?? $g;
      ?>
        <?php if (!empty($gTitle)): ?>
          <tr class="chapter-row">
            <td colspan="2" class="chapter-title"><?= htmlspecialchars($gTitle) ?></td>
          </tr>
        <?php endif; ?>
        <?php foreach ($gSlots as $slotName): ?>
          <tr class="slot-row">
            <?= renderSlotRow($slotName, $list, $bySlot, $user) ?>
          </tr>
        <?php endforeach; ?>
      <?php endforeach; ?>
      
      <?php if (!empty($ungrouped)): ?>
        <?php if (!empty($groups)): ?>
          <tr class="chapter-row">
            <td colspan="2" class="chapter-title">Autres points</td>
          </tr>
        <?php endif; ?>
        <?php foreach ($ungrouped as $slotName): ?>
          <tr class="slot-row">
            <?= renderSlotRow($slotName, $list, $bySlot, $user) ?>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </table>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>

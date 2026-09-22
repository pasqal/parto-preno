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
    
    // Helper to get initials
    function getInitials($text) {
        $words = explode(' ', $text);
        $initials = '';
        foreach ($words as $word) {
            if (trim($word)) {
                $initials .= strtoupper(substr(trim($word), 0, 1));
            }
        }
        return substr($initials, 0, 2);
    }
    
    // Helper to render a slot row like the prototype
    function renderSlotRow($slotName, $list, $bySlot, $user) {
        $slotNameEsc = htmlspecialchars($slotName);
        $people = $bySlot[$slotName] ?? [];
        $count = count($people);
        $myHere = false;
        foreach ($people as $p) {
            if (($p['user_id'] ?? '') === ($user['id'] ?? null)) { $myHere = true; break; }
        }
        
        $initials = getInitials($slotName);
        
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
        
        ob_start();
        ?>
        <div class="slot-row" role="listitem">
          <div class="slot-left">
            <div class="slot-badge"><?= $initials ?></div>
            <div class="slot-meta">
              <div class="slot-title"><?= $slotNameEsc ?></div>
              <div class="slot-sub"><?= $count ?> inscrit<?= $count > 1 ? 's' : '' ?></div>
            </div>
          </div>
          <div class="slot-attendees"><?= $attendeesHtml ?></div>
          <?php if (Auth::check()): ?>
            <form method="post" action="index.php?a=signup" class="slot-toggle-form">
              <input type="hidden" name="id" value="<?= htmlspecialchars($list['id']) ?>">
              <input type="hidden" name="slot" value="<?= $slotNameEsc ?>">
              <input type="hidden" name="do" value="<?= $myHere ? 'remove' : 'add' ?>">
              <button type="submit" class="slot-toggle <?= $myHere ? 'leave' : 'join' ?>" 
                      title="<?= $myHere ? 'Cliquez pour vous désinscrire' : 'Cliquez pour vous inscrire' ?>">
                <?= $myHere ? 'Désinscrire' : "S'inscrire" ?>
              </button>
            </form>
          <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    // Display groups
    foreach ($groups as $g):
        $gTitle = $g['title'] ?? '';
        $gSlots = $g['slots'] ?? $g;
    ?>
      <?php if (!empty($gTitle)): ?>
        <h3 class="group-title chapter-title"><?= htmlspecialchars($gTitle) ?></h3>
      <?php endif; ?>
      <?php foreach ($gSlots as $slotName): ?>
        <?= renderSlotRow($slotName, $list, $bySlot, $user) ?>
      <?php endforeach; ?>
    <?php endforeach; ?>
    
    <?php if (!empty($ungrouped)): ?>
      <?php if (!empty($groups)): ?>
        <h3 class="group-title chapter-title">Autres points</h3>
      <?php endif; ?>
      <?php foreach ($ungrouped as $slotName): ?>
        <?= renderSlotRow($slotName, $list, $bySlot, $user) ?>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>
<?php require __DIR__ . '/_footer.php'; ?>

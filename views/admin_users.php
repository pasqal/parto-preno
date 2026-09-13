<?php require __DIR__ . '/_header.php'; ?>
<h1>Utilisateurs</h1>

<div class="card">
  <h2 style="margin-top:0">Créer un utilisateur</h2>
  <form method="post" action="index.php?admin=user_create">
    <div class="row">
      <div style="flex:1;min-width:140px">
        <label for="n_login">Identifiant</label>
        <input type="text" id="n_login" name="login" required>
      </div>
      <div style="flex:1;min-width:140px">
        <label for="n_pseudo">Pseudo (optionnel)</label>
        <input type="text" id="n_pseudo" name="pseudo">
      </div>
      <div style="flex:1;min-width:140px">
        <label for="n_password">Mot de passe</label>
        <input type="password" id="n_password" name="password" required>
      </div>
      <div style="flex:1;min-width:120px">
        <label for="n_role">Rôle</label>
        <select id="n_role" name="role">
          <option value="user">Utilisateur</option>
          <option value="mod">Modificateur</option>
          <option value="admin">Administrateur</option>
        </select>
      </div>
    </div>
    <p><button type="submit">Créer</button></p>
  </form>
</div>

<h2>Comptes existants</h2>
<?php foreach ($users as $u): ?>
  <div class="card">
    <form method="post" action="index.php?admin=user_save">
      <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>">
      <div class="row" style="align-items:flex-end">
        <div style="flex:2;min-width:150px">
          <strong><?= htmlspecialchars($u['login']) ?></strong>
          <div class="small">Créé le <?= date('d/m/Y', strtotime($u['created'] ?? 'now')) ?></div>
        </div>
        <div style="flex:2;min-width:140px">
          <label>Pseudo</label>
          <input type="text" name="pseudo" value="<?= htmlspecialchars($u['pseudo'] ?? '') ?>">
        </div>
        <div style="flex:1;min-width:130px">
          <label>Rôle</label>
          <select name="role">
            <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>Utilisateur</option>
            <option value="mod" <?= $u['role'] === 'mod' ? 'selected' : '' ?>>Modificateur</option>
            <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>Administrateur</option>
          </select>
        </div>
        <div style="flex:1;min-width:130px">
          <label>Nouveau mot de passe</label>
          <input type="password" name="new_password" placeholder="laisser vide">
        </div>
        <div>
          <button type="submit" class="btn">Enregistrer</button>
        </div>
      </div>
    </form>
    <form method="post" action="index.php?admin=user_delete" onsubmit="return confirm('Effacer cet utilisateur ?')" style="margin-top:10px">
      <input type="hidden" name="id" value="<?= htmlspecialchars($u['id']) ?>">
      <button type="submit" class="btn danger" style="font-size:0.8rem">Effacer ce compte</button>
    </form>
  </div>
<?php endforeach; ?>
<?php require __DIR__ . '/_footer.php'; ?>

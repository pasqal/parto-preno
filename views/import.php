<?php require __DIR__ . '/_header.php'; ?>
<h1>Importer des listes</h1>
<p class="muted">Importez un fichier Markdown. Les titres <code>#</code> deviennent les titres des tableaux, les listes ordonnées <code>1.</code> deviennent les lignes d'inscription.</p>

<div class="card">
  <form method="post" action="index.php?a=import" enctype="multipart/form-data">
    <label for="file">Fichier Markdown (.md, .txt)</label>
    <input type="file" id="file" name="file" accept=".md,.txt,text/markdown,text/plain">

    <label for="markdown">…ou collez le Markdown directement</label>
    <textarea id="markdown" name="markdown" placeholder="# Sortie vélo&#10;&#10;1. Pilote&#10;2. Mécanicien&#10;3. Ravitaillement&#10;&#10;# Repas&#10;&#10;1. Apéritif&#10;2. Plat&#10;3. Dessert"></textarea>

    <label for="password">Mot de passe de la liste (optionnel)</label>
    <input type="password" id="password" name="password" placeholder="Laisser vide pour une liste publique">

    <label><input type="checkbox" name="one_per_user" value="1"> Une seule inscription par personne par liste</label>

    <p><button type="submit">Importer</button></p>
  </form>
</div>

<div class="card">
  <h2 style="margin-top:0">Exemple de format</h2>
<pre style="background:#f6f6f6;padding:12px;border-radius:8px;overflow:auto"># Sortie vélo

1. Pilote
2. Mécanicien
3. Ravitaillement

# Repas

1. Apéritif
2. Plat
3. Dessert</pre>
</div>
<?php require __DIR__ . '/_footer.php'; ?>

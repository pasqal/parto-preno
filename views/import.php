<?php require __DIR__ . '/_header.php'; ?>
<h1>Importer des listes</h1>
<p class="muted">Importez un fichier Markdown.</p>
<div class="card">
  <form method="post" action="index.php?a=import" enctype="multipart/form-data">
    <label for="file">Fichier Markdown (.md, .txt)</label>
    <input type="file" id="file" name="file" accept=".md,.txt,text/markdown,text/plain">
    <label for="markdown">…ou collez le Markdown directement</label>
    <textarea id="markdown" name="markdown" placeholder="# Sortie vélo&#10;&#10;La grande sortie annuelle du club.&#10;&#10;## Encadrement&#10;&#10;- Pilote&#10;- Mécanicien&#10;&#10;## Logistique&#10;&#10;- Ravitaillement&#10;- Trajet aller&#10;- Trajet retour&#10;&#10;# Repas&#10;&#10;Le repas de fin de saison.&#10;&#10;## Samedi&#10;&#10;- Apéritif&#10;- Plat&#10;- Dessert"></textarea>
    <label for="password">Mot de passe de la liste (optionnel)</label>
    <input type="password" id="password" name="password" placeholder="Laisser vide pour une liste publique">
    <label><input type="checkbox" name="one_per_user" value="1"> Une seule inscription par personne par liste</label>
    <p><button type="submit">Importer</button></p>
  </form>
</div>
<div class="card">
  <h2 style="margin-top:0">Format attendu</h2>
  <pre style="background:#f6f6f6;padding:12px;border-radius:8px;overflow:auto"># Nom de la liste

La description de la liste (paragraphe sous le titre).

## Titre d'un groupe

- Point d'inscription
- Autre point

## Autre groupe

- Encore un point</pre>
  <ul class="small">
    <li><code>#</code> : le nom de la liste</li>
    <li>Le paragraphe juste en dessous : la description de la liste</li>
    <li><code>##</code> : le titre d'un groupe</li>
    <li><code>-</code> : les points auxquels on peut s'inscrire</li>
  </ul>
</div>
<?php require __DIR__ . '/_footer.php'; ?>

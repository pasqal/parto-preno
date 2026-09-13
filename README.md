# Parto-preno

Inscription à des listes — site léger, sans base de données, installable sur hébergement mutualisé PHP.

- **Aucune base de données** : tout est stocké dans des fichiers JSON (`data/`).
- **3 rôles** : administrateur, modificateur, utilisateur (lecteur).
- **Import de listes depuis un fichier Markdown** : les titres deviennent les titres des tableaux, les listes ordonnées deviennent les lignes d'inscription.
- **Export** des résultats en Markdown ou CSV.
- **Listes publiques ou protégées par mot de passe.**
- **Pseudo** possible à l'inscription ; les autres inscrits sont visibles.

## Installation (hébergement mutualisé)

1. Copiez tout le dossier sur le serveur (FTP ou autre).
2. Vérifiez que `data/` est protégé (un `.htaccess` est fourni : `Deny from all`). Pour la 1re visite, il faut que PHP puisse écrire dans `data/` et `data/lists/`.
3. Ouvrez le site dans le navigateur : au 1er lancement, un écran de **configuration** permet de créer le compte administrateur et de fixer le nom du site.
4. Connectez-vous avec le compte administrateur, puis créez les utilisateurs modificateurs et importez des listes.

Aucune installation de dépendances n'est requise : PHP 7.4+ (8.x recommandé).

## Rôles

| Rôle            | Droits                                                                 |
|-----------------|------------------------------------------------------------------------|
| Administrateur  | Tout : paramètres du site, gestion des utilisateurs, gestion des listes |
| Modificateur    | Importer des listes, protéger une liste par mot de passe, exporter     |
| Utilisateur     | S'inscrire / se désinscrire, voir les autres inscrits, utiliser un pseudo |

## Format d'import Markdown

```
# Titre de la liste

1. Ligne d'inscription 1
2. Ligne d'inscription 2
3. Ligne d'inscription 3

# Autre liste

1. ...
```

- Chaque `#` démarre une nouvelle liste (tableau).
- Les listes ordonnées (`1.` `2.` ...) deviennent les **lignes** (slots) d'inscription.

## Sécurité

- Mots de passe hachés (`password_hash`).
- `data/` interdit d'accès HTTP via `.htaccess`.
- Les listes protégées exigent un mot de passe (haché) pour s'inscrire.

## Licence

MIT.

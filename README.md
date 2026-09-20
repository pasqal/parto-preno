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
# Nom de la liste

Le paragraphe juste en dessous donne la description de la liste.

## Titre d'un groupe

- Point d'inscription 1
- Point d'inscription 2

## Autre groupe

- Point d'inscription 3

# Autre liste

Description de l'autre liste.

- Point sans groupe
```

- `#` : le nom de la liste.
- Le paragraphe juste en dessous : la description de la liste.
- `##` : le titre d'un groupe.
- `-` : les points auxquels on peut s'inscrire.
- Les listes ordonnées (`1.` `2.` ...) restent acceptées en compatibilité.

## Inscription en un clic

Les listes sont affichées directement sur la page d'accueil : il suffit de cliquer sur un point pour s'inscrire, et re-cliquer pour se désinscrire. Les inscrits apparaissent sous chaque point sous forme de bulles de couleurs (la vôtre est mise en évidence).

## Sécurité

- Mots de passe hachés (`password_hash`).
- `data/` interdit d'accès HTTP via `.htaccess`.
- Les listes protégées exigent un mot de passe (haché) pour s'inscrire.

## Licence

MIT.

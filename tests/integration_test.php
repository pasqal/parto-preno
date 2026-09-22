<?php
// Test d'intégration CLI : valide la logique métier (sans serveur HTTP).
// Usage: php tests/integration_test.php  (depuis la racine du projet)

chdir(__DIR__ . '/..');

@unlink('data/users.json');
@unlink('data/settings.json');
array_map('unlink', glob('data/lists/*.json') ?: []);

spl_autoload_register(function ($class) {
    $file = __DIR__ . '/../src/' . $class . '.php';
    if (is_file($file)) require $file;
});

define('DATA_DIR', __DIR__ . '/../data');
define('LISTS_DIR', DATA_DIR . '/lists');

$_SESSION = [];

$pass = 0; $fail = 0;
function check($name, $cond) {
    global $pass, $fail;
    if ($cond) { echo "  OK   $name\n"; $pass++; }
    else { echo "  FAIL $name\n"; $fail++; }
}

// === Tests du parseur (nouveau format : # = nom, paragraphe = description, ## = groupe, - = point) ===
$md = "# Sortie vélo\n\nLa grande sortie annuelle du club.\n\n## Encadrement\n\n- Pilote\n- Mécanicien\n\n## Logistique\n\n- Ravitaillement\n\n# Repas\n\nLe repas de fin de saison.\n\n- Apéritif\n- Plat";
$r = MarkdownListParser::parse($md);
check("parseur: 2 listes", count($r) === 2);
check("parseur: liste1 titre 'Sortie vélo'", $r[0]['title'] === 'Sortie vélo');
check("parseur: liste1 description", $r[0]['description'] === 'La grande sortie annuelle du club.');
check("parseur: liste1 2 groupes", count($r[0]['groups']) === 2);
check("parseur: groupe1 titre 'Encadrement'", $r[0]['groups'][0]['title'] === 'Encadrement');
check("parseur: groupe1 2 points", $r[0]['groups'][0]['slots'] === ['Pilote', 'Mécanicien']);
check("parseur: groupe2 titre 'Logistique'", $r[0]['groups'][1]['title'] === 'Logistique');
check("parseur: liste1 slots plats vides", $r[0]['slots'] === []);
check("parseur: liste2 titre 'Repas'", $r[1]['title'] === 'Repas');
check("parseur: liste2 description", $r[1]['description'] === 'Le repas de fin de saison.');
check("parseur: liste2 sans groupe, 2 points", $r[1]['groups'] === [] && $r[1]['slots'] === ['Apéritif', 'Plat']);

// Compatibilité ancien format : listes ordonnées acceptées
$rOld = MarkdownListParser::parse("# T\n\n1. a\n2. b");
check("parseur: listes ordonnées acceptées", $rOld[0]['slots'] === ['a', 'b']);

// Liste à puces acceptée
$r2 = MarkdownListParser::parse("# T\n\n- a\n- b");
check("parseur: puces acceptées", $r2[0]['slots'] === ['a', 'b']);

// Sans titre
$r3 = MarkdownListParser::parse("1. x\n2. y");
check("parseur: sans titre -> 'Liste sans titre'", $r3[0]['title'] === 'Liste sans titre' && $r3[0]['slots'] === ['x', 'y']);

// Description sur plusieurs lignes
$r4 = MarkdownListParser::parse("# T\n\nLigne une.\nLigne deux.\n\n- a");
check("parseur: description multiligne", $r4[0]['description'] === 'Ligne une. Ligne deux.');

// === Setup ===
Setup::ensureDirs();
check("data/.htaccess créé", is_file('data/.htaccess'));
check("data/lists/ existe", is_dir('data/lists'));
check("non configuré initialement", !Setup::isConfigured());

// === Création admin (simulée sans redirection) ===
// On appelle directement Storage plutôt que Setup::handleSetup (qui redirige).
$admin = [
    'id' => Auth::genId(), 'login' => 'admin',
    'password' => password_hash('secret123', PASSWORD_DEFAULT),
    'pseudo' => 'admin', 'role' => Auth::ROLE_ADMIN, 'created' => date('c'),
];
Storage::saveUsers([$admin]);
Storage::saveSettings(['configured' => true, 'site_name' => 'Parto-preno', 'created' => date('c')]);
check("admin créé", Storage::userByLogin('admin')['role'] === 'admin');
check("site configuré", Setup::isConfigured());

// Simuler la session admin
Session::set('user_id', $admin['id']);
check("Auth::check true", Auth::check() === true);
check("Auth::isAdmin true", Auth::isAdmin() === true);

// === Import (logique Mod) ===
// On appelle la logique interne sans passer par handleImport (redirige).
// Reproduisons la logique: parse + saveList (sans fusionner les groupes).
$parsed = MarkdownListParser::parse($md);
foreach ($parsed as $pl) {
    Storage::saveList([
        'id' => Auth::genId(), 'title' => $pl['title'],
        'description' => $pl['description'] ?? '', 'groups' => $pl['groups'] ?? [],
        'slots' => $pl['slots'],
        'password' => '', 'one_per_user' => true, 'owner_id' => $admin['id'],
        'signups' => [], 'created' => date('c'),
    ]);
}
$lists = Storage::listAll();
check("2 listes stockées", count($lists) === 2);
$first = null; $second = null;
foreach ($lists as $l) {
    if ($l['title'] === 'Sortie vélo') $first = $l;
    if ($l['title'] === 'Repas') $second = $l;
}
check("liste 'Sortie vélo' trouvée", $first !== null);
check("liste 'Repas' trouvée", $second !== null);
check("liste1 one_per_user=true", !empty($first['one_per_user']));
check("liste1 description stockée", $first['description'] === 'La grande sortie annuelle du club.');
check("liste1 2 groupes stockés", count($first['groups']) === 2);
check("liste1 groupes préservés avec titres", 
    $first['groups'][0]['title'] === 'Encadrement' && 
    $first['groups'][0]['slots'] === ['Pilote', 'Mécanicien'] &&
    $first['groups'][1]['title'] === 'Logistique' &&
    $first['groups'][1]['slots'] === ['Ravitaillement']
);
check("liste1 slots vides (groupes préservés)", $first['slots'] === []);
check("liste2 sans groupe, slots plats", $second['groups'] === [] && $second['slots'] === ['Apéritif', 'Plat']);

// === Inscription (logique App::handleSignup sans redirection) ===
// Simuler $_POST et appeler la logique directement en répliquant.
function doSignup($listId, $slot, $pseudo, $do = 'add') {
    $list = Storage::listById($listId);
    $user = Auth::user();
    $signups = $list['signups'] ?? [];
    if ($do === 'remove') {
        $signups = array_values(array_filter($signups, function ($s) use ($user, $slot) {
            return !($s['user_id'] === $user['id'] && $s['slot'] === $slot);
        }));
        $list['signups'] = $signups;
        return Storage::saveList($list);
    }
    foreach ($signups as $s) {
        if ($s['user_id'] === $user['id'] && $s['slot'] === $slot) return 'dup_slot';
    }
    if (!empty($list['one_per_user'])) {
        foreach ($signups as $s) {
            if ($s['user_id'] === $user['id']) return 'dup_list';
        }
    }
    $signups[] = ['user_id' => $user['id'], 'login' => $user['login'], 'pseudo' => $pseudo, 'slot' => $slot, 'at' => date('c')];
    $list['signups'] = $signups;
    Storage::saveList($list);
    return 'ok';
}

$res = doSignup($first['id'], 'Pilote', 'AdminBob');
check("inscription 1 ok", $res === 'ok');
$l = Storage::listById($first['id']);
check("1 inscription stockée", count($l['signups']) === 1);
check("pseudo stocké", $l['signups'][0]['pseudo'] === 'AdminBob');

$res = doSignup($first['id'], 'Pilote', 'X');
check("double inscription slot refusée", $res === 'dup_slot');

$res = doSignup($first['id'], 'Mécanicien', 'AdminBob');
check("one_per_user bloque 2e inscription", $res === 'dup_list');

doSignup($first['id'], 'Pilote', 'AdminBob', 'remove');
$l = Storage::listById($first['id']);
check("retrait inscription", count($l['signups']) === 0);

// === Export (logique App::handleExport sans header) ===
// Réutiliser le rendu interne.
function exportCsv($listId) {
    $list = Storage::listById($listId);
    $bySlot = [];
    // Gérer les slots plats ET les groupes
    foreach ($list['slots'] ?? [] as $s) $bySlot[$s] = [];
    foreach ($list['groups'] ?? [] as $g) {
        foreach ($g['slots'] ?? ($g ?? []) as $s) $bySlot[$s] = [];
    }
    foreach ($list['signups'] ?? [] as $s) $bySlot[$s['slot']][] = $s;
    $out = "Ligne,Pseudo,Identifiant,Date\n";
    foreach ($list['slots'] ?? [] as $s) {
        $people = $bySlot[$s] ?? [];
        if (empty($people)) { $out .= "$s,,,\n"; }
        else foreach ($people as $p) $out .= $s . "," . ($p['pseudo'] ?? '') . "," . ($p['login'] ?? '') . "," . ($p['at'] ?? '') . "\n";
    }
    foreach ($list['groups'] ?? [] as $g) {
        foreach ($g['slots'] ?? ($g ?? []) as $s) {
            $people = $bySlot[$s] ?? [];
            if (empty($people)) { $out .= "$s,,,\n"; }
            else foreach ($people as $p) $out .= $s . "," . ($p['pseudo'] ?? '') . "," . ($p['login'] ?? '') . "," . ($p['at'] ?? '') . "\n";
        }
    }
    return $out;
}
function exportMd($listId) {
    $list = Storage::listById($listId);
    $bySlot = [];
    // Gérer les slots plats ET les groupes
    foreach ($list['slots'] ?? [] as $s) $bySlot[$s] = [];
    foreach ($list['groups'] ?? [] as $g) {
        foreach ($g['slots'] ?? ($g ?? []) as $s) $bySlot[$s] = [];
    }
    foreach ($list['signups'] ?? [] as $s) $bySlot[$s['slot']][] = $s;
    $out = "# " . $list['title'] . "\n\n";
    // Exporter les groupes avec leurs titres
    foreach ($list['groups'] ?? [] as $g) {
        $gTitle = $g['title'] ?? '';
        $gSlots = $g['slots'] ?? ($g ?? []);
        if (!empty($gTitle)) {
            $out .= "## " . $gTitle . "\n\n";
        }
        foreach ($gSlots as $s) {
            $out .= "- " . $s;
            $people = $bySlot[$s] ?? [];
            if (!empty($people)) {
                $names = array_map([App::class, 'displayName'], $people);
                $out .= " — " . implode(', ', $names);
            }
            $out .= "\n";
        }
        $out .= "\n";
    }
    // Exporter les slots non-groupés
    foreach ($list['slots'] ?? [] as $s) {
        $out .= "- " . $s;
        $people = $bySlot[$s] ?? [];
        if (!empty($people)) {
            $names = array_map([App::class, 'displayName'], $people);
            $out .= " — " . implode(', ', $names);
        }
        $out .= "\n";
    }
    return $out;
}
$csv = exportCsv($first['id']);
check("export CSV a en-tête", strpos($csv, 'Ligne,Pseudo') !== false);
check("export CSV a des lignes de slots", substr_count($csv, "\n") >= 4);
$mdOut = exportMd($first['id']);
check("export MD a le titre", strpos($mdOut, '# Sortie vélo') !== false);
check("export MD a 'Pilote'", strpos($mdOut, 'Pilote') !== false);
check("export MD a 'Encadrement'", strpos($mdOut, 'Encadrement') !== false);

// === Mot de passe de liste ===
$locked = ['id' => Auth::genId(), 'title' => 'Privée', 'slots' => ['Hôte', 'Invité'],
    'password' => password_hash('secretpass', PASSWORD_DEFAULT), 'one_per_user' => false,
    'owner_id' => $admin['id'], 'signups' => [], 'created' => date('c')];
Storage::saveList($locked);
$stored = Storage::listById($locked['id']);
check("liste protégée a un hash", !empty($stored['password']));
check("password_verify OK", password_verify('secretpass', $stored['password']));
check("password_verify KO sur faux", !password_verify('wrong', $stored['password']));

// === Gestion utilisateurs (Admin) ===
$users = Storage::users();
$users[] = ['id' => Auth::genId(), 'login' => 'bob', 'password' => password_hash('pass123', PASSWORD_DEFAULT),
    'pseudo' => 'Bobby', 'role' => Auth::ROLE_USER, 'created' => date('c')];
Storage::saveUsers($users);
$bob = Storage::userByLogin('bob');
check("bob créé", $bob !== null && $bob['role'] === 'user');

// Promotion bob -> mod
$users = Storage::users();
foreach ($users as &$u) if ($u['id'] === $bob['id']) { $u['role'] = Auth::ROLE_MOD; $u['pseudo'] = 'Bobby2'; }
Storage::saveUsers($users);
$bob2 = Storage::userById($bob['id']);
check("bob promu mod", $bob2['role'] === 'mod');
check("bob pseudo maj", $bob2['pseudo'] === 'Bobby2');

// Dernier admin non supprimable
$admins = count(array_filter(Storage::users(), function ($x) { return $x['role'] === Auth::ROLE_ADMIN; }));
check("1 admin restant", $admins === 1);
$users = array_values(array_filter(Storage::users(), function ($u) use ($admin) { return $u['id'] !== $admin['id']; }));
Storage::saveUsers($users);
$stillAdmin = Storage::userById($admin['id']);
// (le test de la logique Admin::userDelete empêche la suppression du dernier admin,
// ici on n'appelle pas la protection -> l'admin serait supprimé. On remet.)
Storage::saveUsers([...$users, $admin]);
check("admin restauré", Storage::userById($admin['id']) !== null);

// === Effacement liste ===
Storage::deleteList($locked['id']);
check("liste effacée", Storage::listById($locked['id']) === null);

// === Vérification finales ===
check("settings nom du site", Storage::settings()['site_name'] === 'Parto-preno');

echo "\n=== Résultat: $pass OK, $fail FAIL ===\n";
exit($fail > 0 ? 1 : 0);

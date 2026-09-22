<?php
// Test de rendu des vues : charge chaque vue et vérifie l'absence d'erreur fatale.
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
Setup::ensureDirs();

$_SESSION = [];
$pass = 0; $fail = 0;
function check($n, $c) { global $pass,$fail; if ($c){echo "  OK   $n\n";$pass++;}else{echo "  FAIL $n\n";$fail++;} }

function render($view, $data = []) {
    // View::render met en session les flash + extract. On capture les erreurs.
    set_error_handler(function ($errno, $errstr) { return true; });
    ob_start();
    try { View::render($view, $data); $out = ob_get_clean(); }
    catch (\Throwable $e) { ob_end_clean(); $out = 'EXCEPTION: ' . $e->getMessage(); }
    restore_error_handler();
    return $out;
}

// Setup admin
Storage::saveSettings(['configured' => true, 'site_name' => 'Parto-preno', 'created' => date('c')]);
$admin = ['id' => Auth::genId(), 'login' => 'admin', 'password' => password_hash('s', PASSWORD_DEFAULT),
    'pseudo' => 'admin', 'role' => Auth::ROLE_ADMIN, 'created' => date('c')];
Storage::saveUsers([$admin]);
Session::set('user_id', $admin['id']);

// Vue home (vide)
$h = render('home', ['lists' => []]);
check("home rend sans erreur", strpos($h, 'EXCEPTION') === false);
check("home a 'Listes d'inscription'", strpos($h, "Listes d'inscription") !== false);

// Créer une liste + render home + list
Storage::saveList([
    'id' => Auth::genId(), 'title' => 'Sortie vélo',
    'description' => 'La grande sortie annuelle du club.',
    'groups' => [['title' => 'Encadrement', 'slots' => ['Pilote', 'Mécanicien']]],
    'slots' => ['Pilote', 'Mécanicien'],
    'password' => '', 'one_per_user' => false, 'owner_id' => $admin['id'],
    'signups' => [], 'created' => date('c'),
]);
$lists = Storage::listAll();
$h = render('home', ['lists' => $lists]);
check("home affiche le tableau des listes", strpos($h, '<table>') !== false);
check("home avec liste affiche 'Sortie vélo'", strpos($h, 'Sortie vélo') !== false);
check("home affiche la description", strpos($h, 'La grande sortie annuelle du club.') !== false);
check("home affiche le nombre de points", strpos($h, 'Points') !== false);
check("home a un lien vers la liste (a=list)", strpos($h, 'a=list&') !== false);

$lid = $lists[0]['id'];
$list = Storage::listById($lid);
$bySlot = [];
foreach ($list['signups'] as $s) $bySlot[$s['slot']][] = $s;
$v = render('list', ['list' => $list, 'user' => Auth::user(), 'unlocked' => true, 'bySlot' => $bySlot]);
check("vue list rend sans erreur", strpos($v, 'EXCEPTION') === false);
check("vue list affiche 'Pilote'", strpos($v, 'Pilote') !== false);
check("vue list affiche le titre du groupe", strpos($v, 'Encadrement') !== false);
check("vue list affiche la description", strpos($v, 'La grande sortie annuelle du club.') !== false);

// Vue home avec un inscrit : bulle de couleur présente
$list['signups'] = [['user_id' => $admin['id'], 'login' => 'admin', 'pseudo' => 'AdminBob', 'slot' => 'Pilote', 'at' => date('c')]];
Storage::saveList($list);
$lists = Storage::listAll();
$h = render('home', ['lists' => $lists]);
check("home affiche le nombre d'inscrits (1)", strpos($h, 'Inscriptions') !== false);
check("home ne montre plus les inscrits en détail", strpos($h, 'AdminBob') === false);
check("home ne montre plus les points cliquables", strpos($h, 'slot-chip') === false);
$bySlot = ['Pilote' => $list['signups']];
$v = render('list', ['list' => $list, 'user' => Auth::user(), 'unlocked' => true, 'bySlot' => $bySlot]);
check("vue list affiche la bulle de l'inscrit", strpos($v, 'AdminBob') !== false && strpos($v, 'bubble') !== false);
check("vue list permet le retrait (do=remove)", strpos($v, 'do=remove') !== false || strpos($v, 'value="remove"') !== false);

// Vue home hors connexion : pas de formulaire d'inscription, chips simples
Session::set('user_id', null);
$h = render('home', ['lists' => $lists]);
check("home hors-connexion : pas de formulaire d'inscription (pas de a=signup)", strpos($h, 'a=signup') === false);
check("home hors-connexion affiche quand même les listes", strpos($h, 'Sortie vélo') !== false);

// Liste protégée (non déverrouillée)
$locked = ['id' => Auth::genId(), 'title' => 'Privée', 'slots' => ['Hôte'],
    'password' => password_hash('p', PASSWORD_DEFAULT), 'one_per_user' => false,
    'owner_id' => $admin['id'], 'signups' => [], 'created' => date('c')];
Storage::saveList($locked);
$v = render('list', ['list' => $locked, 'user' => Auth::user(), 'unlocked' => false, 'bySlot' => []]);
check("vue list protégée affiche déverrouillage", strpos($v, 'protégée par mot de passe') !== false || strpos($v, 'prot&eacute;g&eacute;e par mot de passe') !== false);

// Vue login
$v = render('login');
check("vue login rend", strpos($v, 'Connexion') !== false && strpos($v, 'EXCEPTION') === false);

// Vue register
$v = render('register');
check("vue register rend", strpos($v, 'Créer un compte') !== false && strpos($v, 'EXCEPTION') === false);

// Vue setup
$v = render('setup');
check("vue setup rend", strpos($v, 'Configuration initiale') !== false && strpos($v, 'EXCEPTION') === false);

// Vue import
$v = render('import');
check("vue import rend", strpos($v, 'Importer des listes') !== false && strpos($v, 'EXCEPTION') === false);

// Vue edit_list
$v = render('edit_list', ['list' => $lists[0]]);
check("vue edit_list rend", strpos($v, 'Modifier la liste') !== false && strpos($v, 'EXCEPTION') === false);

// Vue admin_settings
$v = render('admin_settings');
check("vue admin_settings rend", strpos($v, 'Paramètres du site') !== false && strpos($v, 'EXCEPTION') === false);

// Vue admin_users
$users = Storage::users();
$v = render('admin_users', ['users' => $users]);
check("vue admin_users rend", strpos($v, 'Utilisateurs') !== false && strpos($v, 'EXCEPTION') === false);
check("admin_users affiche le compte admin", strpos($v, 'admin') !== false);

// Hors connexion (user null)
Session::set('user_id', null);
$v = render('home', ['lists' => $lists]);
check("home hors-connexion rend", strpos($v, 'EXCEPTION') === false);
check("home hors-connexion a lien Connexion", strpos($v, 'Connexion') !== false);

echo "\n=== Rendu: $pass OK, $fail FAIL ===\n";
exit($fail > 0 ? 1 : 0);

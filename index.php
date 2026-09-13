<?php
// Chargement automatique simple des classes du dossier src/.
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/src/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

define('DATA_DIR', __DIR__ . '/data');
define('LISTS_DIR', DATA_DIR . '/lists');

// S'assurer que les dossiers de données existent et sont protégés.
Setup::ensureDirs();
// Démarrer la session (persistance entre connexions).
Session::start();
// Au tout premier lancement : afficher l'écran de configuration.
Setup::bootstrap();

// Router principal.
$action = $_GET['a'] ?? 'home';

// En-têtes de sécurité.
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

// Préfixe d'action par rôle pour la zone admin/mod.
$adminAction = $_GET['admin'] ?? '';

if ($adminAction !== '' && Auth::check()) {
    Admin::route($adminAction);
    exit;
}

switch ($action) {
    case 'login':
        Auth::handleLogin();
        break;
    case 'logout':
        Auth::logout();
        break;
    case 'register':
        Auth::handleRegister();
        break;
    case 'setup':
        Setup::handleSetup();
        break;
    case 'list':
        App::viewList();
        break;
    case 'signup':
        App::handleSignup();
        break;
    case 'export':
        App::handleExport();
        break;
    case 'import':
        Mod::handleImport();
        break;
    case 'edit_list':
        Mod::handleEditList();
        break;
    case 'delete_list':
        Mod::handleDeleteList();
        break;
    default:
        App::home();
        break;
}

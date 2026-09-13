<?php
// Setup : création des dossiers, configuration initiale du site et du compte admin.
class Setup
{
    public static function ensureDirs()
    {
        if (!is_dir(DATA_DIR)) {
            @mkdir(DATA_DIR, 0775, true);
        }
        if (!is_dir(LISTS_DIR)) {
            @mkdir(LISTS_DIR, 0775, true);
        }
        // Protection du dossier data via .htaccess (Apache).
        $ht = DATA_DIR . '/.htaccess';
        if (!is_file($ht)) {
            @file_put_contents($ht, "Order deny,allow\nDeny from all\n");
        }
        // Protection via index.html vide.
        $idx = DATA_DIR . '/index.html';
        if (!is_file($idx)) {
            @file_put_contents($idx, '');
        }
    }

    public static function isConfigured()
    {
        $s = Storage::settings();
        return !empty($s['configured']);
    }

    // Bloque tout tant que le site n'est pas configuré.
    public static function bootstrap()
    {
        if (!self::isConfigured()) {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['a'] ?? '') === 'setup') {
                self::handleSetup();
            }
            View::render('setup');
            exit;
        }
    }

    public static function handleSetup()
    {
        $siteName = trim($_POST['site_name'] ?? '');
        $login    = trim($_POST['login'] ?? '');
        $pass     = $_POST['password'] ?? '';
        if ($siteName === '' || $login === '' || $pass === '') {
            Session::flash('error', 'Tous les champs sont requis.');
            Auth::redirect('a=setup');
        }
        $admin = [
            'id'       => Auth::genId(),
            'login'    => $login,
            'password' => password_hash($pass, PASSWORD_DEFAULT),
            'pseudo'   => $login,
            'role'     => Auth::ROLE_ADMIN,
            'created'  => date('c'),
        ];
        Storage::saveUsers([$admin]);
        Storage::saveSettings([
            'configured' => true,
            'site_name'  => $siteName,
            'created'    => date('c'),
        ]);
        Auth::login($admin['id']);
        Session::flash('ok', 'Site configuré. Bienvenue, administrateur.');
        Auth::redirect('');
    }
}

<?php
// Admin : paramètres du site, gestion des utilisateurs, droits complets.
class Admin
{
    public static function route($action)
    {
        Auth::requireAdmin();
        switch ($action) {
            case 'settings':
                self::settings();
                break;
            case 'users':
                self::users();
                break;
            case 'user_save':
                self::userSave();
                break;
            case 'user_delete':
                self::userDelete();
                break;
            case 'user_create':
                self::userCreate();
                break;
            default:
                Auth::redirect('');
        }
    }

    public static function settings()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $siteName = trim($_POST['site_name'] ?? '');
            $settings = Storage::settings();
            $settings['site_name'] = $siteName !== '' ? $siteName : ($settings['site_name'] ?? 'Parto-preno');
            Storage::saveSettings($settings);
            Session::flash('ok', 'Paramètres enregistrés.');
            Auth::redirect('admin=settings');
        }
        View::render('admin_settings');
    }

    public static function users()
    {
        View::render('admin_users', ['users' => Storage::users()]);
    }

    public static function userCreate()
    {
        $login = trim($_POST['login'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? Auth::ROLE_USER;
        $pseudo = trim($_POST['pseudo'] ?? '');
        if ($login === '' || $pass === '') {
            Session::flash('error', 'Identifiant et mot de passe requis.');
            Auth::redirect('admin=users');
        }
        if (!in_array($role, [Auth::ROLE_ADMIN, Auth::ROLE_MOD, Auth::ROLE_USER], true)) {
            $role = Auth::ROLE_USER;
        }
        if (Storage::userByLogin($login)) {
            Session::flash('error', 'Cet identifiant existe déjà.');
            Auth::redirect('admin=users');
        }
        $users = Storage::users();
        $users[] = [
            'id'       => Auth::genId(),
            'login'    => $login,
            'password' => password_hash($pass, PASSWORD_DEFAULT),
            'pseudo'   => $pseudo !== '' ? $pseudo : $login,
            'role'     => $role,
            'created'  => date('c'),
        ];
        Storage::saveUsers($users);
        Session::flash('ok', 'Utilisateur créé.');
        Auth::redirect('admin=users');
    }

    public static function userSave()
    {
        $id = $_POST['id'] ?? '';
        $users = Storage::users();
        foreach ($users as &$u) {
            if ($u['id'] === $id) {
                $pseudo = trim($_POST['pseudo'] ?? '');
                if ($pseudo !== '') {
                    $u['pseudo'] = $pseudo;
                }
                $role = $_POST['role'] ?? $u['role'];
                if (in_array($role, [Auth::ROLE_ADMIN, Auth::ROLE_MOD, Auth::ROLE_USER], true)) {
                    // Ne pas rétrograder le dernier admin.
                    if ($u['role'] === Auth::ROLE_ADMIN && $role !== Auth::ROLE_ADMIN) {
                        $admins = count(array_filter($users, function ($x) {
                            return ($x['role'] ?? '') === Auth::ROLE_ADMIN;
                        }));
                        if ($admins <= 1) {
                            Session::flash('error', 'Impossible : c\'est le dernier administrateur.');
                            Auth::redirect('admin=users');
                        }
                    }
                    $u['role'] = $role;
                }
                $newPass = $_POST['new_password'] ?? '';
                if ($newPass !== '') {
                    $u['password'] = password_hash($newPass, PASSWORD_DEFAULT);
                }
                Storage::saveUsers($users);
                Session::flash('ok', 'Utilisateur mis à jour.');
                Auth::redirect('admin=users');
            }
        }
        Session::flash('error', 'Utilisateur introuvable.');
        Auth::redirect('admin=users');
    }

    public static function userDelete()
    {
        $id = $_POST['id'] ?? '';
        $users = Storage::users();
        // Empêcher l'auto-suppression et la suppression du dernier admin.
        $target = Storage::userById($id);
        if (!$target) {
            Session::flash('error', 'Utilisateur introuvable.');
            Auth::redirect('admin=users');
        }
        if ($id === Auth::user()['id']) {
            Session::flash('error', 'Vous ne pouvez pas effacer votre propre compte.');
            Auth::redirect('admin=users');
        }
        if ($target['role'] === Auth::ROLE_ADMIN) {
            $admins = count(array_filter($users, function ($x) {
                return ($x['role'] ?? '') === Auth::ROLE_ADMIN;
            }));
            if ($admins <= 1) {
                Session::flash('error', 'Impossible : c\'est le dernier administrateur.');
                Auth::redirect('admin=users');
            }
        }
        $users = array_values(array_filter($users, function ($u) use ($id) {
            return $u['id'] !== $id;
        }));
        Storage::saveUsers($users);
        Session::flash('ok', 'Utilisateur effacé.');
        Auth::redirect('admin=users');
    }
}

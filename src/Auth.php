<?php
// Authentification : login, logout, inscription, rôles.
class Auth
{
    const ROLE_ADMIN = 'admin';
    const ROLE_MOD   = 'mod';
    const ROLE_USER  = 'user';

    public static function check()
    {
        return Session::get('user_id') !== null;
    }

    public static function user()
    {
        $id = Session::get('user_id');
        return $id ? Storage::userById($id) : null;
    }

    public static function role()
    {
        $u = self::user();
        return $u['role'] ?? self::ROLE_USER;
    }

    public static function isAdmin()
    {
        return self::role() === self::ROLE_ADMIN;
    }

    public static function isMod()
    {
        return self::role() === self::ROLE_MOD || self::isAdmin();
    }

    public static function login($id)
    {
        Session::set('user_id', $id);
    }

    public static function logout()
    {
        Session::set('user_id', null);
        self::redirect('');
    }

    public static function handleLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $login = trim($_POST['login'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $user  = Storage::userByLogin($login);
            if ($user && password_verify($pass, $user['password'])) {
                self::login($user['id']);
                self::redirect('');
            }
            Session::flash('error', 'Identifiant ou mot de passe incorrect.');
            self::redirect('a=login');
        }
        View::render('login');
    }

    public static function handleRegister()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $login = trim($_POST['login'] ?? '');
            $pass  = $_POST['password'] ?? '';
            $pseudo = trim($_POST['pseudo'] ?? '');
            if ($login === '' || $pass === '') {
                Session::flash('error', 'Identifiant et mot de passe requis.');
                self::redirect('a=register');
            }
            if (Storage::userByLogin($login)) {
                Session::flash('error', 'Cet identifiant existe déjà.');
                self::redirect('a=register');
            }
            $users = Storage::users();
            $users[] = [
                'id'       => self::genId(),
                'login'    => $login,
                'password' => password_hash($pass, PASSWORD_DEFAULT),
                'pseudo'   => $pseudo !== '' ? $pseudo : $login,
                'role'     => self::ROLE_USER,
                'created'  => date('c'),
            ];
            Storage::saveUsers($users);
            Session::flash('ok', 'Compte créé. Vous pouvez vous connecter.');
            self::redirect('a=login');
        }
        View::render('register');
    }

    public static function genId()
    {
        return bin2hex(random_bytes(8));
    }

    public static function requireAdmin()
    {
        if (!self::isAdmin()) {
            self::redirect('');
        }
    }

    public static function requireMod()
    {
        if (!self::isMod()) {
            self::redirect('');
        }
    }

    public static function redirect($qs)
    {
        $url = 'index.php' . ($qs !== '' ? '?' . $qs : '');
        header('Location: ' . $url);
        exit;
    }
}

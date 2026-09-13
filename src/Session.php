<?php
// Session PHP avec persistance entre connexions.
class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('partopreno');
            session_set_cookie_params([
                'lifetime' => 60 * 60 * 24 * 30,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function set($key, $value)
    {
        $_SESSION['partopreno'][$key] = $value;
    }

    public static function get($key, $default = null)
    {
        return $_SESSION['partopreno'][$key] ?? $default;
    }

    public static function flash($key, $value = null)
    {
        if ($value === null) {
            $v = $_SESSION['partopreno']['_flash'][$key] ?? null;
            unset($_SESSION['partopreno']['_flash'][$key]);
            return $v;
        }
        $_SESSION['partopreno']['_flash'][$key] = $value;
    }
}

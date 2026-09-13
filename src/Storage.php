<?php
// Stockage JSON simple, sans base de données.
class Storage
{
    private static function path($name)
    {
        return DATA_DIR . '/' . $name . '.json';
    }

    public static function read($name, $default = null)
    {
        $file = self::path($name);
        if (!is_file($file)) {
            return $default;
        }
        $raw = @file_get_contents($file);
        if ($raw === false || $raw === '') {
            return $default;
        }
        $data = json_decode($raw, true);
        return is_array($data) ? $data : $default;
    }

    public static function write($name, $data)
    {
        $file = self::path($name);
        $tmp = $file . '.tmp';
        $ok = @file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($ok === false) {
            return false;
        }
        return @rename($tmp, $file);
    }

    // --- Données de type "collections" : users, lists, settings ---

    public static function users()
    {
        return self::read('users', []);
    }

    public static function saveUsers($users)
    {
        return self::write('users', $users);
    }

    public static function settings()
    {
        return self::read('settings', []);
    }

    public static function saveSettings($settings)
    {
        return self::write('settings', $settings);
    }

    public static function userById($id)
    {
        foreach (self::users() as $u) {
            if (($u['id'] ?? null) === $id) {
                return $u;
            }
        }
        return null;
    }

    public static function userByLogin($login)
    {
        $login = strtolower(trim($login));
        foreach (self::users() as $u) {
            if (strtolower($u['login'] ?? '') === $login) {
                return $u;
            }
        }
        return null;
    }

    public static function listAll()
    {
        $out = [];
        if (is_dir(LISTS_DIR)) {
            foreach (glob(LISTS_DIR . '/*.json') as $f) {
                $data = json_decode(@file_get_contents($f), true);
                if (is_array($data)) {
                    $out[] = $data;
                }
            }
        }
        return $out;
    }

    public static function listById($id)
    {
        $file = LISTS_DIR . '/' . $id . '.json';
        if (!is_file($file)) {
            return null;
        }
        return json_decode(@file_get_contents($file), true);
    }

    public static function saveList($list)
    {
        if (!is_dir(LISTS_DIR)) {
            @mkdir(LISTS_DIR, 0775, true);
        }
        $file = LISTS_DIR . '/' . $list['id'] . '.json';
        return (bool) @file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function deleteList($id)
    {
        $file = LISTS_DIR . '/' . $id . '.json';
        if (is_file($file)) {
            return @unlink($file);
        }
        return false;
    }
}

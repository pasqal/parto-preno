<?php
// App : partie publique / utilisateur (lecteur).
class App
{
    public static function home()
    {
        $lists = Storage::listAll();
        // Trier par date de création décroissante.
        usort($lists, function ($a, $b) {
            return strcmp($b['created'] ?? '', $a['created'] ?? '');
        });
        View::render('home', ['lists' => $lists]);
    }

    public static function viewList()
    {
        $id = $_GET['id'] ?? '';
        $list = Storage::listById($id);
        if (!$list) {
            Session::flash('error', 'Liste introuvable.');
            Auth::redirect('');
        }
        $user = Auth::user();
        // Accès aux listes protégées par mot de passe.
        $unlocked = false;
        if (!empty($list['password'])) {
            $key = 'list_unlock_' . $id;
            if (Session::get($key)) {
                $unlocked = true;
            }
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['do'] ?? '') === 'unlock') {
                if (password_verify($_POST['password'] ?? '', $list['password'])) {
                    Session::set($key, true);
                    $unlocked = true;
                } else {
                    Session::flash('error', 'Mot de passe incorrect.');
                }
            }
        } else {
            $unlocked = true;
        }

        // Préparer les inscriptions par slot.
        $signups = $list['signups'] ?? [];
        // Construire un index slot -> [pseudo|login, user_id]
        $bySlot = [];
        foreach ($signups as $s) {
            $bySlot[$s['slot']][] = $s;
        }

        View::render('list', [
            'list'     => $list,
            'user'     => $user,
            'unlocked' => $unlocked,
            'bySlot'   => $bySlot,
        ]);
    }

    public static function handleSignup()
    {
        $id = $_POST['id'] ?? '';
        $slot = trim($_POST['slot'] ?? '');
        $list = Storage::listById($id);
        if (!$list) {
            Session::flash('error', 'Liste introuvable.');
            Auth::redirect('');
        }
        // Vérifier verrouillage mot de passe.
        if (!empty($list['password']) && !Session::get('list_unlock_' . $id)) {
            Session::flash('error', 'Liste protégée : mot de passe requis.');
            Auth::redirect('a=list&id=' . $id);
        }
        // Login requis : on doit être connecté pour s'inscrire.
        if (!Auth::check()) {
            Session::flash('error', 'Connectez-vous pour vous inscrire.');
            Auth::redirect('a=login');
        }
        $user = Auth::user();
        $pseudo = trim($_POST['pseudo'] ?? '');
        if ($pseudo === '') {
            $pseudo = $user['pseudo'] ?? $user['login'];
        }

        // Limite : 1 inscription par slot par utilisateur, 1 slot par utilisateur.
        $signups = $list['signups'] ?? [];
        $do = $_POST['do'] ?? 'add';
        if ($do === 'remove') {
            $signups = array_values(array_filter($signups, function ($s) use ($user, $slot) {
                return !($s['user_id'] === $user['id'] && $s['slot'] === $slot);
            }));
            $list['signups'] = $signups;
            Storage::saveList($list);
            Session::flash('ok', 'Inscription retirée.');
            Auth::redirect('a=list&id=' . $id);
        }

        // Vérifier que l'utilisateur n'est pas déjà inscrit ailleurs sur cette liste.
        foreach ($signups as $s) {
            if ($s['user_id'] === $user['id'] && $s['slot'] === $slot) {
                Session::flash('error', 'Vous êtes déjà inscrit à cette ligne.');
                Auth::redirect('a=list&id=' . $id);
            }
        }
        // Optionnel : une seule inscription par utilisateur par liste.
        if (!empty($list['one_per_user'])) {
            foreach ($signups as $s) {
                if ($s['user_id'] === $user['id']) {
                    Session::flash('error', 'Vous êtes déjà inscrit dans cette liste (une seule inscription autorisée).');
                    Auth::redirect('a=list&id=' . $id);
                }
            }
        }
        $signups[] = [
            'user_id' => $user['id'],
            'login'   => $user['login'],
            'pseudo'  => $pseudo,
            'slot'    => $slot,
            'at'      => date('c'),
        ];
        $list['signups'] = $signups;
        Storage::saveList($list);
        Session::flash('ok', 'Inscription enregistrée.');
        Auth::redirect('a=list&id=' . $id);
    }

    public static function handleExport()
    {
        $id = $_GET['id'] ?? '';
        $format = $_GET['format'] ?? 'csv';
        $list = Storage::listById($id);
        if (!$list) {
            Session::flash('error', 'Liste introuvable.');
            Auth::redirect('');
        }
        // Seuls admin/mod peuvent exporter (ou liste publique ? on autorise mod+admin).
        if (!Auth::isMod()) {
            Session::flash('error', 'Export réservé aux modérateurs et administrateurs.');
            Auth::redirect('a=list&id=' . $id);
        }
        $signups = $list['signups'] ?? [];
        // Grouper par slot.
        $bySlot = [];
        foreach ($list['slots'] as $idx => $slotName) {
            $bySlot[$slotName] = [];
        }
        foreach ($signups as $s) {
            $bySlot[$s['slot']][] = $s;
        }

        $safeName = preg_replace('/[^A-Za-z0-9_-]/', '_', $list['title']);
        if ($format === 'md') {
            header('Content-Type: text/markdown; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $safeName . '.md"');
            echo "# " . $list['title'] . "\n\n";
            foreach ($list['slots'] as $slotName) {
                echo "## " . $slotName . "\n\n";
                $people = $bySlot[$slotName] ?? [];
                if (empty($people)) {
                    echo "_(aucun inscrit)_\n\n";
                } else {
                    foreach ($people as $p) {
                        echo "- " . self::displayName($p) . "\n";
                    }
                    echo "\n";
                }
            }
            exit;
        }

        // CSV par défaut.
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $safeName . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8
        fputcsv($out, ['Ligne', 'Pseudo', 'Identifiant', 'Date']);
        foreach ($list['slots'] as $slotName) {
            $people = $bySlot[$slotName] ?? [];
            if (empty($people)) {
                fputcsv($out, [$slotName, '', '', '']);
            } else {
                foreach ($people as $p) {
                    fputcsv($out, [$slotName, $p['pseudo'] ?? '', $p['login'] ?? '', $p['at'] ?? '']);
                }
            }
        }
        fclose($out);
        exit;
    }

    public static function displayName($p)
    {
        $pseudo = $p['pseudo'] ?? '';
        return $pseudo !== '' ? $pseudo : ($p['login'] ?? 'Anonyme');
    }
}

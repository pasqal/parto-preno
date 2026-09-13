<?php
// Mod : partie modificateur (importer, éditer, effacer des listes, mot de passe).
class Mod
{
    public static function handleImport()
    {
        Auth::requireMod();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $text = '';
            if (!empty($_FILES['file']['tmp_name']) && is_uploaded_file($_FILES['file']['tmp_name'])) {
                $text = file_get_contents($_FILES['file']['tmp_name']);
            } else {
                $text = $_POST['markdown'] ?? '';
            }
            $parsed = MarkdownListParser::parse($text);
            if (empty($parsed)) {
                Session::flash('error', 'Aucune liste détectée. Vérifiez le format Markdown (titres # + listes ordonnées).');
                Auth::redirect('a=import');
            }
            $password = trim($_POST['password'] ?? '');
            $onePerUser = !empty($_POST['one_per_user']);
            $count = 0;
            foreach ($parsed as $parsed_list) {
                $id = Auth::genId();
                $list = [
                    'id'           => $id,
                    'title'        => $parsed_list['title'],
                    'slots'        => $parsed_list['slots'],
                    'password'     => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '',
                    'one_per_user' => $onePerUser,
                    'owner_id'     => Auth::user()['id'],
                    'signups'      => [],
                    'created'      => date('c'),
                ];
                Storage::saveList($list);
                $count++;
            }
            Session::flash('ok', $count . ' liste(s) importée(s).');
            Auth::redirect('');
        }
        View::render('import');
    }

    public static function handleEditList()
    {
        Auth::requireMod();
        $id = $_GET['id'] ?? ($_POST['id'] ?? '');
        $list = Storage::listById($id);
        if (!$list) {
            Session::flash('error', 'Liste introuvable.');
            Auth::redirect('');
        }
        // Seul l'admin ou le propriétaire peut éditer.
        if (!Auth::isAdmin() && $list['owner_id'] !== Auth::user()['id']) {
            Session::flash('error', 'Vous ne pouvez pas modifier cette liste.');
            Auth::redirect('');
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $title = trim($_POST['title'] ?? '');
            $slots = $_POST['slots'] ?? [];
            $slots = array_values(array_filter(array_map('trim', $slots), function ($s) {
                return $s !== '';
            }));
            $password = trim($_POST['password'] ?? '');
            $removePassword = !empty($_POST['remove_password']);
            $onePerUser = !empty($_POST['one_per_user']);
            $list['title'] = $title !== '' ? $title : $list['title'];
            $list['slots'] = $slots;
            if ($removePassword) {
                $list['password'] = '';
            } elseif ($password !== '') {
                $list['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $list['one_per_user'] = $onePerUser;
            // Nettoyer les inscriptions dont le slot n'existe plus.
            $list['signups'] = array_values(array_filter($list['signups'] ?? [], function ($s) use ($slots) {
                return in_array($s['slot'], $slots, true);
            }));
            Storage::saveList($list);
            Session::flash('ok', 'Liste mise à jour.');
            Auth::redirect('a=list&id=' . $id);
        }
        View::render('edit_list', ['list' => $list]);
    }

    public static function handleDeleteList()
    {
        Auth::requireMod();
        $id = $_POST['id'] ?? '';
        $list = Storage::listById($id);
        if (!$list) {
            Session::flash('error', 'Liste introuvable.');
            Auth::redirect('');
        }
        if (!Auth::isAdmin() && $list['owner_id'] !== Auth::user()['id']) {
            Session::flash('error', 'Vous ne pouvez pas effacer cette liste.');
            Auth::redirect('');
        }
        Storage::deleteList($id);
        Session::flash('ok', 'Liste effacée.');
        Auth::redirect('');
    }
}

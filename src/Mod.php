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
                    'description'  => $parsed_list['description'] ?? '',
                    'groups'       => $parsed_list['groups'] ?? [],
                    'slots'        => $parsed_list['slots'],
                    'password'     => $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : '',
                    'one_per_user' => $onePerUser,
                    'owner_id'     => Auth::user()['id'],
                    'signups'      => [],
                    'created'      => date('c'),
                ];
                // Normalisation : si on a des groupes mais pas de slots,
                // et que les groupes n'ont pas de titres (ancien format),
                // les convertir en groupes avec titres vides pour compatibilité.
                if (empty($list['slots']) && !empty($list['groups'])) {
                    foreach ($list['groups'] as $i => $g) {
                        if (!isset($g['title'])) {
                            $list['groups'][$i] = ['title' => '', 'slots' => $g];
                        }
                    }
                }
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
            $description = trim($_POST['description'] ?? '');
            
            // Gestion des groupes avec titres
            $groupsIn = $_POST['groups'] ?? [];
            $groupTitles = $_POST['group_titles'] ?? [];
            $groups = [];
            
            if (is_array($groupsIn)) {
                foreach ($groupsIn as $gi => $g) {
                    $g = array_values(array_filter(array_map('trim', is_array($g) ? $g : []), function ($s) {
                        return $s !== '';
                    }));
                    if (!empty($g)) {
                        $groupTitle = trim($groupTitles[$gi] ?? '');
                        $groups[] = [
                            'title' => $groupTitle,
                            'slots' => $g
                        ];
                    }
                }
            }
            
            $password = trim($_POST['password'] ?? '');
            $removePassword = !empty($_POST['remove_password']);
            $onePerUser = !empty($_POST['one_per_user']);
            $list['title'] = $title !== '' ? $title : $list['title'];
            $list['description'] = $description;
            $list['slots'] = $slots;
            $list['groups'] = $groups;
            
            // Normaliser : si on a des groupes, on les garde. Sinon, on utilise les slots.
            // Ne plus aplatir automatiquement les groupes en slots.
            // Si on a à la fois des slots non-groupés et des groupes, on les conserve séparément.
            if ($removePassword) {
                $list['password'] = '';
            } elseif ($password !== '') {
                $list['password'] = password_hash($password, PASSWORD_DEFAULT);
            }
            $list['one_per_user'] = $onePerUser;
            // Nettoyer les inscriptions dont le slot n'existe plus.
            // Vérifier dans les slots non-groupés ET dans les groupes.
            $validSlots = $list['slots'] ?? [];
            foreach ($list['groups'] ?? [] as $g) {
                $validSlots = array_merge($validSlots, $g['slots'] ?? ($g ?? []));
            }
            $list['signups'] = array_values(array_filter($list['signups'] ?? [], function ($s) use ($validSlots) {
                return in_array($s['slot'], $validSlots, true);
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

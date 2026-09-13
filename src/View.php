<?php
// Moteur de rendu simple : layouts + vues dans /views.
class View
{
    public static function render($view, $data = [])
    {
        $data['settings'] = Storage::settings();
        $data['user'] = Auth::user();
        $data['flash_error'] = Session::flash('error');
        $data['flash_ok'] = Session::flash('ok');
        extract($data, EXTR_SKIP);
        $file = __DIR__ . '/../views/' . $view . '.php';
        if (!is_file($file)) {
            echo "Vue introuvable : $view";
            return;
        }
        require $file;
    }
}

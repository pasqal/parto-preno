<?php
// Parseur Markdown : titres (h1) = titres de tableaux, listes ordonnées = lignes d'inscription.
class MarkdownListParser
{
    // Retourne un tableau de listes : [['title'=>..., 'slots'=>['...','...']], ...]
    public static function parse($text)
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $lists = [];
        $current = null;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }
            // Titre de niveau 1
            if (preg_match('/^#\s+(.*)$/', $trimmed, $m)) {
                if ($current !== null) {
                    $lists[] = $current;
                }
                $current = ['title' => trim($m[1]), 'slots' => []];
                continue;
            }
            // Autres titres (#2, ###) -> titres alternatifs de tableau
            if (preg_match('/^#{2,6}\s+(.*)$/', $trimmed, $m)) {
                if ($current !== null) {
                    $lists[] = $current;
                }
                $current = ['title' => trim($m[1]), 'slots' => []];
                continue;
            }
            // Liste ordonnée : "1. ..." ou "1) ..."
            if (preg_match('/^\d+[.)]\s+(.*)$/', $trimmed, $m)) {
                if ($current === null) {
                    $current = ['title' => 'Liste sans titre', 'slots' => []];
                }
                $current['slots'][] = trim($m[1]);
                continue;
            }
            // Liste à puces "- ..." ou "* ..." : acceptées aussi comme lignes
            if (preg_match('/^[-*+]\s+(.*)$/', $trimmed, $m)) {
                if ($current === null) {
                    $current = ['title' => 'Liste sans titre', 'slots' => []];
                }
                $current['slots'][] = trim($m[1]);
                continue;
            }
        }
        if ($current !== null) {
            $lists[] = $current;
        }
        return $lists;
    }
}

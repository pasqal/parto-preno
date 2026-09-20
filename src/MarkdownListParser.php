<?php
// Parseur Markdown : # = nom de la liste, paragraphe = description,
// ## = titre de groupe, - = point d'inscription.
class MarkdownListParser
{
    // Retourne une liste : ['title'=>..., 'description'=>..., 'groups'=>[['title'=>..., 'slots'=>['...']], ...]]
    // ou ['title'=>..., 'description'=>..., 'slots'=>['...']] si aucun groupe.
    public static function parse($text)
    {
        $lines = preg_split('/\r\n|\r|\n/', $text);
        $lists = [];
        $current = null;
        $paragraph = [];
        $group = null;

        $flushGroup = function () use (&$current, &$group) {
            if ($current !== null && $group !== null) {
                $current['groups'][] = $group;
                $group = null;
            }
        };
        $flushParagraph = function () use (&$current, &$paragraph) {
            if ($current !== null && !empty($paragraph)) {
                $current['description'] = trim(implode(' ', $paragraph));
                $paragraph = [];
            }
        };

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                $flushParagraph();
                continue;
            }
            // Titre de niveau 1 : nom de la liste.
            if (preg_match('/^#\s+(.*)$/', $trimmed, $m)) {
                $flushGroup();
                if ($current !== null) {
                    $lists[] = self::normalize($current);
                }
                $current = ['title' => trim($m[1]), 'description' => '', 'groups' => [], 'slots' => []];
                continue;
            }
            // Titre de niveau 2 : titre d'un groupe.
            if (preg_match('/^#{2,6}\s+(.*)$/', $trimmed, $m)) {
                $flushParagraph();
                $flushGroup();
                if ($current === null) {
                    $current = ['title' => 'Liste sans titre', 'description' => '', 'groups' => [], 'slots' => []];
                }
                $group = ['title' => trim($m[1]), 'slots' => []];
                continue;
            }
            // Puce "- ..." : point d'inscription.
            if (preg_match('/^[-*+]\s+(.*)$/', $trimmed, $m)) {
                $flushParagraph();
                if ($current === null) {
                    $current = ['title' => 'Liste sans titre', 'description' => '', 'groups' => [], 'slots' => []];
                }
                $slot = trim($m[1]);
                if ($group !== null) {
                    $group['slots'][] = $slot;
                } else {
                    $current['slots'][] = $slot;
                }
                continue;
            }
            // Liste ordonnée "1. ..." : acceptée aussi comme points.
            if (preg_match('/^\d+[.)]\s+(.*)$/', $trimmed, $m)) {
                $flushParagraph();
                if ($current === null) {
                    $current = ['title' => 'Liste sans titre', 'description' => '', 'groups' => [], 'slots' => []];
                }
                $slot = trim($m[1]);
                if ($group !== null) {
                    $group['slots'][] = $slot;
                } else {
                    $current['slots'][] = $slot;
                }
                continue;
            }
            // Autre texte : paragraphe descriptif.
            if ($current !== null) {
                $paragraph[] = $trimmed;
            }
        }
        $flushGroup();
        $flushParagraph();
        if ($current !== null) {
            $lists[] = self::normalize($current);
        }
        return $lists;
    }

    private static function normalize($list)
    {
        // Si aucun groupe, tout va dans "slots" (compatibilité ancien format).
        if (!empty($list['groups'])) {
            $list['groups'] = array_values(array_filter($list['groups'], function ($g) {
                return !empty($g['slots']);
            }));
        }
        return $list;
    }
}

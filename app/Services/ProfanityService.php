<?php

namespace App\Services;

class ProfanityService
{
    private array $words = [
        // English
        'fuck', 'fucking', 'fucker', 'shit', 'shitty', 'ass', 'asshole',
        'bitch', 'bastard', 'crap', 'piss', 'cock', 'dick', 'pussy',
        'whore', 'slut', 'nigger', 'nigga', 'faggot', 'fag', 'cunt',
        'twat', 'wanker', 'arsehole', 'bollocks', 'motherfucker',
        // French
        'pute', 'putain', 'merde', 'connard', 'connasse', 'salope',
        'enculé', 'encule', 'enculer', 'foutre', 'chier', 'chieur',
        'bite', 'couille', 'couilles', 'con', 'conne', 'cul',
        'bordel', 'bâtard', 'batard', 'salopard', 'pd', 'fdp',
    ];

    public function contains(string $text): bool
    {
        $lower = mb_strtolower($text);
        foreach ($this->words as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/u', $lower)) {
                return true;
            }
        }
        return false;
    }

    public function clean(string $text): string
    {
        foreach ($this->words as $word) {
            $replacement = str_repeat('*', mb_strlen($word));
            $text = preg_replace('/\b' . preg_quote($word, '/') . '\b/iu', $replacement, $text);
        }
        return $text;
    }
}

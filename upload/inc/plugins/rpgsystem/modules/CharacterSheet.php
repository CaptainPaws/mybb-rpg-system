<?php
namespace RPGSystem\Modules;

class CharacterSheet
{
    public function render(int $uid): string
    {
        // Build character sheet using profile and custom data
        return '<div>Character sheet placeholder for user ' . $uid . '</div>';
    }
}

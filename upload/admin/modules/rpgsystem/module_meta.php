<?php
if (!defined('IN_MYBB')) {
    die('Direct access not allowed.');
}

function rpgsystem_meta()
{
    global $lang;

    return [
        'name' => $lang->rpgsystem_name,
        'description' => $lang->rpgsystem_description,
    ];
}

function rpgsystem_action_handler(&$actions)
{
    $actions['rpgsystem'] = [
        'active' => 'rpgsystem',
        'file' => 'index',
    ];
    $mods = [
        'charactercreation', 'charactersheet', 'attributes', 'items', 'inventory',
        'currency', 'shop', 'crafting', 'loot', 'bestiary', 'battle', 'scenes',
        'quests', 'toolbar', 'counter',
    ];
    foreach ($mods as $mod) {
        $actions['rpgsystem-' . $mod] = [
            'active' => 'rpgsystem',
            'file' => 'index',
        ];
    }
}

function rpgsystem_admin_permissions()
{
    return [
        'overview' => 'Can manage RPG System',
    ];
}

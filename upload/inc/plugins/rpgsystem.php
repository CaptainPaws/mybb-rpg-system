<?php
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function rpgsystem_info(): array
{
    return [
        'name' => 'RPG System',
        'description' => 'Modular RPG system providing items, inventory, currency, store, crafting and more.',
        'website' => 'https://example.com',
        'author' => 'RPG System Team',
        'authorsite' => 'https://example.com',
        'version' => '0.1.0',
        'compatibility' => '18*'
    ];
}

function rpgsystem_install()
{
    global $db;
    if (!$db->table_exists('rpgsystem_modules')) {
        $db->write_query("CREATE TABLE `" . TABLE_PREFIX . "rpgsystem_modules` (
            `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }
}

function rpgsystem_is_installed(): bool
{
    global $db;
    return $db->table_exists('rpgsystem_modules');
}

function rpgsystem_uninstall()
{
    global $db;
    if ($db->table_exists('rpgsystem_modules')) {
        $db->write_query("DROP TABLE `" . TABLE_PREFIX . "rpgsystem_modules`");
    }
}

function rpgsystem_activate()
{
    // Add templates or settings here
}

function rpgsystem_deactivate()
{
    // Remove templates or settings here
}

require_once __DIR__ . '/../../rpgsystem/core.php';

<?php

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

function rpgsystem_info(): array
{
    return [
        'name' => 'RPG System',
        'description' => 'Модульная RPG-система: инвентарь, валюта, крафт и другое.',
        'website' => '',
        'author' => 'CaptainPaws',
        'authorsite' => 'https://github.com/CaptainPaws/',
        'version' => '1.0.0',
        'compatibility' => '18*'
    ];
}

function rpgsystem_install()
{
    global $db;

    if (!$db->table_exists('rpgsystem_modules')) {
        $db->write_query("
            CREATE TABLE " . TABLE_PREFIX . "rpgsystem_modules (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(100) NOT NULL,
                title VARCHAR(255) NOT NULL,
                version VARCHAR(20) DEFAULT '',
                active TINYINT(1) NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    if (!$db->table_exists('rpgsystem_currencies')) {
        $db->write_query("
            CREATE TABLE " . TABLE_PREFIX . "rpgsystem_currencies (
                cid INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                slug VARCHAR(50) NOT NULL DEFAULT '',
                name VARCHAR(100) NOT NULL,
                prefix VARCHAR(16) NOT NULL DEFAULT '',
                suffix VARCHAR(16) NOT NULL DEFAULT '',
                on_register INT NOT NULL DEFAULT 0,
                on_activation INT NOT NULL DEFAULT 0,
                on_application INT NOT NULL DEFAULT 0,
                application_fid INT NOT NULL DEFAULT 0,
                chars_per_coin INT NOT NULL DEFAULT 200,
                allowed_groups TEXT NOT NULL,
                reward_forums TEXT NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } else {
        $fields = [
            'slug' => "VARCHAR(50) NOT NULL DEFAULT ''",
            'on_register' => "INT NOT NULL DEFAULT 0",
            'on_activation' => "INT NOT NULL DEFAULT 0",
            'on_application' => "INT NOT NULL DEFAULT 0",
            'application_fid' => "INT NOT NULL DEFAULT 0",
            'chars_per_coin' => "INT NOT NULL DEFAULT 200",
            'allowed_groups' => "TEXT NOT NULL",
            'reward_forums' => "TEXT NOT NULL"
        ];

        foreach ($fields as $field => $type) {
            if (!$db->field_exists($field, 'rpgsystem_currencies')) {
                $db->write_query("ALTER TABLE " . TABLE_PREFIX . "rpgsystem_currencies ADD `{$field}` {$type};");
            }
        }
    }

    if (!$db->table_exists('rpgsystem_currency_balances')) {
        $db->write_query("
            CREATE TABLE " . TABLE_PREFIX . "rpgsystem_currency_balances (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                uid INT UNSIGNED NOT NULL,
                cid INT UNSIGNED NOT NULL,
                balance INT NOT NULL DEFAULT 0,
                UNIQUE KEY(uid, cid)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    if (!$db->table_exists('rpgsystem_currency_transactions')) {
        $db->write_query("
            CREATE TABLE " . TABLE_PREFIX . "rpgsystem_currency_transactions (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                uid INT UNSIGNED NOT NULL,
                cid INT UNSIGNED NOT NULL,
                amount INT NOT NULL,
                type ENUM('add', 'remove') NOT NULL,
                time INT UNSIGNED NOT NULL,
                comment TEXT
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    }

    // Добавление полей опыта и уровня пользователям
    if (!$db->field_exists('rpg_exp', 'users')) {
        $db->write_query("ALTER TABLE " . TABLE_PREFIX . "users ADD `rpg_exp` INT(10) NOT NULL DEFAULT 0;");
    }

    if (!$db->field_exists('rpg_level', 'users')) {
        $db->write_query("ALTER TABLE " . TABLE_PREFIX . "users ADD `rpg_level` INT(3) NOT NULL DEFAULT 1;");
    }

    // Группа настроек для модуля уровней
    $gid = (int)$db->fetch_field(
        $db->simple_select('settinggroups', 'gid', "name = 'rpg_levels'"),
        'gid'
    );

    if (!$gid) {
        $gid = $db->insert_query('settinggroups', [
            'name' => 'rpg_levels',
            'title' => 'RPG: Уровни',
            'description' => 'Настройки системы уровней RPG',
            'disporder' => 50,
            'isdefault' => 0
        ]);
    }

    $default_settings = [
        ['name' => 'rpg_levels_exp_per_char', 'value' => '0.01', 'title' => 'EXP за символ'],
        ['name' => 'rpg_levels_exp_base', 'value' => '2000', 'title' => 'EXP на 2 уровень'],
        ['name' => 'rpg_levels_exp_step', 'value' => '1000', 'title' => 'Рост EXP на уровень'],
        ['name' => 'rpg_levels_level_cap', 'value' => '50', 'title' => 'Максимальный уровень'],
        ['name' => 'rpg_levels_enabled_forums', 'value' => '', 'title' => 'Форумы, где начисляется EXP'],
    ];

    foreach ($default_settings as $setting) {
        $exists = $db->fetch_field(
            $db->simple_select('settings', 'name', "name = '{$db->escape_string($setting['name'])}'"),
            'name'
        );

        if (!$exists) {
            $insert = [
                'name' => $db->escape_string($setting['name']),
                'title' => $db->escape_string($setting['title']),
                'description' => '',
                'optionscode' => 'text',
                'value' => $db->escape_string($setting['value']),
                'disporder' => 0,
                'gid' => $gid
            ];
            $db->insert_query('settings', $insert);
        }
    }

    rebuild_settings();



    require_once __DIR__ . '/rpgsystem/core.php';
    RPGSystem\Core::getInstance()->scanAndRegisterModules(true);
}

function rpgsystem_activate()
{
    require_once __DIR__ . '/rpgsystem/core.php';
    RPGSystem\Core::getInstance()->scanAndRegisterModules(true);
}

function rpgsystem_deactivate() {}
function rpgsystem_is_installed(): bool
{
    global $db;
    return $db->table_exists('rpgsystem_modules');
}

function rpgsystem_uninstall()
{
    global $db;
    $db->drop_table('rpgsystem_modules');
    $db->drop_table('rpgsystem_currencies');
    $db->drop_table('rpgsystem_currency_balances');
    $db->drop_table('rpgsystem_currency_transactions');
}

// Подключение ядра
require_once __DIR__ . '/rpgsystem/core.php';

global $db, $mybb;

if ($db->table_exists('rpgsystem_modules')) {
    RPGSystem\Core::getInstance()->loadEnabledModules();
}

if (defined('IN_ADMINCP') && $mybb->input['module'] === 'rpgstuff-rpgsystem') {
    RPGSystem\Core::getInstance()->loadEnabledModules();
}

// Хук для фронта
$plugins->add_hook('global_start', 'rpgsystem_force_boot', 1);
$plugins->add_hook('modcp_start', 'rpgsystem_modcp_router');

function rpgsystem_global_init()
{
    if (defined('IN_ADMINCP')) return;
    require_once __DIR__ . '/rpgsystem/core.php';
    RPGSystem\Core::getInstance()->loadEnabledModules();
}

function rpgsystem_modcp_router()
{
    global $mybb, $modcp;

    if ($mybb->input['action'] !== 'rpg_balance') return;

    require_once MYBB_ROOT . 'inc/plugins/rpgsystem/modules/currency/modcpbalance.php';
    $modcp .= \RPGSystem\Modules\currency\modcpbalance::render();

}

function rpgsystem_force_boot()
{
    global $mybb;
    if (!defined('IN_ADMINCP')) {
        require_once __DIR__ . '/rpgsystem/core.php';
        \RPGSystem\Core::getInstance()->loadEnabledModules();
    }
}

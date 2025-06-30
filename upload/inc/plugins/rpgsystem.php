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
        'author' => 'Ханна и Джо',
        'authorsite' => '',
        'version' => '1.0.0',
        'compatibility' => '18*'
    ];
}

function rpgsystem_install()
{
    global $db;

    // Таблица модулей
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

    // Таблица валют
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
        // Расширяем таблицу, если какие-то поля отсутствуют
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

    // Таблица балансов
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

    // Таблица транзакций
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

    // Сканируем и активируем модули
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
//$plugins->add_hook('global_start', 'rpgsystem_global_init');
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

    require_once MYBB_ROOT . 'inc/plugins/rpgsystem/modules/Currency/ModcpBalance.php';

    $modcp .= \RPGSystem\Modules\Currency\ModcpBalance::render();
}

function rpgsystem_force_boot()
{
    global $mybb;

    // Запускаем как можно раньше
    if (!defined('IN_ADMINCP')) {
        require_once __DIR__ . '/rpgsystem/core.php';
        \RPGSystem\Core::getInstance()->loadEnabledModules();
    }
}

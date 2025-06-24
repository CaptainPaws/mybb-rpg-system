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


require_once __DIR__ . '/rpgsystem/core.php';
require_once __DIR__ . '/rpgsystem/modules/CharacterCreation.php';
require_once __DIR__ . '/rpgsystem/modules/CharacterSheet.php';
require_once __DIR__ . '/rpgsystem/modules/Attributes.php';
require_once __DIR__ . '/rpgsystem/modules/Items.php';
require_once __DIR__ . '/rpgsystem/modules/Inventory.php';
require_once __DIR__ . '/rpgsystem/modules/Currency.php';
require_once __DIR__ . '/rpgsystem/modules/Shop.php';
require_once __DIR__ . '/rpgsystem/modules/Crafting.php';
require_once __DIR__ . '/rpgsystem/modules/Loot.php';
require_once __DIR__ . '/rpgsystem/modules/Bestiary.php';
require_once __DIR__ . '/rpgsystem/modules/Battle.php';
require_once __DIR__ . '/rpgsystem/modules/Scenes.php';
require_once __DIR__ . '/rpgsystem/modules/Quests.php';
require_once __DIR__ . '/rpgsystem/modules/Toolbar.php';
require_once __DIR__ . '/rpgsystem/core.php';
require_once __DIR__ . '/rpgsystem/modules/CharacterCreation.php';
require_once __DIR__ . '/rpgsystem/modules/CharacterSheet.php';


use RPGSystem\Core;
use RPGSystem\Modules\CharacterCreation;
use RPGSystem\Modules\CharacterSheet;
use RPGSystem\Modules\Attributes;
use RPGSystem\Modules\Items;
use RPGSystem\Modules\Inventory;
use RPGSystem\Modules\Currency;
use RPGSystem\Modules\Shop;
use RPGSystem\Modules\Crafting;
use RPGSystem\Modules\Loot;
use RPGSystem\Modules\Bestiary;
use RPGSystem\Modules\Battle;
use RPGSystem\Modules\Scenes;
use RPGSystem\Modules\Quests;
use RPGSystem\Modules\Toolbar;


$core = Core::getInstance();
$core->registerModule('character_creation', new CharacterCreation());
$core->registerModule('character_sheet', new CharacterSheet());
$core->registerModule('attributes', new Attributes());
$core->registerModule('items', new Items());
$core->registerModule('inventory', new Inventory());
$core->registerModule('currency', new Currency());
$core->registerModule('shop', new Shop());
$core->registerModule('crafting', new Crafting());
$core->registerModule('loot', new Loot());
$core->registerModule('bestiary', new Bestiary());
$core->registerModule('battle', new Battle());
$core->registerModule('scenes', new Scenes());
$core->registerModule('quests', new Quests());
$core->registerModule('toolbar', new Toolbar());


$plugins->add_hook('admin_home_menu', 'rpgsystem_admin_menu');
$plugins->add_hook('admin_load', 'rpgsystem_admin_page');

function rpgsystem_admin_menu(array &$sub_menu): void
{
    global $lang;
    $sub_menu[] = [
        'id' => 'rpgsystem',
        'title' => $lang->rpgsystem_name,
        'link' => 'index.php?module=rpgsystem'
    ];
}

function rpgsystem_admin_page(): void
{
    global $mybb, $lang, $page;

    if ($mybb->input['module'] !== 'rpgsystem') {
        return;
    }

    $page->add_breadcrumb_item($lang->rpgsystem_name, 'index.php?module=rpgsystem');
    $page->output_header($lang->rpgsystem_name);

    $sub_tabs['overview'] = [
        'title' => $lang->rpgsystem_name,
        'link' => 'index.php?module=rpgsystem',
        'description' => $lang->rpgsystem_description,
    ];

    $page->output_nav_tabs($sub_tabs, 'overview');
    echo '<p>' . $lang->rpgsystem_description . '</p>';
    $page->output_footer();
    exit;
}


require_once __DIR__ . '/../../rpgsystem/core.php';


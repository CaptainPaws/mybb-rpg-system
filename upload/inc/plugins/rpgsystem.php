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

    if (!$db->table_exists('rpgsystem_currencies')) {
        $db->write_query("CREATE TABLE `" . TABLE_PREFIX . "rpgsystem_currencies` (
            `cid` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `prefix` VARCHAR(16) NOT NULL DEFAULT '',
            `suffix` VARCHAR(16) NOT NULL DEFAULT ''
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    if (!$db->table_exists('rpgsystem_currency_balances')) {
        $db->write_query("CREATE TABLE `" . TABLE_PREFIX . "rpgsystem_currency_balances` (
            `uid` INT UNSIGNED NOT NULL,
            `cid` INT UNSIGNED NOT NULL,
            `balance` INT NOT NULL DEFAULT 0,
            PRIMARY KEY(`uid`,`cid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    if (!$db->table_exists('rpgsystem_currency_queue')) {
        $db->write_query("CREATE TABLE `" . TABLE_PREFIX . "rpgsystem_currency_queue` (
            `qid` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `uid` INT UNSIGNED NOT NULL,
            `cid` INT UNSIGNED NOT NULL,
            `amount` INT NOT NULL,
            `reason` VARCHAR(255) NOT NULL DEFAULT '',
            `created` INT UNSIGNED NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    if (!$db->table_exists('rpgsystem_word_counts')) {
        $db->write_query("CREATE TABLE `" . TABLE_PREFIX . "rpgsystem_word_counts` (
            `uid` INT UNSIGNED PRIMARY KEY,
            `chars` INT UNSIGNED NOT NULL DEFAULT 0,
            `words` INT UNSIGNED NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
    }

    // Settings
    $group = [
        'name' => 'rpgsystem_currency',
        'title' => 'RPG System Currency',
        'description' => 'Currency settings',
        'disporder' => 5,
        'isdefault' => 0
    ];
    $db->insert_query('settinggroups', $group);
    $gid = $db->insert_id();

    $settings = [
        [
            'name' => 'rpgsystem_currency_register_amount',
            'title' => 'Amount on register',
            'description' => 'Currency granted on registration',
            'optionscode' => 'text',
            'value' => '0',
            'disporder' => 1,
            'gid' => $gid
        ],
        [
            'name' => 'rpgsystem_currency_activation_amount',
            'title' => 'Amount on activation',
            'description' => 'Currency granted when account is activated',
            'optionscode' => 'text',
            'value' => '0',
            'disporder' => 2,
            'gid' => $gid
        ],
        [
            'name' => 'rpgsystem_currency_thread_amount',
            'title' => 'Amount on new thread',
            'description' => 'Currency granted when creating a thread',
            'optionscode' => 'text',
            'value' => '0',
            'disporder' => 3,
            'gid' => $gid
        ],
        [
            'name' => 'rpgsystem_currency_post_amount',
            'title' => 'Amount on new post',
            'description' => 'Currency granted when creating a post',
            'optionscode' => 'text',
            'value' => '0',
            'disporder' => 4,
            'gid' => $gid
        ],
        [
            'name' => 'rpgsystem_currency_chars_per_coin',
            'title' => 'Characters per coin',
            'description' => 'Characters required to grant one coin',
            'optionscode' => 'text',
            'value' => '200',
            'disporder' => 5,
            'gid' => $gid
        ],
        [
            'name' => 'rpgsystem_currency_forums',
            'title' => 'Forums for char count',
            'description' => 'Comma separated forum IDs',
            'optionscode' => 'text',
            'value' => '',
            'disporder' => 6,
            'gid' => $gid
        ],
        [
            'name' => 'rpgsystem_currency_groups',
            'title' => 'Groups allowed to edit balances',
            'description' => 'Comma separated group IDs',
            'optionscode' => 'text',
            'value' => '',
            'disporder' => 7,
            'gid' => $gid
        ]
    ];
    foreach ($settings as $setting) {
        $db->insert_query('settings', $setting);
    }

    $group = [
        'name' => 'rpgsystem_counter',
        'title' => 'RPG System Counter',
        'description' => 'Text counter settings',
        'disporder' => 6,
        'isdefault' => 0
    ];
    $db->insert_query('settinggroups', $group);
    $gid = $db->insert_id();
    $db->insert_query('settings', [
        'name' => 'rpgsystem_counter_forums',
        'title' => 'Forums for counter',
        'description' => 'Comma separated forum IDs',
        'optionscode' => 'text',
        'value' => '',
        'disporder' => 1,
        'gid' => $gid
    ]);

    $group = [
        'name' => 'rpgsystem_character',
        'title' => 'RPG System Character Creation',
        'description' => 'Character creation settings',
        'disporder' => 7,
        'isdefault' => 0
    ];
    $db->insert_query('settinggroups', $group);
    $gid = $db->insert_id();
    $settings = [
        [
            'name' => 'rpgsystem_character_title',
            'title' => 'Form Title',
            'description' => '',
            'optionscode' => 'text',
            'value' => 'Шаблон анкеты',
            'disporder' => 1,
            'gid' => $gid
        ],
        [
            'name' => 'rpgsystem_character_url',
            'title' => 'Form URL',
            'description' => '',
            'optionscode' => 'text',
            'value' => 'charactercreation.php',
            'disporder' => 2,
            'gid' => $gid
        ],
        [
            'name' => 'rpgsystem_character_fields',
            'title' => 'Profile Fields',
            'description' => 'Comma separated field IDs',
            'optionscode' => 'text',
            'value' => '',
            'disporder' => 3,
            'gid' => $gid
        ]
    ];
    foreach ($settings as $setting) {
        $db->insert_query('settings', $setting);
    }
    rebuild_settings();
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
    if ($db->table_exists('rpgsystem_currencies')) {
        $db->write_query("DROP TABLE `" . TABLE_PREFIX . "rpgsystem_currencies`");
    }
    if ($db->table_exists('rpgsystem_currency_balances')) {
        $db->write_query("DROP TABLE `" . TABLE_PREFIX . "rpgsystem_currency_balances`");
    }
    if ($db->table_exists('rpgsystem_currency_queue')) {
        $db->write_query("DROP TABLE `" . TABLE_PREFIX . "rpgsystem_currency_queue`");
    }
    if ($db->table_exists('rpgsystem_word_counts')) {
        $db->write_query("DROP TABLE `" . TABLE_PREFIX . "rpgsystem_word_counts`");
    }

    $db->delete_query('settinggroups', "name='rpgsystem_currency'");
    $db->delete_query('settings', "name LIKE 'rpgsystem_currency_%'");
    $db->delete_query('settinggroups', "name='rpgsystem_counter'");
    $db->delete_query('settings', "name LIKE 'rpgsystem_counter_%'");
    $db->delete_query('settinggroups', "name='rpgsystem_character'");
    $db->delete_query('settings', "name LIKE 'rpgsystem_character_%'");
    rebuild_settings();
}

function rpgsystem_activate()
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
    find_replace_templatesets('postbit', '#\{\$post\[\'postdate\'\]\}</span>#', '{\$post[\'postdate\']}</span> {\$post[\'rpgcharacter\']} <span class="rpg-count">{\$post[\'rpg_count\']}</span>');
    find_replace_templatesets('newreply', '#</textarea>#', '</textarea><span id="rpg-counter"></span>');
    find_replace_templatesets('newthread', '#</textarea>#', '</textarea><span id="rpg-counter"></span>');
}

function rpgsystem_deactivate()
{
    require_once MYBB_ROOT . 'inc/adminfunctions_templates.php';
    find_replace_templatesets('postbit', '# \{\$post\[\'rpgcharacter\'\]\} <span class="rpg-count">\{\$post\[\'rpg_count\'\]\}</span>#', '', 0);
    find_replace_templatesets('newreply', '#<span id="rpg-counter"></span>#', '', 0);
    find_replace_templatesets('newthread', '#<span id="rpg-counter"></span>#', '', 0);
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
require_once __DIR__ . '/rpgsystem/modules/Counter.php';

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
use RPGSystem\Modules\Counter;

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
$core->registerModule('counter', new Counter());

$plugins->add_hook('admin_home_menu', 'rpgsystem_admin_menu');
$plugins->add_hook('admin_load', 'rpgsystem_admin_page');
$plugins->add_hook('member_do_register_end', 'rpgsystem_currency_register');
$plugins->add_hook('member_activate_account', 'rpgsystem_currency_activation');
$plugins->add_hook('datahandler_post_insert_thread', 'rpgsystem_currency_thread');
$plugins->add_hook('datahandler_post_insert_post', 'rpgsystem_currency_post');
$plugins->add_hook('datahandler_post_insert_post', 'rpgsystem_counter_post');
$plugins->add_hook('newreply_end', 'rpgsystem_counter_form');
$plugins->add_hook('newthread_end', 'rpgsystem_counter_form');
$plugins->add_hook('postbit', 'rpgsystem_counter_postbit');
$plugins->add_hook('postbit', 'rpgsystem_currency_postbit');
$plugins->add_hook('postbit', 'rpgsystem_character_postbit');

function rpgsystem_admin_menu(array &$sub_menu): void
{
    global $lang;
    $sub_menu[] = [
        'id' => 'rpgsystem',
        'title' => $lang->rpgsystem_name,
        'link' => 'index.php?module=rpgsystem'
    ];
    $sub_menu[] = [
        'id' => 'rpgsystem-charactercreation',
        'title' => $lang->rpgsystem_character_creation,
        'link' => 'index.php?module=rpgsystem-charactercreation'
    ];
    $sub_menu[] = [
        'id' => 'rpgsystem-currency',
        'title' => $lang->rpgsystem_currency,
        'link' => 'index.php?module=rpgsystem-currency'
    ];
    $sub_menu[] = [
        'id' => 'rpgsystem-counter',
        'title' => $lang->rpgsystem_counter,
        'link' => 'index.php?module=rpgsystem-counter'
    ];
}

function rpgsystem_admin_page(): void
{
    global $mybb, $lang, $page, $db;


    if ($mybb->input['module'] === 'rpgsystem-charactercreation') {
        $page->add_breadcrumb_item($lang->rpgsystem_character_creation, 'index.php?module=rpgsystem-charactercreation');
        $page->output_header($lang->rpgsystem_character_creation);

        $sub_tabs['charactercreation'] = [
            'title' => $lang->rpgsystem_character_creation,
            'link'  => 'index.php?module=rpgsystem-charactercreation'
        ];

        $page->output_nav_tabs($sub_tabs, 'charactercreation');

        if ($mybb->request_method === 'post') {
            $title = $db->escape_string($mybb->input['title'] ?? '');
            $url = $db->escape_string($mybb->input['url'] ?? '');
            $fields = array_map('intval', $mybb->input['fields'] ?? []);
            $db->update_query('settings', ['value' => $title], "name='rpgsystem_character_title'");
            $db->update_query('settings', ['value' => $url], "name='rpgsystem_character_url'");
            $db->update_query('settings', ['value' => implode(',', $fields)], "name='rpgsystem_character_fields'");
            rebuild_settings();
            flash_message($lang->rpgsystem_saved, 'success');
        }

        $selected = array_filter(array_map('intval', explode(',', $mybb->settings['rpgsystem_character_fields'])));
        $title = $mybb->settings['rpgsystem_character_title'];
        $url = $mybb->settings['rpgsystem_character_url'];

        $form = new Form('index.php?module=rpgsystem-charactercreation', 'post');
        $form_container = new FormContainer($lang->rpgsystem_character_creation);
        $form_container->output_row($lang->rpgsystem_character_title, '', $form->generate_text_box('title', $title), 'title');
        $form_container->output_row($lang->rpgsystem_character_url, '', $form->generate_text_box('url', $url), 'url');

        $fields_html = '';
        $query = $db->simple_select('profilefields', 'fid,name');
        while ($field = $db->fetch_array($query)) {
            $fields_html .= '<label><input type="checkbox" name="fields[]" value="'.$field['fid'].'"'.(in_array((int)$field['fid'], $selected, true) ? ' checked="checked"' : '').'> '.htmlspecialchars_uni($field['name']).'</label><br />';
        }
        $fields_html .= '<br><a href="index.php?module=config-profile_fields">'.$lang->rpgsystem_manage_profilefields.'</a>';
        $form_container->output_row($lang->rpgsystem_character_fields, '', $fields_html, 'fields');
        $form_container->end();
        $buttons[] = $form->generate_submit_button($lang->save);
        $form->output_submit_wrapper($buttons);
        $form->end();

        $page->output_footer();
        exit;
    }

    if ($mybb->input['module'] === 'rpgsystem-currency') {
        $page->add_breadcrumb_item($lang->rpgsystem_currency, 'index.php?module=rpgsystem-currency');
        $page->output_header($lang->rpgsystem_currency);

        $sub_tabs['currency'] = [
            'title' => $lang->rpgsystem_currency,
            'link' => 'index.php?module=rpgsystem-currency'
        ];

        $page->output_nav_tabs($sub_tabs, 'currency');

        if ($mybb->request_method === 'post') {
            $new = [
                'name' => $db->escape_string($mybb->input['name'] ?? ''),
                'prefix' => $db->escape_string($mybb->input['prefix'] ?? ''),
                'suffix' => $db->escape_string($mybb->input['suffix'] ?? '')
            ];
            if ($new['name'] !== '') {
                $db->insert_query('rpgsystem_currencies', $new);
                flash_message('Currency created', 'success');
            }
        }

        $form = new Form('index.php?module=rpgsystem-currency', 'post');
        $form_container = new FormContainer($lang->rpgsystem_currency_add);
        $form_container->output_row($lang->rpgsystem_currency_name, '', $form->generate_text_box('name'), 'name');
        $form_container->output_row($lang->rpgsystem_currency_prefix, '', $form->generate_text_box('prefix'), 'prefix');
        $form_container->output_row($lang->rpgsystem_currency_suffix, '', $form->generate_text_box('suffix'), 'suffix');
        $form_container->end();
        $buttons[] = $form->generate_submit_button($lang->rpgsystem_currency_add);
        $form->output_submit_wrapper($buttons);
        $form->end();

        $table = new Table;
        $table->construct_header($lang->rpgsystem_currency_name);
        $table->construct_header($lang->rpgsystem_currency_prefix);
        $table->construct_header($lang->rpgsystem_currency_suffix);

        $query = $db->simple_select('rpgsystem_currencies');
        while ($cur = $db->fetch_array($query)) {
            $table->construct_cell(htmlspecialchars_uni($cur['name']));
            $table->construct_cell(htmlspecialchars_uni($cur['prefix']));
            $table->construct_cell(htmlspecialchars_uni($cur['suffix']));
            $table->construct_row();
        }

        if ($table->num_rows() > 0) {
            $table->output($lang->rpgsystem_currency);
        }

        $page->output_footer();
        exit;
    }

    if ($mybb->input['module'] === 'rpgsystem-counter') {
        $page->add_breadcrumb_item($lang->rpgsystem_counter, 'index.php?module=rpgsystem-counter');
        $page->output_header($lang->rpgsystem_counter);

        $sub_tabs['counter'] = [
            'title' => $lang->rpgsystem_counter,
            'link' => 'index.php?module=rpgsystem-counter'
        ];

        $page->output_nav_tabs($sub_tabs, 'counter');

        $table = new Table;
        $table->construct_header($lang->username);
        $table->construct_header($lang->rpgsystem_counter_chars);
        $table->construct_header($lang->rpgsystem_counter_words);

        $counter = Core::getInstance()->getModule('counter');
        if ($counter) {
            foreach ($counter->listStats() as $row) {
                $table->construct_cell(htmlspecialchars_uni($row['username']));
                $table->construct_cell((int)$row['chars']);
                $table->construct_cell((int)$row['words']);
                $table->construct_row();
            }
        }

        if ($table->num_rows() > 0) {
            $table->output($lang->rpgsystem_counter);
        }

        $page->output_footer();
        exit;
    }
}

function rpgsystem_currency_register()
{
    global $mybb;
    $amount = (int)$mybb->settings['rpgsystem_currency_register_amount'];
    if ($amount <= 0) {
        return;
    }
    $currency = Core::getInstance()->getModule('currency');
    if ($currency) {
        $currency->addBalance($mybb->user['uid'], 1, $amount);
    }
}

function rpgsystem_currency_activation()
{
    global $mybb;
    $amount = (int)$mybb->settings['rpgsystem_currency_activation_amount'];
    if ($amount <= 0) {
        return;
    }
    $currency = Core::getInstance()->getModule('currency');
    if ($currency) {
        $currency->addBalance($mybb->user['uid'], 1, $amount);
    }
}

function rpgsystem_currency_thread(&$thread)
{
    global $mybb;
    $amount = (int)$mybb->settings['rpgsystem_currency_thread_amount'];
    if ($amount <= 0) {
        return;
    }
    $currency = Core::getInstance()->getModule('currency');
    if ($currency) {
        $currency->addBalance($thread['uid'], 1, $amount);
    }
}

function rpgsystem_currency_post(&$post)
{
    global $mybb;
    $amount = (int)$mybb->settings['rpgsystem_currency_post_amount'];
    $chars_per_coin = max(1, (int)$mybb->settings['rpgsystem_currency_chars_per_coin']);
    $forums = array_filter(array_map('intval', explode(',', $mybb->settings['rpgsystem_currency_forums'])));
    if (!empty($forums) && !in_array((int)$post['fid'], $forums, true)) {
        return;
    }
    $earned = $amount;
    $char_bonus = floor(strlen($post['message']) / $chars_per_coin);
    $earned += $char_bonus;
    if ($earned <= 0) {
        return;
    }
    $currency = Core::getInstance()->getModule('currency');
    if ($currency) {
        $currency->addBalance($post['uid'], 1, $earned);
    }
}

function rpgsystem_counter_post(&$post)
{
    global $mybb;
    $forums = array_filter(array_map('intval', explode(',', $mybb->settings['rpgsystem_counter_forums'])));
    if (!empty($forums) && !in_array((int)$post['fid'], $forums, true)) {
        return;
    }
    $chars = mb_strlen($post['message']);
    preg_match_all('/\S+/u', $post['message'], $m);
    $words = count($m[0]);
    $counter = Core::getInstance()->getModule('counter');
    if ($counter) {
        $counter->addStats($post['uid'], $chars, $words);
    }
}

function rpgsystem_counter_form()
{
    echo '<span id="rpg-counter"></span><script>document.addEventListener("DOMContentLoaded",function(){var t=document.getElementById("message");if(!t)return;var s=document.getElementById("rpg-counter");function u(){var v=t.value;var w=v.trim().split(/\s+/).filter(Boolean).length;s.textContent="Chars: "+v.length+" Words: "+w;}t.addEventListener("input",u);u();});</script>';
}

function rpgsystem_counter_postbit(&$post)
{
    $chars = mb_strlen(strip_tags($post['message']));
    $post['rpg_count'] = $chars;
}

function rpgsystem_currency_postbit(&$post)
{
    $currency = Core::getInstance()->getModule('currency');
    if (!$currency) {
        return;
    }
    $balance = $currency->getBalance((int)$post['uid'], 1);
    $info = $currency->getCurrencyInfo(1) ?? ['prefix' => '', 'suffix' => ''];
    $post['rpgcurrency'] = $info['prefix'] . $balance . $info['suffix'];
}

function rpgsystem_character_postbit(&$post)
{
    global $mybb, $lang;
    if (!isset($lang->rpgsystem_character_button)) {
        $lang->load('rpgsystem');
    }
    $url = $mybb->settings['rpgsystem_character_url'];
    $post['rpgcharacter'] = '<a href="' . htmlspecialchars_uni($url) . '?uid=' . (int)$post['uid'] . '" class="rpg-character-button" target="_blank">' . htmlspecialchars_uni($lang->rpgsystem_character_button) . '</a>';
}


<?php
/**
 * Copyright 2014 MyBB Group, All Rights Reserved
 * @author risuena & little.evil.genius
 */

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

/**
 * @return bool true
 */
function rpgstuff_meta()
{
    global $page, $lang, $plugins;

    $sub_menu = array();
    $sub_menu = $plugins->run_hooks("admin_rpgstuff_menu", $sub_menu);

    $page->add_menu_item($lang->rpg_stuff, "rpgstuff", "index.php?module=rpgstuff", 60, $sub_menu);

    return true;
}

/**
 * @param string $action
 * @return string
 */
function rpgstuff_action_handler($action)
{
    global $page, $lang, $plugins, $db;

    $page->active_module = "rpgstuff";

    // Основные действия
    $actions = [
        'stylesheet_updates' => ['active' => 'stylesheet_updates', 'file' => 'stylesheet_updates.php'],
        'plugin_updates'     => ['active' => 'plugin_updates',     'file' => 'plugin_updates.php'],
        'rpgsystem'          => ['active' => 'rpgsystem',          'file' => 'rpgsystem.php']
    ];

    // 🔄 Автоматическая регистрация активных RPG-модулей
    if ($db->table_exists('rpgsystem_modules')) {
        $query = $db->simple_select('rpgsystem_modules', '*', 'active=1');
        while ($mod = $db->fetch_array($query)) {
            $modname = $mod['name'];
            $modfile = "rpgsystem_{$modname}.php";
            if (file_exists(MYBB_ADMIN_DIR . "modules/rpgstuff/{$modfile}")) {
                $actions["rpgsystem_{$modname}"] = [
                    'active' => "rpgsystem_{$modname}",
                    'file'   => $modfile
                ];
            }
        }
    }

    $actions = $plugins->run_hooks("admin_rpgstuff_action_handler", $actions);

    // Боковое меню основной категории
    $sub_menu = [
        '10' => ["id" => "stylesheet_updates", "title" => $lang->stylesheet_updates, "link" => "index.php?module=rpgstuff-stylesheet_updates"],
        '20' => ["id" => "plugin_updates",     "title" => $lang->plugin_updates,     "link" => "index.php?module=rpgstuff-plugin_updates"]
    ];
    $sub_menu = $plugins->run_hooks("admin_rpgstuff_menu_updates", $sub_menu);

    $sidebar = new SidebarItem($lang->sidebar);
    $sidebar->add_menu_items($sub_menu, $actions[$action]['active'] ?? 'stylesheet_updates');
    $page->sidebar .= $sidebar->get_markup();

    // 📦 RPG System — отдельный блок
    $rpgsystem_menu = [
        'rpgsystem' => [
            'id'    => 'rpgsystem',
            'title' => 'RPG System',
            'link'  => 'index.php?module=rpgstuff-rpgsystem'
        ]
    ];

    if ($db->table_exists('rpgsystem_modules')) {
        $query = $db->simple_select('rpgsystem_modules', '*', 'active=1');
        while ($mod = $db->fetch_array($query)) {
            $modname = $mod['name'];
            $modfile = "rpgsystem_{$modname}.php";
            if (file_exists(MYBB_ADMIN_DIR . "modules/rpgstuff/{$modfile}")) {
                $rpgsystem_menu[$modname] = [
                    'id'    => $modname,
                    'title' => $mod['title'],
                    'link'  => "index.php?module=rpgstuff-rpgsystem_{$modname}"
                ];
            }
        }
    }

    $rpg_sidebar = new SidebarItem('RPG System');
    $rpg_sidebar->add_menu_items($rpgsystem_menu, $actions[$action]['active'] ?? '');
    $page->sidebar .= $rpg_sidebar->get_markup();

    // Возврат нужного файла
    if (isset($actions[$action])) {
        $page->active_action = $actions[$action]['active'];
        return $actions[$action]['file'];
    }

    return "stylesheet_updates.php";
}

/**
 * @return array
 */
function rpgstuff_admin_permissions()
{
    global $lang, $plugins;

    $admin_permissions = [
        "stylesheet_updates" => $lang->can_updates_stylesheet,
        "plugin_updates"     => $lang->can_updates_plugin
    ];

    $admin_permissions = $plugins->run_hooks("admin_rpgstuff_permissions", $admin_permissions);

    return ["name" => $lang->rpg_stuff, "permissions" => $admin_permissions, "disporder" => 60];
}

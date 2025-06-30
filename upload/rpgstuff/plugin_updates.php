<?php
/**
 * RPGStuff admin plugin_updates page.
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item($lang->plugins, "index.php?module=rpgstuff-plugin_updates");

$page->output_header($lang->plugins);

$sub_tabs['update_plugins'] = array(
    'title' => $lang->plugins,
    'link' => "index.php?module=rpgstuff-plugin_updates",
    'description' => $lang->plugins_desc
);
$page->output_nav_tabs($sub_tabs, 'update_plugins');

// Tabelle erstellen
$table = new Table;

// Hook für die Tabelleninhalte hinzufügen
$plugins->run_hooks("admin_rpgstuff_update_plugin", $table);

// Tabelle anzeigen
$table->output($lang->plugins_table);

$page->output_footer();

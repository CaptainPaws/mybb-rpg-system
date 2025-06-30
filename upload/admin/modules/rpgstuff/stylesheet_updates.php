<?php
/**
 * RPGStuff admin stylesheet_updates page.
*/

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item($lang->stylesheets, "index.php?module=rpgstuff-stylesheet_updates");

$page->output_header($lang->stylesheets);

$sub_tabs['update_stylesheet'] = array(
    'title' => $lang->stylesheets,
    'link' => "index.php?module=rpgstuff-stylesheet_updates",
    'description' => $lang->stylesheets_desc
);
$page->output_nav_tabs($sub_tabs, 'update_stylesheet');

// Tabelle erstellen
$table = new Table;

// Hook für die Tabelleninhalte hinzufügen
$plugins->run_hooks("admin_rpgstuff_update_stylesheet", $table);

// Tabelle anzeigen
$table->output($lang->stylesheets_table);

$page->output_footer();

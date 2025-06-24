<?php
if (!defined('IN_MYBB')) {
    die('Direct access not allowed.');
}

$page->add_breadcrumb_item($lang->rpgsystem_name, 'index.php?module=rpgsystem');
$page->output_header($lang->rpgsystem_name);

$sub_tabs['overview'] = [
    'title' => $lang->rpgsystem_name,
    'link' => 'index.php?module=rpgsystem',
    'description' => $lang->rpgsystem_description,
];
$page->output_nav_tabs($sub_tabs, 'overview');

echo '<ul>';
$modules = [
    'charactercreation' => $lang->rpgsystem_character_creation,
    'charactersheet'    => $lang->rpgsystem_character_sheet,
    'attributes'        => 'Attributes',
    'items'             => 'Items',
    'inventory'         => 'Inventory',
    'currency'          => $lang->rpgsystem_currency,
    'shop'              => 'Shop',
    'crafting'          => 'Crafting',
    'loot'              => 'Loot',
    'bestiary'          => 'Bestiary',
    'battle'            => 'Battle',
    'scenes'            => 'Scenes',
    'quests'            => 'Quests',
    'toolbar'           => 'Toolbar',
    'counter'           => $lang->rpgsystem_counter,
];
foreach ($modules as $id => $title) {
    echo '<li><a href="index.php?module=rpgsystem-' . $id . '">' . htmlspecialchars_uni($title) . '</a></li>';
}
echo '</ul>';

$page->output_footer();
exit;

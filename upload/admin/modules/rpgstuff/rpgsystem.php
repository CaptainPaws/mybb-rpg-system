<?php

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

$lang->load("rpgsystem");

// 🔥 Загрузка активных модулей
require_once MYBB_ROOT . 'inc/plugins/rpgsystem/core.php';
$core = RPGSystem\Core::getInstance();
$core->loadEnabledModules();

$page->add_breadcrumb_item("RPG System", "index.php?module=rpgstuff-rpgsystem");
$page->output_header("RPG System");

$sub_tabs['overview'] = [
    'title' => "RPG System",
    'link' => "index.php?module=rpgstuff-rpgsystem",
    'description' => "Управление RPG-модулями: включение, отключение, переход к настройкам"
];

$page->output_nav_tabs($sub_tabs, 'overview');

// 🔄 Обработка включения / отключения
if ($mybb->request_method === 'post' && verify_post_check($mybb->input['my_post_key'])) {
    $name = $db->escape_string($mybb->input['modname'] ?? '');
    $action = $mybb->input['modaction'] ?? '';

    if ($name && in_array($action, ['enable', 'disable'])) {
        $db->update_query('rpgsystem_modules', [
            'active' => $action === 'enable' ? 1 : 0
        ], "name='{$name}'");

        flash_message("Модуль '{$name}' " . ($action === 'enable' ? 'включён' : 'отключён'), 'success');
        admin_redirect("index.php?module=rpgstuff-rpgsystem");
    }
}

// 🔘 Боковое меню активных модулей
$rpg_submenu = [];
$query = $db->simple_select('rpgsystem_modules', '*', 'active=1');
while ($mod = $db->fetch_array($query)) {
    $modname = $mod['name'];
    $modfile = MYBB_ADMIN_DIR . "modules/rpgstuff/rpgsystem_{$modname}.php";
    if (file_exists($modfile)) {
        $rpg_submenu[$modname] = [
            'title' => $mod['title'],
            'link' => "index.php?module=rpgstuff-rpgsystem_{$modname}"
        ];
    }
}

if (!empty($rpg_submenu)) {
    echo '<div style="float: left; width: 20%; padding-right: 2%;">';
    echo '<fieldset><legend><strong>Активные модули</strong></legend><ul style="margin-top: 6px;">';
    foreach ($rpg_submenu as $mod) {
        echo '<li><a href="' . $mod['link'] . '">' . htmlspecialchars_uni($mod['title']) . '</a></li>';
    }
    echo '</ul></fieldset>';
    echo '</div>';
    echo '<div style="float: right; width: 77%;">';
} else {
    echo '<div>';
}

// 🧩 Таблица модулей
$table = new Table;
$table->construct_header("Название модуля");
$table->construct_header("Версия", ["width" => "10%"]);
$table->construct_header("Статус", ["width" => "10%"]);
$table->construct_header("Действие", ["width" => "15%"]);
$table->construct_header("Настройки", ["width" => "15%"]);

$query = $db->simple_select('rpgsystem_modules');
while ($mod = $db->fetch_array($query)) {
    $table->construct_cell(htmlspecialchars_uni($mod['title']));
    $table->construct_cell(htmlspecialchars_uni($mod['version']));
    $table->construct_cell($mod['active'] ? '<strong style="color:green;">Включён</strong>' : '<span style="color:gray;">Отключён</span>');

    // 🧨 Одна форма = одна кнопка в таблице
    ob_start();
    $form = new Form("index.php?module=rpgstuff-rpgsystem", "post");
    echo $form->generate_hidden_field("my_post_key", $mybb->post_code);
    echo $form->generate_hidden_field("modname", $mod['name']);
    echo $form->generate_hidden_field("modaction", $mod['active'] ? 'disable' : 'enable');
    echo $form->generate_submit_button($mod['active'] ? 'Отключить' : 'Включить');
    $form->end();
    $table->construct_cell(ob_get_clean());

    // Ссылка на модуль в ACP
    $modfile = "rpgsystem_" . $mod['name'] . ".php";
    if (file_exists(MYBB_ADMIN_DIR . "modules/rpgstuff/" . $modfile)) {
        $table->construct_cell('<a href="index.php?module=rpgstuff-' . $modfile . '" class="button">Перейти</a>');
    } else {
        $table->construct_cell('<span style="color:#aaa;">Недоступно</span>');
    }

    $table->construct_row();
}

$table->output("Установленные модули RPG System");

echo '</div>';
$page->output_footer();

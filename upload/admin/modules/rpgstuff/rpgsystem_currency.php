<?php

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

global $lang;
if (!isset($lang->rpgsystem)) {
    $lang->load('rpgsystem');
}

$action = $mybb->get_input('action');

$sub_tabs = [
    'currency' => [
        'title' => "Валюта",
        'link' => "index.php?module=rpgstuff-rpgsystem_currency",
        'description' => "Управление валютами форума"
    ],
    'transactions' => [
        'title' => "Транзакции",
        'link' => "index.php?module=rpgstuff-rpgsystem_currency&action=transactions",
        'description' => "История начислений и списаний валюты"
    ]
];

// ▼ Вкладка ТРАНЗАКЦИИ
if ($action === 'transactions') {
    $page->add_breadcrumb_item("RPG System", "index.php?module=rpgstuff-rpgsystem");
    $page->add_breadcrumb_item("Валюта", "index.php?module=rpgstuff-rpgsystem_currency");
    $page->add_breadcrumb_item("Транзакции", "index.php?module=rpgstuff-rpgsystem_currency&action=transactions");

    $page->output_header("RPG System — Транзакции");
    $page->output_nav_tabs($sub_tabs, 'transactions');

    require __DIR__ . '/rpgsystem_currency_transactions.php';

    $page->output_footer();
    exit;
}

// ▼ Вкладка ВАЛЮТА
$page->add_breadcrumb_item("RPG System", "index.php?module=rpgstuff-rpgsystem");
$page->add_breadcrumb_item("Валюта", "index.php?module=rpgstuff-rpgsystem_currency");

$page->output_header("RPG System — Валюта");
$page->output_nav_tabs($sub_tabs, 'currency');

$edit_id = (int)($mybb->input['edit'] ?? 0);

// Сохранение валюты
if ($mybb->request_method === 'post' && verify_post_check($mybb->input['my_post_key'])) {
    $data = [
        'name'   => $db->escape_string($mybb->input['name'] ?? ''),
        'slug'   => $db->escape_string($mybb->input['slug'] ?? ''),
        'prefix' => $db->escape_string($mybb->input['prefix'] ?? ''),
        'suffix' => $db->escape_string($mybb->input['suffix'] ?? ''),
        'on_register' => (int)($mybb->input['on_register'] ?? 0),
        'on_activation' => (int)($mybb->input['on_activation'] ?? 0),
        'on_application' => (int)($mybb->input['on_application'] ?? 0),
        'application_fid' => (int)($mybb->input['application_fid'] ?? 0),
        'chars_per_coin' => (int)($mybb->input['chars_per_coin'] ?? 200),
        'allowed_groups' => $db->escape_string(implode(',', $mybb->input['allowed_groups'] ?? [])),
        'reward_forums' => $db->escape_string(implode(',', $mybb->input['reward_forums'] ?? [])),
    ];

    if (!$data['name'] || !$data['slug']) {
        flash_message("Введите название и ID валюты", 'error');
    } else {
        $exists = $db->fetch_field(
            $db->simple_select('rpgsystem_currencies', 'cid', "slug='{$data['slug']}'" . ($edit_id ? " AND cid != {$edit_id}" : '')),
            'cid'
        );

        if ($exists) {
            flash_message("Такой ID валюты уже используется", 'error');
        } else {
            if ($edit_id) {
                $db->update_query('rpgsystem_currencies', $data, "cid={$edit_id}");
                flash_message("Валюта обновлена", 'success');
            } else {
                $db->insert_query('rpgsystem_currencies', $data);
                flash_message("Валюта добавлена", 'success');
            }

            admin_redirect("index.php?module=rpgstuff-rpgsystem_currency");
        }
    }
}

// Таблица валют
$table = new Table;
$table->construct_header("Название");
$table->construct_header("ID (slug)");
$table->construct_header("Префикс");
$table->construct_header("Суффикс");
$table->construct_header("Управление", ["class" => "align_center", "width" => "10%"]);

$query = $db->simple_select('rpgsystem_currencies', '*', '', ['order_by' => 'name']);
while ($row = $db->fetch_array($query)) {
    $slug = htmlspecialchars_uni($row['slug']);
    $table->construct_cell(htmlspecialchars_uni($row['name']));
    $table->construct_cell("<code>{\$post['rpg_currency_{$slug}']}</code>");
    $table->construct_cell(htmlspecialchars_uni($row['prefix']));
    $table->construct_cell(htmlspecialchars_uni($row['suffix']));
    $table->construct_cell("<a href=\"index.php?module=rpgstuff-rpgsystem_currency&edit={$row['cid']}\" class=\"button small_button\">Редактировать</a>");
    $table->construct_row();
}
$table->output("Список валют");

// Форма добавления/редактирования
$form = new Form("index.php?module=rpgstuff-rpgsystem_currency" . ($edit_id ? "&edit={$edit_id}" : ''), "post");
echo $form->generate_hidden_field("my_post_key", $mybb->post_code);

if ($edit_id) {
    $currency = $db->fetch_array($db->simple_select('rpgsystem_currencies', '*', "cid={$edit_id}"));
    $form_title = "Редактировать валюту: " . htmlspecialchars_uni($currency['name']);
} else {
    $currency = [
        'name' => '', 'slug' => '', 'prefix' => '', 'suffix' => '',
        'on_register' => 0, 'on_activation' => 0,
        'on_application' => 0, 'application_fid' => 0,
        'chars_per_coin' => 200,
        'allowed_groups' => '',
        'reward_forums' => ''
    ];
    $form_title = "Создать новую валюту";
}

$form_container = new FormContainer($form_title);

$form_container->output_row("Название", "", $form->generate_text_box("name", $currency['name'], ['style' => 'width: 200px']), 'name');
$form_container->output_row("ID (slug)", "Только латиница. Используется для шаблонов.", $form->generate_text_box("slug", $currency['slug'], ['style' => 'width: 200px']), 'slug');
$form_container->output_row("Префикс", "", $form->generate_text_box("prefix", $currency['prefix'], ['style' => 'width: 100px']), 'prefix');
$form_container->output_row("Суффикс", "", $form->generate_text_box("suffix", $currency['suffix'], ['style' => 'width: 100px']), 'suffix');

$form_container->output_row("Начисление при регистрации", "Сколько валюты начисляется при регистрации.", $form->generate_text_box("on_register", $currency['on_register'], ['style' => 'width: 100px']), 'on_register');
$form_container->output_row("Начисление при активации", "Сколько валюты начисляется при активации аккаунта.", $form->generate_text_box("on_activation", $currency['on_activation'], ['style' => 'width: 100px']), 'on_activation');
$form_container->output_row("Начисление за анкету", "Сколько валюты даётся за создание темы в выбранном разделе.", $form->generate_text_box("on_application", $currency['on_application'], ['style' => 'width: 100px']), 'on_application');
$form_container->output_row("ID форума анкет", "Укажите ID форума, где создаются анкеты.", $form->generate_text_box("application_fid", $currency['application_fid'], ['style' => 'width: 100px']), 'application_fid');

$form_container->output_row("1 монета за ... символов", "Коэффициент начисления валюты за посты. Например, 1 монета за 200 символов.", $form->generate_text_box("chars_per_coin", $currency['chars_per_coin'], ['style' => 'width: 100px']), 'chars_per_coin');

// Группы
$group_options = [];
$query = $db->simple_select("usergroups", "gid, title", "", ["order_by" => "title"]);
while ($group = $db->fetch_array($query)) {
    $group_options[$group['gid']] = $group['title'];
}
$selected_groups = explode(',', $currency['allowed_groups']);
$form_container->output_row("Группы, которые могут вручную редактировать баланс", "", $form->generate_select_box("allowed_groups[]", $group_options, $selected_groups, ["multiple" => true, "size" => 5]), 'allowed_groups');

// Форумы
$forum_options = [];
$query = $db->simple_select("forums", "fid, name", "type = 'f'", ["order_by" => "pid, disporder"]);
while ($forum = $db->fetch_array($query)) {
    $forum_options[$forum['fid']] = $forum['name'];
}
$selected_forums = explode(',', $currency['reward_forums']);
$form_container->output_row("Форумы для начисления за посты", "", $form->generate_select_box("reward_forums[]", $forum_options, $selected_forums, ["multiple" => true, "size" => 5]), 'reward_forums');

$form_container->end();

$buttons = [$form->generate_submit_button($edit_id ? "Сохранить изменения" : "Добавить валюту")];
$form->output_submit_wrapper($buttons);
$form->end();

$page->output_footer();

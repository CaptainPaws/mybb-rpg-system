<?php

if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}

$lang->load('rpgsystem');

$sub_tabs['currency'] = [
    'title' => "Валюта",
    'link' => "index.php?module=rpgstuff-rpgsystem_currency",
];
$sub_tabs['transactions'] = [
    'title' => "Транзакции",
    'link' => "index.php?module=rpgstuff-rpgsystem_currency&action=transactions",
    'description' => "История операций с балансом"
];


// постраничность
$perpage = 20;
$page_num = max((int)$mybb->input['page'], 1);
$start = ($page_num - 1) * $perpage;

$total = $db->fetch_field($db->simple_select('rpgsystem_currency_transactions', 'COUNT(*) as total'), 'total');
$query = $db->query("
    SELECT t.*, u.username
    FROM " . TABLE_PREFIX . "rpgsystem_currency_transactions t
    LEFT JOIN " . TABLE_PREFIX . "users u ON u.uid = t.uid
    ORDER BY t.time DESC
    LIMIT {$start}, {$perpage}
");

$table = new Table;
$table->construct_header("Пользователь");
$table->construct_header("Сумма", ["width" => "10%"]);
$table->construct_header("Тип", ["width" => "10%"]);
$table->construct_header("Дата", ["width" => "20%"]);

while ($row = $db->fetch_array($query)) {
    $user = build_profile_link(htmlspecialchars_uni($row['username']), $row['uid']);
    $table->construct_cell($user);
    $table->construct_cell(($row['type'] === 'add' ? '+' : '-') . (int)$row['amount']);
    $table->construct_cell($row['type'] === 'add' ? 'Начисление' : 'Списание');
    $table->construct_cell(my_date('relative', $row['time']));
    $table->construct_row();
}

$table->output("Журнал транзакций");

echo multipage($total, $perpage, $page_num, "index.php?module=rpgstuff-rpgsystem_currency&action=transactions");

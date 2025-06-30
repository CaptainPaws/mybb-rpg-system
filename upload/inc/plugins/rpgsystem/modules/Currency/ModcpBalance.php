<?php
namespace RPGSystem\Modules\Currency;

class ModcpBalance
{
    public static function render(): string
    {
        global $mybb, $db;

        // Проверка доступа
        $currency = $db->fetch_array($db->simple_select('rpgsystem_currencies', '*', '', ['limit' => 1]));
        if (!$currency) {
            error('Валюта не найдена');
        }

        $cid = (int)$currency['cid'];
        $gid = (int)$mybb->user['usergroup'];
        $allowed = array_map('intval', explode(',', $currency['allowed_groups']));

        if (!in_array($gid, $allowed)) {
            error_no_permission();
        }

        // Обработка формы
        if ($mybb->request_method === 'post' && verify_post_check($mybb->input['my_post_key'])) {
            $target_uid = (int)$mybb->get_input('edit_uid');
            $amount = (int)$mybb->get_input('edit_amount');
            $mode = $mybb->get_input('edit_mode');

            if ($target_uid && $amount > 0) {
                $existing = $db->fetch_field(
                    $db->simple_select("rpgsystem_currency_balances", "balance", "uid={$target_uid} AND cid={$cid}"),
                    "balance"
                );

                if ($existing === null) {
                    $db->insert_query("rpgsystem_currency_balances", [
                        "uid" => $target_uid,
                        "cid" => $cid,
                        "balance" => 0
                    ]);
                    $existing = 0;
                }

                $new = $mode === 'remove' ? max(0, $existing - $amount) : $existing + $amount;

                $db->update_query("rpgsystem_currency_balances", [
                    "balance" => $new
                ], "uid={$target_uid} AND cid={$cid}");

                $db->insert_query("rpgsystem_currency_transactions", [
                    "uid" => $target_uid,
                    "cid" => $cid,
                    "amount" => $amount,
                    "type" => $mode,
                    "time" => TIME_NOW,
                    "comment" => $db->escape_string("Корректировка модератором UID {$mybb->user['uid']}")
                ]);

                header("Location: modcp.php?action=rpg_balance&balance_updated=1");
                exit;
            }
        }

        add_breadcrumb('Модераторский раздел', 'modcp.php');
        add_breadcrumb('Корректировка баланса', 'modcp.php?action=rpg_balance');

        // Постраничный вывод
        $per_page = 20;
        $page_num = max((int)$mybb->get_input('page'), 1);
        $start = ($page_num - 1) * $per_page;

        $total = $db->fetch_field(
            $db->query("SELECT COUNT(*) AS total FROM " . TABLE_PREFIX . "users"),
            'total'
        );

        $query = $db->query("
            SELECT u.uid, u.username, b.balance
            FROM " . TABLE_PREFIX . "users u
            LEFT JOIN " . TABLE_PREFIX . "rpgsystem_currency_balances b
              ON u.uid = b.uid AND b.cid = {$cid}
            ORDER BY u.username ASC
            LIMIT {$start}, {$per_page}
        ");

        // Таблица
        $output = "<br /><div class=\"thead\">Редактирование баланса</div>";

        // Уведомление после сохранения
        if ((int)($mybb->input['balance_updated'] ?? 0) === 1) {
            $output .= "<div class=\"success\">Баланс успешно обновлён.</div><br />";
        }

        $output .= "<form method=\"post\" action=\"modcp.php?action=rpg_balance\">";
        $output .= "<input type=\"hidden\" name=\"my_post_key\" value=\"{$mybb->post_code}\" />";
        $output .= "<table border=\"0\" cellspacing=\"1\" cellpadding=\"4\" class=\"tborder\" style=\"width: 100%;\">
            <thead>
                <tr>
                    <td class=\"thead\">Пользователь</td>
                    <td class=\"thead\">Баланс</td>
                    <td class=\"thead\" style=\"text-align:center;\">Управление</td>
                </tr>
            </thead>
            <tbody>";

        $row_count = 0;
        while ($row = $db->fetch_array($query)) {
            $uid = (int)$row['uid'];
            $username = htmlspecialchars_uni($row['username']);
            $balance = (int)$row['balance'];
            $rowclass = ($row_count++ % 2 == 0) ? "trow1" : "trow2";

            $output .= "<tr>
                <td class=\"{$rowclass}\"><a href=\"member.php?action=profile&uid={$uid}\" target=\"_blank\">{$username}</a></td>
                <td class=\"{$rowclass}\">{$currency['prefix']}{$balance}{$currency['suffix']}</td>
                <td class=\"{$rowclass}\" style=\"text-align:center;\">
                    <button type=\"button\" class=\"button edit-button\" data-uid=\"{$uid}\" data-username=\"{$username}\">Редактировать</button>
                </td>
            </tr>";
        }

        $output .= "</tbody></table></form>";

        $pagination = multipage($total, $per_page, $page_num, "modcp.php?action=rpg_balance");
        $output .= $pagination;

        // Модальное окно
        $output .= <<<HTML
<div id="balanceModal" style="display:none; position: fixed; top: 20%; left: 50%; transform: translate(-50%, 0); background: #fff; border: 2px solid #333; padding: 20px; z-index: 9999; width: 400px;" class="modal tborder">
    <div class="thead">Корректировка баланса: <span id="modalUsername"></span></div>
    <form method="post" action="modcp.php?action=rpg_balance">
        <input type="hidden" name="my_post_key" value="{$mybb->post_code}">
        <input type="hidden" name="edit_uid" id="edit_uid" value="">
        <br />
        <label>Сумма:</label><br />
        <input type="number" name="edit_amount" style="width: 100%;" required><br /><br />
        <label>Действие:</label><br />
        <input type="radio" name="edit_mode" value="add" checked> Начислить
        <input type="radio" name="edit_mode" value="remove"> Списать
        <br /><br />
        <input type="submit" value="Сохранить" class="button">
        <button type="button" onclick="document.getElementById('balanceModal').style.display='none';" class="button">Отмена</button>
    </form>
</div>
<script>
    document.querySelectorAll('.edit-button').forEach(btn => {
        btn.addEventListener('click', () => {
            document.getElementById('edit_uid').value = btn.dataset.uid;
            document.getElementById('modalUsername').innerText = btn.dataset.username;
            document.getElementById('balanceModal').style.display = 'block';
        });
    });
</script>
HTML;

        output_page($output);
        exit;
    }
}

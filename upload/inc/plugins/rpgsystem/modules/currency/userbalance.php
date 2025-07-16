<?php

namespace RPGSystem\Modules\currency;

use MyBB;

class userbalance
{
    public static array $queued_uids = [];
    public static array $balances = [];
    public static bool $loaded = false;

    /**
     * Собираем UID всех пользователей на странице
     */
    public static function queueUser(int $uid): void
    {
        if (!in_array($uid, self::$queued_uids, true)) {
            self::$queued_uids[] = $uid;
        }
    }

    /**
     * Загрузка балансов всех собранных UID
     */
    public static function loadBalances(): void
    {
        global $db;

        if (self::$loaded || empty(self::$queued_uids)) {
            return;
        }

        $uids = implode(',', array_map('intval', self::$queued_uids));
        self::$balances = [];

        // Получаем все валюты
        $currencies = [];
        $query = $db->simple_select('rpgsystem_currencies');
        while ($currency = $db->fetch_array($query)) {
            $currencies[$currency['cid']] = $currency;
        }

        // Получаем балансы
        $balance_query = $db->query("
            SELECT uid, cid, balance
            FROM " . TABLE_PREFIX . "rpgsystem_currency_balances
            WHERE uid IN ($uids)
        ");

        while ($row = $db->fetch_array($balance_query)) {
            $uid = (int)$row['uid'];
            $cid = (int)$row['cid'];
            $balance = (int)$row['balance'];

            if (!isset(self::$balances[$uid])) {
                self::$balances[$uid] = [];
            }

            if (isset($currencies[$cid])) {
                $currency = $currencies[$cid];
                $slug = $currency['slug'];
                $formatted = $currency['prefix'] . $balance . $currency['suffix'];

                self::$balances[$uid][$slug] = [
                    'name' => $currency['name'],
                    'formatted' => htmlspecialchars_uni($formatted)
                ];
            }
        }

        self::$loaded = true;
    }

    /**
     * Получение отформатированной строки для postbit
     */
    public static function renderForUser(int $uid): string
    {
        if (!self::$loaded || !isset(self::$balances[$uid])) {
            return '';
        }

        $html = '';
        foreach (self::$balances[$uid] as $slug => $data) {
            $html .= "<div class=\"rpgcurrency\">{$data['name']}: {$data['formatted']}</div>";
        }

        return $html;
    }
}

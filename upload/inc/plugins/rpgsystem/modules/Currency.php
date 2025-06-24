<?php
namespace RPGSystem\Modules;

class Currency
{
    /**
     * Текущий баланс пользователя в выбранной валюте
     */
    public function getBalance(int $uid, int $cid = 1): int
    {
        global $db;

        $row = $db->fetch_array(
            $db->simple_select('rpgsystem_currency_balances', 'balance', 'uid='.(int)$uid.' AND cid='.(int)$cid)
        );

        return (int)($row['balance'] ?? 0);
    }

    /**
     * Изменить баланс (положительная или отрицательная сумма)
     */
    public function addBalance(int $uid, int $cid, int $amount): void
    {
        global $db;

        $current = $this->getBalance($uid, $cid);

        if ($current === 0) {
            $db->insert_query('rpgsystem_currency_balances', [
                'uid'     => (int)$uid,
                'cid'     => (int)$cid,
                'balance' => $amount
            ]);
        } else {
            $db->update_query(
                'rpgsystem_currency_balances',
                ['balance' => $current + $amount],
                'uid='.(int)$uid.' AND cid='.(int)$cid
            );
        }
    }

    /**
     * Списать средства
     */
    public function subtractBalance(int $uid, int $cid, int $amount): void
    {
        $this->addBalance($uid, $cid, -$amount);
    }

    /**
     * Создать новую валюту и вернуть её ID
     */
    public function createCurrency(string $name, string $prefix = '', string $suffix = ''): int
    {
        global $db;

        $db->insert_query('rpgsystem_currencies', [
            'name'   => $db->escape_string($name),
            'prefix' => $db->escape_string($prefix),
            'suffix' => $db->escape_string($suffix)
        ]);

        return (int)$db->insert_id();
    }

    /**
     * Список всех валют
     *
     * @return array<int, array>
     */
    public function listCurrencies(): array
    {
        global $db;

        $list  = [];
        $query = $db->simple_select('rpgsystem_currencies');

        while ($row = $db->fetch_array($query)) {
            $list[] = $row;
        }

        return $list;
    }

    /**
     * Подробности валюты
     */
    public function getCurrencyInfo(int $cid): ?array
    {
        global $db;

        $row = $db->fetch_array(
            $db->simple_select('rpgsystem_currencies', '*', 'cid='.(int)$cid)
        );

        return $row ?: null;
    }
}

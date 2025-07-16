<?php

namespace RPGSystem\Modules;
if (!defined('IN_MYBB')) {
    die('Direct initialization of this file is not allowed.');
}
use Core;

class currency
{
    public function registerHooks(): void
    {
        global $plugins;
        

        $plugins->add_hook('member_do_register_end', [$this, 'onRegister']);
        $plugins->add_hook('member_activate_accountactivated', [$this, 'onActivate']);
        $plugins->add_hook('datahandler_post_insert_thread', [$this, 'onThread']);
        $plugins->add_hook('datahandler_post_insert_post', [$this, 'onPost']);
        $plugins->add_hook('postbit', [$this, 'postbit_replace_currency']);
        $plugins->add_hook('modcp_nav', [$this, 'addModcpNav']);
        $plugins->add_hook('postbit', [$this, 'postbit_collect']);
        $plugins->add_hook('global_end', [$this, 'load_all_balances']);
    }

    public function postbit_collect(array &$post): void
    {
        $log = '[HOOK] postbit_collect called for UID: ' . ($post['uid'] ?? 'нет UID') . PHP_EOL;
        file_put_contents(MYBB_ROOT . 'cache/postbit_debug.log', $log, FILE_APPEND);

        if (empty($post['uid'])) {
            return;
        }

        require_once __DIR__ . '/userbalance.php';
        \RPGSystem\Modules\currency\userbalance::queueUser((int)$post['uid']);

        \RPGSystem\Modules\currency\userbalance::loadBalances();
        $post['rpg_currency_display'] = \RPGSystem\Modules\currency\userbalance::renderForUser((int)$post['uid']);
    }



    public function load_all_balances(): void
    {
        require_once __DIR__ . '/userbalance.php';
        \RPGSystem\Modules\currency\userbalance::loadBalances();

        global $postbits;
        if (is_array($postbits)) {
            foreach ($postbits as &$postbit) {
                if (isset($postbit['uid'])) {
                    $uid = (int)$postbit['uid'];
                    $postbit['rpg_currency_display'] = \RPGSystem\Modules\currency\userbalance::renderForUser($uid);
                }
            }
        }
    }

    public function onRegister(): void
    {
        global $user_info;
        if (!isset($user_info['uid'])) return;

        $this->rewardUser((int)$user_info['uid'], 'on_register');
    }

    public function onActivate(): void
    {
        global $mybb;
        if (!isset($mybb->user['uid'])) return;

        $this->rewardUser((int)$mybb->user['uid'], 'on_activation');
    }

    public function onThread(array &$thread): void
    {
        global $db;

        $uid = (int)($thread['uid'] ?? 0);
        $fid = (int)($thread['fid'] ?? 0);
        if (!$uid || !$fid) return;

        $query = $db->simple_select('rpgsystem_currencies');
        while ($currency = $db->fetch_array($query)) {
            if ((int)$currency['application_fid'] === $fid) {
                $this->addBalance($uid, $currency['cid'], (int)$currency['on_application'], 'Создание анкеты');
            }
        }
    }

    public function onPost(\PostDataHandler $posthandler): void
    {
        global $db;

        $post = $posthandler->data;

        $uid = (int)($post['uid'] ?? 0);
        $fid = (int)($post['fid'] ?? 0);
        $message = $post['message'] ?? '';

        if (!$uid || !$fid || !$message) return;

        $query = $db->simple_select('rpgsystem_currencies');
        while ($currency = $db->fetch_array($query)) {
            $allowed_fids = array_map('intval', explode(',', $currency['reward_forums']));
            if (!in_array($fid, $allowed_fids)) continue;

            $chars = mb_strlen(strip_tags($message));
            $rate = max((int)$currency['chars_per_coin'], 1);
            $coins = (int) floor($chars / $rate);
            if ($coins > 0) {
                $this->addBalance($uid, $currency['cid'], $coins, "Пост ({$chars} символов)");
            }
        }
    }


    public function postbit_replace_currency(array &$post): void
    {
        global $db;

        $uid = (int)($post['uid'] ?? 0);
        if (!$uid) return;

        $post['rpg_currency_display'] = "<div style='color:green;'>[UID={$uid}]</div>";

        $query = $db->simple_select('rpgsystem_currencies');
        while ($currency = $db->fetch_array($query)) {
            $cid = (int)$currency['cid'];
            $slug = $currency['slug'];

            $balance = $db->fetch_field(
                $db->simple_select('rpgsystem_currency_balances', 'balance', "uid={$uid} AND cid={$cid}"),
                'balance'
            );

            if ($balance === null) {
                $balance = 0;
            }

            $formatted = $currency['prefix'] . $balance . $currency['suffix'];
            $post["rpg_currency_{$slug}"] = htmlspecialchars_uni($formatted);

            $post['rpg_currency_display'] .= "<div class=\"rpgcurrency\">{$currency['name']}: {$formatted}</div>";
        }
    }





    public function addBalance(int $uid, int $cid, int $amount, string $comment = ''): void
    {
        global $db;

        if ($amount === 0) return;

        $current = $db->fetch_field(
            $db->simple_select('rpgsystem_currency_balances', 'balance', "uid={$uid} AND cid={$cid}"),
            'balance'
        );

        if ($current === null) {
            $db->insert_query('rpgsystem_currency_balances', [
                'uid' => $uid,
                'cid' => $cid,
                'balance' => $amount
            ]);
        } else {
            $db->update_query('rpgsystem_currency_balances', [
                'balance' => $current + $amount
            ], "uid={$uid} AND cid={$cid}");
        }

        $db->insert_query('rpgsystem_currency_transactions', [
            'uid' => $uid,
            'cid' => $cid,
            'amount' => abs($amount),
            'type' => $amount > 0 ? 'add' : 'remove',
            'time' => TIME_NOW,
            'comment' => $db->escape_string($comment)
        ]);
    }

    private function rewardUser(int $uid, string $field): void
    {
        global $db;
        $query = $db->simple_select('rpgsystem_currencies');
        while ($currency = $db->fetch_array($query)) {
            $value = (int)($currency[$field] ?? 0);
            if ($value > 0) {
                $this->addBalance($uid, $currency['cid'], $value, $this->fieldLabel($field));
            }
        }
    }

    private function fieldLabel(string $field): string
    {
        return match ($field) {
            'on_register' => 'Регистрация',
            'on_activation' => 'Активация учётной записи',
            'on_application' => 'Создание анкеты',
            default => ucfirst($field)
        };
    }

    public function addModcpNav()
    {
        global $modcp_nav;

        $modcp_nav['rpg_balance'] = [
            'title' => 'Корректировка баланса',
            'link'  => 'modcp.php?action=rpg_balance'
        ];
    }
}



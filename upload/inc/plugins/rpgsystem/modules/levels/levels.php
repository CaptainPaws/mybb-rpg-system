<?php

namespace RPGSystem\Modules\levels;

require_once __DIR__ . '/../../core.php';

use RPGSystem\Core\Module;

class levels
{
    public function onPost($post)
    {
        global $mybb, $db;

        $settings = $this->getSettings();
        $fid = (int)$post['fid'];

        if (!in_array($fid, explode(',', $settings['enabled_forums']))) {
            return;
        }

        $message = $post['message'];
        $charCount = mb_strlen(strip_tags($message));
        $expGain = round($charCount * (float)$settings['exp_per_char']);

        if ($expGain <= 0) {
            return;
        }

        $uid = (int)$post['uid'];
        $user = get_user($uid);

        $newExp = $user['rpg_exp'] + $expGain;
        $newLevel = $this->calculateLevel($newExp, $settings);

        $update = [
            'rpg_exp' => $newExp,
            'rpg_level' => $newLevel,
        ];

        $db->update_query('users', $update, "uid = {$uid}");
    }

    private function calculateLevel($exp, $settings)
    {
        $base = (int)$settings['exp_base'];
        $step = (int)$settings['exp_step'];
        $cap = (int)$settings['level_cap'];

        $level = 1;
        $required = $base;

        while ($exp >= $required && $level < $cap) {
            $level++;
            $required += $step;
        }

        return $level;
    }

    private function getSettings(): array
    {
        global $mybb;
        return [
            'exp_per_char' => $mybb->settings['rpg_levels_exp_per_char'] ?? 0.01,
            'exp_base'     => $mybb->settings['rpg_levels_exp_base'] ?? 2000,
            'exp_step'     => $mybb->settings['rpg_levels_exp_step'] ?? 1000,
            'level_cap'    => $mybb->settings['rpg_levels_level_cap'] ?? 50,
            'enabled_forums' => $mybb->settings['rpg_levels_enabled_forums'] ?? '',
        ];
    }
}

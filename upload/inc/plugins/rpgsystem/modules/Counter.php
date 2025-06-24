<?php
namespace RPGSystem\Modules;

class Counter
{
    public function addStats(int $uid, int $chars, int $words): void
    {
        global $db;
        $row = $db->fetch_array($db->simple_select('rpgsystem_word_counts', '*', "uid={$uid}"));
        if ($row) {
            $db->update_query('rpgsystem_word_counts', [
                'chars' => (int)$row['chars'] + $chars,
                'words' => (int)$row['words'] + $words
            ], "uid={$uid}");
        } else {
            $db->insert_query('rpgsystem_word_counts', [
                'uid' => $uid,
                'chars' => $chars,
                'words' => $words
            ]);
        }
    }

    public function listStats(): array
    {
        global $db;
        $list = [];
        $query = $db->query("SELECT u.uid, u.username, c.chars, c.words FROM " . TABLE_PREFIX . "users u LEFT JOIN " . TABLE_PREFIX . "rpgsystem_word_counts c ON u.uid=c.uid ORDER BY c.chars DESC");
        while ($row = $db->fetch_array($query)) {
            $list[] = $row;
        }
        return $list;
    }
}

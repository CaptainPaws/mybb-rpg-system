<?php
namespace RPGSystem\Modules;

class CharacterCreation
{
    /**
     * Get profile fields selected in settings.
     */
    public function getFields(): array
    {
        global $mybb, $db;
        $ids = array_filter(array_map('intval', explode(',', $mybb->settings['rpgsystem_character_fields'])));
        if (empty($ids)) {
            return [];
        }
        $list = [];
        $query = $db->simple_select('profilefields', 'fid,name', 'fid IN(' . implode(',', $ids) . ')');
        while ($row = $db->fetch_array($query)) {
            $list[] = $row;
        }
        return $list;
    }

    /**
     * Render the form or view of character data.
     */
    public function renderForm(int $uid, bool $viewOnly = false): string
    {
        global $db, $mybb, $lang;
        $fields = $this->getFields();
        if (empty($fields)) {
            return '';
        }
        $userfields = $db->fetch_array($db->simple_select('userfields', '*', 'ufid=' . $uid));
        $html = '<form method="post" action="' . htmlspecialchars_uni($mybb->settings['rpgsystem_character_url']) . '?uid=' . $uid . '">';
        if (!$viewOnly) {
            $html .= '<input type="hidden" name="my_post_key" value="' . $mybb->post_code . '" />';
        }
        foreach ($fields as $field) {
            $name = 'fid' . $field['fid'];
            $val = htmlspecialchars_uni($userfields[$name] ?? '');
            if ($viewOnly) {
                $html .= '<div><strong>' . htmlspecialchars_uni($field['name']) . ':</strong> ' . $val . '</div>';
            } else {
                $html .= '<div><label>' . htmlspecialchars_uni($field['name']) . '</label><br />';
                $html .= '<input type="text" name="' . $name . '" value="' . $val . '" /></div>';
            }
        }
        if (!$viewOnly) {
            $html .= '<input type="submit" value="' . $lang->save . '" />';
        }
        $html .= '</form>';
        return $html;
    }

    /**
     * Save data from form.
     */
    public function saveForm(int $uid, array $input): void
    {
        global $db;
        $fields = $this->getFields();
        if (empty($fields)) {
            return;
        }
        $data = [];
        foreach ($fields as $field) {
            $key = 'fid' . $field['fid'];
            $data[$key] = $db->escape_string($input[$key] ?? '');
        }
        $exists = $db->num_rows($db->simple_select('userfields', 'ufid', 'ufid=' . $uid)) > 0;
        if ($exists) {
            $db->update_query('userfields', $data, 'ufid=' . $uid);
        } else {
            $data['ufid'] = $uid;
            $db->insert_query('userfields', $data);
        }
    }
}

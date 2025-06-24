<?php
namespace RPGSystem\Modules;

class CharacterCreation
{
    /**
     * Возвращает массив выбранных в настройках проф-полей.
     *
     * @return array<int, array{fid:int,name:string}>
     */
    public function getFields(): array
    {
        global $mybb, $db;

        $ids = array_filter(
            array_map('intval', explode(',', $mybb->settings['rpgsystem_character_fields']))
        );

        if (!$ids) {
            return [];
        }

        $list  = [];
        $query = $db->simple_select(
            'profilefields',
            'fid,name',
            'fid IN('.implode(',', $ids).')'
        );

        while ($row = $db->fetch_array($query)) {
            $list[] = $row;
        }

        return $list;
    }

    /**
     * Рендер анкеты (view / edit).
     */
    public function renderForm(int $uid, bool $viewOnly = false): string
    {
        global $db, $mybb, $lang;

        $fields = $this->getFields();
        if (!$fields) {
            return '';
        }

        $userfields = $db->fetch_array(
            $db->simple_select('userfields', '*', 'ufid='.(int)$uid)
        );

        $html  = '<form method="post" action="'
               . htmlspecialchars_uni($mybb->settings['rpgsystem_character_url'])
               . '?uid='.$uid.'">';

        if (!$viewOnly) {
            $html .= '<input type="hidden" name="my_post_key" value="'.$mybb->post_code.'" />';
        }

        foreach ($fields as $field) {
            $name = 'fid'.$field['fid'];
            $val  = htmlspecialchars_uni($userfields[$name] ?? '');

            if ($viewOnly) {
                $html .= '<div><strong>'
                       . htmlspecialchars_uni($field['name'])
                       . ':</strong> '.$val.'</div>';
            } else {
                $html .= '<div><label>'
                       . htmlspecialchars_uni($field['name'])
                       . '</label><br />'
                       . '<input type="text" name="'.$name.'" value="'.$val.'" /></div>';
            }
        }

        if (!$viewOnly) {
            $html .= '<input type="submit" value="'.$lang->save.'" />';
        }

        return $html.'</form>';
    }

    /**
     * Сохраняет данные анкеты.
     */
    public function saveForm(int $uid, array $input): void
    {
        global $db;

        $fields = $this->getFields();
        if (!$fields) {
            return;
        }

        $data = [];
        foreach ($fields as $field) {
            $key        = 'fid'.$field['fid'];
            $data[$key] = $db->escape_string($input[$key] ?? '');
        }

        $exists = (bool)$db->num_rows(
            $db->simple_select('userfields', 'ufid', 'ufid='.(int)$uid)
        );

        if ($exists) {
            $db->update_query('userfields', $data, 'ufid='.(int)$uid);
        } else {
            $data['ufid'] = $uid;
            $db->insert_query('userfields', $data);
        }
    }
}

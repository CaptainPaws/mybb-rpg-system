<?php

if (!defined('IN_MYBB') || !defined('IN_ADMINCP')) {
    die('Direct access not allowed.');
}

$page->add_breadcrumb_item('Система уровней', 'index.php?module=rpgstuff-rpgsystem_levels');

if ($mybb->request_method == 'post') {
    verify_post_check($mybb->input['my_post_key']);

    $update = [
        'rpg_levels_exp_per_char'     => (float)$mybb->input['exp_per_char'],
        'rpg_levels_exp_base'         => (int)$mybb->input['exp_base'],
        'rpg_levels_exp_step'         => (int)$mybb->input['exp_step'],
        'rpg_levels_level_cap'        => (int)$mybb->input['level_cap'],
        'rpg_levels_enabled_forums'   => implode(',', array_map('intval', (array)$mybb->input['enabled_forums'])),
    ];

    foreach ($update as $name => $value) {
        $db->update_query('settings', ['value' => $db->escape_string($value)], "name = '{$db->escape_string($name)}'");
    }

    rebuild_settings();

    flash_message('Настройки сохранены.', 'success');
    admin_redirect('index.php?module=rpgstuff-rpgsystem_levels');
}


$page->output_header('Система уровней');

$form = new Form('index.php?module=rpgstuff-rpgsystem_levels&amp;action=save', 'post');

$form_container = new FormContainer('Настройки начисления опыта и уровней');

$form_container->output_row(
    'EXP за символ',
    'Сколько опыта начислять за каждый символ (например, 0.01)',
    $form->generate_text_box('exp_per_char', $mybb->settings['rpg_levels_exp_per_char'], ['style' => 'width: 100px']),
    'exp_per_char'
);

$form_container->output_row(
    'EXP на 2 уровень',
    'Сколько опыта нужно для достижения 2 уровня',
    $form->generate_text_box('exp_base', $mybb->settings['rpg_levels_exp_base'], ['style' => 'width: 100px']),
    'exp_base'
);

$form_container->output_row(
    'Рост опыта на уровень',
    'На сколько увеличивается требуемый опыт для каждого следующего уровня',
    $form->generate_text_box('exp_step', $mybb->settings['rpg_levels_exp_step'], ['style' => 'width: 100px']),
    'exp_step'
);

$form_container->output_row(
    'Максимальный уровень',
    'Предел, после которого опыт больше не начисляется',
    $form->generate_text_box('level_cap', $mybb->settings['rpg_levels_level_cap'], ['style' => 'width: 100px']),
    'level_cap'
);

$forum_options = [];
$query = $db->simple_select('forums', 'fid, name', '', ['order_by' => 'pid, disporder']);
while ($forum = $db->fetch_array($query)) {
    $forum_options[$forum['fid']] = htmlspecialchars_uni($forum['name']);
}

$selected_forums = explode(',', $mybb->settings['rpg_levels_enabled_forums']);

$form_container->output_row(
    'Форумы с начислением EXP',
    'Выберите форумы, в которых будет начисляться опыт',
    $form->generate_select_box('enabled_forums[]', $forum_options, $selected_forums, ['multiple' => true, 'size' => 8]),
    'enabled_forums'
);

$form_container->end();

$buttons[] = $form->generate_submit_button('Сохранить настройки');
$form->output_submit_wrapper($buttons);
$form->end();

$page->output_footer();

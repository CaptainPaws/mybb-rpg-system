<?php
define('IN_MYBB', 1);
require_once __DIR__ . '/global.php';

$lang->load('rpgsystem');
$uid = (int)($mybb->input['uid'] ?? $mybb->user['uid']);
$readonly = $uid !== $mybb->user['uid'];

$module = RPGSystem\Core::getInstance()->getModule('character_creation');
if (!$module) {
    error_no_permission();
}

if ($mybb->request_method === 'post' && !$readonly) {
    verify_post_check($mybb->input['my_post_key']);
    $module->saveForm($uid, $mybb->input);
    redirect('charactercreation.php?uid=' . $uid, $lang->rpgsystem_saved);
}

$output = $module->renderForm($uid, $readonly);
add_breadcrumb($mybb->settings['rpgsystem_character_title']);

echo '<html><head><title>' . htmlspecialchars_uni($mybb->settings['rpgsystem_character_title']) . "</title></head><body>" . $output . '</body></html>';

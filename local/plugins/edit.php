<?php

/**
 * This file allows the user to edit the editable parts of a plugin
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/edit_form.php');

$visible = optional_param('visible', null, PARAM_INT);
$delete = optional_param('delete', null, PARAM_INT);

$plugin = local_plugins_helper::get_plugin_from_params(MUST_EXIST);
$context = context_system::instance();

$PAGE->set_url($plugin->editlink);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name. ': '. get_string('editplugin', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->navbar->add(get_string('editplugin', 'local_plugins'));

require_login();
if (isguestuser()) {
    throw new local_plugins_exception('exc_noguestsallowed');
}
if (!$plugin->can_edit()) {
    throw new local_plugins_exception('exc_cannoteditplugin');
}
local_plugins_log::remember_state($plugin);
if ($visible !== null && confirm_sesskey()) {
    $plugin->change_visibility($visible);
    local_plugins_log::log_edited($plugin);
    local_plugins_redirect($plugin->viewlink, get_string('visibilitychanged', 'local_plugins'), 3);
}

navigation_node::override_active_url($plugin->viewlink);
local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation)->make_active();
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$renderer = local_plugins_get_renderer($plugin);

if ($delete) {
    if (!$plugin->can_delete()) {
        redirect($plugin->viewlink);
    } else if (!optional_param('confirm', false, PARAM_BOOL) || !confirm_sesskey()) {
        echo $renderer->header(get_string('editplugin', 'local_plugins'));
        $continue = $plugin->confirmdeletelink;
        echo $renderer->confirm(get_string('deletepluginconfirm', 'local_plugins', $plugin->formatted_name), $plugin->confirmdeletelink, $plugin->viewlink);
        echo $renderer->footer();
        exit;
    } else {
        $plugin->delete();
        local_plugins_log::log_deleted($plugin);
        redirect(new local_plugins_url('/local/plugins'), get_string('plugindeleted', 'local_plugins'), 3);
    }
}

$plugindata = (object)array(
    'id'                => $plugin->id,
    'name'              => $plugin->name,
    'frankenstyle'      => $plugin->frankenstyle,
    'categoryid'        => $plugin->categoryid,
    'shortdescription'  => $plugin->shortdescription,
    'description'       => $plugin->description,
    'descriptionformat' => $plugin->descriptionformat,
    'websiteurl'        => $plugin->websiteurl,
    'sourcecontrolurl'  => $plugin->sourcecontrolurl,
    'documentationurl'  => $plugin->documentationurl,
    'bugtrackerurl'     => $plugin->bugtrackerurl,
    'discussionurl'     => $plugin->discussionurl,
    'trackingwidgets'   => $plugin->trackingwidgets
);
$plugindata = file_prepare_standard_editor($plugindata, 'description', local_plugins_helper::editor_options_plugin_description(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINDESCRIPTION, $plugin->id);
$plugindata = file_prepare_standard_filemanager($plugindata, 'screenshots', local_plugins_helper::filemanager_options_plugin_screenshots(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINSCREENSHOTS, $plugin->id);
$plugindata = file_prepare_standard_filemanager($plugindata, 'logo', local_plugins_helper::filemanager_options_plugin_logo(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINLOGO, $plugin->id);

$mform = new local_plugins_edit_plugin_form();
$mform->set_data($plugindata);
if ($mform->is_cancelled()) {
    redirect($plugin->viewlink);
} else if ($mform->is_submitted() && $mform->is_validated() && confirm_sesskey()) {
    $data = $mform->get_data();

    $data = file_postupdate_standard_editor($data, 'description', local_plugins_helper::editor_options_plugin_description(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINDESCRIPTION, $plugin->id);
    $data = file_postupdate_standard_filemanager($data, 'screenshots', local_plugins_helper::filemanager_options_plugin_screenshots(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINSCREENSHOTS, $plugin->id);
    $data = file_postupdate_standard_filemanager($data, 'logo', local_plugins_helper::filemanager_options_plugin_logo(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINLOGO, $plugin->id);

    $plugin->update($data);
    local_plugins_log::log_edited($plugin);
    redirect($plugin->viewlink, get_string('pluginupdated', 'local_plugins'), 3);
}

echo $renderer->header(get_string('editplugin', 'local_plugins'));
$mform->display();
echo $renderer->footer();
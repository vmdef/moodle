<?php

/**
 * This file allows the user to view one version of a plugin
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
require_once($CFG->dirroot.'/local/plugins/pluginversion_form.php');

$versionid = required_param('id', PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$smurf = optional_param('smurf', null, PARAM_ALPHA);

$plugin = local_plugins_helper::get_plugin_by_version($versionid);
$version = $plugin->get_version($versionid);
$context = context_system::instance();

if (!$version->can_view()) {
    throw new local_plugins_exception('exc_cannotviewversion');
}

if ($smurf) {
    $manager = new local_plugins\local\precheck\manager();
    $precheck = $manager->get_latest_precheck_result($version);

    if ($smurf === 'html') {
        echo($precheck->smurfhtml);
        die();
    } else if ($smurf === 'xml') {
        @header('Content-Type: application/xml');
        @header('Content-Disposition: inline; filename="precheck-'.$version->id.'.xml"');
        echo($precheck->smurfxml);
        die();
    }
}

navigation_node::override_active_url($plugin->viewversionslink);
$PAGE->set_url($version->viewlink);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name. ': '. get_string('downloadversions', 'local_plugins'). ': '. $version->formatted_releasename);
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add($version->formatted_releasename);

local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$renderer = local_plugins_get_renderer($plugin);

if ($action === 'confirmdelete' && $version->can_delete() && confirm_sesskey()) {
    local_plugins_log::remember_state($version);
    $plugin->delete_version($version);
    local_plugins_log::log_deleted($version);
    redirect($plugin->viewversionslink);
}
if ($action === 'approve' && confirm_sesskey()) {
    $approve = required_param('approve', PARAM_INT);
    local_plugins_log::remember_state($version);
    $version->approve($approve);
    local_plugins_log::log_edited($version);
    if ($plugin->approved == local_plugins_plugin::PLUGIN_APPROVED and $version->approved == local_plugins_plugin::PLUGIN_APPROVED) {
        \local_plugins\local\amos\exporter::request_strings_update($plugin);
    }
    local_plugins_redirect($version->viewlink);
}
if ($action == 'visibility' && confirm_sesskey()) {
    $visibility = required_param('visible', PARAM_INT);
    local_plugins_log::remember_state($version);
    $version->change_visibility($visibility);
    local_plugins_log::log_edited($version);
    local_plugins_redirect($version->viewlink);
}
if ($action === 'download' && $version->can_download()) {
    local_plugins_helper::send_version_file($version);
}

if ($version->can_delete() && $action === 'delete' && confirm_sesskey()) {
    echo $renderer->header();

    $confirm = new single_button(new local_plugins_url($version->deletelink, array('action' => 'confirmdelete')), get_string('deleteversion', 'local_plugins'));
    $cancel  = new single_button($plugin->viewversionslink, get_string('cancel', 'local_plugins'));
    echo $renderer->confirm(get_string('confirmdeleteversion', 'local_plugins').' '.$version->formatted_releasename, $confirm , $cancel);
    echo $renderer->footer();
    die();
}

if ($action === 'edit') {
    $data = array(
        'id' => $version->id,
        'version' => $version->version,
        'updateableid' => $version->updateable_versions,
        'releasename' => $version->releasename,
        'maturity' => $version->maturity,
        'releasenotes' => $version->releasenotes,
        'releasenotesformat' => $version->releasenotesformat,
        'changelogurl' => $version->changelogurl,
        'altdownloadurl' => $version->altdownloadurl,
        'softwareversion' => $version->supportedsoftware,
        'vcssystem' => $version->vcssystem,
        'vcssystemother' => $version->vcssystemother,
        'vcsrepositoryurl' => $version->vcsrepositoryurl,
        'vcsbranch' => $version->vcsbranch,
        'vcstag' => $version->vcstag
    );
    $data = file_prepare_standard_editor((object)$data, 'releasenotes', local_plugins_helper::editor_options_version_releasenotes(), $context, 'local_plugins', local_plugins::FILEAREA_VERSIONRELEASENOTES, $version->id);
    $mform = new local_plugins_edit_version_form(null, array('versions' => $plugin->versions, 'version' => $version));
    $mform->set_data($data);
    if ($mform->is_cancelled()) {
        redirect($version->viewlink);
    } else if ($mform->is_submitted() && $mform->is_validated() && confirm_sesskey()) {
        local_plugins_log::remember_state($version);
        $data = $mform->get_data();
        $data = file_postupdate_standard_editor($data, 'releasenotes', local_plugins_helper::editor_options_version_releasenotes(), $context, 'local_plugins', local_plugins::FILEAREA_VERSIONRELEASENOTES, $version->id);
        $version->update($data);
        local_plugins_log::log_edited($version);
        if ($plugin->approved == local_plugins_plugin::PLUGIN_APPROVED and $version->approved == local_plugins_plugin::PLUGIN_APPROVED) {
            \local_plugins\local\amos\exporter::request_strings_update($plugin);
        }
        redirect($version->viewlink, get_string('pluginversionupdated', 'local_plugins'), 3);
    }
}

if (isset($mform)) {
    echo $renderer->header(get_string('editversion', 'local_plugins', $version->formatted_releasename));
    $mform->display();
} else {
    echo $renderer->header();
    echo $renderer->render($version);
}
echo $renderer->footer();
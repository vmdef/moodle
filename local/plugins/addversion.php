<?php

/**
 * This file allows the user to add a new version to an existing plugin
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
require_once($CFG->dirroot.'/local/plugins/addversion_form.php');

$plugin = local_plugins_helper::get_plugin_from_params(IGNORE_MISSING);
$vcstag = optional_param('vcstag', null, PARAM_RAW);

$context = context_system::instance();

require_login();

if (isguestuser()) {
    local_plugins_error(get_string('error'), get_string('exc_noguestsallowed', 'local_plugins'), 403);
}

if (empty($plugin)) {
    local_plugins_error();
}

if (!$plugin->can_edit()) {
    local_plugins_error(null, null, 403);
}

$PAGE->set_url($plugin->addversionlink);
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name. ': '. get_string('addnewversion', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->navbar->add(get_string('addnewversion', 'local_plugins'));
$PAGE->requires->yui_module('moodle-local_plugins-vcswidget', 'M.local_plugins.vcswidget.init',
    array(
        array(
            'ajaxurl' => (new local_plugins_url('/local/plugins/ajax/vcswidget.php', array('id' => $plugin->id, 'sesskey' => sesskey())))->out(false),
        )
    )
);

navigation_node::override_active_url($plugin->viewversionslink);
local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation)->make_active();
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$data = new stdClass;
$data->id = $plugin->id;
$mform = new local_plugins_upload_version_form(null, array('versions' => $plugin->versions));

$vcsman = new local_plugins_vcs_manager($plugin);

if (!empty($plugin->sourcecontrolurl)) {
    // set the default value for VCS URL to the plugin source control url
    $data->version_vcsrepositoryurl = $plugin->sourcecontrolurl;
    $data->version_vcssystem = $plugin->get_latestvcssystem('other');

    // If we have VCS tag known from the first screen, use it to pre-populate
    // other fields in the final screen.
    $datasubmitted = data_submitted();
    if ($datasubmitted and isset($datasubmitted->version_vcstag)) {
        require_sesskey();
        if ($vcsman->uses_github()) {
            $vcsinfo = $vcsman->get_vcs_info();
            $data->version_changelogurl = sprintf('https://github.com/%s/%s/commits/%s',
                $vcsinfo->github_username, $vcsinfo->github_reponame, $datasubmitted->version_vcstag);
            $data->version_altdownloadurl = sprintf('https://github.com/%s/%s/archive/%s.zip',
                $vcsinfo->github_username, $vcsinfo->github_reponame, $datasubmitted->version_vcstag);
        }
    }
}

$draftitemid = file_get_submitted_draft_itemid('version_archive_filemanager');

if ($vcstag !== null and empty($draftitemid)) {
    require_sesskey();
    // Attempt to fetch the ZIP of the tagged version and inject it into the file picker.
    $fetchresult = $vcsman->fetch_tagged_version($vcstag);
    if (!empty($fetchresult)) {
        $data->version_archive_filemanager = $fetchresult->draftitemid;
        $data->version_options['renameroot'] = 1;
        $data->version_vcstag = $fetchresult->vcstag;
    }
}

$mform->set_data($data);

if ($mform->is_cancelled()) {
    redirect($plugin->viewlink);
} else if ($mform->is_submitted() && $mform->is_validated()) {
    $data = $mform->get_data();
    $version = local_plugins_helper::quick_upload_version($plugin, $data->version_archive_filemanager, $mform->validator);
    if ($version instanceof local_plugins_version) {
        if (!empty($data->version_releasenotes_editor['text'])) {
            $data = file_postupdate_standard_editor($data, 'version_releasenotes', local_plugins_helper::editor_options_version_releasenotes(), $context, 'local_plugins', local_plugins::FILEAREA_VERSIONRELEASENOTES, $version->id);
        }
        $properties = array();
        foreach (local_plugins_edit_version_form::fields_list(false) as $key) {
            if (!empty($data->{'version_'.$key})) {
                $properties[$key] = $data->{'version_'.$key};
            }
        }
        $version->update($properties);
        local_plugins_log::log_added($version);
        if ($plugin->approved == local_plugins_plugin::PLUGIN_APPROVED and $version->approved == local_plugins_plugin::PLUGIN_APPROVED) {
            \local_plugins\local\amos\exporter::request_strings_update($plugin);
        }
        redirect($version->viewlink);
    }
}

$renderer = local_plugins_get_renderer($plugin);

echo $renderer->header(get_string('addnewversion', 'local_plugins'));
if ($vcstag === null and empty($draftitemid) and $vcsman->uses_github()) {
    echo html_writer::div('', 'addvcsversion placeholder');
}
$mform->display();
echo $renderer->footer();
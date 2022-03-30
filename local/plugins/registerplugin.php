<?php

/**
 * This file allows the user to register a new plugin.
 * Through this registration process the use creates the plugin itself
 * as well as the initial version of the plugin.
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
require_once($CFG->dirroot.'/local/plugins/registerplugin_form.php');

$forcecategory = local_plugins_helper::get_category(optional_param('forcecategoryid', 0, PARAM_INT));
if (!$forcecategory || !$forcecategory->can_create_plugin()) {
    $forcecategory = null;
}

$context = context_system::instance();
require_login();
require_capability(local_plugins::CAP_CREATEPLUGINS, $context);

$PAGE->set_url(new local_plugins_url('/local/plugins/registerplugin.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('createnewplugin', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

$options = array(
    'forcecategory' => $forcecategory,
);

$form = new local_plugins_register_plugin_form(null, $options);

if (!empty($forcecategory) && !empty($forcecategory->plugin_frankenstyle_prefix)) {
    $form->set_data((object)array('frankenstyle' => $forcecategory->plugin_frankenstyle_prefix));
}

if ($form->is_submitted() && $form->is_validated()) {
    $data = $form->get_data();
    if (!isset($data->frankenstyle)) {
        $data->frankenstyle = null;
    }
    // First create the plugin
    $plugin = array(
        'name' => $data->name,
        'frankenstyle' => $form->validator->frankenstyle,
        'categoryid' => $form->validator->category->id,
        'shortdescription' => $data->shortdescription,
        /*'description' => $data->description_editor['text'],
        'descriptionformat' => $data->description_editor['format'],
        'websiteurl' => $data->websiteurl,
        'sourcecontrolurl' => $data->sourcecontrolurl,
        'documentationurl'  => $data->documentationurl,
        'bugtrackerurl'  => $data->bugtrackerurl,
        'discussionurl'  => $data->discussionurl,
         */
    );
    $plugin = local_plugins_helper::create_plugin($plugin);
/*
    // Save files the user added to the plugin description
    $data = file_postupdate_standard_editor($data, 'description', local_plugins_helper::editor_options_plugin_description(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINDESCRIPTION, $plugin->id);
    // Save any screenshots the user has created.
    $data = file_postupdate_standard_filemanager($data, 'screenshots', local_plugins_helper::filemanager_options_plugin_screenshots(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINSCREENSHOTS, $plugin->id);
    // Save the logo the user selected for the plugin if there is one
    $data = file_postupdate_standard_filemanager($data, 'logo', local_plugins_helper::filemanager_options_plugin_logo(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINLOGO, $plugin->id);
*/
    // Update the plugin description now that file URL's will have been fixed.
    $plugin->update($data);

    // Now create the version
    try {
        $version = local_plugins_helper::quick_upload_version($plugin, $data->version_archive_filemanager, $form->validator);
    } catch (local_plugins_exception $exc) {
        $plugin->delete(false);
        throw $exc;
    }
    if ($version instanceof local_plugins_version) {
        // We'll create an object with the values the user filled out so we can update the newly created version.
        if (!empty($data->version_releasenotes_editor['text'])) {
            //if contributor did not enter any release notes, do not override what we might have found in the uploadedarchive
            $data = file_postupdate_standard_editor($data, 'version_releasenotes', local_plugins_helper::editor_options_version_releasenotes(), $context, 'local_plugins', local_plugins::FILEAREA_VERSIONRELEASENOTES, $version->id);
        }
        $properties = array();
        foreach (local_plugins_edit_version_form::fields_list(true) as $key) {
            if (!empty($data->{'version_'.$key})) {
                $properties[$key] = $data->{'version_'.$key};
            }
        }
        // If user specified Source control URL for a plugin, but did not specify VCS URL for a version, copy one to another
        if (!empty($data->sourcecontrolurl) && empty($data->version_vcsrepositoryurl)) {
            $properties['vcsrepositoryurl'] = $data->sourcecontrolurl;
            if (empty($data->version_vcssystem) || $data->version_vcssystem == 'none') {
                $properties['vcssystem'] = 'other';
            }
        }
        $version->update($properties);
        local_plugins_log::log_added($plugin);
        local_plugins_log::log_added($version);
        if ($plugin->approved == local_plugins_plugin::PLUGIN_APPROVED and $version->approved == local_plugins_plugin::PLUGIN_APPROVED) {
            \local_plugins\local\amos\exporter::request_strings_update($plugin);
        }
        $report = local_plugins_helper::get_report('pendingapproval_plugins');
        $a = new stdClass();
        $a->pendingcount = $report->registration_queue_count();
        redirect($plugin->editlink, get_string('redirecteditplugin', 'local_plugins', $a), 5);
    }
}

$renderer = local_plugins_get_renderer();
if ($forcecategory) {
    echo $renderer->header(get_string('createnewpluginincategory', 'local_plugins', $forcecategory->formatted_name));
} else {
    echo $renderer->header(get_string('createnewplugin', 'local_plugins'));
}
$form->display();
echo $renderer->footer();

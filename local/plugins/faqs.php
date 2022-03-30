<?php

/**
 * This file displays the FAQ's that have been created for this plugin
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
require_once($CFG->dirroot.'/local/plugins/faqs_form.php');

$edit = optional_param('edit', false, PARAM_BOOL);
$plugin = local_plugins_helper::get_plugin_from_params(MUST_EXIST);
$context = context_system::instance();

if (!$plugin->can_view()) {
    local_plugins_error();
}

$canedit = $plugin->can_edit();

$PAGE->set_url($plugin->viewfaqslink);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name. ': '. get_string('faqs', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$renderer = local_plugins_get_renderer($plugin);

if ($canedit && $edit) {
    $mform = new local_plugins_edit_plugin_faqs_form($plugin->editfaqslink);
    $data = new stdClass;
    $data->id = $plugin->id;
    $data->faqs = $plugin->faqs;
    $data->faqsformat = $plugin->faqsformat;
    $data = file_prepare_standard_editor($data, 'faqs', local_plugins_helper::editor_options_plugin_faqs(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINFAQS, $plugin->id);
    $mform->set_data($data);

    if ($mform->is_cancelled()) {
        redirect($plugin->viewfaqslink);
    } else if ($mform->is_submitted() && $mform->is_validated() && confirm_sesskey()) {
        $data = $mform->get_data();
        $data = file_postupdate_standard_editor($data, 'faqs', local_plugins_helper::editor_options_plugin_faqs(), $context, 'local_plugins', local_plugins::FILEAREA_PLUGINFAQS, $plugin->id);
        $plugin->update($data);
        redirect($plugin->viewfaqslink, get_string('faqsupdated', 'local_plugins'));
    }
}


echo $renderer->header();
if ($edit && $canedit && $mform) {
    $mform->display();
} else {
    echo $renderer->plugin_faqs($plugin, $canedit);
}
echo $renderer->footer();
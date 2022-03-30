<?php

/**
 * Through this file the user is able to browse, create and edit awards as they desire.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/admin/awards_form.php');

$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

$context = context_system::instance();
$baseurl = new local_plugins_url('/local/plugins/admin/awards.php');
$url = clone($baseurl);

if (!empty($id)) {
    $url->param('id', $id);
    $award = local_plugins_helper::get_award($id);
    $formheading = get_string('editaward', 'local_plugins');
} else {
    $award = null;
    $formheading = get_string('createaward', 'local_plugins');
}

require_login();
require_capability(local_plugins::CAP_MANAGEAWARDS, $context);

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('pluginadministration', 'local_plugins'). ': '. get_string('manageawards', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

if (!empty($award) && !empty($action)) {
    if ($action == 'confirmdelete' && confirm_sesskey()) {
        local_plugins_log::remember_state($award);
        $award->delete();
        local_plugins_log::log_deleted($award);
        redirect($baseurl);
    }

    if ($action == 'delete' && confirm_sesskey()) {
        $heading = get_string('deleteaward', 'local_plugins');
        $message  = html_writer::tag('h3', $award->formatted_name);
        $message .= html_writer::tag('p', get_string('confirmawarddelete', 'local_plugins'));
        $continueurl = new local_plugins_url($url, array('action'=>'confirmdelete', 'sesskey'=>sesskey()));
        $continue = new single_button($continueurl, get_string('continue'), 'post');
        $cancel = new single_button($baseurl, get_string('cancel'), 'post');

        $PAGE->navbar->add($heading);
        $renderer = local_plugins_get_renderer();
        echo $renderer->header($heading);
        echo $renderer->confirm($message, $continue, $cancel);
        echo $renderer->footer();
        die();
    }
}

$awards = local_plugins_helper::get_awards();

if (!empty($award) || $action === 'new' || count($awards) === 0) {
    $form = new local_plugins_awards_form(null, array('formheading' => $formheading));
    $data = new stdClass;
    $data->action = 'new';
    if (!empty($award)) {
        $data->action = 'edit';
        $data->id = $award->id;
        $data->name = $award->name;
        $data->shortname = $award->shortname;
        $data->description = $award->description;
        $data->descriptionformat = $award->descriptionformat;
        $data->onfrontpage = $award->onfrontpage;

        // Prepare the description editor
        $options = local_plugins_helper::editor_options_award_description();
        $filearea = local_plugins::FILEAREA_AWARDDESCRIPTION;
        $data = file_prepare_standard_editor($data, 'description', $options, $context, 'local_plugins', $filearea, $award->id);

        // Prepare the icon file manager
        $options = local_plugins_helper::filemanager_options_award_icon();
        $filearea = local_plugins::FILEAREA_AWARDICON;
        $data = file_prepare_standard_filemanager($data, 'icon', $options, $context, 'local_plugins', $filearea, $award->id);
    }
    $form->set_data($data);
    if ($form->is_cancelled()) {
        redirect($baseurl);
    } else if ($form->is_submitted() && $form->is_validated() && confirm_sesskey()) {
        $data = $form->get_data();
        if (!isset($data->onfrontpage)) {
            $data->onfrontpage = 0;
        }
        if (empty($award)) {
            $award = local_plugins_helper::create_award(array(
                'name' => $data->name,
                'shortname' => $data->shortname,
                'description' => $data->description_editor['text'],
                'descriptionformat' => $data->description_editor['format'],
                'onfrontpage' => $data->onfrontpage
            ));
        } else {
            local_plugins_log::remember_state($award);
        }

        // Process the description editor and any files used within it.
        $options = local_plugins_helper::editor_options_award_description();
        $filearea = local_plugins::FILEAREA_AWARDDESCRIPTION;
        $data = file_postupdate_standard_editor($data, 'description', $options, $context, 'local_plugins', $filearea, $award->id);
        // Process the icon added by the user (if there is one).
        $options = local_plugins_helper::filemanager_options_award_icon();
        $filearea = local_plugins::FILEAREA_AWARDICON;
        $data = file_postupdate_standard_filemanager($data, 'icon', $options, $context, 'local_plugins', $filearea, $award->id);
        // Update the award in the database
        $award->update($data);
        local_plugins_log::log_changed($award, empty($id));
        redirect($baseurl);
    }
}

$renderer = local_plugins_get_renderer();
echo $renderer->header(get_string('manageawards', 'local_plugins'));
if (empty($award) && $action !== 'new') {
    if (count($awards) > 0) {
        echo $renderer->editable_award_version_table($awards, true);
        echo $OUTPUT->single_button(new local_plugins_url($baseurl, array('action' => 'new')), get_string('createaward', 'local_plugins'), 'get');
    } else {
        echo $renderer->notification(get_string('noawards', 'local_plugins'));
    }
}
if (isset($form)) {
    $form->display();
}
echo $renderer->footer();
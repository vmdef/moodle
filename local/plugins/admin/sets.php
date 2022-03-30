<?php

/**
 * Through this file the user is able to browse, create and edit sets as they desire.
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
require_once($CFG->dirroot.'/local/plugins/admin/sets_form.php');

$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

$context = context_system::instance();
$baseurl = new local_plugins_url('/local/plugins/admin/sets.php');
$url = clone($baseurl);

if (!empty($id)) {
    $url->param('id', $id);
    $set = local_plugins_helper::get_set($id);
    $formheading = get_string('editset', 'local_plugins');
} else {
    $set = null;
    $formheading = get_string('createset', 'local_plugins');
}

require_login();
require_capability(local_plugins::CAP_MANAGESETS, $context);

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('pluginadministration', 'local_plugins'). ': '. get_string('managesets', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');
$renderer = local_plugins_get_renderer();

if (!empty($set) && !empty($action)) {
    if ($action == 'confirmdelete' && confirm_sesskey()) {
        local_plugins_log::remember_state($set);
        $set->delete();
        local_plugins_log::log_deleted($set);
        redirect($baseurl);
    }

    if ($action == 'delete' && confirm_sesskey()) {
        $heading = get_string('deleteset', 'local_plugins');
        $message  = html_writer::tag('h3', $set->formatted_name);
        $message .= html_writer::tag('p', get_string('confirmsetdelete', 'local_plugins'));
        $continueurl = new local_plugins_url($url, array('action'=>'confirmdelete', 'sesskey'=>sesskey()));
        $continue = new single_button($continueurl, get_string('continue'), 'post');
        $cancel = new single_button($baseurl, get_string('cancel'), 'post');

        $PAGE->navbar->add($heading);
        echo $renderer->header($heading);
        echo $renderer->confirm($message, $continue, $cancel);
        echo $renderer->footer();
        die();
    }
}

$sets = local_plugins_helper::get_sets();

if (!empty($set) || $action === 'new' || count($sets) === 0) {
    $PAGE->navbar->add($formheading);
    $form = new local_plugins_sets_form(null, array('formheading' => $formheading));
    $data = new stdClass;
    if (!empty($set)) {
        $data->action = 'edit';
        $data->id = $set->id;
        $data->name = $set->name;
        $data->shortname = $set->shortname;
        $data->description = $set->description;
        $data->descriptionformat = $set->descriptionformat;
        $data->maxplugins = $set->maxplugins;
        $data->onfrontpage = $set->onfrontpage;

        // Prepare the short description editor
        $options = local_plugins_helper::editor_options_set_description();
        $filearea = local_plugins::FILEAREA_SETDESCRIPTION;
        $data = file_prepare_standard_editor($data, 'description', $options, $context, 'local_plugins', $filearea, $set->id);
    }
    $form->set_data($data);
    if ($form->is_cancelled()) {
        redirect($baseurl);
    } else if ($form->is_submitted() && $form->is_validated() && confirm_sesskey()) {
        $data = $form->get_data();
        if (!isset($data->onfrontpage)) {
            $data->onfrontpage = 0;
        }
        if (empty($set)) {
            $set = local_plugins_helper::create_set(array(
                'name' => $data->name,
                'shortname' => $data->shortname,
                'description' => $data->description_editor['text'],
                'descriptionformat' => $data->description_editor['format'],
                'maxplugins' => $data->maxplugins,
                'onfrontpage' => $data->onfrontpage
            ));
        } else {
            local_plugins_log::remember_state($set);
        }

        // Prepare the short description editor
        $options = local_plugins_helper::editor_options_set_description();
        $filearea = local_plugins::FILEAREA_SETDESCRIPTION;
        $data = file_postupdate_standard_editor($data, 'description', $options, $context, 'local_plugins', $filearea, $set->id);
        $set->update($data);
        local_plugins_log::log_changed($set, empty($id));
        redirect($baseurl);
    }
}

echo $renderer->header(get_string('managesets', 'local_plugins'));
echo $OUTPUT->box(get_string('managesetsdesc', 'local_plugins'));
if (empty($set) && $action !== 'new') {
    if (count($sets) > 0) {
        echo $renderer->editable_sets_table($sets, true);
        echo $OUTPUT->single_button(new local_plugins_url($baseurl, array('action' => 'new')), get_string('createset', 'local_plugins'), 'get');
    } else {
        echo $renderer->notification(get_string('nosets', 'local_plugins'));
    }
}
if (isset($form)) {
    $form->display();
}
echo $renderer->footer();
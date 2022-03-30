<?php

/**
 * Through this file the user is able to create and edit review criteria as they desire.
 *
 * This file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/admin/criteria_form.php');

$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

$context = context_system::instance();
$baseurl = new local_plugins_url('/local/plugins/admin/criteria.php');
$url = clone($baseurl);

if (!empty($id)) {
    $url->param('id', $id);
    $criterion = local_plugins_helper::get_review_criterion($id);
    $formheading = get_string('editreviewcriterion', 'local_plugins');
} else {
    $criterion = null;
    $formheading = get_string('createreviewcriterion', 'local_plugins');
}

require_login();
require_capability(local_plugins::CAP_MANAGEREVIEWCRITERIA, $context);

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('pluginadministration', 'local_plugins'). ': '. get_string('managereviewcriteria', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');
navigation_node::override_active_url($baseurl);
$renderer = local_plugins_get_renderer();

if (!empty($action) && $action == 'confirmdelete' && confirm_sesskey()) {
    local_plugins_log::remember_state($criterion);
    $criterion->delete();
    local_plugins_log::log_deleted($criterion);
    redirect($baseurl);
}

if (!empty($action) && $action == 'delete' && confirm_sesskey()) {

    $message  = html_writer::tag('p', get_string('confirmreviewcriteriondelete', 'local_plugins'));
    $message .= html_writer::tag('p', $criterion->formatted_name);
    // TODO list here reviews that contain outcome on this criterion
    $continueurl = new local_plugins_url($url, array('action'=>'confirmdelete', 'sesskey'=>sesskey()));
    $continue = new single_button($continueurl, get_string('continue'), 'post');
    $cancel = new single_button($url, get_string('cancel'), 'post');

    echo $renderer->header(get_string('deletereviewcriterion', 'local_plugins'));
    echo $renderer->confirm($message, $continue, $cancel);
    echo $renderer->footer();
    die();
}

$criteria = local_plugins_helper::get_review_criteria();
if (!empty($criterion) || $action === 'new' || count($criteria) === 0) {
    $form = new local_plugins_review_edit_criterion_form(null, array('formheading' => $formheading));
    $form->set_criterion_data($criterion);
    if ($form->is_cancelled()) {
        redirect($baseurl);
    } else if ($form->is_submitted() && $form->is_validated() && confirm_sesskey()) {
        $data = $form->get_data();
        if (empty($criterion)) {
            $criterion = local_plugins_helper::create_review_criterion(array(
                'name' => $data->name,
                'scaleid' => $data->scaleid,
                'cohortid' => $data->cohortid
            ));
        } else {
            local_plugins_log::remember_state($criterion);
        }

        // Process the description field of the criterion
        $filearea = local_plugins::FILEAREA_REVIEWCRITERIADESC;
        $options = local_plugins_helper::editor_options_review_criterion_description();
        $data = file_postupdate_standard_editor($data, 'description', $options, $context, 'local_plugins', $filearea, $criterion->id);

        // Update the criterion
        $criterion->update($data);
        local_plugins_log::log_changed($criterion, empty($id));
        redirect($baseurl);
    }
}


echo $renderer->header(get_string('managereviewcriteria', 'local_plugins'));
if (empty($criterion) && $action !== 'new') {
    if (count($criteria)) {
        echo $renderer->editable_review_criteria_table($criteria, true);
        $newurl = new local_plugins_url($baseurl, array('action' => 'new'));
        echo $OUTPUT->single_button($newurl, get_string('createreviewcriterion', 'local_plugins'), 'get');
    } else {
        echo $renderer->notification(get_string('noreviewcriteria', 'local_plugins'));
    }
}
if (isset($form)) {
    $form->display();
}

echo $renderer->footer();
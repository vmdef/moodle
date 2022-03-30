<?php

/**
 * This file allows user (plugin browsers - moodle.org) to
 * add/edit/remove user sites
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2013 Aparup Banerjee
 */

require_once('../../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/report/user_sites/site_form.php');

$action = optional_param('action', 'add', PARAM_ALPHA);

require_login();

$context = context_system::instance();

$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');

$renderer = local_plugins_get_renderer();
$usersite = false;
$returnurl = new local_plugins_url('/local/plugins/report/index.php', array('report' => 'user_sites'));
navigation_node::override_active_url($returnurl);
$url = new local_plugins_url('/local/plugins/report/user_sites/site.php');
$PAGE->set_url($url);

if ($action === 'edit' || $action === 'delete') {
    $id = required_param('id', PARAM_INT);
    $result = $DB->get_record('local_plugins_usersite', array('id' => $id, 'userid' => $USER->id), '*', MUST_EXIST);
    $usersite = new local_plugins_usersite($result);
    if ($action == 'delete') {
        // If not yet confirmed, display a confirmation message.
        if (!optional_param('confirm', '', PARAM_BOOL)) {
            $title = get_string('deletesiteareyousure', 'local_plugins', $usersite->sitename);
            echo $OUTPUT->header();
            echo $OUTPUT->heading($title);

            $linkcontinue = new moodle_url($url, array('action' => 'delete', 'id' => $id, 'confirm' => 1));
            $formcancel = new single_button(new moodle_url($returnurl), get_string('no'), 'get');
            echo $OUTPUT->confirm(get_string('deletesiteareyousuremessage', 'local_plugins', $usersite->sitename), $linkcontinue, $formcancel);
            echo $OUTPUT->footer();
            exit;
        }
        // Delete the data.
        $usersite->delete();
        local_plugins_redirect($returnurl,get_string('deletesitedone', 'local_plugins'));
        exit;

    } else {
        $formheading = get_string('editsite', 'local_plugins');
    }

} else if ($action === 'add') {
    $formheading = get_string('addsite', 'local_plugins');

} else {
    throw new coding_exception('Unsupported action');
}

$mform = new local_plugins_usersite_form(null, array('formheading' => $formheading, 'action' => $action));

$PAGE->navbar->add($formheading);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. fullname($USER) . ': '. $formheading);
$PAGE->set_heading($PAGE->title);

if ($mform->is_cancelled()) {
    redirect($returnurl);

} else if ($mform->is_submitted() && $mform->is_validated() && confirm_sesskey()) {
    $data = $mform->get_data();
    $data->userid = $USER->id;
    // save data here
    if ($action === 'add') {
        $usersite = local_plugins_helper::create_usersite($data);
        redirect($returnurl, get_string('siteadded', 'local_plugins', $data->sitename));

    } else if (isset($data->id)) {
        //update
        $usersite = local_plugins_helper::get_usersite($data->id);
        if ($usersite->userid == $USER->id) {
            $usersite->update($data);
        }
        redirect($returnurl, get_string('siteupdated', 'local_plugins', $data->sitename));
    }
}

echo $renderer->header();

if ($usersite) {
    $mform->set_data($usersite);
}
$mform->display();

echo $renderer->footer();

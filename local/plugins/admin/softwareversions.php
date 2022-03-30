<?php

require_once('../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/admin/softwareversions_form.php');

$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);

$context = context_system::instance();
$baseurl = new local_plugins_url('/local/plugins/admin/softwareversions.php');
$url = clone($baseurl);

if (!empty($id)) {
    $url->param('id', $id);
    $softwareversion = local_plugins_helper::get_software_version($id);
    $formheading = get_string('editsoftwareversion', 'local_plugins');
} else {
    $softwareversion = null;
    $formheading = get_string('createsoftwareversion', 'local_plugins');
}

require_login();
require_capability(local_plugins::CAP_MANAGESUPPORTABLEVERSIONS, $context);

$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('pluginadministration', 'local_plugins'). ': '. get_string('managesoftwareversions', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');
$renderer = local_plugins_get_renderer();

if (!empty($softwareversion) && !empty($action)) {
    if ($action == 'confirmdelete' && confirm_sesskey()) {
        local_plugins_log::remember_state($softwareversion);
        $softwareversion->delete();
        local_plugins_log::log_deleted($softwareversion);
        redirect($baseurl);
    }

    if ($action == 'delete' && confirm_sesskey()) {

        $message  = html_writer::tag('p', get_string('confirmsoftwareversiondelete', 'local_plugins'));
        $message .= html_writer::tag('p', format_string($softwareversion->name).' '.format_string($softwareversion->releasename));
        $continueurl = new local_plugins_url($url, array('action'=>'confirmdelete', 'sesskey'=>sesskey()));
        $continue = new single_button($continueurl, get_string('continue'), 'post');
        $cancel = new single_button($url, get_string('cancel'), 'post');

        echo $renderer->header(get_string('deletesoftwareversion', 'local_plugins'));
        echo $renderer->confirm($message, $continue, $cancel);
        echo $renderer->footer();
        die();
    }
}

$softwareversions = local_plugins_helper::get_software_versions();

if (!empty($softwareversion) || $action === 'new' || count($softwareversions) === 0) {
    $form = new local_plugins_softwareversions_form(null, array('formheading' => $formheading));
    $data = array('action' => $action);
    if (!empty($softwareversion)) {
        $data['name'] = $softwareversion->name;
        $data['version'] = $softwareversion->version;
        $data['releasename'] = $softwareversion->releasename;
        $data['id'] = $softwareversion->id;
    }
    $form->set_data($data);
    if ($form->is_cancelled()) {
        redirect($baseurl);
    } else if ($form->is_submitted() && $form->is_validated() && confirm_sesskey()) {
        $data = $form->get_data();
        if (empty($softwareversion)) {
            $softwareversion = local_plugins_helper::create_software_version($data);
            local_plugins_log::log_added($softwareversion);
        } else {
            local_plugins_log::remember_state($softwareversion);
            $softwareversion->update($data);
            local_plugins_log::log_edited($softwareversion);
        }
        redirect($baseurl);
    }
}

echo $renderer->header(get_string('managesoftwareversions', 'local_plugins'));
if (empty($softwareversion) && $action !== 'new') {
    if (count($softwareversions) > 0) {
        echo $renderer->editable_software_version_table($softwareversions, true);
        $newurl = new local_plugins_url($baseurl, array('action' => 'new'));
        echo $OUTPUT->single_button($newurl, get_string('createsoftwareversion', 'local_plugins'), 'get');
    } else {
        echo $renderer->notification(get_string('nosoftwareversions', 'local_plugins'));
    }
}
if (isset($form)) {
    $form->display();
}
echo $renderer->footer();
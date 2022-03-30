<?php

/**
 * This is the landing page for the "Install plugins from the Moodle
 * plugins directory" button in Site administration / Plugins / Install plugins.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2013 Aparup Banerjee
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

if (!isloggedin() or isguestuser()) {
    $siteinfo = required_param('site', PARAM_RAW);
    $url = new local_plugins_url('/local/plugins/get.php', array('site' => $siteinfo));
    $PAGE->set_url($url);
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('pluginname', 'local_plugins'));
    $PAGE->set_heading($PAGE->title);
    $PAGE->set_pagelayout('base');
    navigation_node::override_active_url(new local_plugins_url('/local/plugins/index.php'));

    $renderer = local_plugins_get_renderer();

    $buttonlogin = $renderer->render(new single_button(new moodle_url(get_login_url()), get_string('installgetlogin', 'local_plugins'), 'get'));
    $buttonbrowse = $renderer->render(new single_button(new local_plugins_url('/local/plugins/index.php'), get_string('installgetbrowse', 'local_plugins'), 'get'));

    echo $renderer->header();
    echo $renderer->box_start('generalbox', 'notice');
    echo $renderer->heading(get_string('installget', 'local_plugins'));
    echo html_writer::div(get_string('installgetinfo', 'local_plugins'));
    echo html_writer::tag('div', $buttonlogin . $buttonbrowse, array('class' => 'buttons'));
    echo $renderer->box_end();
    echo $renderer->footer();
    exit();
}

require_login();
require_capability(local_plugins::CAP_VIEW, context_system::instance());

$siteinfo = required_param('site', PARAM_RAW);
$siteinfo = base64_decode($siteinfo);
$siteinfo = json_decode($siteinfo);

if (!empty($siteinfo)) {
    $versionid = local_plugins_process_moodle_siteinfo($siteinfo->fullname, $siteinfo->url, $siteinfo->majorversion);
} else {
    $versionid = null;
}

if(!is_null($versionid)) {
    $url = new local_plugins_url('/local/plugins/index.php', array('moodle_version' => $versionid, 'refer' => false));
} else {
    $url = new local_plugins_url('/local/plugins/index.php');
}
local_plugins_redirect($url); //moodle_version here saves current browsing version for user anytime landing here.
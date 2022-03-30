<?php

require_once('../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$reportname = optional_param('report', null, PARAM_ALPHANUMEXT);

$url = new local_plugins_url('/local/plugins/report/index.php');
$context = context_system::instance();
if (!empty($reportname)) {
    $report = local_plugins_helper::get_report($reportname);
    if ($report->requires_login()) {
        require_login();
    }
    if (!$report->user_can_view($context)) {
        throw new local_plugins_exception('exc_permissiondenied');
    }
    $url->param('report', $reportname);
} else {
    require_capability(local_plugins::CAP_VIEWREPORTS, $context);
}

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
if (!empty($report)) {
    $PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('reports', 'local_plugins'). ': '. $report->report_title);
} else {
    $PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('reports', 'local_plugins'));
}
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

$renderer = local_plugins_get_renderer();

if (isset($report)) {
    echo $renderer->header($report->report_title);
    echo $OUTPUT->box($report->report_description);
    if ($report->requires_paging()) {
        echo $OUTPUT->render($report->pagingbar);
    }
    echo html_writer::table($report->html_table);
    if ($report->requires_paging()) {
        echo $OUTPUT->render($report->pagingbar);
    }
} else {
    $reports = local_plugins_helper::get_reports_viewable_by_user();
    echo $renderer->header(get_string('pluginreports', 'local_plugins'));
    echo $renderer->report_selector($reports);
}
echo $renderer->footer();
<?php

require_once('../../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$versionid = required_param('id', PARAM_INT);
$approve = required_param('approve', PARAM_INT);
$reportname = required_param('report', PARAM_ALPHANUMEXT);

if ($reportname !== 'unapproved_versions') {
    throw new local_plugins_exception('exc_permissiondenied');
}

$context = context_system::instance();
require_login();
require_capability(local_plugins::CAP_VIEWUNAPPROVED, $context);
require_capability(local_plugins::CAP_APPROVEPLUGINVERSION, $context);

$report = local_plugins_helper::get_report($reportname);
$plugin = local_plugins_helper::get_plugin_by_version($versionid);
$version = $plugin->get_version($versionid);

local_plugins_log::remember_state($version);
$version->approve($approve);
local_plugins_log::log_edited($version);
if ($plugin->approved == local_plugins_plugin::PLUGIN_APPROVED and $version->approved == local_plugins_plugin::PLUGIN_APPROVED) {
    \local_plugins\local\amos\exporter::request_strings_update($plugin);
}
redirect($report->url);
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Handles the changes in the plugin approval status.
 *
 * @package     local_plugins
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

require_login();

$plugin = local_plugins_helper::get_plugin_from_params();
$context = context_system::instance();

if (!$plugin->can_view()) {
    local_plugins_error(null, null, 403);
}

$status = required_param('status', PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);

if ($status == $plugin->approved) {
    redirect($plugin->viewlink);

} else if ($status == local_plugins_plugin::PLUGIN_APPROVED) {
    $PAGE->set_url($plugin->approvelink);
    $message = get_string('approval_approve', 'local_plugins');

} else if ($status == local_plugins_plugin::PLUGIN_UNAPPROVED) {
    $PAGE->set_url($plugin->disapprovelink);
    $message = get_string('approval_disapprove', 'local_plugins');

} else if ($status == local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
    $PAGE->set_url($plugin->scheduleapprovallink);
    $message = get_string('approval_scheduleapprove', 'local_plugins');

} else {
    print_error('exc_invalidstatus', 'local_plugins', '', null, $status);
}

$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name);
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$renderer = local_plugins_get_renderer();

if (!$confirm) {
    echo $renderer->header($plugin->formatted_name);
    $confirmurl = new local_plugins_url($PAGE->url, array('confirm' => 1, 'sesskey' => sesskey()));
    echo '<p>' . $message . '</p>';
    echo $renderer->confirm(get_string('areyousure'), $confirmurl, $plugin->viewlink);
    echo $renderer->footer();
    die();
}

require_sesskey();
local_plugins_log::remember_state($plugin);

if ($plugin->approve($status)) {
    local_plugins_log::log_edited($plugin);
    local_plugins_queue_stats_manager::invalidate_caches();

    if ($plugin->approved == local_plugins_plugin::PLUGIN_APPROVED) {
        \local_plugins\local\amos\exporter::request_strings_update($plugin);
    }

} else {
    print_error('exc_approvalerror', 'local_plugins');
}

redirect($plugin->viewlink);
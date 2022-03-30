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
 * Provides the plugin developer zone page.
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$retryamosexport = optional_param('retryamosexport', false, PARAM_BOOL);

require_login();

$plugin = local_plugins_helper::get_plugin_from_params();

if (!$plugin->can_edit() and !$plugin->can_approve()) {
    local_plugins_error(null, null, 403);
}

$PAGE->set_url($plugin->devzonelink);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('devzone', 'local_plugins'). ': '. $plugin->formatted_name);
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

if ($retryamosexport) {
    require_sesskey();
    if ($plugin->statusamos == \local_plugins\local\amos\exporter::PLUGIN_PROBLEM) {
        \local_plugins\local\amos\exporter::request_strings_update($plugin);
    }
    redirect($PAGE->url);
}

$output = local_plugins_get_renderer($plugin);

echo $output->header(null, true);

echo html_writer::start_div('devzone-cards');

if ($plugin->approved == local_plugins_plugin::PLUGIN_PENDINGAPPROVAL && $plugin->can_approve()) {
    echo html_writer::start_div('devzone-card p-3 border rounded mb-3');
    echo $output->heading('Plugin approval', 3);
    echo html_writer::start_div('buttons buttons-approval');
    echo ' '.html_writer::link($plugin->approvelink, get_string('approvethisplugin', 'local_plugins'), ['class' => 'btn btn-success']);
    echo ' '.html_writer::link($plugin->disapprovelink, get_string('disapprovethisplugin', 'local_plugins'), ['class' => 'btn btn-default']);
    echo html_writer::end_div();
    echo html_writer::end_div();
}

if ($plugin->approved != local_plugins_plugin::PLUGIN_APPROVED) {
    echo '<div class="devzone-card p-3 border rounded mb-3">';
    echo $output->heading('Approval review feedback', 3);

    $approvalissue = clean_param($plugin->approvalissue, PARAM_ALPHANUMEXT);

    if (empty($approvalissue)) {
        echo '<p>Please wait for the approval task to be created soon in the Moodle tracker.</p>';

    } else {
        echo '<p>Your plugin is not approved and published yet. Approval review feedback will be provided in the Moodle tracker. ';
        echo 'Make sure you have user account created there and start watching the issue to be notified once the feedback is provided.</p>';
        echo '<p><a class="btn btn-default" href="https://tracker.moodle.org/browse/'.$approvalissue.'" target="_blank">' .
            'Go to ' . $approvalissue.'</a></p>';
    }

    echo '<p>Please note. Approval reviews are provided by community volunteers in their free time. It may take a while to ';
    echo 'hear from us. Feel encouraged to keep improving the plugin meanwhile. It is a good opportunity to fix all reported ';
    echo 'coding style issues or add more tests, for example.</p>';
    echo '</div>';
}

if ($plugin->can_edit()) {
    echo html_writer::start_div('devzone-card p-3 border rounded mb-3');
    echo $output->heading('Releasing a new version', 3);
    echo '<p>Maintainers are expected to release new versions of their plugins here in the Plugins directory. ';

    if ($plugin->approved != local_plugins_plugin::PLUGIN_APPROVED) {
        echo 'You should upload new version with fixes before requesting re-approval of your plugin.';
    }

    echo '</p>';

    echo ' '.html_writer::link($plugin->addversionlink, get_string('addnewversion', 'local_plugins'), ['class' => 'btn btn-default']);
    echo html_writer::end_div();
}

if ($plugin->approved == local_plugins_plugin::PLUGIN_UNAPPROVED && ($plugin->can_edit() || $plugin->can_approve())) {
    echo html_writer::start_div('devzone-card p-3 border rounded mb-3');
    echo $output->heading('Submit for approval', 3);
    echo '<p>If you believe you have fixed all raised issues with your plugin and you went through the ';
    echo '<a href="https://docs.moodle.org/dev/Plugin_contribution_checklist" target="_blank">Plugin contribution checklist</a> ';
    echo 'then please submit your plugin for another approval review.</p>';
    echo ' '.html_writer::link($plugin->scheduleapprovallink, get_string('scheduleapprove', 'local_plugins'), ['class' => 'btn btn-default']);
    echo html_writer::end_div();
}

if ($plugin->approved == local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
    $now = time();
    $sql = "SELECT p.id,
                   CASE WHEN p.timelastapprovedchange IS NULL THEN 0 ELSE 1 END AS reviewtype,
                   COALESCE($now - p.timelastapprovedchange, $now - p.timecreated) AS timequeuing
              FROM {local_plugins_plugin} p
             WHERE p.approved = ?";

    $queue = $DB->get_records_sql($sql, [local_plugins_plugin::PLUGIN_PENDINGAPPROVAL]);

    $qcount = count($queue);
    $qtimeself = format_time($queue[$plugin->id]->timequeuing);
    $qtypeself = get_string('reviewtype'.$queue[$plugin->id]->reviewtype, 'local_plugins');
    $qtimemax = 0;

    foreach ($queue as $p) {
        if ($p->timequeuing > $qtimemax) {
            $qtimemax = $p->timequeuing;
        }
    }

    $qtimemax = format_time($qtimemax);

    echo html_writer::start_div('devzone-card p-3 border rounded mb-3');
    echo $output->heading('Waiting for approval', 3);
    echo '<ul>
        <li>Scheduled review type: ' . $qtypeself . '</li>
        <li>Waiting in the queue for: ' . $qtimeself . '</li>
    </ul>';

    echo $output->heading('Approval queue state', 4);
    echo '<ul>
        <li>Number of plugins waiting in the approval queue: <span class="badge badge-info">'.$qcount.'</span></li>
        <li>First one queuing for: ' . $qtimemax . '</li>
    </ul>
    <p><a class="btn btn-default" target="_blank" href="/plugins/queue.php">'.get_string('queuestats', 'local_plugins').'</a></p>';

    echo html_writer::end_div();
}

if ($plugin->can_edit()) {
    echo html_writer::start_div('devzone-card p-3 border rounded mb-3');
    echo $output->heading('Plugin record maintenance', 3);
    echo ' '.html_writer::link($plugin->editlink, get_string('editplugin', 'local_plugins'), array('class' => 'btn btn-default'));

    if ($plugin->can_viewvalidation()) {
        echo ' '.html_writer::link($plugin->viewvalidationlink, get_string('viewvalidation', 'local_plugins'), ['class' => 'btn btn-default']);
    }
    echo html_writer::end_div();
}

if ($plugin->approved == local_plugins_plugin::PLUGIN_APPROVED && $plugin->can_edit()) {
    echo html_writer::start_div('devzone-card p-3 border rounded mb-3');
    echo $output->heading(get_string('amosexportstatus', 'local_plugins'), 3);
    switch ($plugin->statusamos) {
        case \local_plugins\local\amos\exporter::PLUGIN_PROCESSING:
            $text = get_string('amosexportstatus_processing', 'local_plugins');
            $status = 'warning';
            break;
        case \local_plugins\local\amos\exporter::PLUGIN_OK:
            $text = get_string('amosexportstatus_ok', 'local_plugins');
            $status = 'success';
            break;
        case \local_plugins\local\amos\exporter::PLUGIN_PROBLEM:
            $text = get_string('amosexportstatus_problem', 'local_plugins');
            $status = 'warning';
            break;
        default:
            $text = get_string('amosexportstatus_pending', 'local_plugins');
            $status = 'info';
            break;
    }
    echo '<p>';
    echo '<span class="badge badge-'.$status.'">'.$text.'</span>';
    echo $output->help_icon('amosexportresult', 'local_plugins');
    echo '</p>';

    if ($plugin->statusamos == \local_plugins\local\amos\exporter::PLUGIN_PROBLEM) {
        // Allow for re-import.
        echo $output->single_button(new \moodle_url($PAGE->url, ['retryamosexport' => 1]),
            get_string('amosexportretry', 'local_plugins'));
    }

    echo $output->heading(get_string('amosexportresult', 'local_plugins'), 4, 'mt-4');

    $table = new \local_plugins\local\amos\results_table($plugin);
    $table->out(30, false);
    echo html_writer::end_div();
}

echo html_writer::end_div();

echo $output->footer();
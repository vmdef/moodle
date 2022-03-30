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
 * @package     local_plugins
 * @category    task
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\task;

use core\message\message;
use core_user;
use local_plugins_helper;
use local_plugins_plugin;
use local_plugins_tracker_connector;

defined('MOODLE_INTERNAL') || die();

class create_approval_issues extends \core\task\scheduled_task {

    public function get_name() {
        return 'Create approval issues in the tracker';
    }

    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

        foreach ($this->get_new_queuing_plugins() as $id) {
            $plugin = local_plugins_helper::get_plugin($id);
            $fields = $this->prepare_issue_fields($plugin);
            $issue = $this->create_issue_for_plugin($fields);

            if ($issue) {
                mtrace('... approval of the plugin id '.$plugin->id.' to be tracked in '.$issue);
                $plugin->set_approval_issue($issue);
                $this->inform_maintainers($plugin);
            }
        }
    }

    /**
     * Returns ids of the new plugins that need an approval issue.
     *
     * @return array int[]
     */
    protected function get_new_queuing_plugins() {
        global $DB;

        $queue = $DB->get_records('local_plugins_plugin', [
            'approved' => local_plugins_plugin::PLUGIN_PENDINGAPPROVAL,
            'approvalissue' => null,
        ], '', 'id');

        if (empty($queue)) {
            return [];
        }

        return array_keys($queue);
    }

    /**
     * Prepares all required data for the plugin approval issue
     *
     * @param local_plugins_plugin $plugin
     * @return array data to be submitted to the JIRA REST API
     */
    protected function prepare_issue_fields(local_plugins_plugin $plugin) {

        $summary = 'Plugin approval: '.$plugin->formatted_name;
        $frankenstyle = $plugin->frankenstyle;

        if (!empty($frankenstyle)) {
            $summary .= ' ('.$frankenstyle.')';
        }

        $description = 'A new plugin has been submitted to the Plugins directory for approval review:';
        $description .= "\n\n";
        $description .= $plugin->viewlink->out();

        $versions = [];
        foreach ($plugin->moodle_versions as $mversion) {
            $versions[] = [
                'name' => $mversion->releasename,
            ];
        }

        $fields = [
            'project' => [
                'key' => 'CONTRIB',
            ],
            'summary' => $summary,
            'description' => $description,
            'components' => [
                [
                    'name' => 'Plugins reviews',
                ],
            ],
            'issuetype' => [
                'name' => 'Review',
            ],
            'versions' => $versions,
        ];

        return $fields;
    }

    /**
     * Creates new approval issue in the tracker with the given fields values
     *
     * @param array $fields
     * @return string|bool created issue key
     */
    protected function create_issue_for_plugin(array $fields) {
        global $CFG;

        if ($CFG->wwwroot !== 'https://moodle.org') {
            return 'TEST-99999';
        }

        $tracker = new local_plugins_tracker_connector();
        $issue = $tracker->create_issue($fields);

        return $issue;
    }

    /**
     * Informs the maintainers about the approval issue.
     *
     * @param local_plugins_plugin $plugin
     */
    protected function inform_maintainers(local_plugins_plugin $plugin) {
        global $DB;

        if (empty($plugin->approvalissue)) {
            throw new coding_exception('Attempting to inform about plugin with no approval issue created');
        }

        // Look, I am not proud on hard-coding things like this. But I am also
        // pragmatic and life is too short...
        $pluginsbot = core_user::get_user(1797093);

        if (!$pluginsbot) {
            $pluginsbot = core_user::get_user(core_user::NOREPLY_USER);
        }

        $a = [
            'pluginname' => $plugin->formatted_name,
            'viewlink' => $plugin->viewlink->out(),
            'issue' => $plugin->approvalissue,
        ];
        $message = get_string('approvalissuemessage', 'local_plugins', $a);

        // Send all contributors a notification.
        foreach ($plugin->contributors as $contributor) {
            $approvalissuemessage = new message();
            $approvalissuemessage->courseid = SITEID;
            $approvalissuemessage->component = 'local_plugins';
            $approvalissuemessage->name = 'availability';
            $approvalissuemessage->userfrom = $pluginsbot;
            $approvalissuemessage->userto = $contributor->user;
            $approvalissuemessage->subject = get_string('approvalissuesubject', 'local_plugins', $plugin->formatted_name);
            $approvalissuemessage->fullmessage = $message;
            $approvalissuemessage->fullmessageformat = FORMAT_PLAIN;
            $approvalissuemessage->fullmessagehtml = '';
            $approvalissuemessage->smallmessage = '';
            $approvalissuemessage->notification = 1;
            $approvalissuemessage->contexturl = $plugin->viewlink->out();
            $approvalissuemessage->contexturlname = $plugin->formatted_name;

            message_send($approvalissuemessage);
            mtrace('... user '.$contributor->user->id.' notified');
        }

        // Inject a comment on the plugin page. I can't use the comments API
        // for this (no way to inject USER) and also I don't really want to
        // activate callbacks as maintainers are explciitly notified above.
        // We bypass the events API here but is is acceptable trade-off imho.
        $comment = (object) [
            'contextid' => SYSCONTEXTID,
            'commentarea' => 'plugin_general',
            'itemid' => $plugin->id,
            'component' => 'local_plugins',
            'content' => 'Approval issue created: '.$plugin->approvalissue,
            'format' => FORMAT_MOODLE,
            'userid' => $pluginsbot->id,
            'timecreated' => time(),
        ];

        $DB->insert_record('comments', $comment);
        mtrace('... leaving the issue number in a comment on '.$plugin->viewlink->out());
    }
}

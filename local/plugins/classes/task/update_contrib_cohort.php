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
 * Provides the task to update plugins contributors cohort
 *
 * @package     local_plugins
 * @subpackage  plugintype_pluginname
 * @category    optional API reference
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\task;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/cohort/lib.php');

/**
 * Task to update the plugins contributors cohort.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_contrib_cohort extends \core\task\scheduled_task {

    /**
     * @return string the human readable name of the task
     */
    public function get_name() {
        return get_string('taskupdatecontribcohort', 'local_plugins');
    }

    /**
     * Execute the task.
     */
    public function execute() {

        $cohort = $this->get_cohort();
        $current = $this->get_current_members($cohort);
        $contributors = $this->get_plugins_contributors();

        $this->sync($cohort, $current, $contributors);
    }

    /**
     * @return stdClass the cohort record to use
     */
    protected function get_cohort() {
        global $DB;

        $cohort = array(
            'idnumber' => 'local_plugins:contributors',
            'component' => 'local_plugins'
        );

        $existing = $DB->get_record('cohort', $cohort, '*', IGNORE_MISSING);

        if ($existing) {
            return $existing;

        } else {
            $cohort = (object) $cohort;
            $cohort->contextid = \context_system::instance()->id;
            $cohort->name = 'Plugins: Contributors';
            $cohort->description = 'Automatically generated cohort of plugins contributors';
            $cohort->id = cohort_add_cohort($cohort);

            return $DB->get_record('cohort', array('id' => $cohort->id), '*', MUST_EXIST);
        }
    }

    /**
     * Get the list of current (existing) cohort members.
     *
     * @param stdClass $cohort
     * @return array of (int)userid
     */
    protected function get_current_members(stdClass $cohort) {
        global $DB;

        return $DB->get_fieldset_select('cohort_members', 'DISTINCT userid', 'cohortid = ?', array($cohort->id));
    }

    /**
     * Get the list of plugins contributors.
     *
     * @return array of (int)userid
     */
    protected function get_plugins_contributors() {
        global $DB;

        $sql = "SELECT DISTINCT c.userid
                  FROM {local_plugins_contributor} c
                  JOIN {local_plugins_plugin} p ON c.pluginid = p.id
                 WHERE p.approved = 1 AND p.visible = 1";

        return $DB->get_fieldset_sql($sql);
    }

    /**
     * Actually update the cohort members
     *
     * @param stdClass $cohort
     * @param array $current
     * @param array $contributors
     */
    protected function sync(stdClass $cohort, array $current, array $contributors) {

        foreach (array_diff($contributors, $current) as $userid) {
            cohort_add_member($cohort->id, $userid);
        }

        foreach (array_diff($current, $contributors) as $userid) {
            cohort_remove_member($cohort->id, $userid);
        }
    }
}

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
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Updates usage statistics for the plugins
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_usage_stats extends \core\task\scheduled_task {

    /**
     * Returns a descriptive name for this task shown to admins
     *
     * @return string
     */
    public function get_name() {
        return get_string('taskupdateusagestats', 'local_plugins');
    }

    /**
     * Performs the task
     *
     * @throws moodle_exception on an error (the job will be retried)
     */
    public function execute() {

        $usageman = new \local_plugins_usage_manager('mtrace');
        $usageman->update_plugin_usage_data();
    }
}

<?php
// This file is part of Moodle - https://moodle.org/
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
 * @subpackage  amos
 * @category    task
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\task;

use local_plugins\local\amos\exporter;

defined('MOODLE_INTERNAL') || die();

/**
 * Check if some plugin needs its strings exported to AMOS and do it eventually.
 *
 * @copyright 2019 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class amos_export extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return 'Export plugins strings into AMOS';
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

        $plugin = exporter::get_next_pending_plugin();

        if (!$plugin) {
            mtrace('No plugin pending registration with AMOS.');
            return;
        }

        mtrace('Processing plugin "'.$plugin->formatted_name.'" ('.$plugin->frankenstyle.')');
        exporter::set_plugin_processing_result($plugin, exporter::PLUGIN_PROCESSING);

        $success = exporter::process_plugin($plugin);

        if ($success) {
            mtrace('... OK');
        } else {
            mtrace('... FAILED');
        }
    }
}

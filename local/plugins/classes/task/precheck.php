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
 * Provides {@link local_plugins\task\precheck} class
 *
 * @package     local_plugins
 * @category    task
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\task;

use Exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled regular precheck of plugin versions
 *
 * @copyright 2017 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class precheck extends \core\task\scheduled_task {

    public function get_name() {
        return 'Precheck submitted plugins versions';
    }

    public function execute() {
        global $CFG;
        require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

        $manager = new \local_plugins\local\precheck\manager();

        mtrace('Choosing a plugin version to precheck ...');
        $version = $manager->choose_precheck_candidate();

        if ($version) {
            mtrace('Chosen '.$version->plugin->frankenstyle.' version '.$version->id);
            $config = get_config('local_plugins');
            try {
                $manager->precheck_plugin_version($version, $config);
            } catch (Exception $e) {
                // We catch all exceptions as we do not want the next plugin version's
                // precheck be delayed.
                mtrace('FAILURE: '.$e->getMessage());
            }


        } else {
            mtrace('No plugin version found to be prechecked.');
        }
    }
}

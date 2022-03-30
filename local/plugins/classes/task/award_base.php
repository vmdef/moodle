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
 * Provides the {@link \local_plugins\task\award_base} class.
 *
 * @package     local_plugins
 * @category    task
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

/**
 * Abstract base class for granting an award based on some plugin code characteristic.
 *
 * @copyright 2018 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class award_base extends \core\task\scheduled_task {

    /** @var file_packer used to unpack the plugin's ZIP file */
    protected $packer;

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Plugins must support some of the two recent Moodle versions to be awarded.
        $mustsupport = $DB->get_records('local_plugins_software_vers', ['name' => 'Moodle'],
            'version DESC', 'id, releasename', 0, 2);

        $releases = [];
        foreach ($mustsupport as $m) {
            $releases[] = $m->releasename;
        }

        list($issupported, $params) = $DB->get_in_or_equal(array_keys($mustsupport), SQL_PARAMS_NAMED);

        // Find all plugins that have a version supporting these Moodle versions.
        $sql = "SELECT DISTINCT p.id
                  FROM {local_plugins_plugin} p
                  JOIN {local_plugins_vers} v ON v.pluginid = p.id
                  JOIN {local_plugins_supported_vers} s ON s.versionid = v.id
             LEFT JOIN {local_plugins_plugin_awards} a ON a.pluginid = p.id AND a.awardid = :awardid
                 WHERE v.visible = 1
                   AND p.frankenstyle IS NOT NULL
                   AND a.id IS NULL
                   AND s.softwareversionid $issupported";

        $params['awardid'] = $this->awardid;

        $pluginids = $DB->get_records_sql($sql, $params);

        mtrace('... checking for '.count($pluginids).' plugins supporting Moodle versions '. implode(' or ', $releases));

        $this->packer = get_file_packer('application/zip');

        foreach ($pluginids as $pluginid => $unused) {
            $plugin = \local_plugins_helper::get_plugin($pluginid);
            $grant = false;
            foreach ($plugin->latestversions as $version) {
                $zip = $plugin->create_storage_directory().'/'.$version->id.'.zip';
                if (!file_exists($zip)) {
                    mtrace('... zip not found '.$zip.' !!!');
                    continue;
                }
                $contents = $this->packer->list_files($zip);
                if (empty($contents)) {
                    mtrace('... error opening '.$zip.' !!!');
                    continue;
                }

                $grant = $this->should_be_awarded($plugin, $version, $zip);

                if ($grant) {
                    // No need to search for more.
                    break;
                }
            }

            if ($grant) {
                mtrace('... awarding '.$plugin->frankenstyle);

                $DB->insert_record('local_plugins_plugin_awards', [
                    'awardid' => $this->awardid,
                    'pluginid' => $plugin->id,
                    'versionid' => $version->id,
                    'timeawarded' => time(),
                    'userid' => 1797093,
                ]);
            }
        }
    }

    abstract protected function should_be_awarded(\local_plugins_plugin $plugin, \local_plugins_version $version, string $zip);
}

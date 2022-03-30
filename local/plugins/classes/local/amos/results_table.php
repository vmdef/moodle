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
 * Provides the {@link \local_plugins\local\amos\results_table} class.
 *
 * @package     local_plugins
 * @subpackage  amos
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\local\amos;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/tablelib.php');

/**
 * Represents a table with AMOS export results.
 *
 * @copyright 2019 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class results_table extends \table_sql {

    /** @var local_plugins_plugin */
    protected $plugin;

    /**
     * Set up the table.
     *
     * @param local_plugins_plugin $plugin
     */
    public function __construct(\local_plugins_plugin $plugin) {
        $this->plugin = $plugin;

        parent::__construct('local_plugins_amos_results-'.$plugin->id);

        $this->define_baseurl($plugin->devzonelink);

        $this->define_headers([
            get_string('amosexportresulttimecreated', 'local_plugins'),
            get_string('version', 'local_plugins'),
            get_string('amosexportresultbranch', 'local_plugins'),
            get_string('status', 'local_plugins'),
            get_string('info'),
        ]);

        $this->define_columns(['timecreated', 'version', 'moodlebranch', 'status', 'result']);

        $this->set_sql(
            "r.id, r.versionid, r.moodlebranch, r.timecreated, r.status, r.result, v.version, v.releasename",
            "{local_plugins_amos_results} r JOIN {local_plugins_vers} v ON r.versionid = v.id",
            "v.pluginid = :pluginid",
            ['pluginid' => $plugin->id]);

        $this->sortable(true, 'timecreated', SORT_DESC);
        $this->no_sorting('result');
        $this->collapsible(false);
    }

    /**
     * Format the timecreated column.
     *
     * @param object $row SQL result row
     * @return string
     */
    public function col_timecreated($row) {
        return userdate_htmltime($row->timecreated, get_string('strftimedatetime', 'core_langconfig'));
    }

    /**
     * Format the version column.
     *
     * @param object $row SQL result row
     * @return string
     */
    public function col_version($row) {
        return \html_writer::link(
            new \local_plugins_url('/local/plugins/pluginversion.php', ['id' => $row->versionid]),
            $row->releasename ?: $row->version
        );
    }

    /**
     * Format the status column.
     *
     * @param object $row SQL result row
     * @return string
     */
    public function col_status($row) {

        switch ($row->status) {
            case exporter::STATUS_OK:
                $result = get_string('ok');
                $style = 'success';
                break;
            case exporter::STATUS_SKIPPED:
                $result = get_string('skipped');
                $style = 'info';
                break;
            case exporter::STATUS_REMOTE_EXCEPTION:
            case exporter::STATUS_ERROR:
            case exporter::STATUS_UNKNOWN:
            case exporter::STATUS_PROTOCOL_ERROR:
            case exporter::STATUS_UNKNOWN_ERROR:
                $result = get_string('amosexportstatus_problem', 'local_plugins');
                $style = 'warning';
                break;
            default:
                $result = '???';
                $style = 'danger';
        }

        $result = '<span class="badge badge-'.$style.'">'.$result.'</span>';

        return $result;
    }

    /**
     * Format the result column.
     *
     * @param object $row SQL result row
     * @return string
     */
    public function col_result($row) {

        $response = json_decode($row->result);
        $output = '';

        if (is_object($response)) {
            if (isset($response->exception)) {
                // Remote exception was thrown, details stored in errorcode, exception, message and debuginfo properties.
                $output = get_string('amosexportresultexception', 'local_plugins', $response);

            } else if ($response->status ?? '' === 'ok') {
                $output = get_string('amosexportdetails', 'local_plugins', $response);

            } else {
                $output = s($response->message);
            }

        } else if (is_null($response)) {
            // Unable to parse server response.
            $output = get_string('amosexportresulterrorparse', 'local_plugins');

        } else {
            // No string found (not the most recent version, unsupported catgory, mimatched frankenstyle, no lang file, ...)
            $output = get_string('amosexportresulterrorplugin', 'local_plugins');
        }

        return '<small>'.$output.'</small>';
    }

    /**
     * This function is not part of the public api.
     */
    public function print_nothing_to_display() {
        echo get_string('amosexportresultnone', 'local_plugins');
    }
}

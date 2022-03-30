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
 * @subpackage  report
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Implements precheck report
 */
class local_plugins_precheck_report extends local_plugins_report_base {

    public function can_view() {
        return has_capability('local/plugins:viewprecheckreport', context_system::instance());
    }

    protected function get_report_title() {
        return 'Plugins prechecks';
    }

    protected function get_report_description() {
        return 'Shows the list of all executed prechecks and their status';
    }

    public function quick_access() {
        return false;
    }

    protected function define_columns() {
       $columns = [
            'name' => new local_plugins_report_column($this, 'name', 'Plugin', null, true),
            'versionid' => new local_plugins_report_column($this, 'versionid', 'Version ID', null, true),
            'timestart' => new local_plugins_report_column($this, 'timestart', 'Started', null, true),
            'duration' => new local_plugins_report_column($this, 'duration', 'Duration', null, true),
            'status' => new local_plugins_report_column($this, 'status', 'Status', null, true),
        ];
        return $columns;
    }

    protected function fetch_count() {
        global $DB;
        return $DB->count_records('local_plugins_vers_precheck');
    }

    protected function fetch_data() {
        global $USER, $DB;

        $orderby = ['k.timestart DESC'];
        if (!empty($this->sort) && array_key_exists($this->sort, $this->get_columns())) {
            array_unshift($orderby, $this->sort.' '.$this->sortdir);
            switch ($this->sort) {
                case 'name':
                    array_unshift($orderby, 'p.name '.$this->sortdir);
                    break;
                case 'duration':
                    array_unshift($orderby, 'duration '.$this->sortdir);
                    break;
                default:
                    array_unshift($orderby, 'k.'.$this->sort.' '.$this->sortdir);
                    break;
            }
        }

        $sql = "SELECT k.id, p.frankenstyle, p.name, k.versionid, k.timestart,
                       k.timeend - k.timestart AS duration, k.status
                  FROM {local_plugins_vers_precheck} k
             LEFT JOIN {local_plugins_vers} v ON k.versionid = v.id
             LEFT JOIN {local_plugins_plugin} p ON v.pluginid = p.id
              ORDER BY ".join(',', $orderby);

        $data = $DB->get_records_sql($sql, null, $this->page * $this->perpage, $this->perpage);

        foreach ($data as $id => $row) {
            $data[$id]->name = html_writer::link(
                new local_plugins_url('/local/plugins/view.php', ['plugin' => $row->frankenstyle]),
                s($row->name)
            );
            $data[$id]->versionid = html_writer::link(
                new local_plugins_url('/local/plugins/pluginversion.php', ['id' => $row->versionid]),
                s($row->versionid)
            );
            $data[$id]->timestart = userdate($row->timestart, '%d/%m/%Y %H:%M');
            if ($row->duration !== null) {
                $data[$id]->duration = floor($row->duration / 60).' min '.($row->duration % 60).' sec';
            }
            $data[$id]->status = $row->status;
        }

        $rowcount = count($data);
        if ($rowcount < $this->perpage && $this->totalrows === null) {
            // to prevent from running extra query
            $this->totalrows = $rowcount;
        }

        return $data;
    }
}
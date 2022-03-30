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
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Implements My favourite plugins report
 */
class local_plugins_user_favourites_report extends local_plugins_report_base {

    public function can_view() {
        if (!isloggedin() or isguestuser()) {
            return false;
        }

        return has_capability(local_plugins::CAP_MARKFAVOURITE, context_system::instance());
    }

    protected function get_report_title() {
        return get_string('report_userfavourites', 'local_plugins');
    }

    protected function get_report_description() {
        return get_string('report_userfavouritesdesc', 'local_plugins');
    }

    public function quick_access() {
        return true;
    }

    protected function define_columns() {
       $columns = array(
            'name'             => new local_plugins_report_column($this, 'name', get_string('name', 'local_plugins'), null, true),
            'category'         => new local_plugins_report_column($this, 'category', get_string('category', 'local_plugins'), null, true),
            'shortdescription' => new local_plugins_report_column($this, 'shortdescription', get_string('shortdescription', 'local_plugins')),
            'leadmaintainer'   => new local_plugins_report_column($this, 'leadmaintainer', get_string('leadmaintainer', 'local_plugins'), null, true),
            'timemodified'     => new local_plugins_report_column($this, 'timemodified', get_string('favouritesmodified', 'local_plugins'), null, true),
            'actions'          => new local_plugins_report_column($this, 'actions', ''),
        );
       return $columns;
    }

    protected function fetch_count() {
        global $USER, $DB;

        return $DB->count_records('local_plugins_favourite', array('userid' => $USER->id, 'status' => 1));
    }

    protected function fetch_data() {
        global $USER, $DB;

        $orderby = array('f.timemodified '.$this->sortdir);
        if (!empty($this->sort) && array_key_exists($this->sort, $this->get_columns())) {
            switch ($this->sort) {
                case 'category':
                    array_unshift($orderby, 'c.name '.$this->sortdir);
                    break;
                case 'name':
                    array_unshift($orderby, 'p.name '.$this->sortdir);
                    break;
                case 'leadmaintainer':
                    array_unshift($orderby, 'u.firstname '.$this->sortdir);
                    array_unshift($orderby, 'u.lastname '.$this->sortdir);
                    break;
            }
        }

        $userfields = \core_user\fields::for_name()->get_sql('u')->selects;
        $sql = "SELECT p.id, p.name, p.shortdescription, c.name AS category, f.timemodified $userfields
                  FROM {local_plugins_favourite} f
                  JOIN {local_plugins_plugin} p ON f.pluginid = p.id
             LEFT JOIN (
                       SELECT lpc.pluginid $userfields
                       FROM {local_plugins_contributor} lpc
                       LEFT JOIN {user} u ON u.id = lpc.userid
                       WHERE lpc.maintainer = ". local_plugins_contributor::LEAD_MAINTAINER. "
                       ) u ON u.pluginid = p.id
             LEFT JOIN {local_plugins_category} c ON c.id = p.categoryid
                 WHERE f.status = 1 AND f.userid = :userid
              ORDER BY ".join(',', $orderby);

        $data = $DB->get_records_sql($sql, array('userid' => $USER->id), $this->page * $this->perpage, $this->perpage);

        foreach ($data as $id => $row) {
            $data[$id]->leadmaintainer = fullname($row);
            $data[$id]->timemodified = userdate($row->timemodified, '%d/%m/%Y %H:%M');
            $data[$id]->name = html_writer::link(new local_plugins_url('/local/plugins/view.php', array('id' => $id)), s($row->name));
            $data[$id]->actions = html_writer::link(new local_plugins_url('/local/plugins/setfavourite.php', array('id' => $id, 'status' => 0, 'sesskey' => sesskey())),
                get_string('favouritesremove', 'local_plugins'), array('class' => 'btn btn-small'));
            unset($data[$id]->id);
            unset($data[$id]->firstname);
            unset($data[$id]->lastname);
        }

        $rowcount = count($data);
        if ($rowcount < $this->perpage && $this->totalrows === null) {
            // to prevent from running extra query
            $this->totalrows = $rowcount;
        }

        return $data;
    }
}
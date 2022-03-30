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
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Implements Plugins with reviews report
 */
class local_plugins_reviews_report extends local_plugins_report_base {

    public function can_view() {
        return true;
    }

    protected function get_report_title() {
        return get_string('report_reviews', 'local_plugins');
    }

    protected function get_report_description() {
        return get_string('report_reviewsdesc', 'local_plugins');
    }

    public function quick_access() {
        return false;
    }

    protected function define_columns() {
       $columns = array(
            'name'             => new local_plugins_report_column($this, 'name', get_string('name', 'local_plugins'), null, true),
            'category'         => new local_plugins_report_column($this, 'category', get_string('category', 'local_plugins'), null, true),
            'shortdescription' => new local_plugins_report_column($this, 'shortdescription', get_string('shortdescription', 'local_plugins')),
            'leadmaintainer'   => new local_plugins_report_column($this, 'leadmaintainer', get_string('leadmaintainer', 'local_plugins'), null, true),
            'reviews'          => new local_plugins_report_column($this, 'reviews', get_string('reviews', 'local_plugins'), null, true),
        );
       return $columns;
    }

    protected function fetch_count() {
        global $USER, $DB;

        $sql = "SELECT COUNT(DISTINCT(p.id))
                  FROM {local_plugins_review} r
                  JOIN {local_plugins_vers} v ON r.versionid = v.id
                  JOIN {local_plugins_plugin} p ON v.pluginid = p.id
                 WHERE r.status = 1 AND p.approved = :approved";

        return $DB->count_records_sql($sql, array('approved' => local_plugins_plugin::PLUGIN_APPROVED));
    }

    protected function fetch_data() {
        global $USER, $DB;

        $orderby = array('p.aggfavs DESC');
        if (!empty($this->sort) && array_key_exists($this->sort, $this->get_columns())) {
            switch ($this->sort) {
                case 'reviews':
                    array_unshift($orderby, 'reviews '.$this->sortdir);
                    break;
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
        $sql = "SELECT p.id, p.name, p.shortdescription, c.name AS category $userfields, COUNT(*) AS reviews
                  FROM {local_plugins_review} r
                  JOIN {local_plugins_vers} v ON r.versionid = v.id
                  JOIN {local_plugins_plugin} p ON v.pluginid = p.id
             LEFT JOIN (
                       SELECT lpc.pluginid $userfields
                       FROM {local_plugins_contributor} lpc
                       LEFT JOIN {user} u ON u.id = lpc.userid
                       WHERE lpc.maintainer = ". local_plugins_contributor::LEAD_MAINTAINER. "
                       ) u ON u.pluginid = p.id
             LEFT JOIN {local_plugins_category} c ON c.id = p.categoryid
                 WHERE r.status = 1 AND p.approved = :approved
              GROUP BY p.id
              ORDER BY ".join(',', $orderby);

        $data = $DB->get_records_sql($sql, array('approved' => local_plugins_plugin::PLUGIN_APPROVED), $this->page * $this->perpage, $this->perpage);

        foreach ($data as $id => $row) {
            $data[$id]->leadmaintainer = fullname($row);
            $data[$id]->name = html_writer::link(new local_plugins_url('/local/plugins/reviews.php', array('id' => $id)), s($row->name));
            $data[$id]->reviews = $row->reviews;
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
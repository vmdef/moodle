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
 * Provides the {@link local_plugins_unapproved_plugins_public_report} class.
 *
 * @package     local_plugins
 * @subpackage  report
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Publicly available report of not yet approved plugins
 *
 * @copyright 2019 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_unapproved_plugins_public_report extends local_plugins_report_base {

    /** @var string */
    protected $defaultsort = 'timecreated DESC';

    /**
     * @return string
     */
    protected function get_report_title() {
        return get_string('report_unapprovedplugins_public', 'local_plugins');
    }

    /**
     * @return string
     */
    protected function get_report_description() {
        global $PAGE;

        $total = $this->get_totalrows();
        $link = html_writer::link(new local_plugins_url($PAGE->url, ['l' => $total]), $total, [
            'title' => get_string('showall', 'core', $total),
        ]);

        return get_string('report_unapprovedplugins_public_desc', 'local_plugins', $link);
    }

    /**
     * @return bool
     */
    public function requires_login() {
        return false;
    }

    /**
     * @return bool
     */
    public function can_view() {
        return true;
    }

    /**
     * @return array
     */
    protected function define_columns() {

        $columns = [
            'name' => new local_plugins_report_column($this, 'name', get_string('name', 'local_plugins'), null, true),
            'category' => new local_plugins_report_column($this, 'category', get_string('category', 'local_plugins'), null, true),
            'leadmaintainer' => new local_plugins_report_column($this, 'leadmaintainer',
                get_string('leadmaintainer', 'local_plugins'), null, true),
            'timecreated' => new local_plugins_report_column($this, 'timecreated',
                get_string('timecreatedsubmitted', 'local_plugins'), null, true),
            'approvalissue' => new local_plugins_report_column($this, 'approvalissue',
                get_string('approvalissue', 'local_plugins'), null, true),
            'sourcecontrolurl' => new local_plugins_report_column($this, 'sourcecontrolurl',
                get_string('sourcecontrolurl', 'local_plugins')),
        ];

        return $columns;
    }

    /**
     * @return int
     */
    protected function fetch_count() {
        global $DB;

        $pluginssql = "SELECT COUNT(*)
                         FROM {local_plugins_plugin}
                        WHERE approved <> :approved";

        return $DB->count_records_sql($pluginssql, ['approved' => local_plugins_plugin::PLUGIN_APPROVED]);
    }

    /**
     * @return array
     */
    protected function fetch_data() {
        global $DB, $OUTPUT;

        $orderby = array($this->defaultsort);

        if (!empty($this->sort) && array_key_exists($this->sort, $this->get_columns())) {
            switch ($this->sort) {
                case 'name':
                    array_unshift($orderby, 'p.name '.$this->sortdir);
                    break;
                case 'category':
                    array_unshift($orderby, 'c.name '.$this->sortdir);
                    break;
                case 'leadmaintainer':
                    array_unshift($orderby, 'userfirstname '.$this->sortdir);
                    array_unshift($orderby, 'userlastname '.$this->sortdir);
                    break;
                case 'timecreated':
                    array_unshift($orderby, 'p.timecreated '.$this->sortdir);
                    break;
                case 'approvalissue':
                    array_unshift($orderby, 'p.approvalissue '.$this->sortdir);
                    break;
            }
        }

        $userfields1 = join(',', array_map(function ($field) {
            return 'user' . $field;
        }, \core_user\fields::for_name()->get_required_fields()));

        $userfields2 = \core_user\fields::for_name()->get_sql('u', false, 'user')->selects;

        $now = time();

        $sql = "SELECT p.id, p.name, p.timecreated, p.timelastmodified, p.timefirstapproved, c.name AS category, $userfields1,
                       p.sourcecontrolurl, p.approvalissue
                  FROM {local_plugins_plugin} p
             LEFT JOIN (
                           SELECT lpc.pluginid $userfields2
                             FROM {local_plugins_contributor} lpc
                        LEFT JOIN {user} u ON u.id = lpc.userid
                            WHERE lpc.maintainer = ". local_plugins_contributor::LEAD_MAINTAINER. "
                       ) u ON u.pluginid = p.id
             LEFT JOIN {local_plugins_category} c ON c.id = p.categoryid
                 WHERE p.approved <> :approved
              ORDER BY ".join(', ', $orderby);

        $data = $DB->get_records_sql($sql, ['approved' => local_plugins_plugin::PLUGIN_APPROVED],
            $this->page * $this->perpage, $this->perpage);

        foreach ($data as $id => $row) {
            $data[$id]->name = s($row->name);
            $leadmaintainer = user_picture::unalias($row, null, 'userid', 'user');
            $data[$id]->leadmaintainer = s(fullname($leadmaintainer));
            $data[$id]->timecreated = userdate($row->timecreated, get_string('strftimedate', 'core_langconfig'));
            if (clean_param($row->sourcecontrolurl, PARAM_URL) === $row->sourcecontrolurl
                    && (strpos($row->sourcecontrolurl, 'http://') === 0 || strpos($row->sourcecontrolurl, 'https://') === 0)) {
                $data[$id]->sourcecontrolurl = html_writer::link($row->sourcecontrolurl, $row->sourcecontrolurl);
            } else {
                $data[$id]->sourcecontrolurl = '';
            }
            if (!empty($row->approvalissue)) {
                $data[$id]->approvalissue = html_writer::link('https://tracker.moodle.org/browse/'.$row->approvalissue,
                    $row->approvalissue);
            } else {
                $data[$id]->approvalissue = '';
            }
            unset($data[$id]->id);
            unset($data[$id]->firstname);
            unset($data[$id]->lastname);
        }

        $rowcount = count($data);
        if ($rowcount < $this->perpage && $this->totalrows === null) {
            $this->totalrows = $rowcount;
        }

        return $data;
    }
}
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
 * Provides local_moodleorg\manage_donations_table class
 *
 * @package     local_moodleorg
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodleorg;

use table_sql;
use moodle_url;
use html_writer;
use user_picture;

defined('MOODLE_INTERNAL') || die();

/**
 * Displays the list of donations.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_donations_table extends table_sql {

    /**
     * Render the user column
     *
     * @param object $data
     * @return string
     */
    protected function col_userid($data) {
        global $OUTPUT;

        if (!empty($data->userid) and !isguestuser($data->userid)) {
            $user = user_picture::unalias($data, null, 'userid', 'user');
            return $OUTPUT->user_picture($user);
        }
    }

    /**
     * Render the org column
     *
     * @param object $data
     * @return string
     */
    protected function col_org($data) {
        return html_writer::div(s($data->org), 'org').html_writer::div(s($data->url), 'url');
    }

    /**
     * Render the amount column
     *
     * @param object $data
     * @return string
     */
    protected function col_amount($data) {

        setlocale(LC_MONETARY, 'en_AU');
        return money_format('%n', s($data->amount));
    }

    /**
     * Render the timedonated timestamp.
     *
     * @param object $data
     * @return string
     */
    protected function col_timedonated($data) {
        return userdate($data->timedonated, '%Y-%m-%d', 99, false, false);
    }

    /**
     * Displays the actions column
     *
     * @param double $data
     * @return string
     */
    protected function col_actions($data) {
        global $PAGE;

        $actions = [
            'edit' => html_writer::link(
                new moodle_url($PAGE->url, ['edit' => $data->id]),
                get_string('edit'),
                ['class' => 'btn']
            ),
            'delete' => html_writer::link(
                new moodle_url($PAGE->url, ['delete' => $data->id]),
                get_string('delete'),
                ['class' => 'btn']
            ),
        ];

        return implode(' ', $actions);
    }

    /**
     * Default data rendering
     *
     * @param string $column
     * @param object $data
     * @return string
     */
    public function other_cols($column, $data) {
        return s($data->{$column});
    }
}

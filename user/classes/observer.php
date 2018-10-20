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
 * Event observer.
 *
 * @package    core_user
 * @copyright  2018 Victor Deniz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_user;

defined('MOODLE_INTERNAL') || die();

/**
 * Events observer.
 *
 * Stores all actions about modules viewed in recent_activities table.
 *
 * @package    core_user
 * @copyright  2018 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * @var string Recent activities table name.
     */
    private static $table = 'recent_activities';

    /**
     * Register activity views in recent_activities table.
     *
     * When the activity is view for the first time, a new record is created. If the activity was viewed before, the time is
     * updated.
     *
     * @param \core\event\base $event
     */
    public static function store(\core\event\base $event) {
        global $DB;

        $conditions = [
            'userid' => $event->userid,
            'courseid' => $event->courseid,
            'cmid' => $event->contextinstanceid
        ];

        $record = $DB->get_field(self::$table, 'id', $conditions);
        if ($record) {
            $DB->set_field(self::$table, 'timeaccess', $event->timecreated, array('id' => $record));
        } else {
            $eventdata = new \stdClass();

            $eventdata->cmid = $event->contextinstanceid;
            $eventdata->timeaccess = $event->timecreated;
            $eventdata->courseid = $event->courseid;
            $eventdata->userid = $event->userid;

            $DB->insert_record(self::$table, $eventdata);
        }
    }

    /**
     * Remove record when course module is deleted.
     *
     * @param \core\event\base $event
     */
    public static function remove(\core\event\base $event) {
        global $DB;

        $DB->delete_records(self::$table, array('cmid' => $event->contextinstanceid));
    }
}
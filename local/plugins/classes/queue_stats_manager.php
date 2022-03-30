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
 * Approval queue stats controller class is defined here
 *
 * @package     local_plugins
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides access to the queue stats processing
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_queue_stats_manager {

    /** @var int report stats on plugins submitted after this timestamp */
    protected $timestart = null;

    /**
     * Creates an instance of the manager
     */
    public function __construct() {
        $this->timestart = time() - 180 * DAYSECS;
    }

    /**
     * Loads recently submitted plugins together with relevant changelog data
     *
     * @return array
     */
    protected function load_raw_data_from_database() {
        global $DB;

        $sql = "SELECT p.id, p.timecreated, p.timefirstapproved, l.id AS logid, l.bulkid, l.time, l.info, l.userid
                  FROM {local_plugins_plugin} p
                  JOIN {local_plugins_log} l ON l.pluginid = p.id
                 WHERE timecreated >= :timestart
                   AND l.action = :action";

        $rs = $DB->get_recordset_sql($sql, array('timestart' => $this->timestart, 'action' => 'plugin-plugin-edit'));

        $plugins = array();

        foreach ($rs as $record) {
            if (empty($plugins[$record->id])) {
                $plugins[$record->id] = (object)array(
                    'id' => $record->id,
                    'timecreated' => $record->timecreated,
                    'timefirstapproved' => $record->timefirstapproved,
                    'timefirstreviewed' => null,
                    'daystoapprove' => null,
                    'daystoreview' => null,
                    'logs' => array(),
                );
                if ($record->timefirstapproved !== null) {
                    $plugins[$record->id]->daystoapprove = max(1, ceil((($record->timefirstapproved - $record->timecreated) / DAYSECS)));
                }
            }
            $info = unserialize($record->info);
            if (isset($info['oldvalue']['status']) and isset($info['newvalue']['status'])
                    and $info['oldvalue']['status'] !== $info['newvalue']['status']) {
                $plugins[$record->id]->logs[$record->logid] = (object)array(
                    'logid' => $record->logid,
                    'bulkid' => $record->bulkid,
                    'time' => $record->time,
                    'userid' => $record->userid,
                    'oldstatus' => $info['oldvalue']['status'],
                    'newstatus' => $info['newvalue']['status'],
                );
                if ($plugins[$record->id]->timefirstreviewed === null
                        or $record->time < $plugins[$record->id]->timefirstreviewed) {
                    $plugins[$record->id]->timefirstreviewed = $record->time;
                    $plugins[$record->id]->daystoreview = max(1, ceil((($record->time - $plugins[$record->id]->timecreated) / DAYSECS)));
                }
            }
        }

        $rs->close();

        return $plugins;
    }

    /**
     * Returns data structure used for rendering the stats charts
     *
     * @param string $property daystoreview or daystoapprove to be displayed
     * @return stdClass
     */
    protected function prepare_renderable_data($property) {

        if ($property !== 'daystoreview' and $property !== 'daystoapprove') {
            throw new coding_exception('Unexpected property');
        }

        $cache = cache::make('local_plugins', 'queuestats');
        $plugins = $cache->get('plugins');
        if ($plugins === false) {
            $plugins = $this->load_raw_data_from_database();
            $cache->set('plugins', $plugins);
            //debugging('Reloaded raw data into local_plugins/queuestats cache', DEBUG_DEVELOPER);
        }

        foreach ($plugins as $id => $plugin) {
            if ($plugin->{$property} === null) {
                unset($plugins[$id]);
            }
        }

        $data = (object)array(
            'totalplugins' => count($plugins),
            'sample' => array(),
            'mindays' => null,
            'maxdays' => null,
            'mediandays' => null,
            'distribution' => array(),
        );

        if (empty($plugins)) {
            return $data;
        }

        foreach ($plugins as $plugin) {

            if ($data->mindays === null or $plugin->{$property} < $data->mindays) {
                $data->mindays = $plugin->{$property};
            }

            if ($data->maxdays === null or $plugin->{$property} > $data->maxdays) {
                $data->maxdays = $plugin->{$property};
            }

            $data->sample[] = $plugin->{$property};
        }

        $data->mediandays = $this->median($data->sample);

        for ($d = 1; $d <= $data->maxdays; $d++) {
            $data->distribution[$d] = 0;
        }

        foreach ($plugins as $plugin) {
            $data->distribution[$plugin->{$property}]++;
        }

        return $data;
    }

    /**
     * Returns data for rendering the distribution of plugin initial review times
     *
     * @return stdClass
     */
    public function get_review_times_data() {
        return $this->prepare_renderable_data('daystoreview');
    }

    /**
     * Returns data for rendering the distribution of plugin approval times
     *
     * @return stdClass
     */
    public function get_approval_times_data() {
        return $this->prepare_renderable_data('daystoapprove');
    }

    /**
     * Invalidates caches used by this manager
     */
    public static function invalidate_caches() {
        $cache = cache::make('local_plugins', 'queuestats');
        $cache->delete('plugins');
    }

    /**
     * Calculates the median of the given data sample
     *
     * @param array $sample list of integers in our case
     * @return int
     */
    protected function median(array $sample) {

        $values = array_values($sample);

        if (empty($values)) {
            return null;
        }

        $count = count($values);

        if ($count == 1) {
            return $values[0];
        }

        $halfindex = floor(($count - 1) / 2);

        sort($values);

        if ($count % 2) {
            return $values[$halfindex];

        } else {
            return ($values[$halfindex] + $values[$halfindex + 1]) / 2;
        }
    }
}

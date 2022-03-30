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
 * @category    phpunit
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

/**
 * Tests for some functionality of the {@link local_plugins_queue_stats_manager} class
 */
class local_plugins_queue_stats_manager_testcase extends advanced_testcase {

    public function test_get_data_empty() {
        $this->resetAfterTest();

        $manager = new local_plugins_queue_stats_manager();

        $reviews = $manager->get_review_times_data();
        $this->assertTrue(is_object($reviews));
        $this->assertEquals(0, $reviews->totalplugins);

        $approvals = $manager->get_approval_times_data();
        $this->assertTrue(is_object($approvals));
        $this->assertEquals(0, $approvals->totalplugins);
    }

    public function test_get_data_simple() {
        $this->resetAfterTest();

        $now = time();

        // Plugin submitted week ago, just rejected.
        $this->make_plugin($now - 7 * DAYSECS, null, $now);

        // Plugin submitted yesterday, just approved.
        $this->make_plugin($now - 36 * HOURSECS,$now);

        $manager = new local_plugins_queue_stats_manager();

        $data = $manager->get_review_times_data();
        $this->assertEquals(2, $data->totalplugins);
        $this->assertEquals(2, $data->mindays);
        $this->assertEquals(7, $data->maxdays);
        $this->assertEquals(4.5, $data->mediandays);
        $this->assertEquals(0, $data->distribution[1]);
        $this->assertEquals(1, $data->distribution[2]);
        $this->assertEquals(0, $data->distribution[3]);
        $this->assertEquals(0, $data->distribution[4]);
        $this->assertEquals(0, $data->distribution[5]);
        $this->assertEquals(0, $data->distribution[6]);
        $this->assertEquals(1, $data->distribution[7]);
        $this->assertEquals(7, count($data->distribution));

        $data = $manager->get_approval_times_data();
        $this->assertEquals(1, $data->totalplugins);
        $this->assertEquals(2, $data->mindays);
        $this->assertEquals(2, $data->maxdays);
        $this->assertEquals(2, $data->mediandays);
        $this->assertEquals(0, $data->distribution[1]);
        $this->assertEquals(1, $data->distribution[2]);
        $this->assertEquals(2, count($data->distribution));
    }

    public function test_get_data_old_excluded() {
        $this->resetAfterTest();

        $now = time();

        // Plugin submitted year ago, unapproved month ago, approved week ago.
        $this->make_plugin($now - 365 * DAYSECS, $now - 7 * DAYSECS, $now - 30 * DAYSECS);

        $manager = new local_plugins_queue_stats_manager();

        $data = $manager->get_review_times_data();
        $this->assertEquals(0, $data->totalplugins);

        $data = $manager->get_approval_times_data();
        $this->assertEquals(0, $data->totalplugins);
    }

    public function test_get_data_days_counting() {
        $this->resetAfterTest();

        $now = time();

        // Day        1     2     3
        //         |-----|-----|-----|
        // Hrs ago 0    24    48    72
        //
        // Plugin submitted on Monday morning and reviewed on
        // Tuesday afternoon counts as it took 2 days (i.e. more than
        // 24 hours and less than 48 hours).

        $this->make_plugin($now - (24 * HOURSECS - 2 * MINSECS), $now);
        $this->make_plugin($now - (24 * HOURSECS + 2 * MINSECS), $now);

        $this->make_plugin($now - (48 * HOURSECS - 2 * MINSECS), $now, $now - (24 * HOURSECS - 3 * MINSECS));
        $this->make_plugin($now - (48 * HOURSECS + 2 * MINSECS), $now, $now - (24 * HOURSECS + 3 * MINSECS));

        $manager = new local_plugins_queue_stats_manager();

        $data = $manager->get_approval_times_data();
        $this->assertEquals(3, count($data->distribution));
        $this->assertEquals(1, $data->distribution[1]);
        $this->assertEquals(2, $data->distribution[2]);
        $this->assertEquals(1, $data->distribution[3]);

        $data = $manager->get_review_times_data();
        $this->assertEquals(2, count($data->distribution));
        $this->assertEquals(2, $data->distribution[1]);
        $this->assertEquals(2, $data->distribution[2]);
    }

    /**
     * Mock-up a plugin's history records in the database
     *
     * @param int $submitted timestamp of when the plugin was submitted
     * @param int $approved timestamp of when the plugin was first approved, if ever
     * @param int $unapproved timestamp of when the plugin was unapproved, if ever
     */
    protected function make_plugin($submitted, $approved = null, $unapproved = null) {
        global $DB;
        static $bulkid = 1;

        $record = (object)array(
            'categoryid' => 999,
            'shortdescription' => '',
            'timecreated' => $submitted,
            'timelastmodified' => $submitted,
        );

        if ($approved !== null) {
            $record->timefirstapproved = $approved;
            if ($approved > $record->timelastmodified) {
                $record->timelastmodified = $approved;
            }
        }

        if ($unapproved !== null) {
            if ($unapproved > $record->timelastmodified) {
                $record->timelastmodified = $unapproved;
            }
        }

        $pluginid = $DB->insert_record('local_plugins_plugin', $record);

        $DB->insert_record('local_plugins_log', array(
            'action' => 'plugin-plugin-add',
            'pluginid' => $pluginid,
            'time' => $submitted,
            'bulkid' => $bulkid++,
            'timeday' => 999,
            'userid' => 999,
            'ip' => '127.0.0.1',
            'info' => serialize(array(
                'newvalue' => array(
                    'status' => 'Waiting for approval',
                )
            )),
        ));

        if ($approved !== null) {
            $DB->insert_record('local_plugins_log', array(
                'action' => 'plugin-plugin-edit',
                'pluginid' => $pluginid,
                'time' => $approved,
                'bulkid' => $bulkid++,
                'timeday' => 999,
                'userid' => 999,
                'ip' => '127.0.0.1',
                'info' => serialize(array(
                    'oldvalue' => array(
                        'status' => 'Waiting for approval'
                    ),
                    'newvalue' => array(
                        'status' => 'Approved'
                    ),
                )),
            ));
        }

        if ($unapproved !== null) {
            $DB->insert_record('local_plugins_log', array(
                'action' => 'plugin-plugin-edit',
                'pluginid' => $pluginid,
                'time' => $unapproved,
                'bulkid' => $bulkid++,
                'timeday' => 999,
                'userid' => 999,
                'ip' => '127.0.0.1',
                'info' => serialize(array(
                    'oldvalue' => array(
                        'status' => 'Waiting for approval'
                    ),
                    'newvalue' => array(
                        'status' => 'Needs more work'
                    ),
                )),
            ));
        }
    }
}

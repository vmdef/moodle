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
 * @subpackage  stats
 * @category    phpunit
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

/**
 * Tests for some functionality of the {@link local_plugins_stats_manager} class
 */
class local_plugins_stats_manager_testcase extends advanced_testcase {

    public function test_recent_months_helper() {

        // Given that the current date is February 20, 2015.
        $now = mktime(16, 45, 00, 2, 20, 2015);
        // And I want to know 4 recent month.
        $recent = $this->recent_months(4, $now);
        // Then the returned structure should look like this.
        $this->assertEquals(4, count($recent));
        $this->assertEquals(2015, $recent[0]['year']);
        $this->assertEquals(2, $recent[0]['month']);
        $this->assertEquals(2015, $recent[1]['year']);
        $this->assertEquals(1, $recent[1]['month']);
        $this->assertEquals(2014, $recent[2]['year']);
        $this->assertEquals(12, $recent[2]['month']);
        $this->assertEquals(2014, $recent[3]['year']);
        $this->assertEquals(11, $recent[3]['month']);
    }

    public function test_update_download_stats() {
        global $DB;

        $this->resetAfterTest();
        $statsman = new local_plugins_stats_manager();

        // Mock up a plugin.
        $foo = (object)array(
            'name' => 'Foo',
            'categoryid' => 3,
            'shortdescription' => 'Foo rocks!',
            'timecreated' => time() - 2 * YEARSECS,
            'timelastmodified' => time()  - 2 * YEARSECS,
        );
        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        // Mock up two plugin versions.
        $foo1 = (object)array(
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => time() - 2 * YEARSECS,
            'timelastmodified' => time() - 2 * YEARSECS,
        );
        $foo2 = (object)array(
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => time() - YEARSECS,
            'timelastmodified' => time() - YEARSECS,
        );
        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);
        $foo2->id = $DB->insert_record('local_plugins_vers', $foo2);

        // Mock up some download records.
        $ym = $this->recent_months(6);

        $this->assertTrue($statsman->log_version_download($foo1->id, $this->random_timestamp($ym[5]['year'], $ym[5]['month']), 0, 'unittest', '1.0.0.1'));
        $this->assertTrue($statsman->log_version_download($foo2->id, $this->random_timestamp($ym[1]['year'], $ym[1]['month']), 0, 'unittest', '1.0.0.2'));
        $this->assertTrue($statsman->log_version_download($foo1->id, $this->random_timestamp($ym[0]['year'], $ym[0]['month']), 0, 'unittest', '1.0.0.3'));
        $this->assertTrue($statsman->log_version_download($foo1->id, $this->random_timestamp($ym[0]['year'], $ym[0]['month']), 0, 'unittest', '1.0.0.3'));

        $this->assertSame(0, $statsman->get_stats_downloads_recent());
        $this->assertSame(0, $statsman->get_stats_plugin_recent($foo->id));
        $this->assertSame(0, $statsman->get_stats_plugin_recent($foo->id + 1)); // Non-existing plugin.

        // Check the plugin's property aggdownloads is not set yet.
        $this->assertNull($DB->get_field('local_plugins_plugin', 'aggdownloads', array('id' => $foo->id), MUST_EXIST));

        // Update the stats.
        $statsman->update_download_stats();

        // Recent = last 90 days.
        $this->assertSame(3, $statsman->get_stats_downloads_recent());
        $this->assertSame(3, $statsman->get_stats_plugin_recent($foo->id));
        $this->assertSame(0, $statsman->get_stats_plugin_recent($foo->id + 1));

        // Check the plugin's property aggdownloads was set.
        $this->assertEquals(3, $DB->get_field('local_plugins_plugin', 'aggdownloads', array('id' => $foo->id), MUST_EXIST));

        // Check results - recent downloads.
        $this->assertEquals(2, $DB->get_field('local_plugins_stats_cache', 'downloads',
            array('pluginid' => $foo->id, 'versionid' => $foo1->id, 'month' => 0, 'year' => 0), MUST_EXIST));
        $this->assertEquals(1, $DB->get_field('local_plugins_stats_cache', 'downloads',
            array('pluginid' => $foo->id, 'versionid' => $foo2->id, 'month' => 0, 'year' => 0), MUST_EXIST));

        // Check results - monthly downloads.
        $this->assertEquals(2, $DB->get_field('local_plugins_stats_cache', 'downloads',
            array('pluginid' => $foo->id, 'versionid' => $foo1->id, 'month' => $ym[0]['month'], 'year' => $ym[0]['year']), MUST_EXIST));
        $this->assertEquals(1, $DB->get_field('local_plugins_stats_cache', 'downloads',
            array('pluginid' => $foo->id, 'versionid' => $foo1->id, 'month' => $ym[5]['month'], 'year' => $ym[5]['year']), MUST_EXIST));
        $this->assertEquals(1, $DB->get_field('local_plugins_stats_cache', 'downloads',
            array('pluginid' => $foo->id, 'versionid' => $foo2->id, 'month' => $ym[1]['month'], 'year' => $ym[1]['year']), MUST_EXIST));
    }

    public function test_get_stats_plugin_monthly() {
        global $DB;

        $this->resetAfterTest();
        $statsman = new local_plugins_stats_manager();

        // Mock up two plugins.
        $foo = (object)array(
            'name' => 'Foo',
            'categoryid' => 3,
            'shortdescription' => 'Foo rocks!',
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
        );
        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        $bar = (object)array(
            'name' => 'Bar',
            'categoryid' => 3,
            'shortdescription' => 'Bar sucks!',
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
        );
        $bar->id = $DB->insert_record('local_plugins_plugin', $bar);

        // Mock up plugin versions.
        $foo1 = (object)array(
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated,
            'timelastmodified' => $foo->timecreated,
        );
        $foo2 = (object)array(
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated + 60,
            'timelastmodified' => $foo->timecreated + 60,
        );
        $bar1 = (object)array(
            'pluginid' => $bar->id,
            'userid' => 8,
            'timecreated' => $bar->timecreated,
            'timelastmodified' => $bar->timecreated,
        );
        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);
        $foo2->id = $DB->insert_record('local_plugins_vers', $foo2);
        $bar1->id = $DB->insert_record('local_plugins_vers', $bar1);

        // Mock up download requests.
        for ($year = 2000; $year <= 2013; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                for ($x = 1; $x <= $month; $x++) {
                    $statsman->log_version_download($foo1->id, $this->random_timestamp($year, $month), 0, 'unittest', '0.0.0.0', 0);
                    $statsman->log_version_download($foo2->id, $this->random_timestamp($year, $month), 0, 'unittest', '0.0.0.0', 0);
                    $statsman->log_version_download($bar1->id, $this->random_timestamp($year, $month), 0, 'unittest', '0.0.0.0', 0);
                }
            }
        }

        $this->assertIsArray($statsman->get_stats_plugin_monthly($foo->id));

        $statsman->update_download_stats();

        // Check results.
        $foostats = $statsman->get_stats_plugin_monthly($foo->id);
        $barstats = $statsman->get_stats_plugin_monthly($bar->id);

        for ($year = 2000; $year <= 2013; $year++) {
            for ($month = 1; $month <= 12; $month++) {
                $this->assertEquals(2 * $month, $foostats[$year][$month]);
                $this->assertEquals($month, $barstats[$year][$month]);
            }
        }

        $t1 = mktime(23, 59, 59, 3, 31, 1998);
        $t2 = mktime(0, 0, 0, 4, 1, 1998);
        $this->assertEquals(1, $t2 - $t1);
        $foostats = $statsman->get_stats_plugin_monthly($foo->id, $t1, $t2);
        $this->assertSame(array(1998 => array(3 => 0, 4 => 0)), $foostats);
    }

    public function test_get_stats_plugin_by_version_monthly() {
        global $DB;

        $this->resetAfterTest();
        $statsman = new local_plugins_stats_manager();

        // Mock up a plugin.
        $foo = (object)array(
            'name' => 'Foo',
            'categoryid' => 3,
            'shortdescription' => 'Foo rocks!',
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
        );
        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        // Mock up plugin versions.
        $foo1 = (object)array(
            'version' => 2013010203,
            'releasename' => '1.0',
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated,
            'timelastmodified' => $foo->timecreated,
            'approved' => 1,
            'visible' => 1,
        );
        $foo2 = (object)array(
            'version' => 2013010204,
            'releasename' => null,
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated + 60,
            'timelastmodified' => $foo->timecreated + 60,
            'approved' => 1,
            'visible' => 1,
        );
        $foo3 = (object)array(
            'version' => 2014010100,
            'releasename' => '1.3',
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated + 90,
            'timelastmodified' => $foo->timecreated + 90,
            'approved' => 1,
            'visible' => 1,
        );
        $foo4 = (object)array(
            'version' => 2014010100,
            'releasename' => '2.0',
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated + 60,
            'timelastmodified' => $foo->timecreated + 60,
            'approved' => 0,
            'visible' => 1,
        );
        $foo5 = (object)array(
            'version' => 2014010100,
            'releasename' => '2.0',
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated + 60,
            'timelastmodified' => $foo->timecreated + 60,
            'approved' => 1,
            'visible' => 0,
        );
        $foo6 = (object)array(
            'version' => 2015112000,
            'releasename' => '3.0',
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated + 400,
            'timelastmodified' => $foo->timecreated + 400,
            'approved' => 1,
            'visible' => 1,
        );
        $foo7 = (object)array(
            'version' => 2017080800,
            'releasename' => '4.0',
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated + 800,
            'timelastmodified' => $foo->timecreated + 800,
            'approved' => 1,
            'visible' => 1,
        );

        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);
        $foo2->id = $DB->insert_record('local_plugins_vers', $foo2);
        $foo3->id = $DB->insert_record('local_plugins_vers', $foo3);
        $foo4->id = $DB->insert_record('local_plugins_vers', $foo4);
        $foo5->id = $DB->insert_record('local_plugins_vers', $foo5);
        $foo6->id = $DB->insert_record('local_plugins_vers', $foo6);
        $foo7->id = $DB->insert_record('local_plugins_vers', $foo7);

        // Mock up download requests.
        $statsman->log_version_download($foo1->id, $this->random_timestamp(2013, 4), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo1->id, $this->random_timestamp(2013, 4), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo1->id, $this->random_timestamp(2013, 6), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo2->id, $this->random_timestamp(2013, 5), 0, 'unittest', '0.0.0.0', 1);
        $statsman->log_version_download($foo2->id, $this->random_timestamp(2013, 6), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo4->id, $this->random_timestamp(2013, 4), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo5->id, $this->random_timestamp(2013, 5), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo6->id, $this->random_timestamp(2013, 4), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo6->id, $this->random_timestamp(2013, 5), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo7->id, $this->random_timestamp(2013, 8), 0, 'unittest', '0.0.0.0', 0);

        // Check results.
        $statsman->update_download_stats();

        $results = $statsman->get_stats_plugin_by_version_monthly($foo->id, $this->random_timestamp(2013, 3), $this->random_timestamp(2013, 7));

        $this->assertEquals(1, count($results));
        $this->assertEquals(5, count($results[2013]));

        // All months must have the same number of version records (the charts
        // API seems to rely on that) representing versions foo1, foo2, foo3,
        // foo6 and foo7. Versions foo4 and foo5 are not approved or visible.
        foreach ([3, 4, 5, 6, 7] as $m) {
            $this->assertEquals(5, count($results[2013][$m]));
        }

        // The versions come in same order for all the months.
        foreach ([3, 4, 5, 6, 7] as $m) {
            $this->assertSame([$foo1->id, $foo2->id, $foo3->id, $foo6->id, $foo7->id], array_keys($results[2013][$m]));
        }

        // Release name takes precedence over version number, if both are available.
        $this->assertSame('1.0', $results[2013][3][$foo1->id]->name);
        $this->assertEquals(2013010204, $results[2013][3][$foo2->id]->name);

        // 03/2013 has no tracked downloads.
        $this->assertEquals(0, $results[2013][3][$foo1->id]->downloads);
        $this->assertEquals(0, $results[2013][3][$foo2->id]->downloads);
        $this->assertEquals(0, $results[2013][3][$foo3->id]->downloads);
        $this->assertEquals(0, $results[2013][3][$foo6->id]->downloads);
        $this->assertEquals(0, $results[2013][3][$foo7->id]->downloads);

        // 04/2013 has foo1 twice and foo6 once.
        $this->assertEquals(2, $results[2013][4][$foo1->id]->downloads);
        $this->assertEquals(0, $results[2013][4][$foo2->id]->downloads);
        $this->assertEquals(0, $results[2013][4][$foo3->id]->downloads);
        $this->assertEquals(1, $results[2013][4][$foo6->id]->downloads);
        $this->assertEquals(0, $results[2013][4][$foo7->id]->downloads);

        // 05/2013 does not have foo2 - it is excluded.
        $this->assertEquals(0, $results[2013][5][$foo1->id]->downloads);
        $this->assertEquals(0, $results[2013][5][$foo2->id]->downloads);
        $this->assertEquals(0, $results[2013][5][$foo3->id]->downloads);
        $this->assertEquals(1, $results[2013][5][$foo6->id]->downloads);
        $this->assertEquals(0, $results[2013][5][$foo7->id]->downloads);

        // 06/2013 has only foo1 once and foo2 once.
        $this->assertEquals(1, $results[2013][6][$foo1->id]->downloads);
        $this->assertEquals(1, $results[2013][6][$foo2->id]->downloads);
        $this->assertEquals(0, $results[2013][6][$foo3->id]->downloads);
        $this->assertEquals(0, $results[2013][6][$foo6->id]->downloads);
        $this->assertEquals(0, $results[2013][6][$foo7->id]->downloads);

        // 07/2013 has no tracked downloads.
        $this->assertEquals(0, $results[2013][7][$foo1->id]->downloads);
        $this->assertEquals(0, $results[2013][7][$foo2->id]->downloads);
        $this->assertEquals(0, $results[2013][7][$foo3->id]->downloads);
        $this->assertEquals(0, $results[2013][7][$foo6->id]->downloads);
        $this->assertEquals(0, $results[2013][7][$foo7->id]->downloads);
    }

    public function test_update_download_stats_implicit_exclusion() {
        global $DB;

        $this->resetAfterTest();
        $statsman = new local_plugins_stats_manager();

        // Mock up a plugin.
        $foo = (object)array(
            'name' => 'Foo',
            'categoryid' => 3,
            'shortdescription' => 'Foo rocks!',
            'timecreated' => time() - 2 * YEARSECS,
            'timelastmodified' => time() - 2 * YEARSECS,
        );
        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        // Mock up two plugin versions.
        $foo1 = (object)array(
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => time() - 2 * YEARSECS,
            'timelastmodified' => time() - 2 * YEARSECS,
        );
        $foo2 = (object)array(
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => time() - YEARSECS,
            'timelastmodified' => time() - YEARSECS,
        );
        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);
        $foo2->id = $DB->insert_record('local_plugins_vers', $foo2);

        // Simulate recent malicious download activity in two waves.
        for ($i = 0; $i < 100; $i++) {
            $statsman->log_version_download($foo1->id, time() - 4 * DAYSECS + $i, 0, 'unittest', '6.6.6.6');
            $statsman->log_version_download($foo1->id, time() - 2 * DAYSECS + $i, 0, 'unittest', '6.6.6.6');
        }

        // Update the stats.
        $statsman->update_download_stats();

        // Check results - only first 10 downloads from each wave should be counted.
        $this->assertEquals(20, $DB->get_field('local_plugins_stats_cache', 'downloads',
            array('pluginid' => $foo->id, 'versionid' => $foo1->id, 'month' => 0, 'year' => 0), MUST_EXIST));
    }

    public function test_get_overview_stats() {
        global $DB;

        $this->resetAfterTest();
        $statsman = new local_plugins_stats_manager();

        // Mock up versions.
        $cat1 = (object)array(
            'name' => 'Category A',
            'shortdescription' => 'Test category A',
        );
        $cat1->id = $DB->insert_record('local_plugins_category', $cat1);

        $cat2 = (object)array(
            'name' => 'Category B',
            'shortdescription' => 'Test category B',
        );
        $cat2->id = $DB->insert_record('local_plugins_category', $cat1);

        // Mock up plugins.
        $foo = (object)array(
            'name' => 'Foo',
            'categoryid' => $cat1->id,
            'shortdescription' => 'Foo rocks!',
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
            'approved' => 1,
            'visible' => 1,
        );
        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        $bar = (object)array(
            'name' => 'Bar',
            'categoryid' => $cat2->id,
            'shortdescription' => 'Bar sucks!',
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
            'approved' => 1,
            'visible' => 1,
        );
        $bar->id = $DB->insert_record('local_plugins_plugin', $bar);

        $baz = (object)array(
            'name' => 'Baz',
            'categoryid' => $cat2->id,
            'shortdescription' => 'Baz rolls!',
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
            'approved' => 1,
            'visible' => 1,
        );
        $baz->id = $DB->insert_record('local_plugins_plugin', $baz);

        $nil = (object)array(
            'name' => 'Nul',
            'categoryid' => $cat1->id,
            'shortdescription' => 'Not visible',
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
            'approved' => 1,
            'visible' => 0,
        );
        $nil->id = $DB->insert_record('local_plugins_plugin', $nil);

        // Mock up plugin versions.
        $foo1 = (object)array(
            'version' => 2013010203,
            'releasename' => '1.0',
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated,
            'timelastmodified' => $foo->timecreated,
            'approved' => 1,
            'visible' => 1,
        );
        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);

        $foo2 = (object)array(
            'version' => 2013010204,
            'releasename' => '1.1',
            'pluginid' => $foo->id,
            'userid' => 8,
            'timecreated' => $foo->timecreated + 5,
            'timelastmodified' => $foo->timecreated +5,
            'approved' => 1,
            'visible' => 1,
        );
        $foo2->id = $DB->insert_record('local_plugins_vers', $foo2);

        $bar1 = (object)array(
            'version' => 2013010203,
            'releasename' => '1.0',
            'pluginid' => $bar->id,
            'userid' => 8,
            'timecreated' => $bar->timecreated,
            'timelastmodified' => $bar->timecreated,
            'approved' => 1,
            'visible' => 1,
        );
        $bar1->id = $DB->insert_record('local_plugins_vers', $bar1);

        $baz1 = (object)array(
            'version' => 2013010203,
            'releasename' => '1.0',
            'pluginid' => $baz->id,
            'userid' => 8,
            'timecreated' => $baz->timecreated,
            'timelastmodified' => $baz->timecreated,
            'approved' => 1,
            'visible' => 1,
        );
        $baz1->id = $DB->insert_record('local_plugins_vers', $baz1);

        $nil1 = (object)array(
            'version' => 2013010203,
            'releasename' => '1.0',
            'pluginid' => $nil->id,
            'userid' => 8,
            'timecreated' => $nil->timecreated,
            'timelastmodified' => $nil->timecreated,
            'approved' => 1,
            'visible' => 1,
        );
        $nil1->id = $DB->insert_record('local_plugins_vers', $nil1);


        // Mock up download requests.
        $statsman->log_version_download($foo1->id, $this->random_timestamp(2011, 3), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo1->id, $this->random_timestamp(2011, 4), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo1->id, $this->random_timestamp(2012, 4), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo2->id, $this->random_timestamp(2013, 4), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo2->id, $this->random_timestamp(2014, 1), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo2->id, $this->random_timestamp(2014, 2), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($foo1->id, $this->random_timestamp(2014, 3), 0, 'unittest', '0.0.0.0', 0);

        $statsman->log_version_download($bar1->id, $this->random_timestamp(2014, 3), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($bar1->id, $this->random_timestamp(2014, 3), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($bar1->id, $this->random_timestamp(2014, 4), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($bar1->id, $this->random_timestamp(2014, 4), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($bar1->id, $this->random_timestamp(2014, 5), 0, 'unittest', '0.0.0.0', 0);
        $statsman->log_version_download($bar1->id, $this->random_timestamp(2014, 5), 0, 'unittest', '0.0.0.0', 0);

        $statsman->log_version_download($baz1->id, $this->random_timestamp(2014, 5), 0, 'unittest', '0.0.0.0', 0);

        $statsman->log_version_download($nil1->id, $this->random_timestamp(2014, 5), 0, 'unittest', '0.0.0.0', 0);

        // Check results.
        $statsman->update_download_stats();

        $results = $statsman->get_stats_top_plugins();
        $this->assertIsArray($results);
        $this->assertEquals(3, count($results));

        $top = array_shift($results);
        $this->assertEquals($foo->id, $top->id);
        $this->assertSame('Foo', $top->name);
        $this->assertEquals(7, $top->downloads);

        $next = array_shift($results);
        $this->assertEquals($bar->id, $next->id);
        $this->assertSame('Bar', $next->name);
        $this->assertEquals(6, $next->downloads);

        $next = array_shift($results);
        $this->assertEquals($baz->id, $next->id);
        $this->assertSame('Baz', $next->name);
        $this->assertEquals(1, $next->downloads);

        $results = $statsman->get_stats_top_plugins($cat2->id);
        $this->assertIsArray($results);
        $this->assertEquals(2, count($results));
        $this->assertSame(array($bar->id, $baz->id), array_keys($results));

        $results = $statsman->get_stats_top_plugins(0, 2);
        $this->assertIsArray($results);
        $this->assertEquals(2, count($results));
        $this->assertSame(array($foo->id, $bar->id), array_keys($results));

        $results = $statsman->get_stats_top_plugins(0, 20, $this->random_timestamp(2014, 1), $this->random_timestamp(2014, 4));
        $this->assertIsArray($results);
        $this->assertEquals(2, count($results));
        $this->assertSame(array($bar->id, $foo->id), array_keys($results));
        $this->assertEquals(4, $results[$bar->id]->downloads);
        $this->assertEquals(3, $results[$foo->id]->downloads);

        $results = $statsman->get_stats_total_monthly(0, null, $this->random_timestamp(2014, 5));
        $this->assertSame(array(2011, 2012, 2013, 2014), array_keys($results));
        $this->assertEquals(10, count($results[2011]));
        $this->assertEquals(12, count($results[2012]));
        $this->assertEquals(12, count($results[2013]));
        $this->assertEquals(5, count($results[2014]));
        $this->assertEquals(1, $results[2011][3]);
        $this->assertEquals(0, $results[2011][5]);
        $this->assertEquals(0, $results[2012][1]);
        $this->assertEquals(0, $results[2013][2]);
        $this->assertEquals(1, $results[2013][4]);
        $this->assertEquals(3, $results[2014][3]);
        $this->assertEquals(4, $results[2014][5]);

        $results = $statsman->get_stats_total_monthly(0, $this->random_timestamp(2013, 12), $this->random_timestamp(2014, 1));
        $this->assertSame(array(2013, 2014), array_keys($results));
        $this->assertEquals(1, count($results[2013]));
        $this->assertEquals(1, count($results[2014]));
        $this->assertEquals(0, $results[2013][12]);
        $this->assertEquals(1, $results[2014][1]);

        $results = $statsman->get_stats_total_monthly($cat1->id, $this->random_timestamp(2013, 10), $this->random_timestamp(2014, 3));
        $this->assertSame(array(2013, 2014), array_keys($results));
        $this->assertEquals(3, count($results[2013]));
        $this->assertEquals(3, count($results[2014]));
        $this->assertEquals(0, $results[2013][10]);
        $this->assertEquals(0, $results[2013][11]);
        $this->assertEquals(0, $results[2013][12]);
        $this->assertEquals(1, $results[2014][1]);
        $this->assertEquals(1, $results[2014][2]);
        $this->assertEquals(1, $results[2014][3]); // Just foo1, not bar1.
    }

    /**
     * Generates a random timestamp that falls into the given year and month.
     *
     * Tries to minimise potential DST issues by ignoring early morning hours
     * when DST change typically happens.
     *
     * @param int $year
     * @param int $month
     * @return int timestamp
     */
    protected function random_timestamp($year, $month) {
        return mktime(rand(6, 23), rand(0,59), rand(0, 59), $month, rand(1, 28), $year);
    }

    /**
     * Returns $count recent year and months numbers
     *
     * Example, in November 2015, this returns list like (2015, 11), (2015, 10), (2015, 9), ...
     *
     * @param int $count number of months
     * @param int $now timestamp of now
     *
     * @return array list of array(year=>int, month=>int)
     */
    protected function recent_months($count, $now=null) {

        if ($now === null) {
            $now = time();
        }

        $list = array();
        for ($i = 0; $i < $count; $i++) {
            // Get the timestamp of the first day of the month, 10:00 am
            $time = mktime(10, 0, 0, date('n', $now) - $i, date('j', $now), date('Y', $now));
            $list[] = array(
                'year' => date('Y', $time),
                'month' => date('n', $time),
            );
        }

        return $list;
    }
}

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
 * @subpackage  usage
 * @category    phpunit
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/tests/fixtures/testable.php');

/**
 * Tests for some functionality of the {@link local_plugins_usage_manager} class
 */
class local_plugins_usage_manager_testcase extends advanced_testcase {

    public function setUp(): void {
        global $CFG;

        $this->resetAfterTest();
        set_config('usagestatsfilesroot', $CFG->dirroot.'/local/plugins/tests/fixtures/update_stats', 'local_plugins');
    }

    public function test_no_monthly_stats_files_location() {

        set_config('usagestatsfilesroot', '', 'local_plugins');
        $usageman = new testable_local_plugins_usage_manager();
        $this->assertEmpty($usageman->get_all_monthly_stats_files());
    }

    public function test_invalid_monthly_stats_files_location() {

        set_config('usagestatsfilesroot', 'c:/I really/hope/That you do not/have/such/dir!', 'local_plugins');
        $usageman = new testable_local_plugins_usage_manager();
        $this->assertEmpty($usageman->get_all_monthly_stats_files());
    }

    public function test_get_all_monthly_stats_files() {
        $usageman = new testable_local_plugins_usage_manager();

        $files = $usageman->get_all_monthly_stats_files();

        $this->assertEquals('monthly.stats', basename($files['2014']['12']));
        $this->assertEquals('monthly.stats', basename($files['2015']['01']));
        $this->assertEquals(2, count($files));
        $this->assertEquals(1, count($files['2014']));
        $this->assertEquals(1, count($files['2015']));
        $this->assertIsString($files['2014']['12']);
        $this->assertIsString($files['2015']['01']);
    }

    public function test_filter_stats_files() {
        $usageman = new testable_local_plugins_usage_manager();

        $files = array();

        for ($y = 2013; $y <= 2015; $y++) {
            for ($m = 1; $m <= 12; $m++) {
                $mm = sprintf('%02d', $m);
                $files[$y][$mm] = '/path/to/'.$y.'/'.$mm.'/monthly.stats';
            }
            // Shuffle the structure so that we do not rely on particular order.
            $this->helper_shuffle_assoc($files[$y]);
        }
        $this->helper_shuffle_assoc($files);

        unset($files['2013']['01']);
        unset($files['2013']['02']);
        unset($files['2013']['03']);
        unset($files['2015']['10']);
        unset($files['2015']['11']);
        unset($files['2015']['12']);

        // Without limits, filter does not filter anything.
        $a = $this->helper_flatten_files($usageman->filter_stats_files($files));
        $this->assertEquals(30, count($a));
        $this->assertFalse(isset($a['2013/03']));
        $this->assertTrue(isset($a['2013/04']));
        $this->assertTrue(isset($a['2015/09']));
        $this->assertFalse(isset($a['2015/10']));

        // Filter from the given start year.
        $a = $this->helper_flatten_files($usageman->filter_stats_files($files, '2015'));
        $this->assertEquals(9, count($a));
        $this->assertFalse(isset($a['2014/12']));
        $this->assertTrue(isset($a['2015/01']));
        $this->assertTrue(isset($a['2015/09']));

        // Filter from the given start year and month.
        $a = $this->helper_flatten_files($usageman->filter_stats_files($files, '2015', '07'));
        $this->assertEquals(3, count($a));
        $this->assertTrue(isset($a['2015/07']));
        $this->assertTrue(isset($a['2015/08']));
        $this->assertTrue(isset($a['2015/09']));

        // Filter from the given start in the future.
        $a = $this->helper_flatten_files($usageman->filter_stats_files($files, '2016', '01'));
        $this->assertEmpty($a);

        // Filter up to given end.
        $a = $this->helper_flatten_files($usageman->filter_stats_files($files, null, null, '2014', '03'));
        $this->assertEquals(12, count($a));
        $this->assertTrue(isset($a['2013/04']));
        $this->assertTrue(isset($a['2013/12']));
        $this->assertTrue(isset($a['2014/03']));
        $this->assertFalse(isset($a['2014/04']));

        // Filter all but the given period.
        $a = $this->helper_flatten_files($usageman->filter_stats_files($files, '2013', '10', '2015', '02'));
        $this->assertEquals(17, count($a));
        $this->assertFalse(isset($a['2013/09']));
        $this->assertTrue(isset($a['2013/10']));
        $this->assertTrue(isset($a['2015/02']));
        $this->assertFalse(isset($a['2015/03']));

        // Invalid period borders (end before start).
        $a = $this->helper_flatten_files($usageman->filter_stats_files($files, '2015', '12', '2013', '01'));
        $this->assertEmpty($a);

        // Unexpected method call.
        $this->expectException('coding_exception');
        $a = $this->helper_flatten_files($usageman->filter_stats_files($files, null, '03', null, '04'));
    }

    public function test_parse_stats_file() {
        $usageman = new testable_local_plugins_usage_manager();

        $filelines = array(
            'UPDATES API STATS',
            'generated: 1422739859 (2015-02-01T05:30:59+08:00)',
            'plugins installed sum: 413213',
            'plugin mod_example: 42 (16.32%)',
            '- plugin mod_example on moodle 3.4: 2',
            '- plugin mod_example on moodle 3.3: 21',
            '- plugin mod_example on moodle 3.2: 19',
            'plugin local_plugins: 1 (0.01%)',
            'plugin block_spam: 11 (1.7%)',
            '- plugin block_spam on moodle 2.3: 9',
            '- plugin block_spam on moodle 2.2: 2',
            'sites with 0 plugins: 10900 (31.2%)',
        );

        $data = $usageman->parse_stats_file($filelines);

        $this->assertTrue(is_array($data));
        $this->assertEquals(3, count($data));

        $this->assertEquals(4, count($data['mod_example']));
        $this->assertEquals(42, $data['mod_example']['total']);
        $this->assertEquals(2, $data['mod_example']['3.4']);
        $this->assertEquals(21, $data['mod_example']['3.3']);
        $this->assertEquals(19, $data['mod_example']['3.2']);

        $this->assertEquals(1, count($data['local_plugins']));
        $this->assertEquals(1, $data['local_plugins']['total']);

        $this->assertEquals(3, count($data['block_spam']));
        $this->assertEquals(11, $data['block_spam']['total']);
        $this->assertEquals(9, $data['block_spam']['2.3']);
        $this->assertEquals(2, $data['block_spam']['2.2']);
    }

    public function test_update_plugin_usage_data() {
        global $DB;

        $foobar = (object)array(
            'name' => 'Foo bar',
            'frankenstyle' => 'foo_bar',
            'categoryid' => 3,
            'shortdescription' => 'Foo rocks!',
            'timecreated' => time() - 2 * YEARSECS,
            'timelastmodified' => time() - 1 * YEARSECS,
        );
        $foobar->id = $DB->insert_record('local_plugins_plugin', $foobar);

        $modbaz = (object)array(
            'name' => 'Baz activity',
            'frankenstyle' => 'mod_baz',
            'categoryid' => 3,
            'shortdescription' => 'Baz rocks too!',
            'timecreated' => time() - 1 * YEARSECS,
            'timelastmodified' => time() - 1 * YEARSECS,
        );
        $modbaz->id = $DB->insert_record('local_plugins_plugin', $modbaz);

        $usageman = new testable_local_plugins_usage_manager();

        $usageman->fakerecentlyprocessedyear = null;
        $usageman->fakerecentlyprocessedmonth = null;
        $usageman->fakecurrentyear = '2014';
        $usageman->fakecurrentmonth = '12';

        $usageman->update_plugin_usage_data();

        $data = $usageman->get_stats_monthly($foobar->id);
        $this->assertEquals(1, count($data));
        $this->assertEquals(1, count($data['2014']));
        $this->assertEquals(1, count($data['2014']['12']));
        $this->assertEquals(4307, $data['2014']['12']['total']);

        $data = $usageman->get_stats_monthly($modbaz->id);
        $this->assertEquals(1, count($data));
        $this->assertEquals(1, count($data['2014']));
        $this->assertEquals(4821, $data['2014']['12']['total']);

        $this->assertEquals(4307, $DB->get_field('local_plugins_plugin', 'aggsites', array('id' => $foobar->id)));
        $this->assertEquals(4821, $DB->get_field('local_plugins_plugin', 'aggsites', array('id' => $modbaz->id)));

        $usageman->fakerecentlyprocessedyear = '2014';
        $usageman->fakerecentlyprocessedmonth = '12';
        $usageman->fakecurrentyear = '2015';
        $usageman->fakecurrentmonth = '03';

        $usageman->update_plugin_usage_data();

        $data = $usageman->get_stats_monthly($foobar->id);
        $this->assertEquals(2, count($data));
        $this->assertEquals(1, count($data[2014]));
        $this->assertEquals(1, count($data[2015]));
        $this->assertEquals(1, count($data['2014']['12']));
        $this->assertEquals(4307, $data[2014][12]['total']);
        $this->assertEquals(6, count($data['2015']['1']));
        $this->assertEquals(6891, $data[2015][1]['total']);
        $this->assertEquals(12, $data[2015][1]['3.4']);
        $this->assertEquals(1067, $data[2015][1]['3.3']);
        $this->assertEquals(1457, $data[2015][1]['3.2']);
        $this->assertEquals(2891, $data[2015][1]['3.1']);
        $this->assertEquals(1464, $data[2015][1]['3.0']);

        $data = $usageman->get_stats_monthly($modbaz->id);
        $this->assertEquals(1, count($data));
        $this->assertEquals(1, count($data[2014]));
        $this->assertEquals(4821, $data[2014][12]['total']);

        $this->assertEquals(6891, $DB->get_field('local_plugins_plugin', 'aggsites', array('id' => $foobar->id)));
        $this->assertEquals(4821, $DB->get_field('local_plugins_plugin', 'aggsites', array('id' => $modbaz->id)));
    }

    /**
     * Restructuralize the year => month => path array for easier checking
     *
     * @param array $original
     * @return array
     */
    protected function helper_flatten_files(array $original) {

        $files = array();

        foreach ($original as $y => $months) {
            foreach ($months as $m => $filepath) {
                $files[$y.'/'.$m] = $filepath;
            }
        }

        return $files;
    }

    /**
     * Shuffle the given associative array
     *
     * @param array $array
     */
    protected function helper_shuffle_assoc(&$array) {

        $keys = array_keys($array);
        shuffle($keys);
        $new = array();

        foreach ($keys as $key) {
            $new[$key] = $array[$key];
        }

        $array = $new;
    }
}

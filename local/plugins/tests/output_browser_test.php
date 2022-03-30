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
 * Provides {@link local_plugins_output_browser_testcase} class.
 *
 * @package     local_plugins
 * @category    test
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * Tests for the local_plugins\output\browser.php.
 */
class local_plugins_output_browser_testcase extends advanced_testcase {

    /**
     * Browser must be first populated with data prior to exporting them.
     */
    public function test_export_without_populating() {
        global $PAGE;

        $browser = new \local_plugins\output\browser();
        $this->expectException('coding_exception');
        $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
    }

    /**
     * Once populated, browser instance must not be populated again.
     */
    public function test_export_repeated_populating() {
        global $PAGE;

        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('load some cool plugins');
        $browser->search($filter);
        $this->expectException('coding_exception');
        $filter = new \local_plugins\output\filter('look ma, I am using the same instance for searching again!');
        $browser->search($filter);
    }

    /**
     * Test the basics of the plugins search functionality.
     */
    public function test_search() {
        global $DB, $PAGE;

        $this->resetAfterTest();

        $now = time();

        $cat1 = $DB->insert_record('local_plugins_category', [
            'name' => 'Test1',
            'shortdescription' => '',
        ]);

        $cat2 = $DB->insert_record('local_plugins_category', [
            'name' => 'Test2',
            'shortdescription' => '',
        ]);

        $aaa = $DB->insert_record('local_plugins_plugin', [
            'categoryid' => $cat1,
            'name' => 'AAA',
            'frankenstyle' => 'mod_a',
            'type' => 'mod',
            'shortdescription' => 'a-a-a',
            'timecreated' => $now - YEARSECS,
            'timelastmodified' => $now - DAYSECS,
            'timelastreleased' => $now - 3 * WEEKSECS,
            'approved' => 1,
            'visible' => 1,
            'aggdownloads' => 42,
            'aggfavs' => 3,
            'aggsites' => 15,
        ]);

        $bbb = $DB->insert_record('local_plugins_plugin', [
            'categoryid' => $cat2,
            'name' => 'BBB',
            'frankenstyle' => 'block_b',
            'type' => 'block',
            'shortdescription' => 'b-b-b a-a',
            'timecreated' => $now - YEARSECS + 10,
            'timelastmodified' => $now - DAYSECS + 20,
            'timelastreleased' => $now - 2 * WEEKSECS,
            'approved' => 1,
            'visible' => 1,
            'aggdownloads' => 12,
            'aggfavs' => 8,
            'aggsites' => 7,
        ]);

        $ccc = $DB->insert_record('local_plugins_plugin', [
            'categoryid' => $cat2,
            'name' => 'CCC',
            'frankenstyle' => 'enrol_c',
            'type' => '',
            'shortdescription' => 'c-c-c',
            'timecreated' => $now - YEARSECS + 20,
            'timelastmodified' => $now - DAYSECS + 30,
            'timelastreleased' => $now - 1 * WEEKSECS,
            'approved' => 1,
            'visible' => 1,
            'aggdownloads' => 1,
            'aggfavs' => 0,
            'aggsites' => 0,
        ]);

        $ddd = $DB->insert_record('local_plugins_plugin', [
            'categoryid' => $cat1,
            'name' => 'DDD',
            'frankenstyle' => 'local_d',
            'type' => 'local',
            'shortdescription' => 'd-d-d',
            'timecreated' => $now - YEARSECS + 10,
            'timelastmodified' => $now - DAYSECS + 20,
            'timelastreleased' => $now - 1 * WEEKSECS,
            'approved' => 0,
            'visible' => 1,
            'aggdownloads' => 1,
            'aggfavs' => 0,
            'aggsites' => 0,
        ]);

        // Default search() call should return all plugins.

        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('');
        $browser->search($filter);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));

        $this->assertTrue(is_object($data));
        $this->assertTrue(is_array($data->grid->plugins));
        $this->assertEquals(3, count($data->grid->plugins));
        $this->assertEquals('search', $data->grid->source);
        $this->assertEquals('', $data->grid->query);
        $this->assertEquals(0, $data->grid->batch);
        $this->assertNotEmpty($data->grid->batchsize);
        $this->assertEquals(0, $data->grid->plugins[0]->index);
        $this->assertEquals('CCC', $data->grid->plugins[0]->name); // Default is ORDER BY p.timelastreleased DESC.
        $this->assertEquals(1, $data->grid->plugins[1]->index);
        $this->assertEquals('BBB', $data->grid->plugins[1]->name);
        $this->assertEquals(2, $data->grid->plugins[2]->index);
        $this->assertEquals('AAA', $data->grid->plugins[2]->name);

        // When requiring a batch out from the range, I should get empty set.

        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('something');
        $browser->search($filter, 972);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));

        $this->assertTrue(is_object($data));
        $this->assertTrue(is_array($data->grid->plugins));
        $this->assertEmpty($data->grid->plugins);
        $this->assertEquals('search', $data->grid->source);
        $this->assertEquals('something', $data->grid->query);
        $this->assertEquals(972, $data->grid->batch);
        $this->assertNotEmpty($data->grid->batchsize);

        // Requiring next batch.

        $browser = new \local_plugins\output\browser(1);
        $filter = new \local_plugins\output\filter('');
        $browser->search($filter);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(1, count($data->grid->plugins));
        $this->assertEquals(0, $data->grid->plugins[0]->index);
        $this->assertEquals('CCC', $data->grid->plugins[0]->name);
        $this->assertEquals(0, $data->grid->batch);

        $browser = new \local_plugins\output\browser(1);
        $filter = new \local_plugins\output\filter('');
        $browser->search($filter, 1);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(1, count($data->grid->plugins));
        $this->assertEquals(1, $data->grid->plugins[0]->index);
        $this->assertEquals('BBB', $data->grid->plugins[0]->name);
        $this->assertEquals(1, $data->grid->batch);

        $browser = new \local_plugins\output\browser(1);
        $filter = new \local_plugins\output\filter('');
        $browser->search($filter, 2);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(1, count($data->grid->plugins));
        $this->assertEquals(2, $data->grid->plugins[0]->index);
        $this->assertEquals('AAA', $data->grid->plugins[0]->name);
        $this->assertEquals('mod', $data->grid->plugins[0]->plugintype['type']);
        $this->assertEquals(2, $data->grid->batch);

        $browser = new \local_plugins\output\browser(2);
        $filter = new \local_plugins\output\filter('');
        $browser->search($filter, 0);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(2, count($data->grid->plugins));
        $this->assertEquals('CCC', $data->grid->plugins[0]->name);
        $this->assertEquals('BBB', $data->grid->plugins[1]->name);

        $browser = new \local_plugins\output\browser(2);
        $filter = new \local_plugins\output\filter('');
        $browser->search($filter, 1);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(1, count($data->grid->plugins));
        $this->assertEquals('AAA', $data->grid->plugins[0]->name);

        // Searching by keywords.

        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('b-b a-a');
        $browser->search($filter);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(2, count($data->grid->plugins));
        $this->assertEquals('BBB', $data->grid->plugins[0]->name);
        $this->assertEquals('AAA', $data->grid->plugins[1]->name);
        $this->assertEquals('b-b a-a', $data->grid->query);

        // Searching by plugin type.

        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('type:block');
        $browser->search($filter);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(1, count($data->grid->plugins));
        $this->assertEquals('BBB', $data->grid->plugins[0]->name);
        $this->assertEquals('block', $data->grid->plugins[0]->plugintype['type']);
        $this->assertNotEmpty($data->grid->plugins[0]->plugintype['name']);
        $this->assertEquals('type:block', $data->grid->query);

        // Searching by exact phrase.

        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('"b-b-b a-a"');
        $browser->search($filter);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));

        $this->assertTrue(is_object($data));
        $this->assertTrue(is_array($data->grid->plugins));
        $this->assertNotEmpty($data->grid->plugins);
        $this->assertEquals('BBB', $data->grid->plugins[0]->name);
        $this->assertEquals('search', $data->grid->source);
        $this->assertEquals('"b-b-b a-a"', $data->grid->query);
        $this->assertEquals(0, $data->grid->batch);
        $this->assertNotEmpty($data->grid->batchsize);

        // Implicit sorting by relevance.

        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('BBB a-a-a');
        $browser->search($filter);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));

        $this->assertEquals(2, count($data->grid->plugins));
        $this->assertEquals('BBB', $data->grid->plugins[0]->name); // Matching name.
        $this->assertEquals('AAA', $data->grid->plugins[1]->name); // Matching description.

        // Explicit sorting overrides the implicit one.

        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('BBB a-a-a sort-by:sites');
        $browser->search($filter);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));

        $this->assertEquals(2, count($data->grid->plugins));
        $this->assertEquals('AAA', $data->grid->plugins[0]->name);
        $this->assertEquals('BBB', $data->grid->plugins[1]->name);

        // By default unapproved plugins are not included.
        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('d-d-d');
        $browser->search($filter);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(0, count($data->grid->plugins));

        // Unapproved plugins are not returned if we do not search for a keyword.
        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('type:local');
        $browser->search($filter, 0, true);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(0, count($data->grid->plugins));

        // Unapproved plugins are returned if explicitly requested and we search for a keyword.
        $browser = new \local_plugins\output\browser();
        $filter = new \local_plugins\output\filter('type:local d-d-d');
        $browser->search($filter, 0, true);
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));
        $this->assertEquals(1, count($data->grid->plugins));
        $this->assertEquals('DDD', $data->grid->plugins[0]->name);
    }
}

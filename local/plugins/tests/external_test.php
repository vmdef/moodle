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
 * Provides {@link local_plugins\external_testcase} class.
 *
 * @package     local_plugins
 * @category    test
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests the plugins directory external API.
 */
class local_plugins_external_testcase extends externallib_advanced_testcase {

    /**
     * Test external function data_for_front_page().
     */
    public function test_get_plugins_batch() {
        global $DB;

        $this->resetAfterTest();

        // Access as guest.
        $this->setUser(0);

        $now = time();

        $DB->insert_record('local_plugins_plugin', [
            'categoryid' => 1,
            'name' => 'Foo activity',
            'frankenstyle' => 'mod_foo',
            'type' => 'mod',
            'shortdescription' => 'Allows students to foo their learning',
            'timecreated' => $now - YEARSECS,
            'timelastmodified' => $now - DAYSECS,
            'approved' => 1,
            'visible' => 1,
            'aggdownloads' => 42,
            'aggfavs' => 3,
            'aggsites' => 15,
        ]);

        $data = \local_plugins\external\api::get_plugins_batch('Foo', 0);
        $data = \external_api::clean_returnvalue(\local_plugins\external\api::get_plugins_batch_returns(), $data);
        $data = json_decode(json_encode($data));

        $this->assertTrue(is_object($data));
        $this->assertTrue(is_array($data->grid->plugins));
        $this->assertNotEmpty($data->grid->plugins);
        $this->assertEquals(0, $data->grid->plugins[0]->index);
        $this->assertEquals('Foo activity', $data->grid->plugins[0]->name);
        $this->assertEquals('mod', $data->grid->plugins[0]->plugintype->type);
        $this->assertEquals(1, $data->grid->plugins[0]->approved);
        $this->assertNotEmpty($data->grid->plugins[0]->plugintype->name);
        $this->assertNotEmpty($data->grid->screenshotloading);
        $this->assertEquals('search', $data->grid->source);
        $this->assertEquals('Foo', $data->grid->query);
        $this->assertEquals(0, $data->grid->batch);
        $this->assertNotEmpty($data->grid->batchsize);

        $data = \local_plugins\external\api::get_plugins_batch('Foo', 1);
        $data = \external_api::clean_returnvalue(\local_plugins\external\api::get_plugins_batch_returns(), $data);
        $data = json_decode(json_encode($data));

        $this->assertTrue(is_object($data));
        $this->assertTrue(is_array($data->grid->plugins));
        $this->assertEmpty($data->grid->plugins);
        $this->assertNotEmpty($data->grid->screenshotloading);
        $this->assertEquals('search', $data->grid->source);
        $this->assertEquals('Foo', $data->grid->query);
        $this->assertEquals(1, $data->grid->batch);
        $this->assertNotEmpty($data->grid->batchsize);
    }

    /**
     * Test functionality of the local_plugins_get_maintained_plugins external function.
     */
    public function test_get_maintained_plugins() {
        global $DB;

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $settings = external_settings::get_instance();

        // Mock up two plugins and their versions.

        $foo = (object) [
            'name' => 'Foo',
            'categoryid' => 3,
            'shortdescription' => 'Foo rocks!',
            'description' => '## Foo',
            'descriptionformat' => FORMAT_MARKDOWN,
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
        ];

        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        $bar = (object) [
            'name' => 'Bar',
            'categoryid' => 3,
            'shortdescription' => 'Bar sucks!',
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
            'sourcecontrolurl' => 'git://github.com/mudrd8mz/moodle-local_bar.git',
            'bugtrackerurl' => 'Contact us!',
            'discussionurl' => 'https://moodle.org',
        ];

        $bar->id = $DB->insert_record('local_plugins_plugin', $bar);

        $foo1 = (object) [
            'pluginid' => $foo->id,
            'userid' => $user->id,
            'timecreated' => $foo->timecreated,
            'timelastmodified' => $foo->timecreated,
        ];

        $foo2 = (object) [
            'pluginid' => $foo->id,
            'userid' => $user->id,
            'timecreated' => $foo->timecreated + 60,
            'timelastmodified' => $foo->timecreated + 60,
        ];

        $bar1 = (object) [
            'pluginid' => $bar->id,
            'userid' => $user->id,
            'timecreated' => $bar->timecreated,
            'timelastmodified' => $bar->timecreated,
        ];

        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);
        $foo2->id = $DB->insert_record('local_plugins_vers', $foo2);
        $bar1->id = $DB->insert_record('local_plugins_vers', $bar1);

        // Make user the lead maintainer of the foo plugin.
        $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $foo->id,
            'maintainer' => 1,
            'timecreated' => $foo->timecreated,
        ]);

        $response = external_api::call_external_function('local_plugins_get_maintained_plugins', []);

        $this->assertFalse($response['error']);
        $this->assertEquals(1, count($response['data']));
        $this->assertStringContainsString('<h2>Foo</h2>', $response['data'][0]['description']);
        $this->assertEquals(FORMAT_HTML, $response['data'][0]['descriptionformat']);

        // Make user the contributor to the bar plugin.
        $contributionid = $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $bar->id,
            'timecreated' => $foo->timecreated,
        ]);

        $settings->set_raw(true);

        $response = external_api::call_external_function('local_plugins_get_maintained_plugins', []);

        $this->assertFalse($response['error']);
        $this->assertEquals(1, count($response['data']));
        $this->assertStringContainsString('## Foo', $response['data'][0]['description']);
        $this->assertEquals(FORMAT_MARKDOWN, $response['data'][0]['descriptionformat']);

        // Promote the user to the maintainer of the bar plugin.
        $DB->set_field('local_plugins_contributor', 'maintainer', 2, ['id' => $contributionid]);

        $response = external_api::call_external_function('local_plugins_get_maintained_plugins', []);
        $this->assertFalse($response['error']);
        $this->assertEquals(2, count($response['data']));
    }
}

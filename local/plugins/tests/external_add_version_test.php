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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Tests for the add_version external function.
 *
 * @package     local_plugins
 * @category    external
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_external_add_version_testcase extends externallib_advanced_testcase {

    /**
     * Test that valid plugin must be specified.
     */
    public function test_no_valid_plugin() {

        $this->resetAfterTest();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $response = external_api::call_external_function('local_plugins_add_version', []);

        $this->assertTrue($response['error']);
        $this->assertEquals('exc_pluginnotfound', $response['exception']->errorcode);
    }

    /**
     * Test that non-maintainers cannot use the function.
     */
    public function test_non_maintainer() {
        global $DB;

        $this->resetAfterTest();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $settings = external_settings::get_instance();

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

        $foo1 = (object) [
            'pluginid' => $foo->id,
            'userid' => $user->id,
            'timecreated' => $foo->timecreated,
            'timelastmodified' => $foo->timecreated,
        ];

        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);

        // Without being a maintainer, do not allow to add versions.
        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $foo->id,
        ]);

        $this->assertTrue($response['error']);
        $this->assertEquals('exc_cannotedit', $response['exception']->errorcode);

        // Make user the lead maintainer of the foo plugin.
        $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $foo->id,
            'maintainer' => 1,
            'timecreated' => $foo->timecreated,
        ]);

        local_plugins_helper::reset_static_caches();

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $foo->id,
        ]);

        // Still not enough but different error (likely missing ZIP).
        $this->assertTrue($response['error']);
        $this->assertNotEquals('exc_cannotedit', $response['exception']->errorcode);
    }

    /**
     * Test that maintainers must specify ZIP somehow.
     */
    public function test_missing_zip() {
        global $DB;

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $settings = external_settings::get_instance();

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

        $foo1 = (object) [
            'pluginid' => $foo->id,
            'userid' => $user->id,
            'timecreated' => $foo->timecreated,
            'timelastmodified' => $foo->timecreated,
        ];

        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);

        $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $foo->id,
            'maintainer' => 1,
            'timecreated' => $foo->timecreated,
        ]);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $foo->id,
        ]);

        $this->assertTrue($response['error']);
        $this->assertEquals('exc_zipnotspecified', $response['exception']->errorcode);
    }

    /**
     * Test releasing a new version from ZIP uploaded to a draft area.
     */
    public function test_from_zipdrafitemtid() {
        global $CFG, $DB;

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $settings = external_settings::get_instance();

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111800',
        ]);

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020061500',
        ]);

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020110900',
        ]);

        $category = \local_plugins_helper::create_category([
            'name' => 'Testable local plugins',
            'shortdescription' => 'Category for plugins in this test',
            'plugintype' => 'local',
        ]);

        $foo = (object) [
            'name' => 'Foo',
            'frankenstyle' => 'local_foo',
            'categoryid' => $category->id,
            'shortdescription' => 'Foo rocks!',
            'description' => '## Foo',
            'descriptionformat' => FORMAT_MARKDOWN,
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
        ];

        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        $foo1 = (object) [
            'pluginid' => $foo->id,
            'userid' => $user->id,
            'timecreated' => $foo->timecreated,
            'timelastmodified' => $foo->timecreated,
        ];

        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);

        $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $foo->id,
            'maintainer' => 1,
            'timecreated' => $foo->timecreated,
        ]);

        $fileinfo = (object) [
            'component' => 'user',
            'contextid' => context_user::instance($user->id)->id,
            'userid' => $user->id,
            'filearea' => 'draft',
            'filename' => 'foo.zip',
            'filepath' => '/',
            'itemid' => file_get_unused_draft_itemid(),
        ];

        $filesource = $CFG->dirroot . '/local/plugins/tests/fixtures/local_foo.zip';
        $filestored = get_file_storage()->create_file_from_pathname($fileinfo, $filesource);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $foo->id,
            'zipdrafitemtid' => $fileinfo->itemid,
        ]);

        $this->assertFalse($response['error']);
        $this->assertNotEmpty($response['data']['id']);
        $this->assertLessThan(300, time() - $response['data']['timecreated']);
        $this->assertEquals(md5_file($filesource), $response['data']['md5sum']);
        $this->assertNotEmpty(clean_param($response['data']['downloadurl'], PARAM_URL));
        $this->assertNotEmpty(clean_param($response['data']['viewurl'], PARAM_URL));
        $this->assertContains('Release name ($plugin->release) not found in version.php', $response['data']['warnings']);
    }

    /**
     * Test releasing a new version from ZIP contents.
     */
    public function test_from_zipcontentsbase64() {
        global $CFG, $DB;

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $settings = external_settings::get_instance();

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111800',
        ]);

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020061500',
        ]);

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020110900',
        ]);

        $category = \local_plugins_helper::create_category([
            'name' => 'Testable local plugins',
            'shortdescription' => 'Category for plugins in this test',
            'plugintype' => 'local',
        ]);

        $foo = (object) [
            'name' => 'Foo',
            'frankenstyle' => 'local_foo',
            'categoryid' => $category->id,
            'shortdescription' => 'Foo rocks!',
            'description' => '## Foo',
            'descriptionformat' => FORMAT_MARKDOWN,
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
        ];

        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $foo->id,
            'maintainer' => 1,
            'timecreated' => $foo->timecreated,
        ]);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $foo->id,
            'zipcontentsbase64' => '** Some invalid contents **',
        ]);

        $this->assertTrue($response['error']);
        $this->assertEquals('exc_invalidbase64', $response['exception']->errorcode);

        $filesource = $CFG->dirroot . '/local/plugins/tests/fixtures/local_foo.zip';

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $foo->id,
            'zipcontentsbase64' => base64_encode(file_get_contents($filesource)),
        ]);

        $this->assertFalse($response['error']);
        $this->assertNotEmpty($response['data']['id']);
        $this->assertLessThan(300, time() - $response['data']['timecreated']);
        $this->assertEquals(md5_file($filesource), $response['data']['md5sum']);
        $this->assertNotEmpty(clean_param($response['data']['downloadurl'], PARAM_URL));
        $this->assertNotEmpty(clean_param($response['data']['viewurl'], PARAM_URL));
        $this->assertContains('Release name ($plugin->release) not found in version.php', $response['data']['warnings']);
    }

    /**
     * Test releasing a new version from ZIP URL.
     */
    public function test_from_zipurl() {
        global $CFG, $DB;

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $settings = external_settings::get_instance();

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111800',
        ]);

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020061500',
        ]);

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020110900',
        ]);

        $category = \local_plugins_helper::create_category([
            'name' => 'Testable activity modules',
            'shortdescription' => 'Category for plugins in this test',
            'plugintype' => 'mod',
        ]);

        $subcourse = (object) [
            'name' => 'Subcourse',
            'frankenstyle' => 'mod_subcourse',
            'categoryid' => $category->id,
            'shortdescription' => 'Just another awesome plugin :-)',
            'description' => '## Installation',
            'descriptionformat' => FORMAT_MARKDOWN,
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
        ];

        $subcourse->id = $DB->insert_record('local_plugins_plugin', $subcourse);

        $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $subcourse->id,
            'maintainer' => 1,
            'timecreated' => $subcourse->timecreated,
        ]);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $subcourse->id,
            'zipurl' => 'https://github.com/mudrd8mz/moodle-mod_subcourse/archive/refs/tags/v10.0.0.zip',
        ]);

        $this->assertFalse($response['error']);
        $this->assertNotEmpty($response['data']['id']);
        $this->assertLessThan(300, time() - $response['data']['timecreated']);
        $this->assertArrayHasKey('md5sum', $response['data']);
        $this->assertNotEmpty(clean_param($response['data']['downloadurl'], PARAM_URL));
        $this->assertNotEmpty(clean_param($response['data']['viewurl'], PARAM_URL));
    }

    /**
     * Test releasing a new version with the same version number.
     */
    public function test_existing_version_same_version() {
        global $CFG, $DB;

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $settings = external_settings::get_instance();

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111800',
        ]);

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020061500',
        ]);

        \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020110900',
        ]);

        $category = \local_plugins_helper::create_category([
            'name' => 'Testable local plugins',
            'shortdescription' => 'Category for plugins in this test',
            'plugintype' => 'local',
        ]);

        $foo = (object) [
            'name' => 'Foo',
            'frankenstyle' => 'local_foo',
            'categoryid' => $category->id,
            'shortdescription' => 'Foo rocks!',
            'description' => '## Foo',
            'descriptionformat' => FORMAT_MARKDOWN,
            'timecreated' => mktime(0, 0, 0, 1, 1, 2000),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2000),
        ];

        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        $foo1 = (object) [
            'pluginid' => $foo->id,
            'userid' => $user->id,
            'version' => 2020042200,
            'timecreated' => $foo->timecreated,
            'timelastmodified' => $foo->timecreated,
        ];

        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);

        $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $foo->id,
            'maintainer' => 1,
            'timecreated' => $foo->timecreated,
        ]);

        $fileinfo = (object) [
            'component' => 'user',
            'contextid' => context_user::instance($user->id)->id,
            'userid' => $user->id,
            'filearea' => 'draft',
            'filename' => 'foo.zip',
            'filepath' => '/',
            'itemid' => file_get_unused_draft_itemid(),
        ];

        $filesource = $CFG->dirroot . '/local/plugins/tests/fixtures/local_foo.zip';
        $filestored = get_file_storage()->create_file_from_pathname($fileinfo, $filesource);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $foo->id,
            'zipdrafitemtid' => $fileinfo->itemid,
        ]);

        $this->assertFalse($response['error']);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $foo->id,
            'zipdrafitemtid' => $fileinfo->itemid,
        ]);

        $this->assertTrue($response['error']);
        $this->assertEquals('exc_zipvalidationerrors', $response['exception']->errorcode);
        $this->assertStringContainsString('There is already a plugin version with this version number',
            $response['exception']->debuginfo);
    }

    /**
     * Test releasing a new version with lower version number than an existing version.
     */
    public function test_existing_version_lower_version() {
        global $CFG, $DB;

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $settings = external_settings::get_instance();

        $m38 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111800',
        ]);

        $m39 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020061500',
        ]);

        $m310 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020110900',
        ]);

        $category = \local_plugins_helper::create_category([
            'name' => 'Testable local plugins',
            'shortdescription' => 'Category for plugins in this test',
            'plugintype' => 'local',
        ]);

        $bar = (object) [
            'name' => 'Bar',
            'frankenstyle' => 'local_bar',
            'categoryid' => $category->id,
            'shortdescription' => 'Bar is awesome!',
            'description' => '## Bar',
            'descriptionformat' => FORMAT_MARKDOWN,
            'timecreated' => mktime(0, 0, 0, 1, 1, 2001),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2001),
        ];

        $bar->id = $DB->insert_record('local_plugins_plugin', $bar);

        $bar1 = (object) [
            'pluginid' => $bar->id,
            'userid' => $user->id,
            'version' => 2021060400,
            'timecreated' => $bar->timecreated,
            'timelastmodified' => $bar->timecreated,
            'visible' => 1,
            'approved' => 1,
        ];

        $bar1->id = $DB->insert_record('local_plugins_vers', $bar1);

        $DB->insert_records('local_plugins_supported_vers', [
            ['versionid' => $bar1->id, 'softwareversionid' => $m39->id],
            ['versionid' => $bar1->id, 'softwareversionid' => $m310->id],
        ]);

        $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $bar->id,
            'maintainer' => 1,
            'timecreated' => $bar->timecreated,
        ]);

        $fileinfo = (object) [
            'component' => 'user',
            'contextid' => context_user::instance($user->id)->id,
            'userid' => $user->id,
            'filearea' => 'draft',
            'filename' => 'bar.zip',
            'filepath' => '/',
            'itemid' => file_get_unused_draft_itemid(),
        ];

        $filesource = $CFG->dirroot . '/local/plugins/tests/fixtures/local_bar_2021060300_39_310.zip';
        $filestored = get_file_storage()->create_file_from_pathname($fileinfo, $filesource);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $bar->id,
            'zipdrafitemtid' => $fileinfo->itemid,
        ]);

        $this->assertFalse($response['error']);
        $this->assertContains(get_string('exc_addinglowerversionnoeffect', 'local_plugins', '2021060400'),
            $response['data']['warnings']);

        local_plugins_helper::reset_static_caches();

        $fileinfo = (object) [
            'component' => 'user',
            'contextid' => context_user::instance($user->id)->id,
            'userid' => $user->id,
            'filearea' => 'draft',
            'filename' => 'bar.zip',
            'filepath' => '/',
            'itemid' => file_get_unused_draft_itemid(),
        ];

        $filesource = $CFG->dirroot . '/local/plugins/tests/fixtures/local_bar_2020111700_38_39.zip';
        $filestored = get_file_storage()->create_file_from_pathname($fileinfo, $filesource);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $bar->id,
            'zipdrafitemtid' => $fileinfo->itemid,
        ]);

        $this->assertFalse($response['error']);
        $this->assertContains(get_string('exc_addinglowerversionpartialeffect', 'local_plugins'), $response['data']['warnings']);
    }

    /**
     * Test releasing a new version with explicitly specified supported Moodle branches.
     */
    public function test_supportedmoodle() {
        global $CFG, $DB;

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $user = self::getDataGenerator()->create_user();
        $user->ignoresesskey = true;
        self::setUser($user);

        $this->assignUserCapability('local/plugins:editownplugins', SYSCONTEXTID);

        $settings = external_settings::get_instance();

        $m38 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111800',
        ]);

        $m39 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020061500',
        ]);

        $m310 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020110900',
        ]);

        $m311 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.11',
            'version' => '2021051700',
        ]);

        $category = \local_plugins_helper::create_category([
            'name' => 'Testable local plugins',
            'shortdescription' => 'Category for plugins in this test',
            'plugintype' => 'local',
        ]);

        $bar = (object) [
            'name' => 'Bar',
            'frankenstyle' => 'local_bar',
            'categoryid' => $category->id,
            'shortdescription' => 'Bar is awesome!',
            'description' => '## Bar',
            'descriptionformat' => FORMAT_MARKDOWN,
            'timecreated' => mktime(0, 0, 0, 1, 1, 2001),
            'timelastmodified' => mktime(0, 0, 0, 1, 1, 2001),
        ];

        $bar->id = $DB->insert_record('local_plugins_plugin', $bar);

        $DB->insert_record('local_plugins_contributor', [
            'userid' => $user->id,
            'pluginid' => $bar->id,
            'maintainer' => 1,
            'timecreated' => $bar->timecreated,
        ]);

        $fileinfo = (object) [
            'component' => 'user',
            'contextid' => context_user::instance($user->id)->id,
            'userid' => $user->id,
            'filearea' => 'draft',
            'filename' => 'bar.zip',
            'filepath' => '/',
            'itemid' => file_get_unused_draft_itemid(),
        ];

        $filesource = $CFG->dirroot . '/local/plugins/tests/fixtures/local_bar_2020111700_38_39.zip';
        $filestored = get_file_storage()->create_file_from_pathname($fileinfo, $filesource);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $bar->id,
            'zipdrafitemtid' => $fileinfo->itemid,
            'supportedmoodle' => '39, 310,  311, 312, 4.0',
        ]);

        $this->assertTrue($response['error']);
        $this->assertEquals('exc_zipvalidationerrors', $response['exception']->errorcode);
        $this->assertStringContainsString('Invalid supported Moodle version: 4.0', $response['exception']->debuginfo);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $bar->id,
            'zipdrafitemtid' => $fileinfo->itemid,
            'supportedmoodle' => '39, 310,  311, 312, foo',
        ]);

        $this->assertTrue($response['error']);
        $this->assertEquals('exc_zipvalidationerrors', $response['exception']->errorcode);
        $this->assertStringContainsString('Invalid supported Moodle version: foo', $response['exception']->debuginfo);

        $response = external_api::call_external_function('local_plugins_add_version', [
            'pluginid' => $bar->id,
            'zipdrafitemtid' => $fileinfo->itemid,
            'supportedmoodle' => '39, 310,  311, 312',
        ]);

        $this->assertFalse($response['error']);
        $this->assertEqualsCanonicalizing([$m39->id, $m310->id, $m311->id], array_keys($DB->get_records(
            'local_plugins_supported_vers', ['versionid' => $response['data']['id']], '', 'softwareversionid')));
    }
}

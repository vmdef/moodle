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
 * Unit tests for lib/archive_validator.php library
 *
 * @package     local_plugins
 * @subpackage  archive_validator
 * @category    phpunit
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/lib/archive_validator.php');

/**
 * Tests for some functionality of the {@link local_plugins_archive_validator} class
 */
class local_plugins_archive_validator_testcase extends advanced_testcase {

    public function test_empty_archive_error() {

        // Non-existing archive
        $validator = testable_local_plugins_archive_validator::create_from_fixture('this/file/does/not/exist.zip');
        $this->assertEquals($validator::ERROR_LEVEL_FILE, $validator->highest_error_level);
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_FILE, 'exc_emptyarchive'));

        // Existing but empty archive
        $validator = testable_local_plugins_archive_validator::create_from_fixture('empty.zip');
        $this->assertEquals($validator::ERROR_LEVEL_FILE, $validator->highest_error_level);
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_FILE, 'exc_emptyarchive'));
    }

    public function test_multiple_files_provided() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture(array('first.zip', 'second.zip'));
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_FILE, 'uploadonearchive'));
    }

    public function test_invalid_mimetype() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture('');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_FILE, 'exc_archivenotfound'));

        $validator = testable_local_plugins_archive_validator::create_from_fixture('Makefile');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_FILE, 'exc_archivenotfound'));
    }

    public function test_autoremove_option() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture('autoremove.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_WARNING, 'archiveshouldberemoved'));

        $validator = testable_local_plugins_archive_validator::create_from_fixture('autoremove.zip', null, null, null, array('autoremove' => true));
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_INFO, 'archiveautoremoved'));
    }

    public function test_special_category_plugintypes() {

        $category = new local_plugins_category(array('id' => 3, 'plugintype' => '', 'name' => 'Workshop subplugins'));
        $validator = testable_local_plugins_archive_validator::create_from_fixture('patch.zip', $category);
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_CLASSIFICATION, 'exc_categoryforbidden'));

        $category = new local_plugins_category(array('id' => 6, 'plugintype' => '-', 'name' => 'Patches and other stuff'));
        $validator = testable_local_plugins_archive_validator::create_from_fixture('patch.zip', $category);
        $this->assertEmpty($validator->errors);
    }

    public function test_multiple_root_directories() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture('multirootdirs.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'exc_archiveonedir'));
    }

    public function test_missing_version_php_file() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture('noversionphp.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_WARNING, 'versionphpfilenotfound'));
    }

    public function test_invalid_version_php_file() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture('invalidversionphp.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphpinvalidformat'));
    }

    public function test_mod_with_legacy_version_php_file() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture('modlegacyversionphp.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_WARNING, 'versionphplegacyformat'));
    }

    public function test_mod_with_mixed_version_php_file() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture('mixedversionphp.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphpmixedformat'));
    }

    public function test_is_activity_module() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture('noversionphp.zip');
        $this->assertFalse($validator->is_activity_module());

        $validator = testable_local_plugins_archive_validator::create_from_fixture('invalidversionphp.zip');
        $this->assertFalse($validator->is_activity_module());

        $validator = testable_local_plugins_archive_validator::create_from_fixture('mixedversionphp.zip');
        $this->assertFalse($validator->is_activity_module());

        $validator = testable_local_plugins_archive_validator::create_from_fixture('modlegacyversionphp.zip');
        $this->assertTrue($validator->is_activity_module());

        $validator = testable_local_plugins_archive_validator::create_from_fixture('modnewversionphp.zip');
        $this->assertNull($validator->is_activity_module());
    }

    public function test_declared_component() {

        $validator = testable_local_plugins_archive_validator::create_from_fixture('modlegacyversionphp.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_WARNING, 'versionphpcomponentnotfound'));

        $validator = testable_local_plugins_archive_validator::create_from_fixture('component.zip', null, 'foo_barbaz');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_INFO, 'versionphpcomponentfound'));
        $this->assertFalse($validator->has_message($validator::ERROR_LEVEL_CLASSIFICATION, 'versionphpcomponentmismatch'));

        $validator = testable_local_plugins_archive_validator::create_from_fixture('component.zip', null, 'foo_something_else');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_INFO, 'versionphpcomponentfound'));
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_CLASSIFICATION, 'versionphpcomponentmismatch'));
    }

    public function test_parse_version_php() {

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

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

        $validator = testable_local_plugins_archive_validator::create_from_fixture('invalidversion.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphpversionformaterror'));

        $validator = testable_local_plugins_archive_validator::create_from_fixture('requiresonly.zip');
        $this->assertEqualsCanonicalizing([$m38->id, $m39->id, $m310->id, $m311->id],
            $validator->versioninformation['softwareversions']);
        $this->assertContains('Required Moodle core version found in version.php: 2020061401', $validator->infomessages_list);

        $validator = testable_local_plugins_archive_validator::create_from_fixture('requirestoolow.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphprequiresformaterror'));

        $validator = testable_local_plugins_archive_validator::create_from_fixture('component.zip');
        $this->assertFalse($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphprequiresformaterror'));

        $validator = testable_local_plugins_archive_validator::create_from_fixture('incompatible.zip');
        $this->assertEqualsCanonicalizing([$m38->id, $m39->id], $validator->versioninformation['softwareversions']);

        $validator = testable_local_plugins_archive_validator::create_from_fixture('incompatibleinvalid.zip');
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphpincompatibleformaterror'));

        $validator = testable_local_plugins_archive_validator::create_from_fixture('supportedarray.zip');
        $this->assertEqualsCanonicalizing([$m38->id, $m39->id], $validator->versioninformation['softwareversions']);

        $validator = testable_local_plugins_archive_validator::create_from_fixture('supportedbrackets.zip');
        $this->assertEqualsCanonicalizing([$m39->id, $m310->id], $validator->versioninformation['softwareversions']);

        $validator = testable_local_plugins_archive_validator::create_from_fixture('supportedincompatible.zip');
        $this->assertEqualsCanonicalizing([$m39->id, $m310->id, $m311->id], $validator->versioninformation['softwareversions']);
    }

    public function test_version_php_requires_higher_than_defined_major() {

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $m38 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111200',
        ]);

        $m39 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020060700',
        ]);

        $m310 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020102800',
        ]);

        $validator = testable_local_plugins_archive_validator::create_from_fixture('requires2020061500.zip',
            null, null, [$m39, $m310]);
        $this->assertFalse($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphprequirestoobig'));
        $this->assertFalse($validator->has_message($validator::ERROR_LEVEL_INFO, 'versionphprequirestoosmall'));
    }

    public function test_version_php_requires_too_big() {

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $m38 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111200',
        ]);

        $m39 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020060700',
        ]);

        $m310 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020102800',
        ]);

        $validator = testable_local_plugins_archive_validator::create_from_fixture('requires2020061500.zip',
            null, null, [$m38]);
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphprequirestoobig'));
        $this->assertFalse($validator->has_message($validator::ERROR_LEVEL_INFO, 'versionphprequirestoosmall'));
    }

    public function test_version_php_requires_same_as_defined_major() {

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $m38 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111200',
        ]);

        $m39 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020061500',
        ]);

        $m310 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020102800',
        ]);

        $validator = testable_local_plugins_archive_validator::create_from_fixture('requires2020061500.zip',
            null, null, [$m39, $m310]);
        $this->assertFalse($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphprequirestoobig'));
        $this->assertFalse($validator->has_message($validator::ERROR_LEVEL_INFO, 'versionphprequirestoosmall'));
    }

    public function test_version_php_requires_lower_than_defined_major() {

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        $m38 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.8',
            'version' => '2019111200',
        ]);

        $m39 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.9',
            'version' => '2020062000',
        ]);

        $m310 = \local_plugins_helper::create_software_version([
            'name' => 'Moodle',
            'releasename' => '3.10',
            'version' => '2020102800',
        ]);

        $validator = testable_local_plugins_archive_validator::create_from_fixture('requires2020061500.zip',
            null, null, [$m39, $m310]);
        $this->assertFalse($validator->has_message($validator::ERROR_LEVEL_CONTENT, 'versionphprequirestoobig'));
        $this->assertTrue($validator->has_message($validator::ERROR_LEVEL_INFO, 'versionphprequirestoosmall'));
    }
}


/**
 * Testable subclass of the class being tested
 */
class testable_local_plugins_archive_validator extends local_plugins_archive_validator {

    /**
     * Makes a validator instance for the given fixture file
     *
     * @return testable_local_plugins_archive_validator instance
     */
    public static function create_from_fixture($file, $category = null, $frankenstyle = null, $requires = null, $options = null) {
        global $CFG;
        static $counter = 0;

        if (is_array($file)) {
            $files = $file;
        } else {
            $files = array(self::fixture_file_location($file));
        }
        $extractdir = $CFG->tempdir.'/local_plugins/version_upload/test'.$counter++.'/';

        $validator = new testable_local_plugins_archive_validator($files, $category, $frankenstyle, $requires, $options, $extractdir);

        return $validator;
    }

    /**
     * Check if the given validation message was added during validation
     *
     * @param int $level the message level, e.g. {@link local_plugins_archive_validator::ERROR_LEVEL_FILE}
     * @param int $identifier the message identifier
     * @return bool
     */
    public function has_message($level, $identifier) {
        return isset($this->errors[$level][$identifier]);
    }

    /**
     * Allows explicit unit tests for the parent method
     *
     * @return boolean|null
     */
    public function is_activity_module() {
        return parent::is_activity_module();
    }

    /**
     * @param string $file relative path to the fixture file
     * @return string full path to the file
     */
    private static function fixture_file_location($file) {
        global $CFG;

        return $CFG->dirroot.'/local/plugins/tests/fixtures/validator/'.$file;
    }
}

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
 * Testing the H5PEditorAjaxInterface interface implementation.
 *
 * @package    core_h5p
 * @category   test
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p;

use core_h5p\local\library\autoloader;
use ReflectionMethod;

defined('MOODLE_INTERNAL') || die();

/**
 *
 * Test class covering the H5PEditorAjaxInterface interface implementation.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 */
class editor_ajax_testcase extends \advanced_testcase {

    /** @var editorajax H5P editor ajax instance */
    protected $editorajax;

    /**
     * Set up function for tests.
     */
    protected function setUp() {
        parent::setUp();

        autoloader::register();

        $this->editorajax = new editor_ajax();
    }

    /**
     * Test that getLatestLibraryVersions method retrieves the latest installed library versions.
     */
    public function test_getLatestLibraryVersions() {
        $this->resetAfterTest();

        $generator = \testing_util::get_data_generator();
        $h5pgenerator = $generator->get_plugin_generator('core_h5p');

        // Create several libraries records.
        $h5pgenerator->create_library_record('Library1', 'Lib1', 2, 0);
        $lib2 = $h5pgenerator->create_library_record('Library2', 'Lib2', 2, 1);
        $expectedlibraries[] = $lib2->id;
        $lib3 = $h5pgenerator->create_library_record('Library3', 'Lib3', 1, 3);
        $expectedlibraries[] = $lib3->id;
        $h5pgenerator->create_library_record('Library1', 'Lib1', 2, 1);
        $lib12 = $h5pgenerator->create_library_record('Library1', 'Lib1', 3, 0);
        $expectedlibraries[] = $lib12->id;

        $actuallibraries = $this->editorajax->getLatestLibraryVersions();

        ksort($actuallibraries);

        $this->assertEquals($expectedlibraries, array_keys($actuallibraries));
    }

    /**
     * Test that the method validateEditorToken validates an existing token.
     */
    public function test_validateEditorToken() {
        $factory = new factory();
        $core = $factory->get_core();

        // Set \H5PCore::getTimeFactor method accessibility.
        $method = new ReflectionMethod(\H5PCore::class, 'getTimeFactor');
        $method->setAccessible(true);

        $timefactor = $method->invoke($core);

        // Set \H5PCore::hashToken method accessibility.
        $method = new ReflectionMethod(\H5PCore::class, 'hashToken');
        $method->setAccessible(true);

        $token = $method->invoke($core, 'editorajax', $timefactor);

        $validtoken = $this->editorajax->validateEditorToken($token);

        $this->assertTrue($validtoken);
    }

    /**
     * Test that the method getTranslations retrieves the translations of several libraries.
     */
    public function test_getTranslations() {
        global $DB;

        $this->resetAfterTest();

        $defaultlang = 'en';
        $langjson = '{"libraryStrings": {"key": "value"}}';

        // Fetch generator.
        $generator = \testing_util::get_data_generator();
        $h5pgenerator = $generator->get_plugin_generator('core_h5p');

        $newlibraries[] = $h5pgenerator->create_library_record('Library1', 'Lib1', 2, 0);
        $newlibraries[] = $h5pgenerator->create_library_record('Library2', 'Lib2', 2, 1);
        $newlibraries[] = $h5pgenerator->create_library_record('Library3', 'Lib3', 3, 2);

        foreach ($newlibraries as $librarie) {
            $libraries[] = $librarie->machinename. ' ' .$librarie->majorversion. '.' .$librarie->minorversion;
        }

        $translations = $this->editorajax->getTranslations($libraries, $defaultlang);

        $this->assertEmpty($translations);

        foreach ($newlibraries as $library) {
            $langrecord = [
                'libraryid' => $library->id,
                'languagecode' => $defaultlang,
                'languagejson' => $langjson
            ];
            $DB->insert_record('h5p_libraries_languages', $langrecord);
        }

        $translations = $this->editorajax->getTranslations($libraries, $defaultlang);

        ksort($translations);
        sort($libraries);
        $this->assertEquals($libraries, array_keys($translations));
    }
}

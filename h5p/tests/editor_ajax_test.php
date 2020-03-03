<?php

namespace core_h5p;

use ReflectionMethod;

class editor_ajax_testcase extends \advanced_testcase {

    /** @var editor_ajax H5P editor ajax instance */
    protected $editor_ajax;

    protected function setUp() {
        parent::setUp();

        autoloader::register();

        $this->editor_ajax = new editor_ajax();
    }

    public function test_getLatestLibraryVersions() {
        $this->resetAfterTest();

        $generator = \testing_util::get_data_generator();
        $h5p_generator = $generator->get_plugin_generator('core_h5p');

        // Create several libraries records.
        $h5p_generator->create_library_record('Library1', 'Lib1', 2, 0);
        $lib2 = $h5p_generator->create_library_record('Library2', 'Lib2', 2, 1);
        $expectedlibraries[] = $lib2->id;
        $lib3 = $h5p_generator->create_library_record('Library3', 'Lib3', 1, 3);
        $expectedlibraries[] = $lib3->id;
        $h5p_generator->create_library_record('Library1', 'Lib1', 2, 1);
        $lib12 = $h5p_generator->create_library_record('Library1', 'Lib1', 3, 0);
        $expectedlibraries[] = $lib12->id;

        $actuallibraries = $this->editor_ajax->getLatestLibraryVersions();

        $this->assertEquals($expectedlibraries, array_keys($actuallibraries));
    }

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

        $validtoken = $this->editor_ajax->validateEditorToken($token);

        $this->assertTrue($validtoken);
    }

    public function test_getTranslations() {
        global $DB;

        $this->resetAfterTest();

        $defaultlang = 'en';
        $langjson = '{"libraryStrings": {"key": "value"}}';

        // Fetch generator.
        $generator = \testing_util::get_data_generator();
        $h5p_generator = $generator->get_plugin_generator('core_h5p');

        $newlibraries[] = $h5p_generator->create_library_record('Library1', 'Lib1', 2, 0);
        $newlibraries[] = $h5p_generator->create_library_record('Library2', 'Lib2', 2, 1);
        $newlibraries[] = $h5p_generator->create_library_record('Library3', 'Lib3', 3, 2);

        foreach ($newlibraries as $librarie) {
            $libraries[] = $librarie->machinename. ' ' .$librarie->majorversion. '.' .$librarie->minorversion;
        }

        $translations = $this->editor_ajax->getTranslations($libraries, $defaultlang);

        $this->assertEmpty($translations);

        foreach ($newlibraries as $library) {
            $langrecord = [
                'libraryid' => $library->id,
                'languagecode' => $defaultlang,
                'languagejson' => $langjson
            ];
            $DB->insert_record('h5p_libraries_languages', $langrecord);
        }

        $translations = $this->editor_ajax->getTranslations($libraries, $defaultlang);

        ksort($translations);
        sort($libraries);
        $this->assertEquals($libraries, array_keys($translations));
    }
}

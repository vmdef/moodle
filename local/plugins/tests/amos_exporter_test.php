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
 * Provides the {@link local_plugins_source_code_test} class.
 *
 * @package     local_plugins
 * @subpackage  amos
 * @category    phpunit
 * @copyright   2012 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Tests for some functionality of the {@link \local_plugins\local\amos\source_code} class
 *
 * @copyright 2012 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_source_code_test extends basic_testcase {

    /**
     * @see local_plugins_source_code::get_subplugins_from_file()
     */
    public function test_get_subplugins_from_file() {
        global $CFG;

        $fixtures = $CFG->dirroot.'/local/plugins/tests/fixtures/subplugins';

        $assign = testable_local_plugins_source_code::testable_get_subplugins_from_file($fixtures.'/assign.txt');
        $this->assertEquals('array', gettype($assign));
        $this->assertEquals(2, count($assign));
        $this->assertEquals('mod/assign/submission', $assign['assignsubmission']);
        $this->assertEquals('mod/assign/feedback', $assign['assignfeedback']);

        $book = testable_local_plugins_source_code::testable_get_subplugins_from_file($fixtures.'/book.txt');
        $this->assertEquals('array', gettype($book));
        $this->assertEquals(1, count($book));
        $this->assertEquals('mod/book/tool', $book['booktool']);

        $data = testable_local_plugins_source_code::testable_get_subplugins_from_file($fixtures.'/data.txt');
        $this->assertEquals('array', gettype($data));
        $this->assertEquals(2, count($data));
        $this->assertEquals('mod/data/field', $data['datafield']);
        $this->assertEquals('mod/data/preset', $data['datapreset']);

        $empty = testable_local_plugins_source_code::testable_get_subplugins_from_file($fixtures.'/empty.txt');
        $this->assertSame(array(), $empty);

        $assignment = testable_local_plugins_source_code::testable_get_subplugins_from_file($fixtures.'/foo.txt');
        $this->assertEquals('array', gettype($assignment));
        $this->assertEquals(2, count($assignment));
        $this->assertEquals('mod/assignment/type', $assignment['assignment']);
        $this->assertEquals('mod/assignment/bar', $assignment['assignmentfoo']);

        $none = testable_local_plugins_source_code::testable_get_subplugins_from_file($fixtures.'/none.txt');
        $this->assertSame(array(), $none);

        $falsepositive = testable_local_plugins_source_code::testable_get_subplugins_from_file($fixtures.'/falsepositive.txt');
        $this->assertEquals('array', gettype($falsepositive));
        $this->assertEquals(1, count($falsepositive));
        $this->assertEquals('mod/some/thing', $falsepositive['something']);
    }
}


/**
 * Provides access to the internal implementation of a method we want to test
 *
 * @copyright 2012 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_local_plugins_source_code extends \local_plugins\local\amos\source_code {
    public static function testable_get_subplugins_from_file($path) {
        return self::get_subplugins_from_file($path);
    }
}

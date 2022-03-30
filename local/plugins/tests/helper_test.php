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

/**
 * Tests for the functionality provided by the {@see local_plugins_helper} class.
 *
 * @package     local_plugins
 * @category    test
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_helper_test extends advanced_testcase {

    /**
     * @dataProvider data_moodle_branch_to_version
     * @param int $branchcode
     * @param string $expectedversion
     */
    public function test_moodle_branch_to_version(int $branchcode, string $expectedversion) {
        $this->assertSame($expectedversion, local_plugins_helper::moodle_branch_to_version($branchcode));
    }

    /**
     * @dataProvider data_moodle_branch_to_version_exceptions
     * @param mixed $branchcode
     */
    public function test_moodle_branch_to_version_exceptions($branchcode) {

        $this->expectException(Throwable::class);
        local_plugins_helper::moodle_branch_to_version($branchcode);
    }

    /**
     * @dataProvider data_moodle_version_to_branch
     * @param string $version
     * @param int $expectedbranchcode
     */
    public function test_moodle_version_to_branch(string $version, int $expectedbranchcode) {
        $this->assertSame($expectedbranchcode, local_plugins_helper::moodle_version_to_branch($version));
    }

    /**
     * @dataProvider data_moodle_version_to_branch_exceptions
     * @param mixed $version
     */
    public function test_moodle_version_to_branch_exceptions($version) {

        $this->expectException(Throwable::class);
        local_plugins_helper::moodle_version_to_branch($version);
    }

    /**
     * Test getting Moodle software version by its releasename.
     */
    public function test_get_moodle_version_by_releasename_or_branch_code() {

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

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

        $this->assertNull(local_plugins_helper::get_moodle_version_by_releasename('3.1'));
        $this->assertInstanceOf(local_plugins_softwareversion::class,
            local_plugins_helper::get_moodle_version_by_releasename('3.10'));

        $this->assertNull(local_plugins_helper::get_moodle_version_by_branch_code(31));
        $this->assertInstanceOf(local_plugins_softwareversion::class,
            local_plugins_helper::get_moodle_version_by_branch_code(310));
    }

    /**
     * Provides data for {@see self::test_moodle_branch_to_version()}.
     */
    public function data_moodle_branch_to_version() {
        return [
            [20, '2.0'],
            [30, '3.0'],
            [31, '3.1'],
            [39, '3.9'],
            [310, '3.10'],
            [311, '3.11'],
            [312, '3.12'],
            [400, '4.0'],
            [401, '4.1'],
            [410, '4.10'],
            [999, '9.99'],
            [1000, '10.0'],
        ];
    }

    /**
     * Provides data for {@see self::test_moodle_branch_to_version_exceptions()}.
     *
     */
    public function data_moodle_branch_to_version_exceptions() {
        return [
            'Empty' => [''],
            'Null' => [null],
            'Zero' => [0],
            'Negative' => [-1],
            'Too low' => [9],
            'Not a number' => ['Moodle 1.9'],
            'Non-existing' => [40],
        ];
    }

    /**
     * Provides data for {@see self::test_moodle_version_to_branch()}.
     */
    public function data_moodle_version_to_branch() {
        return [
            ['2.0', 20],
            ['3.0', 30],
            ['3.1', 31],
            ['3.9', 39],
            ['3.10', 310],
            ['3.11', 311],
            ['3.12', 312],
            ['3.99', 399],
            ['4.0', 400],
            ['4.1', 401],
            ['4.10', 410],
            ['9.99', 999],
            ['10.0', 1000],
        ];
    }

    /**
     * Provides data for {@see self::test_moodle_version_to_branch_exceptions()}.
     *
     */
    public function data_moodle_version_to_branch_exceptions() {
        return [
            'Empty' => [''],
            'Null' => [null],
            'Zero' => [0],
            'Negative' => [-5],
            'Too low' => ['1'],
            'Not a number' => ['Moodle'],
            'Not major version' => ['3.9.1'],
        ];
    }
}

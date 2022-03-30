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

/**
 * The precheck_manager test class.
 *
 * @package     local_plugins
 * @category    test
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_precheck_manager_testcase extends basic_testcase {

    /**
     * Test behaviour of {@see \local_plugins\local\precheck\manager::is_reasonably_recent_moodle_version()}.
     */
    public function _test_is_reasonably_recent_moodle_version() {

        $manager = new \local_plugins\local\precheck\manager();

        $this->assertTrue($manager->is_reasonably_recent_moodle_version(2020102800, 2021));
        $this->assertTrue($manager->is_reasonably_recent_moodle_version(2024123000, 2021));
        $this->assertTrue($manager->is_reasonably_recent_moodle_version(2020102800, 2022));
        $this->assertFalse($manager->is_reasonably_recent_moodle_version(2020102800, 2023));
        $this->assertFalse($manager->is_reasonably_recent_moodle_version(2018123000));
        $this->assertTrue($manager->is_reasonably_recent_moodle_version(2299010100));
    }

    /**
     * Test behaviour of {@see \local_plugins\local\precheck\manager::moodle_release_branch()}.
     */
    public function test_moodle_release_branch() {

        $manager = new \local_plugins\local\precheck\manager();

        // Use this instead of dataProvider for performance reasons.
        foreach ([
            ['3.8', 38],
            ['3.9', 39],
            ['3.9-beta', 39],
            ['3.9rc1', 39],
            ['3.9.0', 39],
            ['3.9.9-rc2', 39],
            ['3.9.999', 39],
            ['3.10', 310],
            ['3.10beta', 310],
            ['3.10-rc2', 310],
            ['3.10.0', 310],
            ['3.10.99', 310],
            ['3.11', 311],
            ['3.11.1', 311],
            ['4.0DEV', 400],
            ['4.0-rc.1', 400],
            ['4.0 beta.2', 400],
            ['4.0.0-beta', 400],
            ['4.0.3', 400],
        ] as [$moodlerelease, $branch]) {
            $this->assertEquals($branch, $manager->moodle_release_branch($moodlerelease));
        }
    }
}

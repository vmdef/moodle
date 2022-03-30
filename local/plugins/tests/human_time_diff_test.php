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

namespace local_plugins;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for the {@see \local_plugins\human_time_diff} class.
 *
 * @package     local_plugins
 * @category    test
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class human_time_diff_testcase extends \basic_testcase {

    /**
     * Test displaying given time ago.
     */
    public function test_for() {

        $diff = human_time_diff::for(time());
        $this->assertStringStartsWith('<time datetime="', $diff);
        $this->assertStringContainsString('">now</time>', $diff);

        $now = time();

        $diff = human_time_diff::for($now - 5 * YEARSECS + 3 * 30 * DAYSECS, $now, false);
        $this->assertEquals('5 years', $diff);

        $diff = human_time_diff::for($now + 5 * YEARSECS + 3 * 30 * DAYSECS, $now, false);
        $this->assertEquals('5 years', $diff);

        $diff = human_time_diff::for($now - 5 * YEARSECS - 3 * 30 * DAYSECS, $now, false);
        $this->assertEquals('5 years', $diff);

        $diff = human_time_diff::for($now - 5 * YEARSECS - 7 * 30 * DAYSECS, $now, false);
        $this->assertEquals('6 years', $diff);

        $diff = human_time_diff::for($now - 1 * YEARSECS - 13 * 30 * DAYSECS, $now, false);
        $this->assertEquals('2 years', $diff);

        $diff = human_time_diff::for($now - 1 * YEARSECS - 11 * 30 * DAYSECS, $now, false);
        $this->assertEquals('23 months', $diff);

        $diff = human_time_diff::for($now - 370 * DAYSECS, $now, false);
        $this->assertEquals('12 months', $diff);

        $diff = human_time_diff::for($now - 360 * DAYSECS, $now, false);
        $this->assertEquals('12 months', $diff);

        $diff = human_time_diff::for($now - 9 * 30 * DAYSECS, $now, false);
        $this->assertEquals('9 months', $diff);

        $diff = human_time_diff::for($now - 64 * DAYSECS, $now, false);
        $this->assertEquals('2 months', $diff);

        $diff = human_time_diff::for($now - 56 * DAYSECS, $now, false);
        $this->assertEquals('8 weeks', $diff);

        $diff = human_time_diff::for($now - 11 * DAYSECS, $now, false);
        $this->assertEquals('2 weeks', $diff);

        $diff = human_time_diff::for($now - 10 * DAYSECS, $now, false);
        $this->assertEquals('10 days', $diff);

        $diff = human_time_diff::for($now - 37 * HOURSECS, $now, false);
        $this->assertEquals('2 days', $diff);

        $diff = human_time_diff::for($now - 25 * HOURSECS, $now, false);
        $this->assertEquals('1 day', $diff);

        $diff = human_time_diff::for($now - 23 * HOURSECS, $now, false);
        $this->assertEquals('23 hours', $diff);

        $diff = human_time_diff::for($now - 91 * MINSECS, $now, false);
        $this->assertEquals('2 hours', $diff);

        $diff = human_time_diff::for($now - 89 * MINSECS, $now, false);
        $this->assertEquals('89 minutes', $diff);

        $diff = human_time_diff::for($now - 59 * MINSECS, $now, false);
        $this->assertEquals('59 minutes', $diff);

        $diff = human_time_diff::for($now - 121, $now, false);
        $this->assertEquals('2 minutes', $diff);

        $diff = human_time_diff::for($now - MINSECS - 58, $now, false);
        $this->assertEquals('now', $diff);
    }
}

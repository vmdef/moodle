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

/**
 * Describe time period between two timestamps in a fuzzy way natural for human.
 *
 * Inspired by `human_time_diff()` function in Wordpress.
 *
 * @package     local_plugins
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class human_time_diff {

    /**
     * Describe the amount of time between the two timestamps.
     *
     * The purpose is to use it in texts like "Latest released: 2 weeks"
     *
     * @param int $from The timestamp of an event.
     * @param int $to The base timestamp, defaults to now.
     * @param bool $html Produce HTML <time> tag. True by default unless we are in CLI environment.
     * @return string
     */
    public static function for(int $from, int $to = null, bool $html = null): string {

        if ($to === null) {
            $to = time();
        }

        if ($html === null) {
            if (CLI_SCRIPT && !PHPUNIT_TEST) {
                $html = false;
            } else {
                $html = true;
            }
        }

        $now = new \DateTime('@' . $to);
        $when = new \DateTime('@' . $from);
        $diff = $now->diff($when, true);

        if ($diff->y > 1) {
            $userdatestr = get_string('numyears', 'core', $diff->y + ($diff->m >= 6 ? 1 : 0));

        } else if ($diff->y == 1) {
            $userdatestr = get_string('nummonths', 'core', 12 + $diff->m + ($diff->d >= 15 ? 1 : 0));

        } else if ($diff->m > 1) {
            $userdatestr = get_string('nummonths', 'core', $diff->m + ($diff->d >= 15 ? 1 : 0));

        } else if ($diff->days > 10) {
            $userdatestr = get_string('numweeks', 'core', round($diff->days / 7));

        } else if ($diff->days > 1) {
            $userdatestr = get_string('numdays', 'core', $diff->days);

        } else if ($diff->days == 1) {
            if ($diff->h > 12) {
                $userdatestr = get_string('numdays', 'core', 2);
            } else {
                $userdatestr = get_string('numday', 'core', 1);
            }

        } else if ($diff->h > 1) {
            $userdatestr = get_string('numhours', 'core', $diff->h + ($diff->i >= 30 ? 1 : 0));

        } else if ($diff->h == 1) {
            if ($diff->i > 30) {
                $userdatestr = get_string('numhours', 'core', 2);
            } else {
                $userdatestr = get_string('numminutes', 'core', 60 + $diff->i);
            }

        } else if ($diff->i > 1) {
            $userdatestr = get_string('numminutes', 'core', $diff->i);

        } else {
            $userdatestr = get_string('now', 'core');
        }

        if (!$html) {
            return $userdatestr;

        } else {
            $when->setTimezone(\core_date::get_user_timezone_object());

            return \html_writer::tag('time', $userdatestr, [
                'datetime' => $when->format(\DateTime::W3C),
            ]);
        }
    }
}

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
 * @package     local_plugins
 * @category    task
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = array(
    array(
        'classname' => 'local_plugins\task\update_download_stats',
        'blocking' => 0,
        'minute' => '37',
        'hour' => '2',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),

    array(
        'classname' => 'local_plugins\task\invalidate_queue_stats_cache',
        'blocking' => 0,
        'minute' => 0,
        'hour' => 0,
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),

    array(
        'classname' => 'local_plugins\task\update_usage_stats',
        'blocking' => 0,
        'minute' => '12',
        'hour' => '6',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),

    array(
        'classname' => 'local_plugins\task\update_contrib_cohort',
        'blocking' => 0,
        'minute' => '16',
        'hour' => '3',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*'
    ),

    array(
        'classname' => 'local_plugins\task\create_approval_issues',
        'blocking' => 0,
        'minute' => '*/10',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ),

    array(
        'classname' => 'local_plugins\task\precheck',
        'blocking' => 0,
        'minute' => '*/3',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ),

    array(
        'classname' => 'local_plugins\task\award_autotest',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '4',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ),

    array(
        'classname' => 'local_plugins\task\award_privacy',
        'blocking' => 0,
        'minute' => 'R',
        'hour' => '4',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ),

    array(
        'classname' => 'local_plugins\task\amos_export',
        'blocking' => 0,
        'minute' => '*/4',
        'hour' => '*',
        'day' => '*',
        'dayofweek' => '*',
        'month' => '*',
    ),
);

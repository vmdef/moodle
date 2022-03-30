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
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', 1);

require_once(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

list($options, $unrecognized) = cli_get_params(array('reset' => false, 'help' => false), array('h' => 'help'));

$usage = <<<EOF
Updates the plugin version download stats.

Options:
    --reset     Truncate the cache table prior the update
    --help, -h  Display this usage information

EOF;

if ($options['help'] or !empty($unrecognized)) {
    echo $usage . PHP_EOL;
    die(1);
}

$statsman = new local_plugins_stats_manager('mtrace');

if ($options['reset']) {
    $statsman->reset_stats_cache_table();
}

$statsman->update_download_stats();

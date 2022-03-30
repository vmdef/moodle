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
 * Execute CI precheck for the given plugin version.
 *
 * @package     local_plugins
 * @subpackage  cli
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

$usage = "Executes CI precheck for the given plugin version.

Usage:
    # php precheck.php --plugin=<frankenstyle> --versionid=<versionid>
    # php precheck.php [--help|-h]

Options:
    -h --help                   Print this help.
    --plugin=<frankenstyle>     The full component name of the plugin.
    --versionid=<versionid>     The plugin version internal id.

Example:
    # php precheck.php --plugin=mod_poster --versionid=12197
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'plugin' => null,
    'versionid' => null,
], [
    'h' => 'help'
]);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL.'  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

if (empty($options['plugin']) || empty($options['versionid'])) {
    cli_error('Missing plugin or versionid arguments.', 2);
}

require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$plugin = local_plugins_helper::get_plugin_by_frankenstyle($options['plugin'], MUST_EXIST);
$version = $plugin->get_version($options['versionid']);
$config = get_config('local_plugins');
$manager = new local_plugins\local\precheck\manager();
$manager->precheck_plugin_version($version, $config);

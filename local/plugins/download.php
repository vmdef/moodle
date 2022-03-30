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
 * This file allows the user to download one version of a plugin
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package    local_plugins
 * @copyright  2012 Marina Glancy
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$relativepath = get_file_argument();
if (!$relativepath || $relativepath[0] !== '/') {
    local_plugins_error(get_string('error'), get_string('invalidarguments', 'core_error'), 400);
}
$args = explode('/', ltrim($relativepath, '/'));
if (count($args) < 1 || !($versionid = (int)$args[0])) {
    local_plugins_error(get_string('error'), get_string('invalidarguments', 'core_error'), 400);
}

// Get the plugin or die with 404 status.
$plugin = local_plugins_helper::get_plugin_by_version($versionid);

$version = $plugin->get_version($versionid);
$context = context_system::instance();

if (!$version->can_view()) {
    local_plugins_error(get_string('error'), get_string('exc_cannotviewversion', 'local_plugins'), 403);
}

local_plugins_helper::send_version_file($version);

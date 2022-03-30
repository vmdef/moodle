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
 * Sets the favourite status of a plugin for the current user
 *
 * @package     local_plugins
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);

require_once(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$plugin = local_plugins_helper::get_plugin_from_params();
$context = context_system::instance();

require_login();
require_sesskey();
require_capability(local_plugins::CAP_MARKFAVOURITE, $context);

$status = required_param('status', PARAM_INT);

$plugin->set_favourite($status);

header('Content-Type: application/json; charset: utf-8');

$response = array(
    'count' => $plugin->count_favourites(),
);

echo json_encode($response);

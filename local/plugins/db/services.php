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
 * Plugins directory external functions and service definitions
 *
 * @package    local_plugins
 * @category   webservice
 * @copyright  2012 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_plugins_get_available_plugins' => [
        'classname'   => 'local_plugins_external',
        'methodname'  => 'get_available_plugins',
        'classpath'   => 'local/plugins/externallib.php',
        'description' => 'Get all the available (approved and visible) plugins and information about their available versions and supported Moodle releases',
        'capabilities'=> 'local/plugins:editanyplugin',
        'type'        => 'read'
    ],

    'local_plugins_get_plugins_batch' => [
        'classname' => 'local_plugins\external\api',
        'methodname' => 'get_plugins_batch',
        'classpath' => '',
        'description' => 'Returns data needed to render a batch of plugins when browsing/searching the directory',
        'type' => 'read',
        'capabilities' => 'local/plugins:view',
        'loginrequired' => false,
        'ajax' => true,
    ],

    'local_plugins_get_maintained_plugins' => [
        'classname' => '\local_plugins\external\get_maintained_plugins',
        'methodname' => 'execute',
        'description' => 'Returns a list of plugins the user is maintainer of',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ],

    'local_plugins_add_version' => [
        'classname' => '\local_plugins\external\add_version',
        'methodname' => 'execute',
        'description' => 'Release a new version of the registered plugin',
        'type' => 'write',
        'ajax' => false,
        'loginrequired' => true,
    ],
];

$services = [
    'Plugins database web service' => [
        'functions' => [
            'local_plugins_get_available_plugins'
        ],
        'enabled'         => 0,
        'restrictedusers' => 1,
        'shortname'       => 'plugins_web_service',
        'downloadfiles'   => 0
    ],

    'Plugins maintenance' => [
        'functions' => [
            'local_plugins_get_maintained_plugins',
            'local_plugins_add_version',
        ],
        'shortname' => 'plugins_maintenance',
        'requiredcapability' => 'local/plugins:editownplugins',
        'enabled' => true,
        'restrictedusers' => 0,
        'downloadfiles' => true,
        'uploadfiles' => true,
    ],
];

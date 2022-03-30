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
 * local_moodleorg external functions and service definitions
 *
 * @package    local_moodleorg
 * @category   webservice
 * @copyright  2014 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'local_moodleorg_get_countries' => array(
        'classname'   => 'local_moodleorg_external',  //class containing the external function
        'methodname'  => 'get_countries',          //external function name
        'classpath'   => 'local/moodleorg/externallib.php',  //file containing the class/external function
        'description' => 'Retrieve countries that contain partners.',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
    ),
    'local_moodleorg_get_partner_ads' => array(
        'classname'   => 'local_moodleorg_external',  //class containing the external function
        'methodname'  => 'get_partner_ads',          //external function name
        'classpath'   => 'local/moodleorg/externallib.php',  //file containing the class/external function
        'description' => 'Retrieve all current partner ads.',    //human readable description of the web service function
        'type'        => 'read',                  //database rights of the web service function (read, write)
    ),
);

$services = array(
    'Moodle.org partner ad sharing' => array(
        'functions' => array ('local_moodleorg_get_countries', 'local_moodleorg_get_partner_ads'),
        'enabled'         => 0,
        'restrictedusers' => 1,
        'shortname'       => 'moodleorg_partners_web_service',
        'downloadfiles'   => 0,
    )
);

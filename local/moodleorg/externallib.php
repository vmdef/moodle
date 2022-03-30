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
 * External Web Service for local_moodleorg
 *
 * @package    local_moodleorg
 * @copyright  2014 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . "/externallib.php");

class local_moodleorg_external extends external_api {
    /**
     * Returns description of get_countries() parameters
     * @return external_function_parameters
     */
    public static function get_countries_parameters() {
        return new external_function_parameters(array()); // The method has no parameters.
    }

    /**
     * Returns an array of countries specifically target by partner ads
     * @return array an array of countries
     */
    public static function get_countries() {
        global $DB;
        return $DB->get_records('countries', null, '', 'ipfrom, ipto, code2, code3, countryname');
    }

    /**
     * Returns description of get_countries() result value
     * @return external_description
     */
    public static function get_countries_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'ipfrom' => new external_value(PARAM_RAW, 'the lower end of the IP range'),
                    'ipto' => new external_value(PARAM_RAW, 'the upped end of the IP range'),
                    'code2' => new external_value(PARAM_TEXT, 'code 2'),
                    'code3' => new external_value(PARAM_TEXT, 'code 3'),
                    'countryname' => new external_value(PARAM_TEXT, 'the name of the country'),
                )
            )
        );
    }

    /**
     * Returns description of get_partner_ads() parameters
     * @return external_function_parameters
     */
    public static function get_partner_ads_parameters() {
        return new external_function_parameters(array()); // The method has no parameters.
    }

    /**
     * Returns the current set of partner ads
     * @return array partner ads
     */
    public static function get_partner_ads() {
        global $DB;
        return $DB->get_records('register_ads');
    }

    /**
     * Returns description of get_partner_ads() result value
     * @return external_description
     */
    public static function get_partner_ads_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'country' => new external_value(PARAM_TEXT, ''),
                    'partner' => new external_value(PARAM_TEXT, ''),
                    'title' => new external_value(PARAM_TEXT, ''),
                    'image' => new external_value(PARAM_TEXT, ''),
                )
            )
        );
    }
}


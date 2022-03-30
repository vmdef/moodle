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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides {@link \local_plugins\local\amos\client} class.
 *
 * @package     local_plugins
 * @subpackage  amos
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\local\amos;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

/**
 * Implements a REST/JSON client of AMOS web service
 *
 * @copyright 2012 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class client {

    /** @var string web service token */
    protected $amosapikey;

    /** @var string URL of the Moodle site running AMOS */
    protected $amosurl;

    /** @var curl instance to make the actual job */
    protected $curl;

    /**
     * Creates a client instance
     *
     * @return local_plugins_amos_client
     */
    public function __construct() {

        $this->amosapikey = get_config('local_plugins', 'amosapikey');
        $this->amosurl = get_config('local_plugins', 'amosurl');
        $this->curl = new \curl(array('debug' => false));

        if (empty($this->amosapikey)) {
            throw new \coding_exception('Make sure the AMOS API key is defined prior to instantiating local_plugins_amos_client!');
        }

        if (empty($this->amosurl)) {
            throw new \coding_exception('Make sure the AMOS site URL is defined prior to instantiating local_plugins_amos_client!');
        }
    }

    /**
     * Call the given AMOS external function via a web service call
     *
     * Returned value is an array of result objects with properties componentname, moodlebranch,
     * language, status and optional message. If a remote exception is thrown, it is returned as
     * as an object with the property exception set. If there was a problem with parsing the server
     * response, null is returned.
     *
     * @param string $wsfunction The name of the external function to call.
     * @param array $params Params for the function.
     * @return array|stdClass|null
     */
    public function call(string $wsfunction, array $params = []) {

        $wsurl = $this->amosurl.'/webservice/rest/server.php?moodlewsrestformat=json&wsfunction='.
            $wsfunction.'&wstoken='.$this->amosapikey;

        return json_decode($this->curl->post($wsurl, format_postdata_for_curlcall($params)));
    }
}

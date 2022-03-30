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
 * Provides the {@local_plugins_tracker_connector} class.
 *
 * @package     local_plugins
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

/**
 * Implements integration with the JIRA tracker at tracker.moodle.org
 *
 * @copyright 2017 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_tracker_connector {

    /** @var string */
    protected $apiurl = 'https://tracker.moodle.org/rest/api/2';

    /** @var curl instance */
    protected $curl;

    /** @var array */
    protected $curlopts = [];

    /**
     * Create a new issue in the tracker.
     *
     * @param array $fields data for the new issue
     * @return string|bool the issue number or false if it failed
     */
    public function create_issue(array $fields) {

        if (empty($fields)) {
            throw new coding_exception('no issue fields data provided');
        }

        $params = json_encode([
            'fields' => $fields,
        ]);

        if ($params === false) {
            debugging('error when attempting to encode issue data');
            return false;
        }

        $this->init_curl();
        $reply = $this->curl->post($this->apiurl.'/issue/', $params, $this->curlopts);
		$info = $this->curl->get_info();
		$errorno = $this->curl->get_errno();

		if ($errorno) {
            debugging('curl error '.$errorno.': '.s($reply));
            return false;
		}

		if (empty($info['http_code'])) {
            debugging('unknown curl error');
            return false;
		}

        // On successful issue creation, JIRA replies with HTTP 201 Created.
        if ($info['http_code'] != 201) {
            debugging('unexpected response http code '.$info['http_code']);
            return false;
		}

		$response = json_decode($reply, true);

        if (empty($response['key'])) {
            debugging('unexpected response data '.s($reply));
            return false;
        }

        return $response['key'];
    }

    /**
     * Prepares the {@link curl} object and the options to use when talking to JIRA.
     *
     * @return curl
     */
    protected function init_curl() {
        global $CFG;

        if ($this->curl && $this->curlopts) {
            return;
        }

        $this->curl = new curl();
        $this->curl->setHeader('Content-Type: application/json');

        if (empty($CFG->local_plugins_trackeruserpwd)) {
            throw new coding_exception('The local_plugins_trackeruserpwd not configured');
        }

        $this->curlopts = [
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_HTTPAUTH' => CURLAUTH_BASIC,
            'CURLOPT_USERPWD' => $CFG->local_plugins_trackeruserpwd,
            'CURLOPT_SSL_VERIFYPEER' => true,
        ];
    }
}

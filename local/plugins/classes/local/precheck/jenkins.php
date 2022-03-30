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
 * Provides {@link local_plugins\local\precheck\jenkins} class.
 *
 * @package     local_plugins
 * @subpackage  precheck
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\local\precheck;

use curl;
use Exception;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

/**
 * Allows to trigger Jenkins jobs and get back their output
 *
 * See https://wiki.jenkins-ci.org/display/JENKINS/Remote+access+API for
 * details.
 *
 * @copyright 2017 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class jenkins {

    /** @var string */
    protected $baseurl;

    /** @var curl */
    protected $curl;

    /**
     * Instantiate the class
     *
     * @param string $baseurl Jenkins base URL
     */
    public function __construct($baseurl) {

        $this->baseurl = $baseurl;
        $this->curl = new curl();
    }

    /**
     * Trigger a new build of the given job using the authentication token
     *
     * @param string $job name of the job
     * @param string $token
     * @param array $params
     * @return array of (string)output, (string)debug output, (string)executed build URL
     */
    public function build($job, $token, array $params=[]) {

        $output = '';
        $debug = [];

        $buildurl = $this->get_build_url($job, $params);

        $this->log($debug, 'Requesting a new build ['.$buildurl.']');
        $queueurl = $this->get_queue_url($buildurl, ['token' => $token] + $params);

        $this->log($debug, 'Waiting for the build to start ['.$queueurl.']');
        $execurl = $this->wait_for_execution_url($queueurl);

        $this->log($debug, 'Fetching the build output ['.$execurl.']');
        $output = $this->fetch_console_output($execurl);

        return [$output, implode("\n", $debug), $execurl];
    }

    /**
     * Returns the URL to be called to build the job
     *
     * @param string $job
     * @param array $params
     * @return string
     */
    protected function get_build_url($job, array $params) {
        return $this->baseurl.'/job/'.rawurlencode($job).'/'.(empty($params) ? 'build' : 'buildWithParameters');
    }

    /**
     * Adds a new item into the logger array.
     *
     * @param array $logger simple storage of messages
     * @param string $msg
     */
    protected function log(array &$logger, $msg) {
        $logger[] = time().' '.$msg;
    }

    /**
     * Submits a new build request and returns the queue item URL
     *
     * @param string $buildurl
     * @param array $params
     * @return string
     */
    protected function get_queue_url($buildurl, array $params) {

        $this->curl->get($buildurl, $params);

        $httpcode = $this->curl->info['http_code'];

		if ($httpcode != 201) {
            throw new Exception('unexpected HTTP status code '.$httpcode, $httpcode);
        }

        return $this->curl->response['Location'].'api/xml';
    }

    /**
     * Poll the queued build and wait for the actual job execution URL.
     *
     * @param string $queueurl
     * @param mixed $pollfreq frequency of polling requests, in seconds
     * @param mixed $timeout give up on waiting for the build, in seconds
     * @return string
     */
    protected function wait_for_execution_url($queueurl, $pollfreq=5, $timeout=1800) {

        $timestart = time();

        while (true) {
			$xml = $this->curl->get($queueurl, ['xpath' => '/leftItem/executable[last()]/url']);

			if ($this->curl->info['http_code'] == 200) {
                if (preg_match('~<url>(.+)</url>$~', $xml, $matches)) {
                    return $matches[1];
                } else {
                    throw new Exception('unexpected response');
                }

            } else if ($this->curl->info['http_code'] == 404) {
                sleep($pollfreq);

                if (time() > $timestart + $timeout) {
                    throw new Exception('time is out, cancel waiting for the build execution');
                } else {
                    continue;
                }

            } else {
                throw new Exception('unexpected HTTP status code '.$this->curl->info['http_code'], $this->curl->info['http_code']);
            }
		}

        throw new Exception('timeout waiting for the job execution');
    }

    /**
     * Poll the executed build and accumulate its console output
     *
     * @param string $execurl
     * @param int $readfreq frequency of fetching data chunks, in seconds
     * @param int $timeout give up on waiting for the build and return what we have, in seconds
     * @return string
     */
    protected function fetch_console_output($execurl, $readfreq=5, $timeout=1800) {

        $timestart = time();
        $textsize = 0;
        $output = '';

        while (true) {
            $output .= $this->curl->get($execurl.'/logText/progressiveText', ['start' => $textsize]);

            if (time() > $timestart + $timeout) {
                $output .= PHP_EOL.'... (time is out, cancel waiting for the console output)';
                return $output;
            }

            if (isset($this->curl->response['X-More-Data'])) {
                $textsize = $this->curl->response['X-Text-Size'];
                sleep($readfreq);
                continue;

            } else {
                return $output;
            }
        }
	}
}

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
 * @subpackage  checkers
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines renderable results used by checkers
 */
class local_plugins_checker_results implements renderable {

    /** @var array */
    protected $results = null;

    /**
     * @param string $name
     */
    public function add_required($name) {
        $this->add_result(new local_plugins_checker_result($name, local_plugins_checker_result::IMPORTANCE_REQUIRED));
    }

    /**
     * @param string $name
     */
    public function add_recommendation($name) {
        $this->add_result(new local_plugins_checker_result($name, local_plugins_checker_result::IMPORTANCE_RECOMMENDED));
    }

    /**
     * @param string $name
     */
    public function add_suggestion($name) {
        $this->add_result(new local_plugins_checker_result($name, local_plugins_checker_result::IMPORTANCE_SUGGESTED));
    }

    /**
     * @param local_plugins_checker_result $result
     */
    public function add_result(local_plugins_checker_result $result) {
        $this->results[$result->importance][$result->name] = $result;
    }

    /**
     * @return array Two dimensional array [(int)importance][(string)name] => (local_plugins_checker_result)result
     */
    public function get_results() {

        if (empty($this->results)) {
            return array();
        }

        if (krsort($this->results)) {
            return $this->results;
        }

        debugging('Unexpected return status when sorting checker results', DEBUG_DEVELOPER);
        return array();
    }
}

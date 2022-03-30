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
 * Checks for various plugin record aspects
 */
class local_plugins_plugin_checker {

    /** @var local_plugins_plugin */
    protected $plugin = null;

    /** @var local_plugins_checker_results */
    protected $results = null;

    /**
     * Perform all checks for the given plugin record
     *
     * @return local_plugins_plugin_checker
     */
    public static function run(local_plugins_plugin $plugin) {

        $checker = new self();
        $checker->set_target_plugin($plugin);
        $checker->init_results();
        $checker->execute_checks();

        return $checker->get_results();
    }

    /**
     * @return local_plugins_checker_results
     */
    public function get_results() {
        return $this->results;
    }

    /**
     * @param local_plugins_plugin $plugin
     */
    protected function set_target_plugin(local_plugins_plugin $plugin) {
        $this->plugin = $plugin;
    }

    /**
     * Initializes empty results
     */
    protected function init_results() {
        $this->results = new local_plugins_checker_results();
    }

    /**
     * Executes all the checks and adds them to our results queue
     */
    protected function execute_checks() {

        if (empty(strip_tags($this->plugin->description))) {
            $this->results->add_required('filldescription');
        }

        if (empty(trim($this->plugin->bugtrackerurl))) {
            $this->results->add_required('fillbugtrackerurl');
        }

        if (!preg_match('~^https?://.+~', $this->plugin->bugtrackerurl)) {
            $this->results->add_required('invalidurl');
        }

        if (empty(trim($this->plugin->sourcecontrolurl))) {
            $this->results->add_required('fillsourcecontrolurl');
        }

        if (empty($this->plugin->screenshots)) {
            $this->results->add_required('providescreenshots');
        }
    }
}

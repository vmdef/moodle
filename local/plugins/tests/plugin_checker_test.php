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
 * Unit tests for lib/archive_validator.php library
 *
 * @package     local_plugins
 * @subpackage  checker
 * @category    phpunit
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

/**
 * Tests for some functionality of the {@link local_plugins_plugin_checker} class
 */
class local_plugins_plugin_checker_testcase extends basic_testcase {

    public function test_empty_plugin() {

        $stringman = get_string_manager();

        $plugin = new local_plugins_plugin(array('id' => 0));
        $results = local_plugins_plugin_checker::run($plugin);
        $this->assertInstanceOf('local_plugins_checker_results', $results);
        $results = $results->get_results();
        $this->assertIsArray($results);
        $this->assertNotEmpty($results);
        foreach ($results as $type => $typeresults) {
            $this->assertNotEmpty($typeresults);
            foreach ($typeresults as $name => $result) {
                $this->assertInstanceOf('local_plugins_checker_result', $result);
                $this->assertTrue($stringman->string_exists('checkerresult_'.$name, 'local_plugins'));
            }
        }
        $this->assertArrayHasKey('filldescription', $results[local_plugins_checker_result::IMPORTANCE_REQUIRED]);
        $this->assertArrayHasKey('fillsourcecontrolurl', $results[local_plugins_checker_result::IMPORTANCE_REQUIRED]);
        $this->assertArrayHasKey('fillbugtrackerurl', $results[local_plugins_checker_result::IMPORTANCE_REQUIRED]);
        $this->assertArrayHasKey('providescreenshots', $results[local_plugins_checker_result::IMPORTANCE_REQUIRED]);
    }
}

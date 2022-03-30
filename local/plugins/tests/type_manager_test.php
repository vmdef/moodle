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
 * Provides {@link local_plugins_type_manager_testcase} class.
 *
 * @package     local_plugins
 * @category    phpunit
 * @copyright   2016 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Tests for {@link local_plugins_type_manager} class
 */
class local_plugins_type_manager_testcase extends advanced_testcase {

    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_instance() {

        $first = local_plugins_type_manager::instance();
        $second = local_plugins_type_manager::instance();
        $third = local_plugins_type_manager::instance(false);

        $this->assertSame($first, $second);
        $this->assertNotSame($second, $third);
    }

    public function test_list_types() {

        $extra = 'extra :  Extra plugin type '."\t".PHP_EOL.PHP_EOL.
            ' new:Future plugin type
            ';

        set_config('extratypes', $extra, 'local_plugins');

        $typeman = local_plugins_type_manager::instance(false);
        $list = $typeman->list_types();

        $this->assertEquals('local', $list['local']['type']);
        $this->assertEquals('Other', $list['_other_']['name']);
        $this->assertEquals('Extra plugin type', $list['extra']['name']);
        $this->assertEquals('Future plugin type', $list['new']['name']);
    }

    public function test_name() {

        set_config('extratypes', 'extra: Extra plugin type'.PHP_EOL.'block: Renamed blocks', 'local_plugins');

        $pluginman = core_plugin_manager::instance();
        $typeman = local_plugins_type_manager::instance(false);

        $this->assertEquals($pluginman->plugintype_name('mod'), $typeman->name('mod'));
        $this->assertEquals('Renamed blocks', $typeman->name('block'));
        $this->assertEquals('Extra plugin type', $typeman->name('extra'));
    }
}

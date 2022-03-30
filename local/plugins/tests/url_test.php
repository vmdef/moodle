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
 * Provides {@link local_plugins_url_testcase} class.
 *
 * @package     local_plugins
 * @category    phpunit
 * @copyright   2016 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Tests for {@link local_plugins_url} class
 */
class local_plugins_url_testcase extends advanced_testcase {

    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_pathredirect() {
        global $CFG;

        $url = new local_plugins_url('/local/plugins');
        $this->assertEquals($CFG->wwwroot.'/plugins', $url->out());

        $url = new local_plugins_url('/local/plugins/');
        $this->assertEquals($CFG->wwwroot.'/plugins/', $url->out());

        $url = new local_plugins_url('/local/plugins/display.php', ['plugin' => 'foo_bar']);
        $this->assertEquals($CFG->wwwroot.'/plugins/display.php?plugin=foo_bar', $url->out());

        $url = new local_plugins_url('/course/view.php', ['id' => 42]);
        $this->assertEquals($CFG->wwwroot.'/course/view.php?id=42', $url->out());

        $url = new local_plugins_url(new moodle_url('/local/plugins/display.php', ['plugin' => 'foo_bar']));
        $this->assertEquals($CFG->wwwroot.'/local/plugins/display.php?plugin=foo_bar', $url->out());

        $url = new local_plugins_url('/local/plugins/display.php', ['plugin' => 'foo_bar']);
        $this->assertEquals($CFG->wwwroot.'/plugins/display.php?plugin=foo_bar', $url->out());

        $url = new local_plugins_url('/course/view.php', ['id' => 42]);
        $this->assertEquals($CFG->wwwroot.'/course/view.php?id=42', $url->out());

        $url = new local_plugins_url(new moodle_url('/local/plugins/display.php', ['plugin' => 'foo_bar']));
        $this->assertEquals($CFG->wwwroot.'/local/plugins/display.php?plugin=foo_bar', $url->out());
    }

    public function test_pluginpathredirect() {
        global $CFG;

        $url = new local_plugins_url('/local/plugins/display.php', ['plugin' => 'foo_bar']);
        $this->assertEquals($CFG->wwwroot.'/plugins/display.php?plugin=foo_bar', $url->out());

        $url = new local_plugins_url('/local/plugins/view.php', ['plugin' => 'foo_bar']);
        $this->assertEquals($CFG->wwwroot.'/plugins/foo_bar', $url->out());

        $url = new local_plugins_url('/local/plugins/view.php', ['plugin' => 'foo_bar', 'mode' => 'view']);
        $this->assertEquals($CFG->wwwroot.'/plugins/view.php?plugin=foo_bar&mode=view', $url->out(false));

        $url = new local_plugins_url('/course/view.php', ['id' => 42]);
        $this->assertEquals($CFG->wwwroot.'/course/view.php?id=42', $url->out());

        $url = new local_plugins_url(new moodle_url('/local/plugins/display.php', ['plugin' => 'foo_bar']));
        $this->assertEquals($CFG->wwwroot.'/local/plugins/display.php?plugin=foo_bar', $url->out());

        $url = new local_plugins_url('/local/plugins/view.php', ['plugin' => 'foo_bar']);
        $this->assertEquals($CFG->wwwroot.'/plugins/foo_bar', $url->out());

        $url = new local_plugins_url('/local/plugins/pluginversions.php', ['plugin' => 'foo_bar']);
        $this->assertEquals($CFG->wwwroot.'/plugins/foo_bar/versions', $url->out());

        $url = new local_plugins_url('/local/plugins/stats.php', ['plugin' => 'foo_bar']);
        $this->assertEquals($CFG->wwwroot.'/plugins/foo_bar/stats', $url->out());

        $url = new local_plugins_url('/local/plugins/translations.php', ['plugin' => 'foo_bar']);
        $this->assertEquals($CFG->wwwroot.'/plugins/foo_bar/translations', $url->out());

        $url = new local_plugins_url('/local/plugins/devzone.php', ['plugin' => 'foo_bar']);
        $this->assertEquals($CFG->wwwroot.'/plugins/foo_bar/devzone', $url->out());
    }

    public function test_slug() {

        foreach ([
            ['1.0.0', '1.0.0'],
            ['2021032000', '2021032000'],
            ['Alpha Centauri', 'alpha-centauri'],
            ['Álix Ãxel', 'alix-axel'],
            ['3.11.45.3 for Moodle 3.11 - 3.12 and higher!', '3.11.45.3-for-moodle-3.11-3.12-and-higher'],
        ] as [$in, $out]) {
            $this->assertEquals($out, local_plugins_url::slug($in));
        }
    }
}

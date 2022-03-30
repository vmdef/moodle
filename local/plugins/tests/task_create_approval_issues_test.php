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
 * @category    phpunit
 * @copyright   2017 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/tests/fixtures/testable_create_approval_issues.php');

class local_plugins_task_create_approval_issues_testcase extends advanced_testcase {

    public function test_execute() {
        global $DB;

        $this->resetAfterTest();
        local_plugins_helper::reset_static_caches();

        // Mock up a plugin.
        $foo = (object)array(
            'name' => 'Foo',
            'categoryid' => 3,
            'shortdescription' => 'Foo rocks!',
            'timecreated' => time() - 2 * YEARSECS,
            'timelastmodified' => time()  - 2 * YEARSECS,
        );
        $foo->id = $DB->insert_record('local_plugins_plugin', $foo);

        // Mock up a plugin version.
        $foo1 = (object)array(
            'pluginid' => $foo->id,
            'userid' => 8,
            'approved' => local_plugins_plugin::PLUGIN_APPROVED,
            'visible' => 1,
            'timecreated' => time() - 2 * YEARSECS,
            'timelastmodified' => time() - 2 * YEARSECS,
        );
        $foo1->id = $DB->insert_record('local_plugins_vers', $foo1);

        // Prepare a Moodle version.
        $m31 = (object)[
            'name' => 'Moodle',
            'version' => '2016050400',
            'releasename' => '3.1',
            'timecreated' => 1462352961,
        ];
        $m31->id = $DB->insert_record('local_plugins_software_vers', $m31);

        $DB->insert_record('local_plugins_supported_vers', (object)[
            'versionid' => $foo1->id,
            'softwareversionid' => $m31->id,
        ]);

        $this->expectOutputRegex('/approval of the plugin id '.$foo->id.' to be tracked in CONTRIB-1234/');
        $this->expectOutputRegex('/leaving the issue number in a comment/');

        $task = new testable_create_approval_issues();
        $task->execute();

        $this->assertEquals('CONTRIB-1234', $DB->get_field('local_plugins_plugin', 'approvalissue', ['id' => $foo->id]));
        $this->assertEquals('Plugin approval: Foo', $task->recentfields['summary']);
        $this->assertEquals('3.1', $task->recentfields['versions'][0]['name']);
    }
}

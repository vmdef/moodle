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
 * Unit tests for the {@link local_plugins_plugin::get_vcs_info()}
 *
 * @package     local_plugins
 * @category    phpunit
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

class local_plugins_plugin_vcs_info_testcase extends basic_testcase {

    public function test_empty_sourcecontrolurl_field() {

        $plugin = new local_plugins_plugin(array('id' => 11, 'sourcecontrolurl' => ''));
        $this->assertFalse($plugin->get_vcs_info());

        $plugin = new local_plugins_plugin(array('id' => 11, 'sourcecontrolurl' => null));
        $this->assertFalse($plugin->get_vcs_info());
    }

    public function test_unknown_vcs_type() {

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'https://code.google.com/p/mudrd8mz-moodle-plugins/'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('unknown', $result->type);

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'https://bitbucket.org/mudrd8mz/moodle-mod_stampcoll'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('unknown', $result->type);
    }

    public function test_github_repository() {

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'https://github.com/mudrd8mz/moodle-workshopeval_credit'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('github', $result->type);
        $this->assertEquals('mudrd8mz', $result->github_username);
        $this->assertEquals('moodle-workshopeval_credit', $result->github_reponame);

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'http://github.com/mudrd8mz/moodle-workshopeval_credit/'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('github', $result->type);
        $this->assertEquals('mudrd8mz', $result->github_username);
        $this->assertEquals('moodle-workshopeval_credit', $result->github_reponame);

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'https://github.com/mudrd8mz/moodle-mod_subcourse.git'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('github', $result->type);
        $this->assertEquals('mudrd8mz', $result->github_username);
        $this->assertEquals('moodle-mod_subcourse', $result->github_reponame);

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'git@github.com:mudrd8mz/moodle-mod_subcourse.git'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('github', $result->type);
        $this->assertEquals('mudrd8mz', $result->github_username);
        $this->assertEquals('moodle-mod_subcourse', $result->github_reponame);

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'https://github.com/mudrd8mz/moodle-mod_subcourse/releases'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('github', $result->type);
        $this->assertEquals('mudrd8mz', $result->github_username);
        $this->assertEquals('moodle-mod_subcourse', $result->github_reponame);

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'https://github.com/mudrd8mz/moodle-mod_subcourse/blob/master/version.php'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('github', $result->type);
        $this->assertEquals('mudrd8mz', $result->github_username);
        $this->assertEquals('moodle-mod_subcourse', $result->github_reponame);

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'https://github.com/So-me_bo.dy/So-me_th.ink/'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('github', $result->type);
        $this->assertEquals('So-me_bo.dy', $result->github_username);
        $this->assertEquals('So-me_th.ink', $result->github_reponame);

        $plugin = new local_plugins_plugin(array('id' => 11,
            'sourcecontrolurl' => 'git@github.com:So-me_bo.dy/So-me_th.ink.git'
        ));
        $this->assertEquals('object', gettype($result = $plugin->get_vcs_info()));
        $this->assertEquals('github', $result->type);
        $this->assertEquals('So-me_bo.dy', $result->github_username);
        $this->assertEquals('So-me_th.ink', $result->github_reponame);
    }
}

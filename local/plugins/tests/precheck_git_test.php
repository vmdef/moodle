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

class local_plugins_precheck_git_testcase extends basic_testcase {

    public function test_exec() {

        $repo = make_request_directory();
        $git = new local_plugins\local\precheck\git($repo);

        $git->exec('config --global init.defaultBranch main');
        $out = $git->exec('init');
        $this->assertEquals('Initialized empty Git repository in '.$repo.'/.git/', $out[0]);

        file_put_contents($repo.'/README.txt', 'Hello world');
        $git->exec('add README.txt');
        $git->exec('commit -m "Adding a first file"');
        $git->exec('branch -m main');

        $out = $git->exec('log -n 1 --oneline');
        $this->assertTrue(strpos($out[0], 'Adding a first file') > 0);

        $this->expectException(Exception::class);
        $git->exec('add FOO.exe');
    }

    /**
     * Test catching the stderr output while running exec().
     */
    public function test_stderr() {

        $repo = make_request_directory();
        $git = new local_plugins\local\precheck\git($repo);
        $this->assertEmpty($git->get_latest_stderr());

        $git->exec('config --global init.defaultBranch main');
        $out = $git->exec('init');
        $this->assertEmpty($git->get_latest_stderr());

        $out = $git->exec('checkout -b feature');
        $this->assertEquals("Switched to a new branch 'feature'", $git->get_latest_stderr());
    }

    public function test_is_success() {

        $repo = make_request_directory();
        $git = new local_plugins\local\precheck\git($repo);

        $git->exec('config --global init.defaultBranch main');
        $git->exec('init');
        file_put_contents($repo.'/index.php', '<?php // Hello world! ?>');
        $git->exec('add .');
        $git->exec('commit -m "Initial commit"');
        $git->exec('branch -m main');
        $git->exec('checkout -b feature');

        $this->assertTrue($git->is_success('show-ref --verify --quiet refs/heads/main'));
        $this->assertTrue($git->is_success('show-ref --verify --quiet refs/heads/feature'));
        $this->assertFalse($git->is_success('show-ref --verify --quiet refs/heads/justice_exists'));
    }

    public function test_list_local_branches() {

        $repo = make_request_directory();
        $git = new local_plugins\local\precheck\git($repo);

        $git->exec('config --global init.defaultBranch main');
        $git->exec('init');
        $this->assertSame([], $git->list_local_branches());

        file_put_contents($repo.'/index.php', '<?php // Hello world! ?>');
        $git->exec('add .');
        $git->exec('commit -m "Initial commit"');
        $git->exec('branch -m main');
        $this->assertEquals(['main'], $git->list_local_branches());

        $git->exec('checkout -b feature');
        $this->assertEquals(2, count($git->list_local_branches()));
        $this->assertContains('main', $git->list_local_branches());
        $this->assertContains('feature', $git->list_local_branches());
    }

    public function test_has_local_branch() {

        $repo = make_request_directory();
        $git = new local_plugins\local\precheck\git($repo);

        $git->exec('config --global init.defaultBranch main');
        $git->exec('init');
        file_put_contents($repo.'/index.php', '<?php // Hello world! ?>');
        $git->exec('add .');
        $git->exec('commit -m "Initial commit"');
        $git->exec('branch -m main');
        $git->exec('checkout -b feature');

        $this->assertTrue($git->has_local_branch('main'));
        $this->assertTrue($git->has_local_branch('feature'));
        $this->assertFalse($git->has_local_branch('justice_exists'));
    }

    public function test_mirror_standard_branches() {

        // Imagine repo1 as the moodle.git on git.moodle.org.
        $repo1 = make_request_directory();
        $git1 = new local_plugins\local\precheck\git($repo1);
        $git1->exec('config --global init.defaultBranch master');
        $git1->exec('init');
        file_put_contents($repo1.'/index.php', '<?php // Hello world! ?>');
        $git1->exec('add .');
        $git1->exec('commit -m "Initial commit"');
        $git1->exec('checkout -b MOODLE_42_STABLE');
        $git1->exec('checkout -b some_cool_feature');
        file_put_contents($repo1.'/another.php', '<?php // Yeah ... ?>');
        $git1->exec('add .');
        $git1->exec('commit -m "Another commit on non-standard branch"');
        $git1->exec('checkout master');

        // Imagine repo2 as the pluginsbot's public repo.
        $repo2 = make_request_directory();
        $git2 = new local_plugins\local\precheck\git($repo2);
        $git2->exec('config --global init.defaultBranch master');
        $git2->exec('init --bare');

        // Imagine repo3 as the pluginsbot's working repo (at moodle.org host itself).
        $repo3 = make_request_directory();
        $git3 = new local_plugins\local\precheck\git($repo3);
        $git3->exec('clone '.escapeshellarg($repo1).' .');
        $this->assertSame(['master'], $git3->list_local_branches());
        $git3->exec('remote add repo2 '.escapeshellarg($repo2));

        // Mirroring should update branches master and MOODLE_42_STABLE, but not the some_cool_feature one.
        $git3->mirror_standard_branches('origin', 'repo2');
        $this->assertEquals(2, count($git2->list_local_branches()));
        $this->assertContains('master', $git2->list_local_branches());
        $this->assertContains('MOODLE_42_STABLE', $git2->list_local_branches());

        $this->assertEquals(2, count($git3->list_remote_branches('repo2')));
        $this->assertContains('master', $git3->list_remote_branches('repo2'));
        $this->assertContains('MOODLE_42_STABLE', $git3->list_remote_branches('repo2'));

        $this->assertEquals(3, count($git3->list_remote_branches('origin')));
        $this->assertContains('master', $git3->list_remote_branches('origin'));
        $this->assertContains('MOODLE_42_STABLE', $git3->list_remote_branches('origin'));
        $this->assertContains('some_cool_feature', $git3->list_remote_branches('origin'));

        $this->assertTrue($git3->has_remote_branch('master', 'origin'));
        $this->assertTrue($git3->has_remote_branch('MOODLE_42_STABLE', 'origin'));
        $this->assertTrue($git3->has_remote_branch('some_cool_feature', 'origin'));
        $this->assertFalse($git3->has_remote_branch('foobar', 'origin'));
        $this->assertFalse($git3->has_remote_branch('foobar', 'muhehe'));
    }

    public function test_moodle_branch_name() {

        // Mock-up moodle.git with branches master and MOODLE_32_STABLE.
        $repo1 = make_request_directory();
        $git1 = new local_plugins\local\precheck\git($repo1);

        $git1->exec('config --global init.defaultBranch master');
        $git1->exec('init');
        file_put_contents($repo1.'/index.php', '<?php // Hello world! ?>');
        $git1->exec('add .');
        $git1->exec('commit -m "Initial commit"');
        $git1->exec('checkout -b MOODLE_32_STABLE');

        // Mock-up our clone with plugins snapshots.
        $repo2 = make_request_directory();
        $git2 = new local_plugins\local\precheck\git($repo2);
        $git2->exec('clone '.escapeshellarg($repo1).' .');
        $git2->exec('remote rename origin upstream');

        $this->assertSame('MOODLE_32_STABLE', $git2->moodle_branch_name('3.2'));
        $this->assertSame('master', $git2->moodle_branch_name('3.3'));
        $this->assertSame(false, $git2->moodle_branch_name('3.4'));
    }
}

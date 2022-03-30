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
 * Provides {@link local\plugins\local\precheck\git} class.
 *
 * @package     local_plugins
 * @subpackage  precheck
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\local\precheck;

use Exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Implements a thin wrapper for executing git commands in the moodle.git clone
 * for plugin prechecking.
 *
 * @copyright 2017 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class git {

    /** @var string Full path to our repository */
    protected $repodir;

    /** @var string SSH private key to use to authenticate against remote repositories */
    protected $sshprivate;

    /** @var string Contents of the stderr after the latest exec() call. */
    protected $stderr;

    /**
     * The main factory method to be used in production
     *
     * @return new local\plugins\local\precheck\git instance
     */
    public static function init() {
        global $CFG;

        $repodir = $CFG->dataroot.'/local_plugins/precheck/moodle-plugins-snapshots';

        if (!is_dir($repodir)) {
            make_writable_directory($repodir);
        }

        $sshkey = get_config('local_plugins', 'prechecksshkey');

        $git = new static($repodir, get_config('local_plugins', 'prechecksshkey'));

        if (!is_dir($repodir.'/.git')) {
            if (count(scandir($repodir)) > 2) {
                throw new Exception('Unexpected content of the moodle.git repository clone');
            }

            if ($sshkey !== '') {
                $origin = get_config('local_plugins', 'prechecksnapshotsrepo');
                $upstream = 'git://git.moodle.org/moodle.git';

                $git->exec('clone --reference '.escapeshellarg($CFG->dirroot).' '.escapeshellarg($origin).' .');
                $git->exec('remote add upstream git://git.moodle.org/moodle.git');
                $git->exec('remote update');
                $git->exec('repack -a -d');

            } else {
                $git->exec('init');
            }
        }

        return $git;
    }

    /**
     * Creates new worker instance.
     *
     * Note that most code should use the {@link init()} factory method. Direct
     * instantiation is useful for unit tests only.
     *
     * @param string $repodir Full path to the repository to work on
     * @param string $sshprivate SSH private key (e.g. id_rsa) to use when accessing remote repositories
     */
    public function __construct($repodir, $sshprivate='') {

        if (!is_dir($repodir)) {
            throw new Exception('Repository directory does not exist');
        }

        $this->repodir = $repodir;
        $this->sshprivate = $sshprivate;
    }

    /**
     * Executes git with given arguments and returns the command output
     *
     * @param string $args
     * @return array output
     */
    public function exec($args) {

        chdir($this->repodir);
        $out = [];
        $status = 0;

        $stderrlog = make_request_directory() . '/stderr.txt';

        $cmd = $this->git_shell_cmd($args) . ' 2> ' . $stderrlog;

        exec($cmd, $out, $status);

        $this->stderr = file_get_contents($stderrlog);

        if ($status <> 0) {
            throw new Exception('Error executing git command: ' . $cmd . ' (' . $status . '): ' . $this->stderr, $status);
        }

        return $out;
    }

    /**
     * Returns the contents of the stderr after the latest exec() call.
     *
     */
    public function get_latest_stderr(): string {
        return trim($this->stderr);
    }

    /**
     * Returns the full path to the repository root directory
     *
     * @return string
     */
    public function get_repodir() {
        return $this->repodir;
    }

    /**
     * Returns a shell command to execute git
     *
     * We may need to deal with SSH keys here. Unfortunately moodle.org environment
     * still uses quite old git versions so there is no way to use
     * GIT_SSH_COMMAND environment (requires git 2.3) or even core.sshCommand
     * (requires git 2.10). So we prepare an ad-hoc wrapper via the GIT_SSH
     * environment variable.
     *
     * @param string $args
     * @return string
     */
    protected function git_shell_cmd($args) {

        if ($this->sshprivate) {
            $temp = make_request_directory();
            $keypath = $temp.'/id_rsa';
            file_put_contents($keypath, $this->sshprivate);
            chmod($keypath, 0600);

            $gitsshpath = $temp.'/sshwrapper';
            file_put_contents($gitsshpath, '#!/bin/bash'.PHP_EOL.'ssh -i '.$keypath.' $1 $2');
            chmod($gitsshpath, 0700);

            putenv('GIT_SSH='.$gitsshpath);
        }

        $cmd = 'git '.$args;

        return $cmd;
    }

    /**
     * Returns true if executing git with given arguments had success exit code
     *
     * @param string $args
     * @return boolean
     */
    public function is_success($args) {

        try {
            $this->exec($args);
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Returns list of local branches
     *
     * @return array
     */
    public function list_local_branches() {

        $list = $this->exec('for-each-ref --format="%(refname)" '.escapeshellarg('refs/heads/'));

        // Git returns values like 'refs/heads/master'. We want to strip the
        // first two. We can't use format %(refname:strip=2) as the git on
        // moodle.org does not support it yet, so we do it manually here.
        $list = array_map('basename', $list);

        return $list;
    }

    /**
     * Checks if a local branch of the given name exist.
     *
     * @param string $name branch name
     * @return bool
     */
    public function has_local_branch($name) {
        return $this->is_success('show-ref --verify --quiet refs/heads/'.$name);
    }

    /**
     * Checks if a remote branch of the given name exist.
     *
     * Do not forget to update remotes before calling this.
     *
     * @param string $name branch name
     * @return bool
     */
    public function has_remote_branch($name, $remote='origin') {
        return $this->is_success('show-ref --verify --quiet refs/remotes/'.$remote.'/'.$name);
    }

    /**
     * Returns list of remote branches
     *
     * @param string $remote
     * @return array
     */
    public function list_remote_branches($remote='origin') {

        $list = $this->exec('for-each-ref --format="%(refname)" '.escapeshellarg('refs/remotes/'.$remote));

        // Git returns values like 'refs/remotes/origin/master'. We want to strip the
        // first three. We can't use format %(refname:strip=3) as the git on
        // moodle.org does not support it yet, so we do it manually here.
        $list = array_map('basename', $list);

        // Get rid of the ref HEAD (which shows the default branch in
        // the remote repository and is one of the existing branches).
        $list = array_values(array_diff($list, ['HEAD']));

        return $list;
    }

    /**
     * Update standard Moodle branches in the remote repository 'to'
     *
     * This can be used to keep the pluginbot's public repository in sync with
     * the official moodle.git.
     *
     * @param string $from source repository name
     * @param string $to repository name
     */
    public function mirror_standard_branches($from='upstream', $to='origin') {

        $this->exec('remote update');

        $source = $this->list_remote_branches($from);

        foreach ($source as $branch) {
            if (preg_match('~^(master|MOODLE_[0-9]{2}_STABLE)$~', $branch)) {
                $refspec = '+refs/remotes/'.$from.'/'.$branch.':refs/heads/'.$branch;
                $this->exec('push '.escapeshellarg($to).' '.escapeshellarg($refspec));
            }
        }
    }

    /**
     * Find out the name of the moodle.git branch holding the given Moodle version
     *
     * @param string $moodlever Moodle release name, like "3.3"
     * @param string $remote The name of the upstream vanilla moodle.git
     * @return string|bool Something like "MOODLE_33_STABLE" or "master", false if unknown
     */
    public function moodle_branch_name($moodlever, $remote='upstream') {

        // Convert values like 3.3 to 33.
        $moodlever = str_replace('.', '', $moodlever);

        $stable = 'MOODLE_'.$moodlever.'_STABLE';

        if ($this->has_remote_branch($stable, $remote)) {
            return $stable;
        }

        // Check if the $moodlever has been released as a beta version (so that
        // it is known to the Plugins directory, but the code still lives on
        // master). If that is the case, there should exist the stable branch
        // for the current release.

        $previous = 'MOODLE_'.($moodlever - 1).'_STABLE';

        if ($this->has_remote_branch($previous, $remote) && $this->has_remote_branch('master', $remote)) {
            return 'master';
        }

        return false;
    }
}

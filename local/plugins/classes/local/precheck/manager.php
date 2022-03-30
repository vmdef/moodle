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
 * Provides {@link local_plugins\local\precheck\manager} class.
 *
 * @package     local_plugins
 * @subpackage  precheck
 * @copyright   2017 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\local\precheck;

use core\lock\lock_config;
use core\update\code_manager;
use core_component;
use local_plugins_helper;
use local_plugins_plugin;
use local_plugins_version;
use moodle_exception;
use zip_packer;

defined('MOODLE_INTERNAL') || die();

/**
 * Controls the execution of the CI prechecks for submitted plugins.
 *
 * @copyright 2017 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manager {
    /** @var local_plugins\local\precheck\git */
    protected $git;

    /** @var core\lock\lock_factory */
    protected $gitlockfactory;

    /** @var core\lock\lock */
    protected $gitlock;

    /**
     * Prepares precheck manager instance.
     */
    public function __construct() {

        $this->git = git::init();
        $this->gitlockfactory = lock_config::get_lock_factory('local_plugins_precheck_git');
    }

    /**
     * Picks the next plugin version to be prechecked.
     *
     * @return local_plugins_version|null
     */
    public function choose_precheck_candidate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

        $sql = "SELECT p.id AS pluginid, v.id AS versionid
                  FROM {local_plugins_plugin} p
                  JOIN {local_plugins_vers} v ON v.pluginid = p.id
                  JOIN {local_plugins_category} c ON p.categoryid = c.id
             LEFT JOIN {local_plugins_vers_precheck} k ON k.versionid = v.id
                 WHERE v.visible = 1
                       AND k.id IS NULL
                       AND p.frankenstyle IS NOT NULL
                       AND p.frankenstyle <> ''
                       AND c.plugintype IS NOT NULL
                       AND c.plugintype <> '-'
                       AND c.plugintype <> ''
              ORDER BY CASE WHEN p.approved = ".local_plugins_plugin::PLUGIN_PENDINGAPPROVAL." THEN 10
                            WHEN p.approved = ".local_plugins_plugin::PLUGIN_APPROVED." THEN 5
                            ELSE 0
                       END DESC,
                       p.timelastmodified DESC,
                       v.timecreated DESC";

        $rs = $DB->get_recordset_sql($sql);

        $version = null;

        foreach ($rs as $record) {
            // Check that the version is the plugin's current version.
            $plugin = local_plugins_helper::get_plugin($record->pluginid);
            $latestvers = $plugin->latestversions;
            if (!isset($latestvers[$record->versionid])) {
                // It is not the most recent version - create a precheck record for it so that SQL query above
                // does not return it next time.
                $this->skip_plugin_version_precheck($record->versionid);
                continue;
            }
            $version = $plugin->get_version($record->versionid);
            break;
        }

        $rs->close();

        return $version;
    }

    /**
     * Precheck the given plugin version and store results
     *
     * @param local_plugins_version $version plugin version to be checked
     * @param object $config object with properties precheckcihost, precheckcitoken, precheckcijob and prechecksnapshotsreporead
     */
    public function precheck_plugin_version(local_plugins_version $version, $config) {
        global $CFG;

        if (empty($config) || empty($config->precheckcihost) || empty($config->precheckcitoken) || empty($config->precheckcijob)
                || empty($config->prechecksnapshotsreporead)) {
            throw new moodle_exception('precheck_not_configured', 'local_plugins');
        }

        $plugin = $version->plugin;
        $category = $plugin->category;

        if (empty($plugin->frankenstyle) || empty($category->plugintype) || $category->plugintype === '-') {
            throw new moodle_exception('requesting_precheck_without_frankenstyle', 'local_plugins');
        }

        mtrace('Requiring lock for exclusive access to the plugins snapshots repository ...');

        if (!$this->gitlock = $this->gitlockfactory->get_lock('moodle-plugins-snapshots', 60)) {
            throw new moodle_exception('locktimeout');
        }

        mtrace('Preparing precheck results storage ...');
        $precheckid = $this->prepare_precheck_results_storage($version->id);

        $latestsupported = $version->get_latest_moodle_version();
        $moodlever = $latestsupported->releasename;

        if ($moodlever === null) {
            throw new moodle_exception('no_supported_moodle_version', 'local_plugins');
        } else {
            mtrace('Prechecking against the latest supported Moodle version '.$moodlever);
        }

        mtrace('Checking that the latest supported Moodle version is recent enough ...');
        if (!$this->is_reasonably_recent_moodle_version($latestsupported->version)) {
            mtrace('This plugin version does not support recent Moodle version, skipping the precheck.');
            $this->set_precheck_status($precheckid, -95);
            $this->gitlock->release();
            return;
        }

        mtrace('Updating git remotes ...');
        $this->git->exec('remote update --prune');

        mtrace('Looking for a git branch holding the code of Moodle '.$moodlever.' ...');
        $moodlebranch = $this->git->moodle_branch_name($moodlever);

        if ($moodlebranch === false) {
            mtrace('Unable to find such a git branch, skipping the precheck.');
            $this->set_precheck_status($precheckid, -93);
            $this->gitlock->release();
            return;
        } else {
            mtrace('Using branch '.$moodlebranch);
        }

        mtrace('Preparing plugin version branch ...');
        $branch = $this->prepare_plugin_version_branch($plugin->id, $version->id, $moodlever, $moodlebranch, $plugin->frankenstyle);
        $this->set_precheck_status($precheckid, -90);

        mtrace('Extracting plugin version snapshot ...');
        $target = $this->extract_plugin_version_snapshot($plugin->id, $version->id, $plugin->frankenstyle);
        $this->set_precheck_status($precheckid, -80);

        mtrace('Committing plugin version snapshot ...');
        $this->commit_plugin_version_snapshot($target, $version->id, $plugin->frankenstyle);
        $this->set_precheck_status($precheckid, -70);

        mtrace('Executing the precheck build ...');
        list($console, $debuglog, $buildurl) = $this->execute_precheck_job($branch, $moodlebranch, $version->id, $config);
        $this->set_precheck_status($precheckid, -60);

        mtrace('Extracting smurfresult line ...');
        $smurfresult = $this->extract_smurfresult_line($console);
        $this->set_precheck_status($precheckid, -50);

        mtrace('Fetching smurf artifacts ...');
        list($smurfxml, $smurfhtml) = $this->fetch_smurf_artifacts($buildurl);
        $this->set_precheck_status($precheckid, -40);

        mtrace('Storing precheck results ...');
        $this->store_precheck_results($precheckid, [
            'status' => 10,
            'timeend' => time(),
            'buildurl' => $buildurl,
            'smurfresult' => $smurfresult,
        ]);

        mtrace('Storing precheck files ...');
        $this->store_precheck_files($version->id, $precheckid, [
            'console.txt' => $console,
            'debuglog.txt' => $debuglog,
            'smurf.xml' => $smurfxml,
            'smurf.html' => $smurfhtml,
        ]);

        mtrace('Removing the branch ...');
        $this->git->exec('checkout '.escapeshellarg($moodlebranch));
        $this->git->exec('push --delete origin '.escapeshellarg($branch));
        $this->git->exec('branch --delete --force '.escapeshellarg($branch));
    }

    /**
     * Decide if the given Moodle version should be considered recent enough for prechecking.
     *
     * We do not want to run prechecks for plugins that only support very old Moodle versions.
     * Originally this was based on $CFG->branch but with 3.10 and 3.11 it stopped being guaranteed to be a sequence. So
     * now we determine it based on the year which is good enough estimation for this purpose.
     *
     * @param int $version Number such as 2020102800 (for Moodle 3.10).
     * @param int $currentyear Current year used for comparison, defaults to the real one.
     * @return bool
     */
    public function is_reasonably_recent_moodle_version(int $version, int $currentyear = null): bool {
        global $CFG;

        if ($currentyear === null) {
            $currentyear = (int) date('Y');
        }

        $versionyear = (int) substr((string) $version, 0, 4);

        if ($versionyear < $currentyear - 2) {
            return false;

        } else {
            return true;
        }
    }

    /**
     * Get the value of $CFG->branch for the given Moodle release.
     *
     * Note: This is not actually used at the moment, it is a relic of a development branch that was found a dead-end
     * and replaced with different solution. But I keep it here in case we find it useful one day.
     *
     * @param string $releasename like '3.9', '3.10.0' or '4.0dev'
     * @return int Branch code like 39, 310 or 400
     */
    public function moodle_release_branch(string $releasename): int {

        $vers = explode('.', $releasename, 3);

        if (count($vers) < 2) {
            throw new \moodle_exception('invalid_moodle_releasename', 'local_plugins');
        }

        $vers[0] = (int) $vers[0];
        $vers[1] = (int) $vers[1];
        $branch = 0;

        if (($vers[0] >= 4) || ($vers[0] == 3 && $vers[1] >= 10)) {
            $branch = $vers[0] * 100 + $vers[1];

        } else {
            $branch = $vers[0] * 10 + $vers[1];
        }

        return $branch;
    }

    /**
     * Return the most recent successful precheck result.
     *
     * @param local_plugins_version $version plugin version that was checked
     * @return object|bool local_plugins_vers_precheck record with smurfhtml and smurfxml properties added, or false
     */
    public function get_latest_precheck_result(local_plugins_version $version) {
        global $DB;

        $prechecksql = "SELECT *
                          FROM {local_plugins_vers_precheck}
                         WHERE versionid = ? AND status > 0 AND timeend IS NOT NULL
                      ORDER BY status DESC, timeend DESC";

        $precheck = $DB->get_records_sql($prechecksql, [$version->id], 0, 1);

        if ($precheck) {
            $precheck = array_shift($precheck);
            $files = $this->get_precheck_files($version->id, $precheck->id);

            if (!empty($files['smurf.xml'])) {
                $precheck->smurfxml = $files['smurf.xml'];
            }

            if (!empty($files['smurf.html'])) {
                $precheck->smurfhtml = $files['smurf.html'];
            }

            if (!empty($files['console.txt'])) {
                $precheck->console = $files['console.txt'];
            }

            if (!empty($files['debuglog.txt'])) {
                $precheck->debuglog = $files['debuglog.txt'];
            }

            return $precheck;

        } else {
            return false;
        }
    }

    /**
     * Creates a new record in the local_plugins_vers_precheck to store precheck results
     *
     * @param int $versionid
     * @return int precheck id
     */
    protected function prepare_precheck_results_storage($versionid) {
        global $DB;

        $resultid = $DB->insert_record('local_plugins_vers_precheck', [
            'versionid' => $versionid,
            'timestart' => time(),
            'status' => -100,
        ]);

        $smurfzippath = $this->get_pluginversion_precheck_zip_path($versionid, $resultid);
        make_writable_directory(dirname($smurfzippath));

        return $resultid;
    }

    /**
     * Sets the status of the precheck execution
     *
     * @param int $precheckid
     * @param int $status
     */
    protected function set_precheck_status($precheckid, $status) {
        global $DB;

        $DB->set_field('local_plugins_vers_precheck', 'status', $status, ['id' => $precheckid]);
    }

    /**
     * Stores the results of a successful precheck build
     *
     * @param int $precheckid
     * @param array $results
     */
    protected function store_precheck_results($precheckid, array $results) {
        global $DB;

        $results['id'] = $precheckid;

        $DB->update_record('local_plugins_vers_precheck', $results);
    }

    /**
     * Extracts the SMURFRESULT line from the build console output
     *
     * @param string $console
     * @return string
     */
    protected function extract_smurfresult_line($console) {

        $line = preg_match('/^SMURFRESULT: (.+)$/m', $console, $matches);

        if (empty($line) || empty($matches[1])) {
            throw new moodle_exception('smurfresult_line_not_found', 'local_plugins');
        }

        return $matches[1];
    }

    /**
     * Downloads the smurf.xml and smurf.html files from the Jenkins build
     *
     * @param string $buildurl
     * @return array
     */
    protected function fetch_smurf_artifacts($buildurl) {
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');

        $smurfxml = download_file_content($buildurl.'/artifact/work/smurf.xml');
        $smurfhtml = download_file_content($buildurl.'/artifact/work/smurf.html');

        if (empty($smurfxml) || empty($smurfhtml)) {
            throw new moodle_exception('unable_fetch_smurf_artifacts', 'local_plugins', '', $buildurl);
        }

        return [$smurfxml, $smurfhtml];
    }

    /**
     * Return the contents of the smurfxml and smurfhtml files of the given prechecker result.
     *
     * @param int $versionid
     * @param int $resultid
     * @return array list of (string)filename => (string)filecontents
     */
    protected function get_precheck_files(int $versionid, int $resultid) {
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');

        $smurfzippath = $this->get_pluginversion_precheck_zip_path($versionid, $resultid);

        if (!is_readable($smurfzippath)) {
            return [];
        }

        $tempdir = make_request_directory();
        $zip = new zip_packer();

        $files = [];

        foreach ($zip->extract_to_pathname($smurfzippath, $tempdir) as $filename => $ignored) {
            $files[$filename] = file_get_contents($tempdir.'/'.$filename);
        }

        return $files;
    }

    /**
     * Store the contents of the smurf files into a ZIP file.
     *
     * @param int $versionid
     * @param int $resultid
     * @param array $filecontents (string)filename => (string)contents
     */
    protected function store_precheck_files(int $versionid, int $resultid, array $filecontents) {
        global $CFG;
        require_once($CFG->libdir.'/filelib.php');

        $archive = [];

        // Convert each $filecontents item value into an array so that the zip_packer uses it as the content.
        foreach ($filecontents as $file => $content) {
            $archive[$file] = [$content];
        }

        $smurfzippath = $this->get_pluginversion_precheck_zip_path($versionid, $resultid);
        $zip = new zip_packer();
        $zip->archive_to_pathname($archive, $smurfzippath);
    }

    /**
     * Prepares a clean branch in the snapshots repository for the plugin version.
     *
     * @param int $pluginid
     * @param int $versionid
     * @param string $moodlever
     * @param string $moodlebranch
     * @param string $component
     * @return string the branch name
     */
    protected function prepare_plugin_version_branch($pluginid, $versionid, $moodlever, $moodlebranch, $component) {

        // Convert values like 3.2 to 32.
        $moodlever = str_replace('.', '', $moodlever);

        // Compose the branch name (format agreed with Eloy).
        $branchname = $versionid.'-'.$moodlever.'-'.$component;

        if ($this->git->has_local_branch($branchname)) {
            $this->git->exec('checkout '.escapeshellarg($branchname));
            $this->git->exec('reset --hard upstream/'.$moodlebranch);

        } else {
            $this->git->exec('checkout --no-track -b '.escapeshellarg($branchname).' upstream/'.$moodlebranch);
        }

        $this->git->exec('clean -dff');

        return $branchname;
    }

    /**
     * Unzip the plugin version ZIP into the snapshots repository
     *
     * @param int $pluginid
     * @param int $versionid
     * @param string $component
     */
    protected function extract_plugin_version_snapshot($pluginid, $versionid, $component) {

        list($plugintype, $pluginname) = core_component::normalize_component($component);

        $zipfile = $this->get_pluginversion_zip_path($pluginid, $versionid);
        $gitroot = $this->git->get_repodir();
        $plugintyperoot = $this->get_plugintype_relative_root($plugintype);

        if (file_exists($gitroot.'/'.$plugintyperoot.'/'.$pluginname)) {
            throw new moodle_exception('target_dir_already_exists', 'local_plugins', '', $plugintyperoot.'/'.$pluginname);
        }

        $codeman = new code_manager($this->git->get_repodir(), make_request_directory());
        $zipcontents = $codeman->unzip_plugin_file($zipfile, $gitroot.'/'.$plugintyperoot, $pluginname);

        if (empty($zipcontents)) {
            throw new moodle_exception('empty_plugin_zip', 'local_plugins');
        }

        // Just in case the plugin ZIP contains the .git folder.
        remove_dir($gitroot.'/'.$plugintyperoot.'/'.$pluginname.'/'.'.git');

        return $plugintyperoot.'/'.$pluginname;
    }

    /**
     * Returns relative path to the root of the plugin type.
     *
     * @param string $plugintype
     * @return string
     */
    protected function get_plugintype_relative_root($plugintype) {
        global $CFG;

        $plugintyperoot = null;
        foreach (core_component::get_plugin_types() as $type => $fullpath) {
            if ($type === $plugintype) {
                $plugintyperoot = $fullpath;
                break;
            }
        }

        if ($plugintyperoot === null) {
            throw new moodle_exception('unknown_plugin_type', 'local_plugins', '', $plugintype);
        }

        return trim(substr($plugintyperoot, strlen($CFG->dirroot)), '/');
    }

    /**
     * Returns the path to the ZIP of the given plugin version.
     *
     * @param int $pluginid
     * @param int $versionid
     * @return string
     */
    protected function get_pluginversion_zip_path ($pluginid, $versionid) {
        global $CFG;

        return $CFG->dataroot.'/local_plugins/'.$pluginid.'/'.$versionid.'.zip';
    }

    /**
     * Returns the path of the smurf.zip file for the given precheck result.
     *
     * @param int $versionid
     * @param int $resultid
     * @return string
     */
    protected function get_pluginversion_precheck_zip_path(int $versionid, int $resultid): string {
        global $CFG;

        if (empty($versionid) || empty($resultid)) {
            throw new moodle_exception('invalid_precheck_identifier', 'local_plugins');
        }

        return $CFG->dataroot.'/local_plugins/precheck/smurf/'.$versionid.'/'.$resultid.'.zip';
    }

    /**
     * Commits the snapshot of the plugin version
     *
     * @param string $target relative path of the plugin root
     * @param int $versionid
     * @param string $component
     */
    protected function commit_plugin_version_snapshot($target, $versionid, $component) {

        $this->git->exec('config user.email "plugins@moodle.org"');
        $this->git->exec('config user.name "Plugins bot"');
        $this->git->exec('add '.escapeshellarg($target));
        $this->git->exec('commit -m '.escapeshellarg('PLUGIN-'.$versionid.' '.$component.': cibot precheck request'));
    }

    /**
     * Push the branch to the public snapshots repository and trigger the precheck build
     *
     * @param string $branch
     * @param string $moodlebranch
     * @param int $versionid
     * @param object $config
     * @return array of (string)output, (string)debug output, (string)executed build URL
     */
    protected function execute_precheck_job($branch, $moodlebranch, $versionid, $config) {

        $this->git->exec('push -f origin '.escapeshellarg($branch));

        // We do not need to access the repository from now on.
        mtrace('Releasing snapshots repository lock ...');
        $this->gitlock->release();

        $jenkins = new jenkins($config->precheckcihost);

        $data = [
            'remote' => $config->prechecksnapshotsreporead,
            'branch' => $branch,
            'integrateto' => $moodlebranch,
            'issue' => 'PLUGIN-'.$versionid,
            'rebasewarn' => 999,
            'rebaseerror' => 999,
            'filtering' => 'true',
            'format' => 'html',
        ];

        $build = $jenkins->build($config->precheckcijob, $config->precheckcitoken, $data);

        return $build;
    }

    /**
     * Mark the given plugin version so that we do not want it to be prechecked.
     *
     * This is used to create a precheck record for not most recent plugin versions so that we can easily spot actual
     * new versions without the need to check all of old ones over and over again.
     *
     * @param int $versionid id of the plugin version that was not yet and should not be prechecked
     */
    protected function skip_plugin_version_precheck(int $versionid) {
        global $DB;

        if ($DB->record_exists('local_plugins_vers_precheck', ['versionid' => $versionid])) {
            return;
        }

        $DB->insert_record('local_plugins_vers_precheck', [
            'versionid' => $versionid,
            'timestart' => time(),
            'status' => -99,
        ]);
    }
}

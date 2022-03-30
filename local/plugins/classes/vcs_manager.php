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
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides integration with the VCS of the plugin
 *
 * @copyright 2014 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_vcs_manager {

    /** @var local_plugins_plugin */
    public $plugin;

    /** @var stdClass|bool */
    protected $vcsinfo;

    /**
     * @param local_plugins_plugin $plugin
     */
    public function __construct(local_plugins_plugin $plugin) {

        $this->plugin = $plugin;
        $this->vcsinfo = $plugin->get_vcs_info();
    }

    /**
     * Return the VCS info about the associated plugin
     *
     * See {@link local_plugins_plugin::get_vcs_info()}.
     *
     * @return bool|stdClass
     */
    public function get_vcs_info() {
        return $this->vcsinfo;
    }

    /**
     * Does the plugin's VCS allow to add new plugin version quickly?
     *
     * Currently we support publishing new versions based on github tags only.
     *
     * @return bool
     */
    public function uses_github() {

        if (isset($this->vcsinfo->type) and $this->vcsinfo->type === 'github') {
            return true;
        }

        return false;
    }

    /**
     * Returns URL to browse the plugin's repository
     *
     * @return string|bool
     */
    public function get_vcs_url() {

        if (!$this->uses_github()) {
            return false;
        }

        return 'https://github.com/'.$this->vcsinfo->github_username.'/'.$this->vcsinfo->github_reponame;
    }

    /**
     * Returns the list of available tags in the plugin's repository
     *
     * @return array|false Array of (string)tagname => (string)tagname, false if not supported
     */
    public function get_available_tags() {

        if (!$this->uses_github()) {
            return false;
        }

        $tags = array();
        $found = $this->github_list_tags();

        if (!empty($found)) {
            foreach ($found as $taginfo) {
                // Search all available versions to see if the tag has already
                // been used.
                $used = false;
                foreach ($this->plugin->versions as $version) {
                    if ($version->vcstag === $taginfo->name) {
                        $used = $version->releasename;
                        break;
                    }
                }

                if ($used === false) {
                    $tags[$taginfo->name] = $taginfo->name;
                } else {
                    $tags[$taginfo->name] = $taginfo->name.' (already released as '.$used.')';
                }
            }
        }

        return $tags;
    }

    /**
     * Fetch the remote tagged version into a new user draft area
     *
     * @param string $tag
     * @return stdClass|bool false when unable, data object otherwise
     */
    public function fetch_tagged_version($tag) {
        global $USER;

        if (!$this->uses_github()) {
            return false;
        }

        $taginfo = $this->get_tag_info($tag);

        if (empty($taginfo)) {
            return false;
        }

        $usercontext = context_user::instance($USER->id);
        $draftitemid = file_get_unused_draft_itemid();
        $downloadurl = $taginfo->zipball_url;

        $filerecord = array(
            'contextid' => $usercontext->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftitemid,
            'filename' => $this->vcsinfo->github_reponame.'-'.$tag.'.zip',
            'filepath' => '/'
        );
        $fs = get_file_storage();
        $fs->create_file_from_url($filerecord, $downloadurl, null, true);

        return (object)array(
            'vcstag' => $taginfo->name,
            'draftitemid' => $draftitemid,
            'downloadurl' => $downloadurl,
        );
    }

    /**
     * Returns the given tag info as returned by github
     *
     * @param string $tag
     * @return stdClass|bool false when unable, data object otherwise
     */
    protected function get_tag_info($tag) {

        $tags = $this->github_list_tags();

        if (empty($tags)) {
            return false;
        }

        foreach ($tags as $taginfo) {
            if ($taginfo->name === $tag) {
                return $taginfo;
            }
        }

        return false;
    }

    /**
     * Returns the list of tags in the plugin's github repository.
     *
     * @link https://developer.github.com/v3/repos/#list-tags
     * @return array|boolean Array of objects or false on error.
     */
    protected function github_list_tags() {

        if (empty($this->vcsinfo->github_username) or empty($this->vcsinfo->github_reponame)) {
            return false;
        }

        return $this->github_api_call('/repos/'.$this->vcsinfo->github_username.'/'.$this->vcsinfo->github_reponame.'/tags?per_page=100');
    }

    /**
     * Performs a GET query against Github API and returns decoded response.
     *
     * @param string $query The GET query
     * @return mixed Decoded response, false on error.
     */
    protected function github_api_call($query) {

        $baseurl = 'https://api.github.com';
        $curl = new curl();
        $curl->setHeader('Accept: application/vnd.github.v3+json'); // As per https://developer.github.com/v3/#current-version
        $response = $curl->get($baseurl.$query);

        if ($curl->info['http_code'] !== 200 or empty($response)) {
            return false;
        }

        return json_decode($response);
    }
}

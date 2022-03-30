<?php

/**
 * This file contains the plugin version class.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/plugins/lib/download_resolver.php');

/**
 *
 * @property-read int $id
 * @property-read int $pluginid
 * @property-read int $userid
 * @property-read string $version
 * @property-read string $releasename
 * @property-read int $maturity
 * @property-read string $releasenotes
 * @property-read int $releasenotesformat
 * @property-read string $changelogurl
 * @property-read string $altdownloadurl
 * @property-read string $md5sum
 * @property-read string $vcssystem
 * @property-read string $vcssystemother
 * @property-read string $vcsrepositoryurl
 * @property-read string $vcsbranch
 * @property-read string $vcstag
 * @property-read int $timecreated
 * @property-read int $timelastmodified
 * @property-read int $approved
 * @property-read bool $visible
 *
 * @property-read string $pluginversionname
 * @property-read string $formatted_releasename
 * @property-read string $formatted_fullname
 * @property-read string $formatted_vcssystem
 * @property-read string $formatted_maturity
 * @property-read string $formatted_releasenotes
 * @property-read moodle_url $releasenoteslink
 * @property-read string $formatted_timecreated
 * @property-read string $formatted_timelastmodified
 * @property-read moodle_url $downloadlink
 * @property-read moodle_url $downloadlinkredirector
 * @property-read moodle_url $editlink
 * @property-read moodle_url $viewlink
 * @property-read moodle_url $showlink
 * @property-read moodle_url $hidelink
 * @property-read moodle_url $reviewlink
 * @property-read moodle_url $writereviewlink
 * @property-read string $downloadfilename
 * @property-read moodle_url $repositorylink
 * @property rating $rating
 * @property-read array $supportedsoftware
 * @property-read $latest_moodle_version
 * @property-read $moodle_versions
 *
 * @property-read local_plugins_plugin $plugin
 * @property-read array $awards
 * @property-read array $average_review_grades
 */
class local_plugins_version extends local_plugins_class_base implements renderable, local_plugins_loggable {

    // Database properties
    protected $id;
    protected $pluginid;
    protected $userid;
    protected $version;
    protected $releasename;
    protected $maturity;
    protected $releasenotes;
    protected $releasenotesformat;
    protected $changelogurl;
    protected $altdownloadurl;
    protected $md5sum;
    protected $vcssystem;
    protected $vcssystemother;
    protected $vcsrepositoryurl;
    protected $vcsbranch;
    protected $vcstag;
    protected $timecreated;
    protected $timelastmodified;
    protected $approved;
    protected $visible;

    // Object only properties
    protected $plugin = null;
    protected $awards = null;
    protected $reviewcount = 0;
    protected $rating;
    protected $supportedsoftware = null;
    protected $updateableversions = null;
    protected $updatetoversions = null;
    protected $smurfresult = null;

    public function set_rating(rating $rating) {
        $this->rating = $rating;
    }

    public function update($properties) {
        global $DB;
        $properties = (array)$properties;
        $changes = false;

        $fields = array('version', 'releasename', 'maturity', 'releasenotes', 'releasenotesformat', 'changelogurl',
                        'altdownloadurl', 'vcssystem', 'vcssystemother', 'vcsrepositoryurl', 'vcsbranch', 'vcstag');

        $version = new stdClass;
        $version->id = $this->id;
        $version->timelastmodified = time();
        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] != $this->$field) {
                $version->$field = $properties[$field];
                $changes = true;
            }
        }
        if (array_key_exists('softwareversion', $properties)) {
            $changes = $this->update_supported_software($properties['softwareversion']) || $changes;
        }
        if (array_key_exists('updateableid', $properties)) {
            $changes = $this->update_updateable_versions($properties['updateableid']) || $changes;
        }
        if ($changes) {
            $DB->update_record('local_plugins_vers', $version);
            foreach ($version as $property => $value) {
                $this->$property = $value;
            }
            $this->plugin->update_modified($version->timelastmodified);
        }
        return $changes;
    }

    public function update_supported_software(array $softwareversionids) {
        global $DB;

        $software = local_plugins_helper::get_software_versions();
        $current = $this->get_supportedsoftware();

        $bulk = (count($softwareversionids) > 2);
        $added = 0;
        foreach ($softwareversionids as $id) {
            if (!array_key_exists($id, $current) && array_key_exists($id, $software)) {
                $record = new stdClass;
                $record->versionid = $this->id;
                $record->softwareversionid = $id;
                $DB->insert_record('local_plugins_supported_vers', $record, false, $bulk);
                $this->supportedsoftware[$id] = $software[$id];
                $added++;
            }
        }

        $toremove = array();
        foreach ($current as $id => $version) {
            if (!in_array($id, $softwareversionids)) {
                $toremove[] = $id;
                unset($this->supportedsoftware[$id]);
            }
        }

        if (sizeof($toremove)) {
            list($select, $params) = $DB->get_in_or_equal($toremove, SQL_PARAMS_NAMED, 'softwareversionid');
            $select = "softwareversionid ".$select." and versionid = :versionid";
            $params['versionid'] = $this->id;
            $DB->delete_records_select('local_plugins_supported_vers', $select, $params);
        }

        return ($added + count($toremove) > 0);
    }

    public function get_supportedsoftware() {
        global $DB;
        if (is_null($this->supportedsoftware)) {
            $software = local_plugins_helper::get_software_versions();
            $supports = $DB->get_records('local_plugins_supported_vers', array('versionid' => (int)$this->id));
            $this->supportedsoftware = array();
            foreach ($supports as $id => $support) {
                $this->supportedsoftware[$support->softwareversionid] = $software[$support->softwareversionid];
            }
        }
        return $this->supportedsoftware;
    }

    /**
     * Returns the list of the other plugin versions that can be updated to this one
     *
     * @return array the list of version ids
     */
    public function get_updateable_versions() {
        global $DB;
        if (is_null($this->updateableversions)) {
            $dbversions = $DB->get_fieldset_select('local_plugins_vers_updates', 'updateableid', 'versionid=:versionid', array('versionid' => (int)$this->id));
            $this->updateableversions = array_intersect(array_keys($this->plugin->versions), $dbversions);
        }
        return $this->updateableversions;
    }

    /**
     * Returns the list of the other plugin versions that this version can be updated to
     *
     * @return array the list of version ids
     */
    public function get_update_to_versions() {
        global $DB;
        if (is_null($this->updatetoversions)) {
            $dbversions = $DB->get_fieldset_select('local_plugins_vers_updates', 'versionid', 'updateableid=:updateableid', array('updateableid' => (int)$this->id));
            $this->updatetoversions = array_intersect(array_keys($this->plugin->versions), $dbversions);
        }
        return $this->updatetoversions;
    }

    /**
     * Analyzes if the list of updateable versions needs to be changed in DB and perform insert/delete if needed.
     * Returns true if changes were made
     *
     * @param array $updateableversionids
     * @return boolean
     */
    public function update_updateable_versions(array $updateableversionids) {
        global $DB;
        $current = $this->get_updateable_versions();
        // 1. insert new
        $toadd = array_diff($updateableversionids, $current);
        foreach ($toadd as $id) {
            $record = array('versionid' => $this->id, 'updateableid' => $id);
            $DB->insert_record('local_plugins_vers_updates', (object)$record, false);
        }
        // 2. remove unused
        $toremove = array_diff($current, $updateableversionids);
        foreach ($toremove as $id) {
            $DB->delete_records('local_plugins_vers_updates', array('versionid' => $this->id, 'updateableid' => $id));
        }
        // 3. set new list
        if (count($toadd) || count($toremove)) {
            $this->updateableversions = $updateableversionids;
            return true;
        }
        return false;
    }

    /**
     * Returns array of supported Moodle versions
     */
    public function get_moodle_versions() {
        $moodleversions = array();
        foreach ($this->get_supportedsoftware() as $software)  if ($software->name == 'Moodle') {
            $moodleversions[] = $software;
        }
        usort($moodleversions, 'local_plugins_sort_by_version');
        return $moodleversions;
    }

    /**
     * Returns the list of Moodle versions as string
     * @return string
     */
    public function get_formatted_moodle_versions($separator = ', ') {
        $moodleversions = array();
        foreach ($this->get_moodle_versions() as $mversion) {
            $moodleversions[] = $mversion->releasename;
        }
        if (empty($moodleversions)) {
            return '';
        }
        return 'Moodle '.join($separator, $moodleversions);
    }

    /**
     * Returns latest supported Moodle version (as object or just one specified attribute of an object)
     *
     * @param string $attr
     * @return mixed
     */
    public function get_latest_moodle_version($attr = null) {
        $versions = $this->get_moodle_versions();
        if (empty($versions)) {
            return null;
        }
        $version = $versions[sizeof($versions)-1];
        if ($attr === null) {
            return $version;
        } else {
            return $version->$attr;
        }
    }

    public function change_visibility($visibility) {
        global $DB;
        if (!$this->can_change_visibility()) {
            return false;
        }
        $visibility = (bool)$visibility;
        if ($visibility == $this->visible) {
            // The visibility isn't actually changing
            return false;
        }
        $version = new stdClass;
        $version->id = $this->id;
        $version->timelastmodified = time();
        $version->visible = (int)$visibility;
        $DB->update_record('local_plugins_vers', $version);
        foreach ($version as $property => $value) {
            $this->$property = $value;
        }
        $this->plugin->update_modified($version->timelastmodified);
        return true;
    }

    /**
     *
     * @global moodle_database $DB
     * @param local_plugins_review $properties
     */
    public function add_review(stdClass $properties) {
        global $DB, $USER;
        $properties->userid = $USER->id;
        $properties->versionid = $this->id;
        $properties->softwareid = 0;
        $properties->timereviewed = time();
        $properties->timelastmodified = time();
        $properties->id = $DB->insert_record('local_plugins_review', $properties, true);
        $properties->user = $USER;
        $properties->plugin = $this->plugin;
        return new local_plugins_review($properties);
    }

    /**
     * @global moodle_database $DB
     * @param string $archivepath
     * @return bool
     */
    public function save_archive_md5($archivepath) {
        global $DB;
        if (!file_exists($archivepath)) {
            return false;
        }
        $md5 = md5_file($archivepath);
        $DB->set_field('local_plugins_vers', 'md5sum', $md5, array('id' => $this->id));
        $this->md5sum = $md5;
        return true;
    }

    /**
     * Returns true if there are no visible&approved versions following this one
     *
     * @return boolean
     */
    public function is_latest_version() {
        return in_array($this->id, array_keys($this->plugin->latestversions));
    }

    protected function get_releasenoteslink() {
        if (!empty($this->releasenotes)) {
            return $this->viewlink;
        } else {
            return null;
        }
    }

    /**
     * @return moodle_url URL to download this version via our own download.php
     */
    protected function get_downloadlink() { // legacy download link (logs download statistic locally)
        return local_plugins_download_resolver::get_download_link($this->id, $this->downloadfilename, null);
    }

    /**
     * @return moodle_url URL to download this version via external provider (falls back to our download.php)
     */
    protected function get_downloadlinkredirector() {
        global $CFG;

        return local_plugins_download_resolver::get_download_link($this->id, $this->downloadfilename, $CFG->local_plugins_downloadredirectorurl);
    }

    protected function get_reviewlink() {
        return new local_plugins_url('/local/plugins/reviews.php', array('version' => $this->id));
    }

    protected function get_writereviewlink() {
        return new local_plugins_url('/local/plugins/review.php', array('version' => $this->id));
    }

    /**
     * @return string filename part of the download URL for this version
     */
    protected function get_downloadfilename() {
        return local_plugins_download_resolver::get_download_filename($this->plugin->frankenstyle,
            $this->plugin->name, $this->version, $this->get_latest_moodle_version('releasename'));
    }

    protected function get_repositorylink() {
        return new local_plugins_url($this->vcsrepositoryurl);
    }

    protected function get_formatted_timecreated() {
    	return userdate($this->timecreated);
    }

    protected function get_formatted_timelastmodified() {
    	return userdate($this->timelastmodified);
    }

    protected function get_formatted_releasenotes() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_VERSIONRELEASENOTES;
        $fileoptions = local_plugins_helper::editor_options_version_releasenotes();
        $releasenotes = file_rewrite_pluginfile_urls($this->releasenotes, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => false,
            'trusted' => false,
            'filter' => true,
            'context' => $context
        );
        return format_text($releasenotes, $this->releasenotesformat, $formatoptions);
    }

    protected function get_reviews() {
        return $this->plugin->get_reviews($this->id);
    }

    public function get_review($reviewid) {
        $reviews = $this->plugin->reviews;
        if (!array_key_exists($reviewid, $reviews)) {
            throw new local_plugins_exception('exc_invalidreview');
        }
        return $reviews[$reviewid];
    }

    /**
     * Record the version download attempt
     *
     * @param string $method how was the plugin downloaded
     */
    public function log_download($method = 'website') {
        global $USER;

        $statsman = new local_plugins_stats_manager();
        $excluded = $statsman->log_version_download($this->id, time(), $USER->id, $method, getremoteaddr());

        if ($excluded) {
            // TODO The threshold reached, this download attempt will be
            // excluded from the stats. Plugins team should be warned about
            // this (if they were not already).
        }
    }

    /**
     * returns true if version is visible to everybody (visible and approved)
     */
    public function is_available() {
        return ($this->visible && $this->approved == local_plugins_plugin::PLUGIN_APPROVED);
    }

    public function can_edit() {
        return $this->plugin->can_edit();
    }

    public function can_change_visibility() {
        return ($this->can_edit() && $this->approved == local_plugins_plugin::PLUGIN_APPROVED);
    }

    public function can_delete() {
        global $DB;
        $context = context_system::instance();

        return (has_capability(local_plugins::CAP_DELETEANYPLUGINVERSION, $context) ||
                ($this->plugin->user_is_maintainer() && has_capability(local_plugins::CAP_DELETEOWNPLUGINVERSION, $context)));
    }

    public function can_view() {
        if (!$this->plugin->can_view()) {
            return false;
        }
        return ($this->is_available() ||
                 $this->user_is_creator() ||
                 $this->plugin->can_view_unapproved());
    }

    /**
     * Returns list of all actions (urls) this user is allowed to perform on the version
     * array(
     * CLASSNAME => array(URL, LINKTEXT)
     */
    public function actions_list($excludeactions = null) {
        $buttons = array();
        if ($this->can_view()) {
            $buttons['view'] = array($this->viewlink, 'releasenotes');
        }
        if ($this->can_download()) {
            $buttons['download'] = array($this->downloadlink);
        }
        if ($this->can_approve() && $this->approved != local_plugins_plugin::PLUGIN_APPROVED) {
            $buttons['approve'] = array($this->approvelink);
        }
        if ($this->can_approve() && $this->approved != local_plugins_plugin::PLUGIN_UNAPPROVED) {
            $buttons['disapprove'] = array($this->disapprovelink);
        }
        if ($this->can_edit() && $this->approved == local_plugins_plugin::PLUGIN_UNAPPROVED) {
            $buttons['scheduleapprove'] = array($this->scheduleapprovallink);
        }
        if ($this->can_edit()) {
            $buttons['edit'] = array($this->editlink, 'editdetails');
        }
        if ($this->can_change_visibility()) {
            if ($this->visible) {
                $buttons['hide'] = array($this->hidelink);
            } else {
                $buttons['show'] = array($this->showlink);
            }
        }
        if ($this->can_delete()) {
            $buttons['delete'] = array($this->deletelink, 'deleteversion');
        }

        foreach ($buttons as $class => $vals) {
            if (count($vals) < 2) {
                $buttons[$class][1] = $class;
            }
            $buttons[$class][1] = get_string($buttons[$class][1], 'local_plugins');
        }
        if ($this->can_view_review()) {
            if ($this->reviewcount) {
                $buttons['viewreviews'] = array($this->reviewlink, get_string('viewreviews', 'local_plugins', $this->reviewcount));
            } else if ($this->can_publish_review()) {
                $buttons['writereview'] = array($this->writereviewlink, get_string('writereview', 'local_plugins'));
            }
        }
        if (!empty($excludeactions) && is_array($excludeactions)) {
            foreach ($excludeactions as $class) {
                unset($buttons[$class]);
            }
        } else if (!empty($excludeactions) && is_string($excludeactions)) {
            unset($buttons[$excludeactions]);
        }
        return $buttons;
    }

    public function user_is_creator() {
        global $USER;
        return (isloggedin() && !isguestuser() && $USER->id === $this->userid);
    }

    public function can_view_status() {
        return $this->user_is_creator() || $this->plugin->can_view_status();
    }

    public function can_view_review() {
        return $this->can_view();
    }

    public function can_publish_review() {
        return $this->can_view() && has_capability(local_plugins::CAP_PUBLISHREVIEWS, context_system::instance());
    }

    public function can_download() {
        return $this->can_view();
    }

    public function can_approve() {
        return has_capability(local_plugins::CAP_APPROVEPLUGINVERSION, context_system::instance());
    }

    protected function get_editlink() {
        return new local_plugins_url('/local/plugins/pluginversion.php', array('id' => $this->id, 'action' => 'edit'));
    }

    protected function get_deletelink() {
        return new local_plugins_url('/local/plugins/pluginversion.php', array('id' => $this->id, 'action' => 'delete', 'sesskey' => sesskey()));
    }

    protected function get_viewlink() {

        $versionlinkparams = ['id' => $this->id];
        $pluginlinkparams = $this->plugin->link_params();

        if (!empty($pluginlinkparams['plugin'])) {
            $versionlinkparams['plugin'] = $pluginlinkparams['plugin'];
            $versionlinkparams['releasename'] = local_plugins_url::slug($this->get_formatted_releasename());
        }

        return new local_plugins_url('/local/plugins/pluginversion.php', $versionlinkparams);
    }

    protected function get_approvelink() {
        global $PAGE;
        return new local_plugins_url('/local/plugins/pluginversion.php', array('id' => $this->id, 'action' => 'approve', 'approve' => local_plugins_plugin::PLUGIN_APPROVED, 'sesskey' => sesskey(), 'redirect' => $PAGE->url));
    }

    protected function get_disapprovelink() {
        global $PAGE;
        return new local_plugins_url('/local/plugins/pluginversion.php', array('id' => $this->id, 'action' => 'approve', 'approve' => local_plugins_plugin::PLUGIN_UNAPPROVED, 'sesskey' => sesskey(), 'redirect' => $PAGE->url));
    }

    protected function get_scheduleapprovallink() {
        global $PAGE;
        return new local_plugins_url('/local/plugins/pluginversion.php', array('id' => $this->id, 'action' => 'approve', 'approve' => local_plugins_plugin::PLUGIN_PENDINGAPPROVAL, 'sesskey' => sesskey(), 'redirect' => $PAGE->url));
    }
    protected function get_hidelink() {
        global $PAGE;
        return new local_plugins_url('/local/plugins/pluginversion.php', array('id' => $this->id, 'action' => 'visibility', 'visible' => 0, 'sesskey' => sesskey(), 'redirect' => $PAGE->url));
    }
    protected function get_showlink() {
        global $PAGE;
        return new local_plugins_url('/local/plugins/pluginversion.php', array('id' => $this->id, 'action' => 'visibility', 'visible' => 1, 'sesskey' => sesskey(), 'redirect' => $PAGE->url));
    }

    /**
     * Deletes a version completely from database.
     * In the most cases the contributors can not delete versions even when they created them.
     * But if error occurs during version creation, this function is called without checking
     * current user's permission to delete.
     *
     * @param boolean $checkpermission
     */
    public function delete($checkpermission = true) {
        global $DB;

        if ($checkpermission && !$this->can_delete()) {
            throw new local_plugins_exception('exc_permissiondenied');
        }

        $reviews = $this->get_reviews();
        if (count($reviews)) {
            foreach ($reviews as $review) {
                local_plugins_log::remember_state($review);
                $review->delete();
                local_plugins_log::log_deleted($review);
            }
            $this->plugin->load_reviews();
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_plugins_plugin_awards', array('versionid' => $this->id));
        $DB->delete_records('local_plugins_supported_vers', array('versionid' => $this->id));
        $DB->delete_records('local_plugins_stats_raw', array('versionid' => $this->id));
        $DB->delete_records('local_plugins_vers_updates', array('versionid' => $this->id));
        $DB->delete_records('local_plugins_vers_updates', array('updateableid' => $this->id));
        $DB->delete_records('local_plugins_vers', array('id' => $this->id));
        $transaction->allow_commit();
        $transaction = null;
        //TODO delete files
        //TODO delete ratings
    }

    public function approve($approve = local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
        global $DB;
        $approve = (int)$approve;
        if ($approve == local_plugins_plugin::PLUGIN_APPROVED || $approve == local_plugins_plugin::PLUGIN_UNAPPROVED) {
            if (!$this->can_approve()) {
                return false;
            }
        } else if ($approve == local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
            if (!$this->can_edit()) {
                return false;
            }
        } else {
            return false;
        }
        $version = new stdClass();
        $version->id = $this->id;
        $version->timelastmodified = time();
        $version->approved = $approve;
        if ($approve != local_plugins_plugin::PLUGIN_APPROVED && !$this->visible) {
            // visibility can only be toggled in APPROVED state. For other states it should be set to true
            $version->visible = 1;
        }
        $DB->update_record('local_plugins_vers', $version);
        foreach ($version as $property => $value) {
            $this->$property = $value;
        }
        $this->plugin->update_modified($version->timelastmodified);
        return true;
    }

    /**
     * @deprecated
     * @return string
     */
    public function get_status() {
        debugging('Deprecated: status is a deprecated property, please convert your code to use maturity', DEBUG_DEVELOPER);
        return $this->get_formatted_maturity();
    }

    public function get_formatted_maturity() {
        if ($this->maturity && get_string_manager()->string_exists('maturity'.$this->maturity, 'admin')) {
            return get_string('maturity'.$this->maturity, 'admin');
        }
        return get_string('unknown', 'local_plugins');
    }

    /**
     * @deprecated
     * @return string
     */
    public function get_name() {
        debugging('Deprecated: name is a deprecated property, please convert your code to use releasename', DEBUG_DEVELOPER);
        return $this->get_formatted_releasename();
    }

    public function get_formatted_releasename() {
        if (!empty($this->releasename)) {
            return format_string($this->releasename, true, array('context' => context_system::instance()));
        } else {
            return get_string('defaultreleasename', 'local_plugins', $this->version);
        }
    }

    /**
     * Returns the plugin version name
     *
     * @return string
     */
    public function get_pluginversionname() {

        $displayname = $this->plugin->formatted_name;

        if (!empty($this->releasename)) {
            $displayname .= ' '.$this->releasename;
        } else if ($this->version !== null) {
            $displayname .= ' '.$this->version;
        } else {
            $displayname .= ' [ver id '.$this->id.']';
        }

        return s($displayname);
    }


    public function get_formatted_fullname() {
        if (!empty($this->releasename) && !empty($this->version)) {
            return $this->releasename . " ".html_writer::tag('span', "(" . $this->version . ")", array('class' => 'version'));
        } else if (!empty($this->releasename) || !empty($this->version)) {
            return $this->releasename . $this->version ;
        } else {
            return get_string('defaultreleasename', 'local_plugins', $this->version);
        }
    }

    /**
     * Retruns the release name and the required Moodle version, i.e. "1.0 for Moodle 2.1"
     */
    public function get_formatted_releasename_and_moodle_version() {
        $a = array('version' => $this->get_formatted_releasename(), 'requirements' => $this->get_formatted_moodle_versions(', '));
        if (empty($a['requirements'])) {
            return $a['version'];
        } else {
            return get_string('versionreleasefor', 'local_plugins', (object)$a);
        }
    }

    /**
     * Retruns the release name, version build number and the required Moodle version, i.e. "1.0 (20111001) for Moodle 2.1"
     */
    public function get_formatted_fullname_and_moodle_version() {
        $a = array('version' => $this->get_formatted_fullname(), 'requirements' => $this->get_formatted_moodle_versions(', '));
        if (empty($a['requirements'])) {
            return $a['version'];
        } else {
            return get_string('versionreleasefor', 'local_plugins', (object)$a);
        }
    }

    public function get_formatted_vcssystem() {
        if ($this->vcssystem == 'none' || empty($this->vcssystem)) {
            return '';
        }
        if ($this->vcssystem == 'other' && !empty($this->vcssystemother)) {
            return $this->vcssystemother;
        }
        return get_string($this->vcssystem, 'local_plugins');
    }

    public function get_average_review_grades() {
        $reviews = $this->plugin->get_reviews($this->id);
        $criteria = local_plugins_helper::get_review_criteria();
        $outcomes = array();
        $grades = array();
        foreach ($reviews as $review) {
            foreach ($review->outcomes as $outcome) {
                if ($outcome->is_graded()) {
                    if (!array_key_exists($outcome->criteriaid, $grades)) {
                        $grades[$outcome->criteriaid] = array('cnt' => 0, 'sum' => 0);
                    }
                    $grades[$outcome->criteriaid]['cnt']++;
                    $grades[$outcome->criteriaid]['sum'] = $outcome->criterion->parse_grade($outcome->grade);
                }
            }
        }
        foreach ($criteria as $criterion) {
            if (array_key_exists($criterion->id, $grades)) {
                $outcome = new stdClass();
                $outcome->id = 0;
                $outcome->criteriaid = $criterion->id;
                $outcome->grade = $grades[$criterion->id]['sum'] / $grades[$criterion->id]['cnt'];
                $outcomes[] = new local_plugins_review_outcome($outcome);
            }
        }
        return $outcomes;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_action_prefix() {
        return local_plugins_log::LOG_PREFIX_PLUGIN_VERS;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_identifier($forplugin) {
        $a = html_writer::link($this->get_viewlink(), $this->get_formatted_fullname());
        $vinfo = get_string('logidentifierversion', 'local_plugins', $a);
        if ($forplugin) {
            return $vinfo;
        } else {
            $a = new stdClass();
            $a->pluginidentifier = $this->plugin->log_identifier(false);
            $a->object = $vinfo;
            return get_string('logidentifierpluginobject', 'local_plugins', $a);
        }
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_data() {
        // prepare human-readable status
        if ($this->approved == local_plugins_plugin::PLUGIN_UNAPPROVED) {
            $status = get_string('notapproved', 'local_plugins');
        } else if ($this->approved == local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
            $status = get_string('pendingapproval', 'local_plugins');
        } else if (!$this->visible) {
            $status = get_string('invisible', 'local_plugins');
        } else {
            $status = get_string('available', 'local_plugins');
        }
        // prepare releasename
        if (empty($this->releasename)) {
            $releasename = null;
        } else {
            $releasename = $this->formatted_releasename;
        }
        // prepare supportedsoftware
        $supportedsoftware = array();
        foreach ($this->get_supportedsoftware() as $soft) {
            $supportedsoftware[] = $soft->fullname_version;
        }
        // prepare updateable versions list
        $updateablefrom = array();
        foreach ($this->get_updateable_versions() as $versid) {
            $vers = $this->plugin->get_version($versid);
            $updateablefrom[] = $vers->get_formatted_fullname_and_moodle_version();
        }
        // return the array of version attributes
        return array(
            'versionnumber' => $this->version,
            'versionname' => $this->releasename,
            'status' => $status,
            'supportedsoftware' => join(', ', $supportedsoftware),
            'maturity' => $this->formatted_maturity,
            'updateableversions' => join('<br>', $updateablefrom),
            'changelogurl' => $this->changelogurl,
            'altdownloadurl' => $this->changelogurl,
            'releasenotes' => $this->formatted_releasenotes,
            'vcssystem' => $this->formatted_vcssystem,
            'vcsrepositoryurl' => $this->vcsrepositoryurl,
            'vcsbranch' => $this->vcsbranch,
            'vcstag' => $this->vcstag,
        );
    }

    /**
     * @see local_plugins_subscribable
     */
    public function get_subscription_type() {
        return local_plugins::NOTIFY_VERSION;
    }
}
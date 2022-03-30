<?php

/**
 * This file contains the plugin class. This class is likely the most
 * frequently used class within this system as it is the base
 * for most actions.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

/**
 * The plugin class
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 *
 * @property-read int $id
 * @property-read int $categoryid
 * @property-read string $name
 * @property-read string $frankenstyle
 * @property-read string $shortdescription
 * @property-read string $description
 * @property-read int $descriptionformat
 * @property-read string $websiteurl
 * @property-read string $sourcecontrolurl
 * @property-read string $faqs
 * @property-read int $faqsformat
 * @property-read string $documentation
 * @property-read int $documentationformat
 * @property-read string $documentationurl
 * @property-read string $bugtrackerurl
 * @property-read string $discussionurl
 * @property-read string $trackingwidgets
 * @property-read int $timecreated
 * @property-read int $timelastmodified
 * @property-read int $timelastreleased
 * @property-read int $timefirstapproved
 * @property-read int $approved
 * @property-read bool $visible
 * @property-read string $approvalissue
 *
 * @property-read moodle_url $addversionlink
 * @property-read array $awards
 * @property-read local_plugins_category $category
 * @property-read array $contributors
 * @property-read string $maintained_by
 * @property-read moodle_url $baselink
 * @property-read moodle_url $approvelink
 * @property-read moodle_url $disapprovelink
 * @property-read moodle_url $scheduleapprovallink
 * @property-read moodle_url $hidelink
 * @property-read moodle_url $editfaqslink
 * @property-read moodle_url $editlink
 * @property-read moodle_url $deletelink
 * @property-read moodle_url $confirmdeletelink
 * @property-read moodle_url $showlink
 * @property-read moodle_url $addcontributorlink
 * @property-read string $formatted_description
 * @property-read string $formatted_docs
 * @property-read string $formatted_faqs
 * @property-read string $formatted_name
 * @property-read string $formatted_shortdescription
 * @property-read array $versions
 * @property-read array $moodle_versions
 * @property-read array $latestversions
 * @property-read array $unavailablelatestversions
 * @property-read moodle_url $logo The logo for this plugin (own or default)
 * @property-read moodle_url|null $ownlogo The logo for this plugin (own or nothing)
 * @property-read local_plugins_version $mostrecentversion
 * @property-read array $previousversions
 * @property-read array $reviews
 * @property-read array $subscribers
 * @property-read array $screenshots Array of screenshot info
 * @property-read array $sets
 * @property-read array $versions
 * @property-read moodle_url $viewfaqslink
 * @property-read moodle_url $viewlink
 * @property-read moodle_url $viewlink_log
 * @property-read moodle_url $viewreviewslink
 * @property-read moodle_url $viewstatslink
 * @property-read moodle_url $viewversionslink
 * @property-read moodle_url $devzonelink
 * @property-read moodle_url $translationslink
 * @property-read array $searchinfo
 * @property-read array $favourites
 */
class local_plugins_plugin extends local_plugins_class_base implements renderable, local_plugins_loggable, local_plugins_subscribable {
    const PLUGIN_PENDINGAPPROVAL = 0;
    const PLUGIN_APPROVED = 1;
    const PLUGIN_UNAPPROVED = -1;

    // Database properties
    protected $id;
    protected $categoryid;
    protected $name;
    protected $frankenstyle;
    protected $type;
    protected $shortdescription;
    protected $description;
    protected $descriptionformat;
    protected $websiteurl;
    protected $sourcecontrolurl;
    protected $faqs;
    protected $faqsformat;
    protected $documentation;
    protected $documentationformat;
    protected $documentationurl;
    protected $bugtrackerurl;
    protected $discussionurl;
    protected $trackingwidgets;
    protected $timecreated;
    protected $timelastmodified;
    protected $timelastreleased;
    protected $timefirstapproved;
    protected $timelastapprovedchange;
    protected $approved;
    protected $approvedby;
    protected $visible;
    protected $aggdownloads;
    protected $aggfavs;
    protected $aggsites;
    protected $approvalissue;
    protected $statusamos;

    // Object only properties
    protected $category = null;
    protected $versions = null;
    protected $awards = null;
    protected $sets = null;
    protected $contributors = null;
    protected $classifiedversions = null;
    protected $favourites = null;

    protected $reviews = null;

    protected $searchinfo = null;

    /** @var array */
    protected $descriptors = null;

    protected function get_category() {
        if ($this->category === null) {
            $this->category = local_plugins_helper::get_category($this->categoryid);
        }
        return $this->category;
    }

    public function get_versions() {
        if ($this->versions === null) {
            $this->load_versions();
        }
        return $this->versions;
    }

    /**
     *
     * @param type $versionid
     * @return local_plugins_version
     */
    public function get_version($versionid = null) {
        $this->get_versions();
        if ($versionid === null) {
            return $this->get_mostrecentversion();
        }
        if (!array_key_exists($versionid, $this->versions)) {
            throw new local_plugins_exception('exc_invalidversion');
        }
        return $this->versions[$versionid];
    }

    /**
     *
     * @global moodle_database $DB
     */
    protected function load_versions() {
        global $DB;

        // get the list of all columns in local_plugins_vers table to correctly form SQL GROUP BY
        $vercolumns = 'v.'.join(', v.', array_keys($DB->get_columns('local_plugins_vers')));

        $sql = "SELECT $vercolumns, c.smurfresult, c.timestart, COUNT(r.id) AS reviewcount
                  FROM {local_plugins_vers} v
             LEFT JOIN {local_plugins_review} r ON r.versionid = v.id
             LEFT JOIN {local_plugins_vers_precheck} c ON c.versionid = v.id AND c.status > 0 AND c.timeend IS NOT NULL
                 WHERE v.pluginid = :pluginid
              GROUP BY $vercolumns, c.smurfresult, c.timestart
              ORDER BY v.timecreated, c.timestart DESC";

        $rs = $DB->get_recordset_sql($sql, array('pluginid' => $this->id));
        $versions = array();
        foreach ($rs as $version) {
            if (isset($versions[$version->id])) {
                continue;
            }
            $version->plugin = $this;
            $versions[$version->id] = new local_plugins_version($version);
        }
        $rs->close();
        unset($rs);

        $options = new stdClass;
        $options->context = context_system::instance();
        $options->aggregate = RATING_AGGREGATE_AVERAGE;
        $options->items = $versions;
        $options->scaleid = 5;
        $options->component = 'local_plugins';
        $options->ratingarea = 'plugin_vers';
        $options->plugintype = 'local';
        $options->pluginname = 'plugins';
        $rm = new rating_manager();
        $rm->get_ratings($options);

        $this->versions = $versions;
    }

    /**
     * Returns array of supported Moodle versions
     */
    public function get_moodle_versions() {
        $moodleversions = array();
        foreach ($this->get_versions() as $version) {
            if ($version->is_available()) {
                foreach ($version->moodle_versions as $mversion) {
                    $moodleversions[$mversion->version] = $mversion;
                }
            }
        }
        $moodleversions = array_values($moodleversions);
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
     * @see local_plugins_subscribable
     */
    public function sub_get_type($notificationtype) {
        $subscriptionmap = array(
            local_plugins::NOTIFY_COMMENT => local_plugins_subscription::SUB_PLUGIN_COMMENTS,
            local_plugins::NOTIFY_AVAILABILITY=> null, //@todo
            local_plugins::NOTIFY_REGISTRATION => null, //@todo

        );
        if (array_key_exists($notificationtype, $subscriptionmap)) {
            return $subscriptionmap[$notificationtype];
        } else {
            return local_plugins_subscription::SUB_PLUGINS_UNKNOWN;
        }
    }

    public function sub_toggle($subscription) {
        if ($this->sub_is_subscribed($subscription)) {
            return $this->sub_unsubscribe($subscription);
        } else {
            return $this->sub_subscribe($subscription);
        }
    }

    public function sub_subscribe($subscription) {

        return local_plugins_subscription::subscribe($subscription, $this);
    }

    public function sub_unsubscribe($subscription) {
        return local_plugins_subscription::unsubscribe($subscription);
    }

    public function sub_is_subscribed($subscription) {
        return local_plugins_subscription::is_subscribed($subscription->pluginid, $subscription->type, $subscription->userid);
    }

    /**
     *
     * @global moodle_database $DB
     */
    public function load_reviews() {
        global $DB, $USER;

        $userfields = \core_user\fields::for_userpic()->get_sql('u', false, 'user_', 'user_id', false)->selects;

        $sql = "SELECT r.*, $userfields
        	      FROM {local_plugins_review} r
        	      JOIN {user} u ON u.id = r.userid
        	      JOIN {local_plugins_vers} v ON v.id = r.versionid
        	 	 WHERE v.pluginid = :pluginid
    	 	  ORDER BY r.timereviewed";
        $reviews = $DB->get_records_sql($sql, array('pluginid' => $this->id), 0, 50);
        $this->reviews = array();
        foreach ($reviews as $review) {
            if ($review->status == 0) {
                // Only load own or published reviews.
                if ($USER->id != $review->userid) {
                    if (!has_capability('local/plugins:approvereviews', context_system::instance())) {
                        continue;
                    }
                }
            }
            $review->user = user_picture::unalias($review, null, 'user_id', 'user_');
            $review->plugin = $this;
            $this->reviews[$review->id] = new local_plugins_review($review);
        }

        return $this->reviews;
    }

    public function get_contributors($force = false) {
        global $DB;
        if ($this->contributors === null || $force) {
            $this->contributors = local_plugins_helper::get_plugin_contributors($this->id);
        }
        return $this->contributors;
    }

    public function get_maintained_by($asarray = false) {
        $contributors = $this->get_contributors();
        $maintainers = array();
        foreach ($contributors as $contributor) {
            if ($contributor->is_maintainer()) { //TODO replace with is_lead_maintainer if needed
                if ($asarray) {
                    $maintainers[] = $contributor;
                } else {
                    $maintainers[] = $contributor->username;
                }
            }
        }
        if ($asarray) {
            return $maintainers;
        }
        return join(', ', $maintainers);
    }

    public function add_contributor($properties) {
        global $DB;
        $properties = (array)$properties;

        $this->get_contributors();

        if (array_key_exists('maintainer', $properties) && $properties['maintainer'] == local_plugins_contributor::LEAD_MAINTAINER) {
            $this->remove_lead_maintainer();
        }

        $contributor = new stdClass;
        $contributor->pluginid = $this->id;
        $contributor->timecreated = time();
        foreach (array('userid','maintainer','type') as $key) {
            if (array_key_exists($key, $properties)) {
                $contributor->$key = $properties[$key];
            }
        }

        $contributorid = $DB->insert_record('local_plugins_contributor', $contributor, true);
        $contributor = local_plugins_helper::get_contributor($contributorid);
        $this->contributors[$contributor->userid] = $contributor;
        $this->update_modified($contributor->timecreated);
        return $contributor;
    }

    /**
     * Plugin is allowed to have only one lead maintainer. If some user is assigned a 'lead maintainer',
     * the previous lead maintainer should become the maintainer
     */
    public function remove_lead_maintainer() {
        $contributors = $this->get_contributors();
        foreach ($contributors as $contributor) {
            if ($contributor->is_lead_maintainer()) {
                $contributor->update(array('maintainer' => local_plugins_contributor::MAINTAINER));
            }
        }
    }

    /**
     *
     * @global moodle_database $DB
     * @param array $properties
     * @return local_plugins_version
     */
    public function add_version(array $properties) {
        global $DB, $USER;
        $context = context_system::instance();

        $fields = array('version', 'releasename', 'maturity', 'releasenotes',
            'releasenotesformat', 'changelogurl', 'altdownloadurl', 'vcssystem', 'vcssystemother',
            'vcsrepositoryurl', 'vcsbranch', 'vcstag');

        $version = new stdClass;
        $version->pluginid = $this->id;
        $version->userid = $USER->id;
        $version->timecreated = time();
        if ($version->timecreated < $this->timecreated + 2) {
            // this version is created together with plugin and should have the same creation date
            $version->timecreated = $this->timecreated;
        }
        $version->timelastmodified = $version->timecreated;
        $version->visible = true;
        if (has_any_capability(array(local_plugins::CAP_AUTOAPPROVEPLUGINVERSIONS, local_plugins::CAP_APPROVEPLUGINVERSION), $context)) {
            $version->approved = self::PLUGIN_APPROVED;
        } else {
            $version->approved = self::PLUGIN_PENDINGAPPROVAL;
        }
        foreach ($fields as $field) {
            if (array_key_exists($field, $properties)) {
                $version->$field = $properties[$field];
            } else {
                $version->$field = null;
            }
        }

        $version->id = $DB->insert_record('local_plugins_vers', $version, true);
        $this->versions = null;
        $this->load_versions();
        $this->update_modified($version->timecreated);
        return $this->versions[$version->id];
    }

    public function delete_version(local_plugins_version $version) {
        if ($version->pluginid != $this->id) {
            return;
        }
        $version->delete();
        $this->load_versions();
        $this->update_modified();
    }

    /**
     * Update the plugin properties and save them to the database (touches timelastmodified).
     *
     * @global moodle_database $DB
     * @param type $properties
     */
    public function update($properties) {
        global $DB;

        $properties = (array)$properties;
        $changes = false;

        // List of properties that can be updated via this method.
        $fields = ['categoryid', 'name', 'frankenstyle', 'type', 'shortdescription', 'description', 'descriptionformat',
            'websiteurl', 'sourcecontrolurl', 'faqs', 'faqsformat', 'documentation', 'documentationformat', 'documentationurl',
            'bugtrackerurl', 'discussionurl', 'trackingwidgets', 'statusamos',
        ];

        $plugin = new stdClass;
        $plugin->id = $this->id;
        $plugin->timelastmodified = time();

        if (!empty($properties['frankenstyle'])) {
            list($ptype, $pname) = core_component::normalize_component($properties['frankenstyle']);
            $properties['type'] = $ptype;
        }

        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] != $this->{$field}) {
                $plugin->{$field} = $properties[$field];
                $changes = true;
            }
        }
        if ($changes) {
            $DB->update_record('local_plugins_plugin', $plugin);
            foreach ($plugin as $property => $value) {
                $this->$property = $value;
            }
            $this->update_modified($plugin->timelastmodified);
        }
        return true;
    }

    /**
     * updates the field timelastmodified with $time (or current time),
     * also checks if timelastreleased needs to be updated
     */
    public function update_modified($time = null) {
        global $DB;
        if ($time === null) {
            $time = time();
        }
        $timelastreleased = $this->calculate_timelastreleased();
        $plugin = new stdClass();
        $changes = false;
        if ($this->timelastmodified != $time) {
            $plugin->timelastmodified = $time;
            $changes = true;
        }
        if ($this->timelastreleased != $timelastreleased) {
            $plugin->timelastreleased = $timelastreleased;
            $changes = true;
        }
        if (!$changes) {
            return false;
        }
        $plugin->id = $this->id;
        $DB->update_record('local_plugins_plugin', $plugin);
        foreach ($plugin as $property => $value) {
            $this->$property = $value;
        }
        return true;
    }

    /**
     * Deletes a plugin completely from database.
     * In the most cases the contributors can not delete plugins even when they created them.
     * But if error occurs during plugin creation, this function is called without checking
     * current user's permission to delete a plugin.
     *
     * @param boolean $checkpermission
     */
    public function delete($checkpermission = true) {
        global $DB;
        if ($checkpermission && !$this->can_delete()) {
            throw new local_plugins_exception('exc_permissiondenied');
        }

        $versions = $this->get_versions();
        foreach ($versions as $version) {
            local_plugins_log::remember_state($version);
            $version->delete(false);
            local_plugins_log::log_deleted($version);
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_plugins_plugin_awards', array('pluginid' => $this->id));
        $DB->delete_records('local_plugins_set_plugin', array('pluginid' => $this->id));
        $DB->delete_records('local_plugins_contributor', array('pluginid' => $this->id));
        $DB->delete_records('local_plugins_plugin', array('id' => $this->id));
        $transaction->allow_commit();
        $transaction = null;

        //delete files
        $fs = get_file_storage();
        $context = context_system::instance();
        $fs->delete_area_files($context->id, 'local_plugins', local_plugins::FILEAREA_PLUGINSCREENSHOTS, $this->id);
        $fs->delete_area_files($context->id, 'local_plugins', local_plugins::FILEAREA_PLUGINDESCRIPTION, $this->id);
        $fs->delete_area_files($context->id, 'local_plugins', local_plugins::FILEAREA_PLUGINDOCS, $this->id);
        $fs->delete_area_files($context->id, 'local_plugins', local_plugins::FILEAREA_PLUGINFAQS, $this->id);
        $fs->delete_area_files($context->id, 'local_plugins', local_plugins::FILEAREA_PLUGINLOGO, $this->id);

        //TODO delete comments
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
        $DB->set_field('local_plugins_plugin', 'visible', ($visibility)?1:0, array('id' => $this->id));
        $this->visible = $visibility;
        $this->update_modified();
        return true;
    }

    /**
     * Sets the tracker issue where the plugin approval process is/was tracked.
     *
     * @param string $issue
     */
    public function set_approval_issue($issue) {
        global $DB;

        if ($this->approvalissue !== null) {
            debugging('Attempting to overwrite already set approval issue');
            return;
        }

        $cleaned = clean_param($issue, PARAM_ALPHANUMEXT);

        if ($cleaned !== $issue) {
            throw new coding_exception('Attempting to assign invalid issue key');
        }

        $DB->set_field('local_plugins_plugin', 'approvalissue', $cleaned, ['id' => $this->id]);
        $this->approvalissue = $issue;

        // This change should not raise this update_modified.

        return true;
    }

    /**
     * If frankenstyle exists, adds ('plugin' => frankenstyle) to params,
     * otherwise adds ('id' => id)
     */
    public function link_params($params = null) {
        if (empty($params)) {
            $params = array();
        }
        if (!empty($this->frankenstyle)) {
            return array('plugin' => $this->frankenstyle) + $params;
        } else {
            return array('id' => $this->id) + $params;
        }
    }

    /**
     * @return moodle_url|string URL to redirect user to to install this plugin, or empty string
     */
    public function get_install_link($usersite = null) {
        global $USER;

        $cattype = $this->get_category()->plugintype;
        if ($cattype === '' or $cattype === '-') {
            // Do not allow auto-installation from such category (such as Other).
            return '';
        }
        if (is_null($usersite)) {
            $usersites = local_plugins_helper::get_usersites($USER->id);
            $usersites_installable_versions = $this->is_installable_on_usersites($usersites);
            if (!empty($usersites_installable_versions)) {
                // Always ask user. This is a security and feedback measure to inform user where we are redirecting them to.
                return new local_plugins_url('/local/plugins/report/index.php', array('report' => 'user_sites', 'pluginid' => $this->id));
            } else {
                return '';
            }
        }
        return $this->get_install_link_usersite($usersite);
    }

    public function get_install_link_usersite($usersite, $version=null) {
        $installaddonrequest = array(
            'name' => $this->name,                    // human readable plugin name, e.g. 'Stamp collection'
            'component' => $this->frankenstyle,          // frankenstyle component name, e.g. 'mod_stampcoll'
//            'version' => $this->versions->version,       // plugin version in form 2YYYMMDDXX, e.g. 2013032800
        );

        // if theres one site only then just link to that site with the parameters directly.
        if (is_null($version)) {
            $version = $this->get_mostrecentversion_for_usersite($usersite);
        }
        if(!is_null($version)) {
            $installaddonrequest['version'] = $version->version;
            if ($this->can_approve() and $this->approved != self::PLUGIN_APPROVED) { //extra info for approvers to download directly for reviewing.
                $installaddonrequest['downloadurl'] = $version->downloadlink->out().'/'.$version->md5sum;
                $installaddonrequest['downloadmd5'] = $version->md5sum;
            }
            $installaddonrequest = base64_encode(json_encode($installaddonrequest));
            $url = new moodle_url($usersite->siteurl . local_plugins_helper::PLUGIN_EXTLANDING, array('installaddonrequest' => $installaddonrequest) );
            return $url->out();
        }
        return '';
    }

    public function get_viewlink($moodleversionid = null) {
        $params = $this->link_params();
        if ($moodleversionid) {
            $params['moodle_version'] = $moodleversionid;
        }
        return new local_plugins_url('/local/plugins/view.php', $params);
    }
    public function get_subscriptionlink($notifytype) {
        return new local_plugins_url('/local/plugins/subscribe.php', array('type' => $notifytype, 'pluginid' => $this->id));
    }
    protected function get_viewlink_log() {
        return new local_plugins_url('/local/plugins/view.php', array('id' => $this->id)); // link that always works even when frankenstyle is changed
    }
    protected function get_baselink() {
        return new local_plugins_url('/local/plugins/view.php', $this->link_params(array('sesskey' => sesskey())));
    }
    protected function get_viewversionslink() {
        return new local_plugins_url('/local/plugins/pluginversions.php', $this->link_params());
    }
    protected function get_viewvalidationlink() {
        return new local_plugins_url('/local/plugins/pluginversions.php', $this->link_params(array('validation' => 1)));
    }
    protected function get_viewfaqslink() {
        return null; //TODO
        //return new local_plugins_url('/local/plugins/faqs.php', $this->link_params());
    }
    protected function get_viewreviewslink() {
        return new local_plugins_url('/local/plugins/reviews.php', $this->link_params());
    }
    protected function get_viewstatslink() {
        return new local_plugins_url('/local/plugins/stats.php', $this->link_params());
    }
    protected function get_devzonelink() {
        return new local_plugins_url('/local/plugins/devzone.php', $this->link_params());
    }
    protected function get_translationslink() {
        return new local_plugins_url('/local/plugins/translations.php', $this->link_params());
    }
    protected function get_editlink() {
        return new local_plugins_url('/local/plugins/edit.php', $this->link_params());
    }
    protected function get_deletelink() {
        return new local_plugins_url('/local/plugins/edit.php', $this->link_params(array('delete' => 1)));
    }
    protected function get_confirmdeletelink() {
        return new local_plugins_url('/local/plugins/edit.php', $this->link_params(array('delete' => 1, 'sesskey' => sesskey(), 'confirm' => 1)));
    }
    protected function get_pluginloglink() {
        return new local_plugins_url('/local/plugins/viewlog.php', $this->link_params(array('pluginid' => $this->id)));
    }
    protected function get_editfaqslink() {
        if ($this->viewfaqslink === null) {
            return null;
        }
        return new local_plugins_url('/local/plugins/faqs.php', $this->link_params(array('edit' => 1, 'sesskey' => sesskey())));
    }
    protected function get_addversionlink() {
        return new local_plugins_url('/local/plugins/addversion.php', array('id' => $this->id));
    }
    protected function get_hidelink() {
        global $PAGE;
        return new local_plugins_url('/local/plugins/edit.php', array('id' => $this->id, 'visible' => 0, 'sesskey' => sesskey(), 'redirect' => $PAGE->url));
    }
    protected function get_showlink() {
        global $PAGE;
        return new local_plugins_url('/local/plugins/edit.php', array('id' => $this->id, 'visible' => 1, 'sesskey' => sesskey(), 'redirect' => $PAGE->url));
    }

    /**
     * @return local_plugins_url to a page for approving the plugin
     */
    protected function get_approvelink() {
        return new local_plugins_url('/local/plugins/pluginapproval.php', $this->link_params(array('status' => local_plugins_plugin::PLUGIN_APPROVED)));
    }

    /**
     * @return local_plugins_url to a page for marking the plugin as needed more work
     */
    protected function get_disapprovelink() {
        return new local_plugins_url('/local/plugins/pluginapproval.php', $this->link_params(array('status' => local_plugins_plugin::PLUGIN_UNAPPROVED)));
    }

    /**
     * @return local_plugins_url to a page for scheduling the plugin for re-approval
     */
    protected function get_scheduleapprovallink() {
        return new local_plugins_url('/local/plugins/pluginapproval.php', $this->link_params(array('status' => local_plugins_plugin::PLUGIN_PENDINGAPPROVAL)));
    }

    protected function get_addcontributorlink() {
        return new local_plugins_url('/local/plugins/contributor.php', array('pluginid' => $this->id));
    }
    protected function get_formatted_description() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_PLUGINDESCRIPTION;
        $fileoptions = local_plugins_helper::editor_options_plugin_description();
        $description = file_rewrite_pluginfile_urls($this->description, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => false,
            'trusted' => false,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->descriptionformat, $formatoptions);
    }

    protected function get_formatted_shortdescription() {
        return format_string($this->shortdescription, true, array('context' => context_system::instance()));
    }

    protected function get_formatted_name() {
        return format_string($this->name, true, array('context' => context_system::instance()));
    }

    protected function get_formatted_trackingwidgets() {
        return format_string($this->trackingwidgets, true, array('context' => context_system::instance()));
    }

    protected function get_formatted_docs() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_PLUGINDOCS;
        $fileoptions = local_plugins_helper::editor_options_plugin_docs();
        $documentation = file_rewrite_pluginfile_urls($this->documentation, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => false,
            'trusted' => false,
            'filter' => true,
            'context' => $context
        );
        return format_text($documentation, $this->documentationformat, $formatoptions);
    }

    protected function get_formatted_faqs() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_PLUGINFAQS;
        $fileoptions = local_plugins_helper::editor_options_plugin_faqs();
        $documentation = file_rewrite_pluginfile_urls($this->faqs, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => false,
            'trusted' => false,
            'filter' => true,
            'context' => $context
        );
        return format_text($documentation, $this->faqsformat, $formatoptions);
    }

    protected function get_formatted_timecreated() {
        return userdate($this->timecreated);
    }

    protected function get_formatted_timelastmodified() {
        return userdate($this->timelastmodified);
    }

    protected function get_formatted_timelastreleased() {
        return userdate($this->timelastreleased);
    }

    /**
     * @return array of objects with properties src and file
     */
    protected function get_screenshots() {
        $fs = get_file_storage();
        $files = $fs->get_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_PLUGINSCREENSHOTS, $this->id,
            'sortorder DESC, filename ASC', false);
        $screenshots = array();
        foreach ($files as $file) {
            $screenshots[] = (object)array(
                'src' => local_plugins_url::make_pluginfile_url(SYSCONTEXTID, 'local_plugins',
                    local_plugins::FILEAREA_PLUGINSCREENSHOTS, $this->id, $file->get_filepath(), $file->get_filename()),
                'file' => $file,
            );
        }

        return $screenshots;
    }

    /**
     * @return moodle_url
     */
    protected function get_logo() {
        $own = $this->get_ownlogo();
        if (is_null($own)) {
            return $this->get_category()->defaultlogo;
        }
        return $own;
    }

    /**
     * @return moodle_url|null
     */
    protected function get_ownlogo() {
        $fs = get_file_storage();
        $files = $fs->get_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_PLUGINLOGO, $this->id);
        foreach ($files as $file) {
            if (strpos($file->get_filename(), '.') !== 0) {
                return local_plugins_url::make_pluginfile_url(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_PLUGINLOGO, $this->id, $file->get_filepath(), $file->get_filename());
            }
        }
        return null;
    }

    /**
     * Returns list of all plugin versions for the specified Moodle version
     * sorted by version build number desc
     *
     * @param int|local_plugins_softwareversion $mversion
     */
    protected function get_versions_for_moodle($mversionid) {
        if ($mversionid instanceof local_plugins_softwareversion) {
            $mversionid = $mversionid->id;
        }
        $versions = array();
        foreach ($this->get_versions() as $version) {
            foreach ($version->get_moodle_versions() as $mversion) {
                if ($mversion->id == $mversionid) {
                    $versions[$version->id] = $version;
                }
            }
        }
        uasort($versions, 'local_plugins_sort_by_version');
        return array_reverse($versions, true);
    }

    /**
     * Analyzes all versions for this plugin and mark them the following way:
     * - sets the latest available version for each Moodle version as 'Latest available'
     * - if there are unavailable versions that are more recent than any of 'Latest available', mark them as 'Latest unavailable'
     * - mark all other versions as 'Other versions'
     *
     * Sort versions descending by build number in each category
     *
     * @param string $key one of ('latestunavailable','latestavailable','other') - which versions to return
     * @return array list of versions
     */
    protected function get_classified_versions($returnkey) {
        if ($this->classifiedversions === null) {
            $moodleversions = $this->get_moodle_versions();
            $this->classifiedversions = array('latestunavailable' => array(), 'latestavailable' => array(), 'other' => array());
            foreach ($moodleversions as $moodleversion) {
                $versionslist = $this->get_versions_for_moodle($moodleversion->id);
                foreach ($versionslist as $id => $version) {
                    if ($version->is_available()) {
                        $this->classifiedversions['latestavailable'][$version->id] = $version;
                        break;
                    } else {
                        $this->classifiedversions['latestunavailable'][$version->id] = $version;
                    }
                }
            }
            $this->classifiedversions['other'] = array_diff_key(array_diff_key($this->get_versions(), $this->classifiedversions['latestavailable']), $this->classifiedversions['latestunavailable']);
            foreach (array_keys($this->classifiedversions) as $key) {
                uasort($this->classifiedversions[$key], 'local_plugins_sort_by_version');
                $this->classifiedversions[$key] = array_reverse($this->classifiedversions[$key], true);
            }
        }
        return $this->classifiedversions[$returnkey];
    }

    /**
     * Returns the list of available latest versions to be shown in section 'Current versions'
     *
     * @return array
     */
    protected function get_latestversions() {
        return $this->get_classified_versions('latestavailable');
    }

    /**
     * Returns the list of unavailable latest versions to be shown in section 'Current unavailable versions' (visible only to people with proper privileges)
     *
     * @return array
     */
    protected function get_unavailablelatestversions() {
        return $this->get_classified_versions('latestunavailable');
    }

    /**
     * Returns the latest available version for the Moodle version specified (or stored in user preference)
     * If no available version exist for this moodle version, returns null
     *
     * @param $moodleversionid
     * @return local_plugins_version|null
     */
    public function get_mostrecentversion($usermoodleversionid = null) {
        $latestavailable = $this->get_classified_versions('latestavailable');
        if ($usermoodleversionid === null) {
            $usermoodleversionid = local_plugins_helper::get_user_moodle_version();
        }
        foreach ($latestavailable as $version) {
            foreach ($version->get_moodle_versions() as $moodleversion) {
                if ($moodleversion->id == $usermoodleversionid) {
                    return $version;
                }
            }
        }
        return null;
    }

    /**
     * Returns the latest available version for the Moodle version specified matching the usersite's version.
     * If no available version exist for this moodle version, returns null
     *
     * @param $usersite
     * @return local_plugins_version|null
     */
    public function get_mostrecentversion_for_usersite(local_plugins_usersite $usersite) {
        $mversion = local_plugins_helper::get_usersite_moodle_version($usersite);
        if ($mversion) {
            return $this->get_mostrecentversion($mversion->id);
        }
        return null;
    }

    /**
     * Returns the latest available plugin's versions (and corresponding usersite) for the Moodle version specified matching the any usersite's version.
     * If no available version exist for this plugin, returns null
     *
     * @param $usersites
     * @return array local_plugins_version[]
     */
    public function is_installable_on_usersites($usersites) {
        $usersites_installable_versions = array();
        foreach ($usersites as $usersite) {
            $version = $this->get_mostrecentversion_for_usersite($usersite);
            if (!is_null($version)) {
                $usersites_installable_versions[] = array('usersite' => $usersite, 'version' => $version);
            }
        }
        return $usersites_installable_versions;
    }
    /**
     * Returns the list of versions to be shown in section 'Previous versions'. This list may include
     * versions that are unavailable or disapproved and therefore not visible to the current user.
     * In this case renderer shows only names of such versions
     *
     * @return array
     */
    protected function get_previousversions() {
        return $this->get_classified_versions('other');
    }

    protected function get_searchinfo() {
        return $this->searchinfo;
    }
    /**
     * Returns the latest value entered in the VCS field for plugin versions. This value is used to pre-populate "Add version" form
     *
     * @return string
     */
    public function get_latestvcssystem($defaultvalue = 'none') {
        $versions = array_reverse($this->get_versions());
        foreach ($versions as $version) {
            if (!empty($version->vcssystem) && $version->vcssystem != 'none') {
                return $version->vcssystem;
            }
        }
        return $defaultvalue;
    }

    public function get_reviews($versionid = null) {
        if (!is_array($this->reviews)) {
            $this->load_reviews();
        }
        if (empty($versionid)) {
            return $this->reviews;
        }
        $reviews = array();
        foreach ($this->reviews as $review) {
            if ($review->versionid == $versionid) {
                $reviews[$review->id] = $review;
            }
        }
        return $reviews;
    }

    public function create_storage_directory() {
        global $CFG;
        $pluginpath = $CFG->dataroot.'/local_plugins/'.$this->id;
        if (!file_exists($pluginpath)) {
            mkdir($pluginpath, $CFG->directorypermissions, true);
        }
        return $pluginpath;
    }

    public function get_sets() {
        global $DB;

        if (!is_array($this->sets)) {
            $this->sets = array();
            $sets = local_plugins_helper::get_sets();
            $pluginsets = $DB->get_records('local_plugins_set_plugin', array('pluginid' => $this->id));
            foreach ($pluginsets as $set) {
                $this->sets[$set->setid] = $sets[$set->setid];
            }
        }
        return $this->sets;
    }

    public function get_awards() {
        global $DB;

        if (!is_array($this->awards)) {
            $this->awards = array();
            $awards = local_plugins_helper::get_awards();
            $pluginawards = $DB->get_records('local_plugins_plugin_awards', array('pluginid' => $this->id));
            foreach ($pluginawards as $award) {
                $this->awards[$award->awardid] = $awards[$award->awardid];
            }
        }
        return $this->awards;
    }

    protected function set_searchinfo($info) {
        return $this->searchinfo=$info;
    }
    public function add_to_set($setid) {
        if (array_key_exists($setid, $this->get_sets())) {
            return false;
        }
        $set = local_plugins_helper::get_set($setid);
        if ($set->add_plugin($this)) {
            $this->sets[$set->id] = $set;
            return true;
        } else {
            return false;
        }
    }

    public function remove_from_set($setid) {
        if (!array_key_exists($setid, $this->get_sets())) {
            return false;
        }
        $set = local_plugins_helper::get_set($setid);
        if ($set->remove_plugin($this)) {
            unset($this->sets[$set->id]);
            return true;
        } else {
            return false;
        }
    }

    public function add_award($awardid) {
        if (array_key_exists($awardid, $this->get_awards())) {
            return false;
        }
        $award = local_plugins_helper::get_award($awardid);
        if ($award->add_plugin($this)) {
            $this->awards[$award->id] = $award;
            return true;
        } else {
            return false;
        }
    }

    public function revoke_award($awardid) {
        if (!array_key_exists($awardid, $this->get_awards())) {
            return false;
        }
        $award = local_plugins_helper::get_award($awardid);
        if ($award->remove_plugin($this)) {
            unset($this->awards[$award->id]);
            return true;
        } else {
            return false;
        }
    }

    public function is_available() {
        return ($this->approved == self::PLUGIN_APPROVED && $this->visible);
    }

    public function can_view() {
        $context = context_system::instance();

        return (has_capability(local_plugins::CAP_VIEW, $context) &&
                ($this->is_available() ||
                    (has_capability(local_plugins::CAP_VIEWUNAPPROVED, $context) || $this->user_is_contributor())
                ));
    }

    public function can_view_unapproved() {
        $context = context_system::instance();
        return $this->user_is_contributor() || has_capability(local_plugins::CAP_VIEWUNAPPROVED, $context);
    }

    public function can_view_status() {
        $context = context_system::instance();
        return $this->user_is_contributor() || has_capability(local_plugins::CAP_VIEWUNAPPROVED, $context);
    }

    public function can_edit() {
        $context = context_system::instance();
        return (has_capability(local_plugins::CAP_EDITANYPLUGIN, $context) ||
                ($this->user_is_maintainer() && has_capability(local_plugins::CAP_EDITOWNPLUGINS, $context)));
    }

    public function can_change_visibility() {
        $context = context_system::instance();
        return (has_capability(local_plugins::CAP_EDITANYPLUGIN, $context) && $this->approved == local_plugins_plugin::PLUGIN_APPROVED);
    }

    public function can_delete() {
        $context = context_system::instance();
        return (has_capability(local_plugins::CAP_DELETEANYPLUGIN, $context) ||
                ($this->user_is_maintainer() && has_capability(local_plugins::CAP_DELETEOWNPLUGIN, $context)));
    }

    public function can_viewvalidation() {
        return $this->can_edit() || $this->can_approve();
    }

    public function can_view_plugin_checker_results() {
        return $this->can_edit() || $this->can_approve();
    }

    protected function get_user_contributor() {
        $contributors = $this->get_contributors();
        foreach ($contributors as $contributor) {
            if ($contributor->is_current_user()) {
                return $contributor;
            }
        }
        return false;
    }

    public function user_is_contributor() {
        return $this->get_user_contributor() !== false;
    }

    public function user_is_maintainer() {
        $contributor = $this->get_user_contributor();
        return ($contributor !== false && $contributor->is_maintainer());
    }

    public function get_truncated_shortdescription($length = 72) {
        $shortdescription = strip_tags($this->shortdescription);
        $len = core_text::strlen($shortdescription);
        $shortdescription = trim(core_text::substr($shortdescription, 0, $length));
        if ($len > core_text::strlen($shortdescription)) {
            $shortdescription .= '...';
        }
        return format_string($shortdescription, true, array('context' => context_system::instance()));
    }

    public function get_truncated_description($length = 512) {
        $description = strip_tags($this->description);
        $len = core_text::strlen($description);
        $description = trim(core_text::substr($description, 0, $length));
        if ($len > core_text::strlen($description)) {
            $description .= '...';
        }
        return format_string($description, true, array('context' => context_system::instance()));
    }

    public function can_approve() {
        return has_capability(local_plugins::CAP_APPROVEPLUGIN, context_system::instance());
    }

    public function approve($approve = local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
        global $DB, $USER;
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
        $plugin = new stdClass();
        $plugin->id = $this->id;
        $now = time();
        $plugin->timelastmodified = $now;
        $plugin->timelastapprovedchange = $now;
        $plugin->approved = $approve;
        if ($approve == local_plugins_plugin::PLUGIN_APPROVED and $this->timefirstapproved === null) {
            $plugin->timefirstapproved = $plugin->timelastmodified;
        }
        if ($approve == local_plugins_plugin::PLUGIN_APPROVED or $approve == local_plugins_plugin::PLUGIN_UNAPPROVED) {
            $plugin->approvedby = $USER->id;
        }
        if ($approve != local_plugins_plugin::PLUGIN_APPROVED && !$this->visible) {
            // visibility can only be toggled in APPROVED state. For other states it should be set to true
            $plugin->visible = 1;
        }
        $this->approved = $approve; // so the timelastreleased is calculated correctly
        $plugin->timelastreleased = $this->calculate_timelastreleased();
        $DB->update_record('local_plugins_plugin', $plugin);
        foreach ($plugin as $property => $value) {
            $this->$property = $value;
        }

        // Inform contributors on approval review result.
        if ($approve == local_plugins_plugin::PLUGIN_APPROVED || $approve == local_plugins_plugin::PLUGIN_UNAPPROVED) {
            $a = [
                'pluginname' => $this->name,
                'viewlink' => $this->get_viewlink()->out(),
            ];

            if ($this->approvalissue !== null) {
                $a['viewlink'] = 'https://tracker.moodle.org/browse/' . $this->approvalissue;
            }

            if ($approve == local_plugins_plugin::PLUGIN_APPROVED) {
                $message = get_string('availabilitymessageapproved', 'local_plugins', $a);
            } else {
                $message = get_string('availabilitymessageunapproved', 'local_plugins', $a);
            }

            foreach ($this->get_contributors() as $contributor) {
                $approvalmessage = new core\message\message();
                $approvalmessage->courseid = SITEID;
                $approvalmessage->component = 'local_plugins';
                $approvalmessage->name = 'availability';
                $approvalmessage->userfrom = $USER;
                $approvalmessage->userto = $contributor->user;
                $approvalmessage->subject = get_string('availabilitymessagesubject', 'local_plugins', $this->name);
                $approvalmessage->fullmessage = $message;
                $approvalmessage->fullmessageformat = FORMAT_PLAIN;
                $approvalmessage->fullmessagehtml = '';
                $approvalmessage->smallmessage = '';
                $approvalmessage->notification = 1;
                $approvalmessage->contexturl = $this->get_viewlink();
                $approvalmessage->contexturlname = $this->get_formatted_name();

                message_send($approvalmessage);
            }
        }

        return true;
    }

    /**
     * If user adds a new version but does not specify version and it also can not be found in version.php
     * it is populated with the current date + 00 (01, ...).
     * This function finds the next appropriate version name
     */
    public function get_next_version_name() {
        $versions = $this->get_versions();
        for ($postfix = 0; $postfix < 100; $postfix++) {
            $versionname = self::create_version_name($postfix);
            foreach ($versions as $version) {
                if ($version->version == $versionname) {
                    continue 2;
                }
            }
            return $versionname;
        }
        //not likely that user adds more than 100 versions for the same plugin within one day
        return null;
    }

    /**
     * Generates a 10-digit version name from the current date and specified postfix (00, 01, ...)
     *
     * @param int $postfix
     * @return string
     */
    public static function create_version_name($postfix = 0) {
        return date('Ymd').sprintf("%02d", $postfix);
    }

    public function can_manage_contributors() {
        $contributor = $this->get_user_contributor();
        return (has_capability(local_plugins::CAP_EDITANYPLUGIN, context_system::instance()) ||
                ($contributor !== false && $contributor->is_lead_maintainer()));
    }

    public function can_change_lead_maintainer() {
        return (has_capability(local_plugins::CAP_EDITANYPLUGIN, context_system::instance()));
    }

    public function can_add_contributors() {
        global $CFG;
        if (has_capability(local_plugins::CAP_EDITANYPLUGIN, context_system::instance())) {
            // no contributors number limit for admin
            return true;
        }
        $maxcontributors = 5;
        if (!empty($CFG->local_plugins_maxcontributors)) {
            $maxcontributors = $CFG->local_plugins_maxcontributors;
        }
        return $this->can_manage_contributors() && (count($this->get_contributors()) < $maxcontributors);
    }

    public function get_average_review_grades() {
        $versions = array_merge($this->get_latestversions(), $this->get_previousversions());
        $outcomes = array();
        //for each review criterion get the version average outcome from the last available reviewed version (tricky)
        foreach ($versions as $version) {
            if ($version->is_available()) {
                $versionoutcomes = $version->average_review_grades;
                foreach ($versionoutcomes as $outcome) {
                    if (!array_key_exists($outcome->criteriaid, $outcomes)) {
                        $outcomes[$outcome->criteriaid] = $outcome;
                    }
                }
            }
        }
        $retval = array();
        //sort outcomes in the order of criteria
        foreach (local_plugins_helper::get_review_criteria() as $criterion) {
            if (array_key_exists($criterion->id, $outcomes)) {
                    $retval[] = $outcomes[$criterion->id];
            }
        }
        return $retval;
    }

    /**
     * Calculates and returns the maximum release time of available versions
     * @return int
     */
    protected function calculate_timelastreleased() {
        $versions = $this->get_versions();
        $lastreleased = null;
        if ($this->is_available()) {
            foreach ($versions as $version) {
                if ($version->is_available() && ($lastreleased === null || $version->timecreated > $lastreleased)) {
                    $lastreleased = $version->timecreated;
                }
            }
        }
        return $lastreleased;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_action_prefix() {
        return local_plugins_log::LOG_PREFIX_PLUGIN;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_identifier($forplugin) {
        if ($forplugin) {
            return '';
        } else {
            $title = new stdClass;
            $title->category = $this->get_category()->formatted_name;
            $title->plugin = html_writer::link($this->get_viewlink_log(), $this->get_formatted_name());
            return get_string('plugintitle', 'local_plugins', $title);
        }
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_data() {
        // Prepare human-readable status (do not use get_string as we may rely
        // on exact values in analysis and reporting).
        if ($this->approved == local_plugins_plugin::PLUGIN_UNAPPROVED) {
            $status = 'Needs more work';
        } else if ($this->approved == local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
            $status = 'Waiting for approval';
        } else if (!$this->visible) {
            $status = 'Invisible';
        } else {
            $status = 'Available';
        }
        // prepare list of screenshots
        $screenshots = array();
        foreach ($this->screenshots as $screenshot) {
            $screenshots[] = $screenshot->file->get_filename();
        }
        // prepare logo
        $logo = $this->logo;
        if ($logo == $this->get_category()->defaultlogo) {
            $logo = null;
        }
        if (!empty($logo)) {
            $logo = html_writer::empty_tag('img', array('src'=>$logo, 'class' => 'log-plugin-logo'));
        }
        // prepare human-readable sets list
        $sets = array();
        foreach ($this->get_sets() as $set) {
            $sets[] = html_writer::link($set->browseurl, $set->formatted_name);
        }
        // prepare human-readable awards list
        $awards = array();
        foreach ($this->get_awards() as $award) {
            $awards[] = html_writer::link($award->browseurl, $award->formatted_name);
        }
        // prepare human-readable list of contributors
        $contributors = array();
        foreach ($this->get_contributors() as $contributor) {
            $coutput = html_writer::link(new local_plugins_url('/user/profile.php', array('id' => $contributor->userid)), $contributor->username);
            if ($contributor->is_lead_maintainer()) {
                $coutput .= ' '. get_string('leadmaintainer_postfix', 'local_plugins');
            }
            if (!empty($contributor->formatted_type)) {
                $coutput .= ': '. $contributor->formatted_type;
            }
            $contributors[] = $coutput;
        }

        // Prepare list of descriptors.
        $descriptors = [];
        foreach ($this->get_descriptors() as $descriptorid => $descriptorvalues) {
            $descriptors[] = local_plugins_helper::descriptor_title($descriptorid).': '.join(', ', $descriptorvalues);
        }

        // return the array of plugin attributes
        return array(
            'name' => $this->formatted_name,
            'category' => html_writer::link($this->get_category()->browseurl, $this->get_category()->formatted_name),
            'pluginfrankenstyle' => $this->frankenstyle,
            'pluginshortdescription' => $this->formatted_shortdescription,
            'logo' => $logo,
            'plugindescription' => $this->formatted_description,
            'screenshots' => join(', ', $screenshots),
            'websiteurl' => $this->websiteurl,
            'sourcecontrolurl' => $this->sourcecontrolurl,
            // 'faqs' => $this->faqs,  //TODO add faqs to logging
            // 'documentation' => $this->documentation ,  //TODO add documentation to logging
            // 'discussionurl' => $this->discussionurl ,  //TODO add discussionurl to logging
            'bugtrackerurl' => $this->bugtrackerurl,
            'trackingwidgets' => $this->trackingwidgets,
            'status' => $status,
            'approved' => $this->approved,
            'pluginssets' => join(', ', $sets),
            'pluginsawards' => join(', ', $awards),
            'contributors' => join('<br>', $contributors),
            'descriptors' => join("\n", $descriptors),
        );
    }

    /**
     * Sets the favourite status of the plugin for the given user (defaults to
     * the current one).
     *
     * @param int $status (0 = not marked, 1 = marked)
     * @param int $userid
     */
    public function set_favourite($status, $userid = null) {
        global $USER, $DB;

        $now = time();

        $status = (int)(bool)$status;

        if ($userid === null) {
            $userid = $USER->id;
        }

        if ($current = $DB->get_record('local_plugins_favourite', array('userid' => $userid, 'pluginid' => $this->id), '*', IGNORE_MISSING)) {
            $current->timemodified = $now;
            $current->status = $status;
            $DB->update_record('local_plugins_favourite', $current);

        } else {
            $DB->insert_record('local_plugins_favourite', array(
                'userid' => $userid,
                'pluginid' => $this->id,
                'timecreated' => $now,
                'timemodified' => $now,
                'status' => $status,
            ));
        }

        $this->favourites = null;
        $this->update_aggfavs();
    }

    /**
     * Loads and returns info about users who marked this as their favourite plugin.
     *
     * @return array indexed by userid, unordered
     */
    public function get_favourites() {
        global $DB;

        if ($this->favourites === null) {
            $this->favourites = $DB->get_records(
                'local_plugins_favourite',
                array('pluginid' => $this->id, 'status' => 1),
                '',
                'userid, timecreated, timemodified'
            );
        }

        if ($this->aggfavs === null) {
            // We have not aggregated favourites for this plugin yet, do it now.
            $this->update_aggfavs();
        }

        return $this->favourites;
    }

    /**
     * Update the aggregated count of plugin's favourites.
     *
     * @return int up-to-date count of plugin's favourites
     */
    protected function update_aggfavs() {
        global $DB;

        $count = $DB->count_records('local_plugins_favourite', array('pluginid' => $this->id, 'status' => 1));

        if ($this->aggfavs === null or $this->aggfavs != $count) {
            $DB->set_field('local_plugins_plugin', 'aggfavs', $count, array('id' => $this->id));
        }

        $this->aggfavs = $count;

        return $count;
    }

    /**
     * @return int How many users favourited this plugin
     */
    public function count_favourites() {
        return count($this->get_favourites());
    }

    /**
     * Return details about the plugin's version control system
     *
     * Parses the provided {@link self::sourcecontrolurl} field. Currently
     * supports github repositories only but can be extended in the future,
     * should we need it.
     *
     * @see local_plugins_plugin_vcs_info_testcase
     * @return false|stdClass
     */
    public function get_vcs_info() {

        if (empty($this->sourcecontrolurl)) {
            return false;
        }

        $info = (object)array(
            'type' => 'unknown',
        );

        // Credit goes to moylop260 at http://stackoverflow.com/a/25102190/2215513
        $regex = '#((git@|https?://)(?P<hostname>[\w\.@]+)(/|:))(?P<username>[\w,\-,\_,\.]+)/(?P<reponame>[\w,\-,\_,\.]+)((/)?)#';

        if (substr($this->sourcecontrolurl, -4) === '.git') {
            $sourcecontrolurl = substr($this->sourcecontrolurl, 0, -4);
        } else {
            $sourcecontrolurl = $this->sourcecontrolurl;
        }

        if (preg_match($regex, $sourcecontrolurl, $matches)) {

            if ($matches['hostname'] === 'github.com') {
                $info->type = 'github';
                $info->github_username = $matches['username'];
                $info->github_reponame = $matches['reponame'];
            }
        }

        return $info;
    }

    /**
     * Get the descriptor values associated with the plugin.
     *
     * @return array of (int)descid => array of (str)descvalue
     */
    public function get_descriptors() {
        global $DB;

        if ($this->descriptors === null) {
            $recordset = $DB->get_recordset_list('local_plugins_desc_values', 'pluginid', [$this->id], 'id,value');
            $this->descriptors = [];
            foreach ($recordset as $record) {
                if (!isset($this->descriptors[$record->descid])) {
                    $this->descriptors[$record->descid] = [$record->value];
                } else {
                    $this->descriptors[$record->descid][] = $record->value;
                }
            }
            $recordset->close();
        }

        return array_filter($this->descriptors);
    }

    /**
     * Sets the new values for the given descriptor.
     *
     * @param int $descid
     * @param array $newvalues string[]
     */
    public function set_descriptors($descid, $newvalues) {
        global $DB;

        // Get the current values.
        $currentdescriptors = $this->get_descriptors();

        if (isset($currentdescriptors[$descid])) {
            $currentvalues = $currentdescriptors[$descid];

        } else {
            $currentvalues = [];
        }

        // Unset those not selected any more.
        foreach ($currentvalues as $currentvalue) {
            if (!in_array($currentvalue, $newvalues)) {
                $DB->delete_records('local_plugins_desc_values', ['pluginid' => $this->id, 'descid' => $descid,
                    'value' => $currentvalue]);
                $this->descriptors[$descid] = array_diff($this->descriptors[$descid], [$currentvalue]);
            }
        }

        // Insert all new ones.
        foreach ($newvalues as $newvalue) {
            if (!in_array($newvalue, $currentvalues)) {
                $DB->insert_record('local_plugins_desc_values', ['pluginid' => $this->id, 'descid' => $descid,
                    'value' => $newvalue]);
                if (isset($this->descriptors[$descid])) {
                    $this->descriptors[$descid][] = $newvalue;
                } else {
                    $this->descriptors[$descid] = [$newvalue];
                }
            }
        }
    }
}

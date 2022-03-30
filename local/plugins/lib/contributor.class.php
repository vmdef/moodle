<?php

/**
 * This file contains the contributor class used to manage contributors
 * within the plugin.
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
 * @property-read int $id
 * @property-read int $userid
 * @property-read int $pluginid
 * @property-read local_plugins_plugin $plugin
 * @property-read int $maintainer
 * @property-read string $type
 * @property-read string $formatted_type
 * @property-read int $timecreated
 */
class local_plugins_contributor extends local_plugins_collection_class implements renderable, local_plugins_subscribable {
    const MAINTAINER = 2;
    const LEAD_MAINTAINER = 1;

    protected $id;
    protected $userid;
    protected $pluginid;
    protected $maintainer;
    protected $type;
    protected $timecreated;
    protected $plugin;

    protected $user;

    protected function get_user() {
        global $DB;
        if (empty($this->user)) {
            $this->user = $DB->get_record('user', array('id' => $this->userid), '*', MUST_EXIST);
        }
        return $this->user;
    }

    protected function get_username() {
        $user = $this->get_user();
        return fullname($user);
    }

    protected function get_formatted_type() {
        return format_string($this->type, true, array('context' => context_system::instance()));
    }

    public function is_current_user() {
        global $USER;
        return ($USER->id == $this->userid);
    }

    public function is_maintainer() {
        return ($this->maintainer == local_plugins_contributor::LEAD_MAINTAINER || $this->maintainer == local_plugins_contributor::MAINTAINER);
    }

    public function is_lead_maintainer() {
        return ($this->maintainer == local_plugins_contributor::LEAD_MAINTAINER);
    }

    protected function get_browseurl() {
        return new local_plugins_url('/local/plugins/browse.php', array('list' => 'contributor', 'id' => $this->userid));
    }

    protected function get_editurl() {
        return new local_plugins_url('/local/plugins/contributor.php', array('id' => $this->id, 'pluginid' => $this->pluginid));
    }

    public function get_rssurl() {
        return local_plugins_helper::get_rss_url('contributor_plugins', $this->userid);
    }

    protected function plugins_alter_query(&$sql, &$params, $unapproved = false, $invisible = false, $alwaysshowown = false) {
        parent::plugins_alter_query($sql, $params, $unapproved, $invisible, $alwaysshowown);
        $sql["FROM"] .= " JOIN {local_plugins_contributor} cc ON cc.pluginid = p.id";
        $sql["WHERE"] .= " AND cc.userid = :contributorid";
        $params['contributorid'] = $this->userid;
    }

    protected function get_plugin() {
        if (empty($this->plugin)) {
            if ($this->pluginid) {
                $this->plugin = local_plugins_helper::get_plugin($this->pluginid);
            }
        }
        return $this->plugin;
    }

    /**
     * @see local_plugins_subscribable
     */
    public function get_subscription_type() {
        return local_plugins::NOTIFY_CONTRIBUTOR;
    }

    /**
     * returns true if current user is allowed to see profile of this contributor
     */
    public function can_view_profile() {
        global $CFG;
        if (!empty($CFG->forceloginforprofiles) && (!isloggedin() || isguestuser())) {
            return false;
        }
        $canviewgeneral = has_capability('moodle/user:viewdetails', context_system::instance());
        return ($this->is_current_user() || $canviewgeneral || has_coursecontact_role($this->userid));
    }

    /**
     * returns true if current user is allowed to send a message to this contributor
     */
    public function can_send_message() {
        global $CFG;
        if (empty($CFG->messaging) || !isloggedin() || isguestuser()) {
            return false;
        }
        return true;
    }

    public function update($properties) {
        global $DB;
        $properties = (array)$properties;
        $changes = false;
        $fields = array('type', 'maintainer');

        $contributor = new stdClass;
        $contributor->id = $this->id;
        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] != $this->$field) {
                $contributor->$field = $properties[$field];
                $changes = true;
            }
        }
        if ($changes) {
            if (!$this->is_lead_maintainer() && !empty($contributor->maintainer) && $contributor->maintainer == local_plugins_contributor::LEAD_MAINTAINER) {
                $this->get_plugin()->remove_lead_maintainer();
            }
            $DB->update_record('local_plugins_contributor', $contributor);
            foreach ($contributor as $key => $value) {
                $this->$key = $value;
            }
            $this->get_plugin()->update_modified();
        }
        return $this;
    }

    public function delete() {
        global $DB;
        $DB->delete_records('local_plugins_contributor', array('id' => $this->id));
        $this->get_plugin()->update_modified();
    }

    /**
     * @see local_plugins_subscribable
     */

    public function sub_get_type($notificationtype) {
        return local_plugins_subscription::SUB_PLUGINS_CONTRIBUTOR;
    }

    public function sub_is_subscribed($subscription) {

    }

    public function sub_subscribe($subscription) {

    }

    public function sub_unsubscribe($subscription) {

    }

    public function sub_toggle($subscription) {

    }
}
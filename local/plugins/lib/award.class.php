<?php

/**
 * This file contains the awards classes.
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
 * This class is used to create objects to represent awards
 * within the local_plugins plugin.
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string $description
 * @property-read int $descriptionformat
 * @property-read int $timecreated
 * @property-read bool $onfrontpage
 * 
 * @property-read int $plugincount
 * @property-read moodle_url $icon
 * @property-read string $formatted_name
 * @property-read string $formatted_description
 * @property-read string $formatted_timecreated
 * @property-read moodle_url $browseurl
 * @property-read moodle_url $rssurl
 */
class local_plugins_award extends local_plugins_collection_class implements renderable, local_plugins_loggable, local_plugins_subscribable {

    protected $id;
    protected $name;
    protected $shortname;
    protected $description;
    protected $descriptionformat;
    protected $timecreated;
    protected $onfrontpage;

    protected $plugincount = 0;

    protected function get_formatted_name() {
        $string = local_plugins_translate_string($this->name);
        return format_string($string, true, array('context' => context_system::instance()));
    }

    protected function get_formatted_description() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_AWARDDESCRIPTION;
        $fileoptions = local_plugins_helper::editor_options_award_description();
        $description = file_rewrite_pluginfile_urls($this->description, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => true,
            'trusted' => true,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->descriptionformat, $formatoptions);
    }

    protected function get_truncated_description() {
        $description = strip_tags($this->description);
        return core_text::substr($description, 0, 255);
    }

    protected function get_formatted_timecreated() {
        return userdate($this->timecreated);
    }

    protected function get_icon() {
        $fs = get_file_storage();
        $files = $fs->get_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_AWARDICON, $this->id);
        foreach ($files as $file) {
            if (strpos($file->get_filename(), '.') !== 0) {
                return new moodle_url(local_plugins_url::make_pluginfile_url(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_AWARDICON, $this->id, $file->get_filepath(), $file->get_filename()), array('preview' => 'thumb'));
            }
        }
        return false;
    }

    public function get_onfrontpage() {
        return $this->onfrontpage ? true : false;
    }

    /**
     *
     * @global moodle_database $DB
     * @param object $properties
     */
    public function update($properties) {
        global $DB;
        $properties = (array)$properties;
        $changes = false;
        $fields = array('name', 'shortname', 'description', 'descriptionformat', 'onfrontpage');

        // To make unique index work, convert empty values to nulls.
        if (empty($properties['shortname'])) {
            $properties['shortname'] = null;
        }

        $award = new stdClass;
        $award->id = $this->id;
        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] != $this->$field) {
                $award->$field = $properties[$field];
                $changes = true;
            }
        }
        if ($changes) {
            $DB->update_record('local_plugins_awards', $award);

            foreach ($award as $key => $value) {
                $this->$key = $value;
            }
        }

        return $this;
    }

    public function delete() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        // remove plugins from this award and log changes in affected plugins
        $sql = "SELECT p.* from {local_plugins_plugin} p, {local_plugins_plugin_awards} pa where pa.pluginid = p.id and pa.awardid = :awardid";
        $params = array('awardid' => $this->id);
        $plugins = local_plugins_helper::load_plugins_from_result($DB->get_records_sql($sql, $params));
        local_plugins_log::remember_state($plugins);
        foreach ($plugins as $plugin) {
            $plugin->revoke_award($this->id);
        }
        local_plugins_log::log_edited($plugins);
        // remove the award
        $DB->delete_records('local_plugins_awards', array('id' => $this->id));
        $transaction->allow_commit();
        //MIM TODO delete uploaded files
    }

    /**
     * Function only to be called from local_plugins_plugin::add_award
     */
    public function add_plugin(local_plugins_plugin $plugin) {
        global $DB, $USER;

        $row = new stdClass;
        $row->awardid = $this->id;
        $row->pluginid = $plugin->id;
        $row->versionid = null;
        $row->timeawarded = time();
        $row->userid = $USER->id;
        $row->id = $DB->insert_record('local_plugins_plugin_awards', $row);

        $this->plugincount++;

        return true;
    }

    /**
     * Function only to be called from local_plugins_plugin::revoke_award
     */
    public function remove_plugin(local_plugins_plugin $plugin) {
        global $DB;
        $DB->delete_records_select('local_plugins_plugin_awards', 'pluginid = :pluginid and awardid = :awardid',
                array('pluginid' => $plugin->id, 'awardid' => $this->id));
        $this->plugincount--;
        return true;
    }

    protected function plugins_alter_query(&$sql, &$params, $unapproved = false, $invisible = false, $alwaysshowown = false) {
        parent::plugins_alter_query($sql, $params, $unapproved, $invisible, $alwaysshowown);
        $sql["FROM"] .= " JOIN {local_plugins_plugin_awards} pa ON p.id = pa.pluginid";
        $sql["WHERE"] .= " AND pa.awardid = :awardid";
        $params['awardid'] = $this->id;
    }

    public function get_browseurl() {
        return new local_plugins_url('/local/plugins/browse.php', array('list' => 'award', 'id' => $this->id));
    }

    public function get_rssurl() {
        return local_plugins_helper::get_rss_url('award_plugins', $this->id);
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_action_prefix() {
        return local_plugins_log::LOG_PREFIX_AWARD;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_identifier($forplugin) {
        $link = html_writer::link($this->browseurl, $this->formatted_name);
        return get_string('logidentifieraward', 'local_plugins', $link);
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_data() {
        $icon = $this->get_icon();
        if (!empty($icon)) {
            $icon = html_writer::empty_tag('img', array('src' => $icon, 'class' => 'log-award-logo'));
        }
        return array(
            'name' => $this->formatted_name,
            'shortname' => $this->shortname,
            'description' => $this->formatted_description,
            'onfrontpage' => $this->onfrontpage ? get_string('yes') : get_string('no'),
            'logo' => $icon,
        );
    }

    /**
     * @see local_plugins_subscribable
     */

    public function sub_get_type($notificationtype) {
        return local_plugins_subscription::SUB_PLUGIN_AWARDS;
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
<?php

/**
 * This file contains the set class used to manage sets within the plugin.
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
 * Instances of this class represents the sets of plugins
 * organised within the local_plugins plugin
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string $description
 * @property-read int $descriptionformat
 * @property-read int $maxplugins
 * @property-read bool $onfrontpage
 * 
 * @property-read string $formatted_name
 * @property-read string $formatted_description
 * @property-read moodle_url $browseurl
 * @property-read moodle_url $rssurl
 */
class local_plugins_set extends local_plugins_collection_class implements renderable, local_plugins_loggable {

    protected $id;
    protected $name;
    protected $shortname;
    protected $description;
    protected $descriptionformat;
    protected $maxplugins;
    protected $plugincount;
    protected $onfrontpage;

    protected function get_formatted_name() {
        $string = local_plugins_translate_string($this->name);
        return format_string($string, true, array('context' => context_system::instance()));
    }

    protected function get_formatted_description() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_SETDESCRIPTION;
        $fileoptions = local_plugins_helper::editor_options_set_description();
        $description = file_rewrite_pluginfile_urls($this->description, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => true,
            'trusted' => true,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->descriptionformat, $formatoptions);
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
        $fields = array('name', 'shortname', 'description', 'descriptionformat', 'maxplugins', 'onfrontpage');

        // To make unique index work, convert empty values to nulls.
        if (empty($properties['shortname'])) {
            $properties['shortname'] = null;
        }

        $set = new stdClass;
        $set->id = $this->id;
        if (array_key_exists('name', $properties) && $properties['name'] != $this->name) {
            $sets = local_plugins_helper::get_sets();
            foreach ($sets as $existingset) {
                if ($existingset->name == $properties['name']) {
                    throw new local_plugins_exception('exc_setnameexists');
                }
            }
        }
        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] != $this->$field) {
                $set->$field = $properties[$field];
                $changes = true;
            }
        }
        if ($changes) {
            $DB->update_record('local_plugins_set', $set);

            foreach ($set as $key => $value) {
                $this->$key = $value;
            }
        }
        return $changes;
    }

    public function delete() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // remove plugins from this set and log changes in affected plugins
        $sql = "SELECT p.* from {local_plugins_plugin} p, {local_plugins_set_plugin} ps where ps.pluginid = p.id and ps.setid = :setid";
        $params = array('setid' => $this->id);
        $plugins = local_plugins_helper::load_plugins_from_result($DB->get_records_sql($sql, $params));
        local_plugins_log::remember_state($plugins);
        foreach ($plugins as $plugin) {
            $plugin->remove_from_set($this->id);
        }
        local_plugins_log::log_edited($plugins);
        // remove the set
        $DB->delete_records('local_plugins_set', array('id' => $this->id));
        $transaction->allow_commit();
        //MIM TODO delete uploaded files
    }

    /**
     * Function only to be called from local_plugins_plugin::add_to_set
     */
    public function add_plugin(local_plugins_plugin $plugin) {
        global $DB, $USER;

        if ($this->plugincount >= $this->maxplugins) {
            return false;
        }

        $row = new stdClass;
        $row->setid = $this->id;
        $row->pluginid = $plugin->id;
        $row->userid = $USER->id;
        $row->timeadded = time();
        $row->id = $DB->insert_record('local_plugins_set_plugin', $row);

        $this->plugincount++;

        return true;
    }

    /**
     * Function only to be called from local_plugins_plugin::remove_from_set
     */
    public function remove_plugin(local_plugins_plugin $plugin) {
        global $DB;
        $DB->delete_records_select('local_plugins_set_plugin', 'pluginid = :pluginid and setid = :setid',
                array('pluginid' => $plugin->id, 'setid' => $this->id));
        $this->plugincount--;
        return true;
    }

    protected function plugins_alter_query(&$sql, &$params, $unapproved = false, $invisible = false, $alwaysshowown = false) {
        parent::plugins_alter_query($sql, $params, $unapproved, $invisible, $alwaysshowown);
        $sql["FROM"] .= " JOIN {local_plugins_set_plugin} sp ON p.id = sp.pluginid";
        $sql["WHERE"] .= " AND sp.setid = :setid";
        $params['setid'] = $this->id;
    }

    public function get_browseurl() {
        return new local_plugins_url('/local/plugins/browse.php', array('list' => 'set', 'id' => $this->id));
    }

    public function get_rssurl() {
        return local_plugins_helper::get_rss_url('set_plugins', $this->id);
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_action_prefix() {
        return local_plugins_log::LOG_PREFIX_SET;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_identifier($forplugin) {
        $link = html_writer::link($this->browseurl, $this->formatted_name);
        return get_string('logidentifierset', 'local_plugins', $link);
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_data() {
        return array(
            'name' => $this->formatted_name,
            'shortname' => $this->shortname,
            'description' => $this->formatted_description,
            'onfrontpage' => $this->onfrontpage ? get_string('yes') : get_string('no'),
            'maxplugins' => $this->maxplugins,
        );
    }
}
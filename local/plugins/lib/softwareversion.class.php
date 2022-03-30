<?php

/**
 * This file contains the software version classes.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Marina Glancy
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used to create objects to represent software version
 * within the local_plugins plugin.
 *
 * @property-read int $id
 * @property-read string $name
 * @property-read string $version
 * @property-read string $releasename
 * @property-read int $timecreated
 *
 * @property-read string $fullname
 * @property-read string $fullname_version
 * @property-read string $formatted_timecreated
 */
class local_plugins_softwareversion extends local_plugins_class_base implements local_plugins_loggable {

    protected $id;
    protected $name;
    protected $version;
    protected $releasename;
    protected $timecreated;

    protected function get_fullname() {
        return format_string($this->name, true, array('context' => context_system::instance())). ' '.
            format_string($this->releasename, true, array('context' => context_system::instance()));
    }

    protected function get_fullname_version() {
        $rv = $this->get_fullname();
        if (!empty($this->version) && $this->version != $this->releasename) {
            $rv .= ' ('. format_string($this->version, true, array('context' => context_system::instance())). ')';
        }
        return $rv;
    }

    protected function get_formatted_timecreated() {
        return userdate($this->timecreated);
    }

    /**
     *
     * @global moodle_database $DB
     * @param $properties
     */
    public function update($properties) {
        global $DB;
        $properties = (array)$properties;
        $changes = false;
        $fields = array('name', 'version', 'releasename');

        $softwareversion = new stdClass;
        $softwareversion->id = $this->id;
        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] != $this->$field) {
                $softwareversion->$field = $properties[$field];
                $changes = true;
            }
        }
        if ($changes) {
            $DB->update_record('local_plugins_software_vers', $softwareversion);
            foreach ($softwareversion as $key => $value) {
                $this->$key = $value;
            }
        }
        local_plugins_helper::get_software_versions(true);
    }

    public function delete() {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records('local_plugins_supported_vers', array('softwareversionid' => $this->id));
        $DB->delete_records('local_plugins_software_vers', array('id' => $this->id));
        $transaction->allow_commit();
        local_plugins_helper::get_software_versions(true);
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_action_prefix() {
        return local_plugins_log::LOG_PREFIX_SOFTVERS;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_identifier($forplugin) {
        return get_string('logidentifiersoftwarevers', 'local_plugins', $this->fullname);
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_data() {
        return array(
            'name' => $this->name,
            'version' => $this->version,
            'releasename' => $this->releasename,
        );
    }
}
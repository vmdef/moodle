<?php

/**
 * This file contains the log class and loggable interface. They provide
 * functionality to log the actions that required db changes.
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
 * Interface for objects that are logged.
 * Not all objects in DB are logged. For example, plugin is a loggable object and
 * among its fields it also logs the list of its contributors. And contributor object
 * is not loggable. List of loggable objects can be found in constants in class
 * local_plugins_log.
 */
interface local_plugins_loggable
{
    /**
     * Prefix of the log action. It is advised to use here constants from
     * local_plugins_log.
     *
     * @return string
     */
    public function log_action_prefix();
    /**
     * Human-readable description of what is altered (for displaying the log).
     * In case $forplugin = true this is a short identifier without plugin info
     * (to be displayed on plugin log page).
     * Argument not used for objects that are not plugin-related
     *
     * @param boolean $forplugin
     * @return string
     */
    public function log_identifier($forplugin);
    /**
     * Creates an array of plugin properties with human-readable values. Keys of the array
     * are the names of strings from lang/en/local_plugins.php
     * The result of function before and after the change is stored in local_plugins_log table
     * and is easy to display (usually only changed fields are displayed).
     *
     * @return array
     */
    public function log_data();
}

/**
 * Class local_plugins_log represents one entry in log table. It also provides
 * number of static methods to add and retrieve log entries.
 *
 * Log action name constists of three dash-separated chunks:
 * [plugin]-TABLENAME-SUFFIX
 * If first chunk is "plugin", this means that field pluginid is populated
 * and this is a change related to particular plugin. First chunk may be empty.
 * Second chunk is the name of the altered table (without local_plugins_ prefix),
 * it may be plugin, vers, ... Field tableid maps to the id field in this table.
 * Trird chunk is the action - add, edit or delete
 *
 * @property-read id
 * @property-read bulkid
 * @property-read time
 * @property-read timeday
 * @property-read userid
 * @property-read ip
 * @property-read action
 * @property-read pluginid
 * @property-read tableid
 * @property-read info
 */
class local_plugins_log extends local_plugins_class_base {
    // Actions
    const LOG_PREFIX_PLUGIN          = 'plugin-plugin';
    const LOG_PREFIX_PLUGIN_VERS     = 'plugin-vers';
    const LOG_PREFIX_PLUGIN_REVIEW   = 'plugin-review';
    const LOG_PREFIX_CATEGORY        = '-category';
    const LOG_PREFIX_AWARD           = '-awards';
    const LOG_PREFIX_SET             = '-set';
    const LOG_PREFIX_REVIEWCRITERION = '-review_test';
    const LOG_PREFIX_SOFTVERS        = '-software_vers';

    const LOG_SUFFIX_ADDED           = '-add';
    const LOG_SUFFIX_EDITED          = '-edit';
    const LOG_SUFFIX_DELETED         = '-delete';

    // Database properties
    protected $id;
    protected $bulkid;
    protected $time;
    protected $timeday;
    protected $userid;
    protected $ip;
    protected $action;
    protected $pluginid;
    protected $tableid;
    protected $info;

    private $user;

    public function  __construct($properties) {
        parent::__construct($properties);
        if (!empty($this->info)) {
            $this->info = unserialize($this->info);
        }
    }

    static $logbulkid = null;
    static $defaulttime = null;
    static $defaultuser = null;
    public static function start_new_bulk($defaulttime = null, $defaultuser = null) {
        global $DB;
        self::$logbulkid = ((int)$DB->get_field_sql('SELECT max(bulkid) from {local_plugins_log}')) + 1;
        self::$defaulttime = $defaulttime;
        self::$defaultuser = $defaultuser;
    }

    /**
     * Inserts a log record into database
     *
     * @return local_plugins_log
     */
    protected static function log_raw($properties) {
        global $USER, $DB;
        if (self::$logbulkid === null) {
            self::start_new_bulk();
        }
        $properties = (array)$properties;
        if (!array_key_exists('bulkid', $properties)) {
            $properties['bulkid'] = self::$logbulkid;
        }
        if (!array_key_exists('time', $properties)) {
            if (self::$defaulttime) {
                $properties['time'] = self::$defaulttime;
            } else {
                $properties['time'] = time();
            }
        }
        if (array_key_exists('id', $properties)) {
            unset($properties['id']);
        }
        if (!array_key_exists('userid', $properties)) {
            if (self::$defaultuser) {
                $properties['userid'] = self::$defaultuser;
            } else {
                $properties['userid'] = $USER->id;
            }
        }
        if (!array_key_exists('ip', $properties)) {
            $properties['ip'] = getremoteaddr();
        }
        if (array_key_exists('info', $properties)) {
            $properties['info'] = serialize($properties['info']);
        }
        $dt = getdate($properties['time']);
        $properties['timeday'] = mktime(0, 0, 0, $dt['mon'], $dt['mday'], $dt['year']);
        $id = $DB->insert_record('local_plugins_log', $properties);
        $log = $DB->get_record('local_plugins_log', array('id' => $id));
        return new local_plugins_log($log);
    }

    /**
     * Logs an object action (add, edit or delete)
     *
     * @param string $action
     * @param array $log
     * @param local_plugins_loggable $object
     * @param string $comment
     */
    protected static function log_object($action, $log, local_plugins_loggable $object, $comment = null) {
        if (!empty($comment)) {
            $log['comment'] = $comment;
        }
        $data = array('info' => $log, 'action' => $action);
        $data['tableid'] = $object->id;
        if ($object instanceof local_plugins_plugin) {
            $data['pluginid'] = $object->id;
        } else if (preg_match('/^plugin-/', $action)) {
            $data['pluginid'] = $object->plugin->id;
        } else {
            // this is not plugin-related object
            unset($log['identifier_plugin']);
        }
        return self::log_raw($data);
    }

    protected static $_cached_objects = array();

    /**
     * Remembers the state of the plugin before the it is about to be changed or deleted
     *
     * @param mixed $object either one object implementing local_plugins_loggable or an array of such objects
     */
    public static function remember_state($object) {
        if (is_array($object)) {
            foreach ($object as $obj) {
                self::remember_state($obj);
            }
            return;
        }
        self::$_cached_objects[$object->log_action_prefix(). '-'. $object->id] = array(
            'identifier' => $object->log_identifier(false),
            'identifier_plugin' => $object->log_identifier(true),
            'oldvalue' => $object->log_data()
        );
    }

    /**
     * Creates the log entry (if required) for object changes. Must be called after remember_state
     *
     * @param mixed $object either one object implementing local_plugins_loggable or an array of such objects
     */
    public static function log_edited($object, $comment = null) {
        if (is_array($object)) {
            foreach ($object as $obj) {
                self::log_edited($obj);
            }
            return;
        }
        $log = self::$_cached_objects[$object->log_action_prefix(). '-'. $object->id];
        $action = $object->log_action_prefix(). self::LOG_SUFFIX_EDITED;
        $log['newvalue'] = $object->log_data();
        $log['identifier'] = $object->log_identifier(false);
        $log['identifier_plugin'] = $object->log_identifier(true);
        foreach ($log['newvalue'] as $key => $value) {
            if ($log['oldvalue'][$key] == $value) {
                unset($log['oldvalue'][$key]);
            }
        }
        if (!empty($log['oldvalue'])) {
            // Some fields have changed. Create log entry
            return self::log_object($action, $log, $object, $comment);
        }
        return null;
    }

    /**
     * Creates the log entry for removing of the object. Must be called after remember_state
     */
    public static function log_deleted(local_plugins_loggable $object, $comment = null) {
        $log = self::$_cached_objects[$object->log_action_prefix(). '-'. $object->id];
        $action = $object->log_action_prefix(). self::LOG_SUFFIX_DELETED;
        return self::log_object($action, $log, $object, $comment);
    }

    /**
     * Creates the log entry for newly created object
     */
    public static function log_added(local_plugins_loggable $object, $comment = null) {
        $action = $object->log_action_prefix(). self::LOG_SUFFIX_ADDED;
        $log = array(
            'newvalue' => $object->log_data(),
            'identifier' => $object->log_identifier(false),
            'identifier_plugin' => $object->log_identifier(true),
        );
        return self::log_object($action, $log, $object, $comment);
    }

    /**
     * Creates the log entry (if required) if object was edited and/or added.
     * In case of edit mode, it must be called after remember_state
     *
     * @param boolean $isadded whether this object was added (false for changed)
     * @param mixed $object either one object implementing local_plugins_loggable or an array of such objects
     */
    public static function log_changed($object, $isadded, $comment = null) {
        if ($isadded) {
            self::log_added($object, $comment);
        } else {
            self::log_edited($object, $comment);
        }
    }

    /**
     * Returns an array of log entries (sorted descending by time) that satisfy the given subquery
     *
     * @param string $subquery
     * @param array $params
     * @param int $limitfrom
     * @param int $limitnum
     * @return array
     */
    public static function get_log_sql($subquery, array $params = null, $limitfrom = 0, $limitnum = 0) {
        global $DB;
        if (empty($subquery)) {
            $subquery = "1=1"; // only for last actions query!
        }
        $records = $DB->get_records_sql('SELECT * from {local_plugins_log} WHERE '. $subquery. ' ORDER BY time DESC, id DESC', $params, $limitfrom, $limitnum);
        $logs = array();
        foreach ($records as $record) {
            $logs[] = new local_plugins_log($record);
        }
        return $logs;
    }

    protected function get_user() {
        global $DB;
        if (empty($this->user)) {
            $this->user = $DB->get_record('user', array('id' => $this->userid), '*', IGNORE_MISSING);
        }
        return $this->user;
    }

    protected function get_username() {
        $user = $this->get_user();
        if (!empty($user)) {
            return fullname($user);
        } else {
            return get_string('unknownuser', 'local_plugins', $this->userid);
        }
    }
}

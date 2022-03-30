<?php

/**
 * This file contains the subscription class and subscribable interface. They provide
 * functionality to subscribe to actions that required db changes.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2012 Aparup Banerjee
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Interface for objects that are subscribable to.
 * Similar to local_plugins_loggable but sometimes (eg:comments) we're subscribing to things that aren't loggable hence 
 * local_plugins_subscribable.
 */
interface local_plugins_subscribable
{
    /**
     * Get subscription type. 
     * 
     * @return const returns one of local_plugin::NOTIFY_* which are message providers within db/messages.php .
     */
    public function sub_get_type($notificationtype);

    public function sub_is_subscribed($subscription);

    public function sub_subscribe($subscription);

    public function sub_unsubscribe($subscription);

    public function sub_toggle($subscription);
}

/**
 * Class local_plugins_subscription represents one entry in subscription table. It also provides
 * static methods to add and retrieve subscription entries.
 */
class local_plugins_subscription extends local_plugins_class_base {

    // Subscribing Areas
    const SUB_PLUGIN_COMMENTS   = 'plugin-comment-sub';
    const SUB_PLUGIN_AWARDS   = 'plugin-awards-sub';
    const SUB_PLUGINS_CONTRIBUTOR   = 'plugin-contributor-sub';
//    const SUB_PLUGINS_APPROVER   = 'plugin-approver-sub';
    const SUB_PLUGINS_UNKNOWN  = 'plugin-unknown-sub';

    // Database properties
    public $id;
    public $userid;
    public $pluginid;
    public $type; // subscription type , some notifications (message providers) can be subscribed to.
                     // for message provider types. See local_plugin::NOTIFY_*

    public function __construct($properties) {
        if (empty($properties->id)) {
            $properties->id = null;
        }
        parent::__construct($properties);
    }
    /**
     * Inserts a subscription record into database
     *
     * @return local_plugins_log
     */
    public static function subscribe($properties, $plugin) {
        global $USER, $DB;
        $properties = (array)$properties;

        if (array_key_exists('id', $properties)) {
            unset($properties['id']);
        }
        if (!array_key_exists('userid', $properties)) {
            $properties['userid'] = $USER->id;
        }
        if (!array_key_exists('pluginid', $properties)) {
            $properties['pluginid'] = $plugin->id;
        }
        if (!array_key_exists('type', $properties)) {
            $properties['type'] = self::SUB_PLUGINS_UNKNOWN;
        }

        return $DB->insert_record('local_plugins_subscription', $properties);
    }

    public static function unsubscribe($properties) {
        global $DB;

        $properties = (array)$properties;
        if (array_key_exists('id', $properties)) {
            if (is_null($properties['id'])) {
                unset($properties['id']);
            }
        }
        return $DB->delete_records('local_plugins_subscription', $properties);
    }

    /**
     *
     * @param type $properties
     * @return type
     */
    public static function is_subscribed($pluginid, $type, $userid) {
        global $DB;
        return $DB->record_exists('local_plugins_subscription', array('pluginid' => $pluginid, 'userid' => $userid, 'type' => $type));
    }

    /**
     * Returns an array of subscription entries
     *
     * @param string $pluginid
     * @param array $userid
     * @param int $limitfrom
     * @param int $limitnum
     * @return array local_plugins_subscription
     */
    public static function get_pluginsubscriptions($pluginid, $userid=null, $type=null, $limitfrom = 0, $limitnum = 0) {
        global $DB;

        $params = array('pluginid'=>$pluginid);
        $subquery = 'pluginid = :pluginid ';
        if (!empty($userid)) {
            $params['userid'] = $userid;
            $subquery .= 'and userid = :userid ';
        } 
        if (!empty($type)) {
            $params['type'] = $type;
            $subquery .= 'and type = :type ';
        }
        $records = $DB->get_records_sql('SELECT * from {local_plugins_subscription} WHERE '.$subquery, $params , $limitfrom, $limitnum);
        $subscriptions = array();
        foreach ($records as $record) {
            $subscriptions[] = new local_plugins_subscription($record);
        }
        return $subscriptions;
    }

    public static function get_subcribers($pluginid, $type=null) {
        global $CFG;
        require_once($CFG->dirroot.'/user/lib.php');
        $subscriptions = self::get_pluginsubscriptions($pluginid, null, $type);
        $userids = array();
        foreach ($subscriptions as $subscription) {
            $userids[] = $subscription->userid;
        }
        return user_get_users_by_id($userids);
    }

    public static function subscription_for_plugin($plugin, $notificationtype) {
        global $USER;
        $subscription = new stdClass();
        $subscription->userid = $USER->id;
        $subscription->pluginid = $plugin->id;
        $subscription->type = $plugin->sub_get_type($notificationtype);
        return new local_plugins_subscription($subscription);
    }
}
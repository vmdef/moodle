<?php

/**
 * This file defines the plugin comment RSS feed
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

// No direct access to this script
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

class local_plugins_rss_set_plugins_feed extends local_plugins_rss_feed {
    
    /**
     *
     * @var local_plugins_set
     */
    protected $set;
    protected $plugins;
    
    public function __construct($id, array $args) {
        parent::__construct($id, $args);
        $this->set = local_plugins_helper::get_set($id);
    }
    
    protected function get_title() {
        return get_string('pluginname', 'local_plugins'). ': '. get_string('set', 'local_plugins'). ': '. $this->set->formatted_name;
    }
    
    protected function get_description() {
        return $this->set->formatted_description;
    }
    
    protected function get_link() {
        return $this->set->browseurl;
    }
    
    protected function get_name() {
        return 'set_plugins';
    }
    
    protected function get_items() {
        if (empty($this->plugins)) {
            $this->plugins = $this->set->get_plugins();
        }
        $items = array();
        foreach ($this->plugins as $plugin) {
            $items[] = local_plugins_rss_item::from_plugin($plugin);
        }
        return $items;
    }
    
    /**
     *
     * @global moodle_database $DB
     */
    protected function get_most_recent_item_timestamp() {
        global $DB;
        $sql = 'SELECT MAX(sp.timeadded) AS mostrecent 
                  FROM {local_plugins_set_plugin} sp 
                 WHERE sp.setid = :id';
        return $DB->get_field_sql($sql, array('id' => $this->id));
    }
}
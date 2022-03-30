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

class local_plugins_rss_award_plugins_feed extends local_plugins_rss_feed {
    
    /**
     *
     * @var local_plugins_award
     */
    protected $award;
    protected $plugins;
    
    public function __construct($id, array $args) {
        parent::__construct($id, $args);
        $this->award = local_plugins_helper::get_award($id);
    }
    
    protected function get_title() {
        return get_string('pluginname', 'local_plugins'). ': '. get_string('award', 'local_plugins'). ': '. $this->award->formatted_name;
    }
    
    protected function get_description() {
        return $this->award->formatted_description;
    }
    
    protected function get_link() {
        return $this->award->browseurl;
    }
    
    protected function get_name() {
        return 'award_plugins';
    }
    
    protected function get_items() {
        if (empty($this->plugins)) {
            $this->plugins = $this->award->get_plugins();
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
        $sql = 'SELECT MAX(pa.timeawarded) AS mostrecent 
                  FROM {local_plugins_plugin_awards} pa 
                 WHERE pa.awardid = :id';
        return $DB->get_field_sql($sql, array('id' => $this->id));
    }
}
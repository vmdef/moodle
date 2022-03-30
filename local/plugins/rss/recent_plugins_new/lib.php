<?php

/**
 * This file defines the plugin comment RSS feed
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2013 Aparup Banerjee
 */

// No direct access to this script
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

class local_plugins_rss_recent_plugins_new_feed extends local_plugins_rss_feed {
    public function __construct($id, array $args) {
        parent::__construct($id, $args);
        if ($id) {
            $this->category = local_plugins_helper::get_category($id);
        } else {
            $this->category = new local_plugins_recentplugins_new();
        }
    }
    protected function get_title() {
        if (isset($this->category->id)) {
            return get_string('pluginname', 'local_plugins'). ': '. get_string('category', 'local_plugins'). ': '. $this->category->formatted_name;
        } else {
            return get_string('pluginname', 'local_plugins'). ': '. $this->category->formatted_name;
        }
    }

    protected function get_description() {
        return $this->category->formatted_shortdescription;
    }

    protected function get_link() {
        return $this->category->browseurl;
    }
    protected function get_name() {
        return 'recent_plugins_new';
    }

    protected function get_items() {
        if (empty($this->plugins)) {
            $this->plugins = $this->category->get_plugins();
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
        $sql = 'SELECT MAX(p.timefirstapproved) AS mostrecent
                  FROM {local_plugins_plugin} p ';
        $sql .= "WHERE p.approved = ". local_plugins_plugin::PLUGIN_APPROVED. " and p.timefirstapproved is not null ";
        $params = array();
        if ($this->id) {
            $sql .= 'AND p.categoryid = :id';
            $params['id'] = $this->id;
        }
        return $DB->get_field_sql($sql, $params);
    }
}

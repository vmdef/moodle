<?php

/**
 * This file manages the list of recent plugins
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2013 Aparup Banerjee
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used to create objects to represent recent plugins list
 * within the local_plugins plugin.
 *
 * @property-read moodle_url $rssurl
 */
class local_plugins_recentplugins_updated extends local_plugins_recentplugins {
    public function __construct() {
    }

    public function get_rssurl() {
        return local_plugins_helper::get_rss_url('recent_plugins_updated', 0);
    }

    public function get_formatted_name() {
        return get_string('mostrecentpluginsupdated', 'local_plugins');
    }

    protected function plugins_alter_query(&$sql, &$params, $unapproved = false, $invisible = false, $alwaysshowown = false) {
        parent::plugins_alter_query($sql, $params, $unapproved, $invisible, $alwaysshowown);
        $sql["WHERE"] .= " AND p.timelastreleased > p.timefirstapproved";
        $sql["ORDER BY"] = "p.timelastreleased DESC, p.id DESC";
    }
}

<?php

/**
 * This file manages the list of recent plugins
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 
 */

defined('MOODLE_INTERNAL') || die();

/**
 * This class is used to create objects to represent recent plugins list
 * within the local_plugins plugin.
 *
 * @property-read moodle_url $rssurl
 */
class local_plugins_recentplugins extends local_plugins_collection_class implements renderable {
    public function __construct() {
    }

    public function get_browseurl() {
        return new local_plugins_url('/local/plugins/'); // this list is actually only displayed on the front page
    }

    public function get_rssurl() {
        return local_plugins_helper::get_rss_url('recent_plugins', 0);
    }

    public function get_formatted_name() {
        return get_string('mostrecentplugins', 'local_plugins');
    }

    public function get_formatted_shortdescription() {
        return '';
    }

    protected function plugins_alter_query(&$sql, &$params, $unapproved = false, $invisible = false, $alwaysshowown = false) {
        parent::plugins_alter_query($sql, $params, $unapproved, $invisible, $alwaysshowown);
        $sql["ORDER BY"] = "p.timelastreleased DESC, p.id DESC";
    }

    public function get_currentpage_plugins() {
        return $this->get_plugins(5); // TODO parametrize number of recent plugins to show (5)
    }

    protected function get_currentpage_plugins_count() {
        return null; // no pagination for recent plugins
    }

    public function get_currentpage_pagingbar() {
        return null; // no pagination for recent plugins
    }
}

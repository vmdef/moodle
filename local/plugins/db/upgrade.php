<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides the {@link xmldb_local_plugins_upgrade()} function.
 *
 * @package     local_plugins
 * @category    upgrade
 * @copyright   2011 Sam Hemelryk
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Perform the upgrade steps.
 *
 * @param int $oldversion
 */
function xmldb_local_plugins_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2011081000) {

        // Changing type of field type on table local_plugins_contributor to char
        $table = new xmldb_table('local_plugins_contributor');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'creator');

        // Launch change of type for field type
        $dbman->change_field_type($table, $field);

        // Changing type of field creator on table local_plugins_contributor to int and rename it to maintainer
        $field = new xmldb_field('creator', XMLDB_TYPE_INTEGER, '2', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'pluginid');

        // Launch change of type for field creator
        $dbman->change_field_type($table, $field);
        // Launch rename field creator to maintainer
        $dbman->rename_field($table, $field, 'maintainer');

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011081000, 'local', 'plugins');
    }

    if ($oldversion < 2011081200) {

        // Define field sortorder to be added to local_plugins_category
        $table = new xmldb_table('local_plugins_category');
        $field = new xmldb_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'onfrontpage');

        // Conditionally launch add field sortorder
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011081200, 'local', 'plugins');
    }

    if ($oldversion < 2011081601) {

        // Define field grade to be added to local_plugins_review_outcome
        $table = new xmldb_table('local_plugins_review_outcome');
        $field = new xmldb_field('grade', XMLDB_TYPE_NUMBER, '10, 5', null, null, null, null, 'reviewformat');

        // Conditionally launch add field grade
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field generalreview to be dropped from local_plugins_review
        $table = new xmldb_table('local_plugins_review');
        $field = new xmldb_field('generalreview');

        // Conditionally launch drop field generalreview
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field generalreviewformat to be dropped from local_plugins_review
        $table = new xmldb_table('local_plugins_review');
        $field = new xmldb_field('generalreviewformat');

        // Conditionally launch drop field generalreviewformat
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // Define field timelastmodified to be added to local_plugins_review
        $table = new xmldb_table('local_plugins_review');
        $field = new xmldb_field('timelastmodified', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null, 'timereviewed');

        // Conditionally launch add field timelastmodified
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011081601, 'local', 'plugins');
    }

    if ($oldversion < 2011081700) {

        // Rename field jiracomponentname on table local_plugins_plugin to bugtrackerurl
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('jiracomponentname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'documentationurl');

        // Launch rename field jiracomponentname
        $dbman->rename_field($table, $field, 'bugtrackerurl');

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011081700, 'local', 'plugins');
    }

    if ($oldversion < 2011082400) {

        // Define field frankenstyle to be added to local_plugins_category
        $table = new xmldb_table('local_plugins_category');
        $field = new xmldb_field('frankenstyle', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'name');

        // Conditionally launch add field frankenstyle
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define field frankenstyle to be added to local_plugins_plugin
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('frankenstyle', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'name');

        // Conditionally launch add field frankenstyle
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011082400, 'local', 'plugins');
    }

    if ($oldversion < 2011082500) {

        // Define index localplug_frankenstyle (unique) to be added to local_plugins_plugin
        $table = new xmldb_table('local_plugins_plugin');
        $index = new xmldb_index('frankenstyle', XMLDB_INDEX_UNIQUE, array('frankenstyle'));

        // Conditionally launch add index localplug_frankenstyle
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011082500, 'local', 'plugins');
    }

    if ($oldversion < 2011082501) {

        // Rename field frankenstyle on table local_plugins_category to plugintype
        $table = new xmldb_table('local_plugins_category');
        $field = new xmldb_field('frankenstyle', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'name');

        // Launch rename field frankenstyle
        $dbman->rename_field($table, $field, 'plugintype');

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011082501, 'local', 'plugins');
    }

    if ($oldversion < 2011083001) {

        // Rename field available on table local_plugins_plugin to visible
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('available', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'approved');

        // Launch rename field available
        $dbman->rename_field($table, $field, 'visible');

        // Rename field available on table local_plugins_vers to visible
        $table = new xmldb_table('local_plugins_vers');
        $field = new xmldb_field('available', XMLDB_TYPE_INTEGER, '1', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, '0', 'approved');

        // Launch rename field available
        $dbman->rename_field($table, $field, 'visible');

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011083001, 'local', 'plugins');
    }

    if ($oldversion < 2011090601) {

        // Define field timelastreleased to be added to local_plugins_plugin
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('timelastreleased', XMLDB_TYPE_INTEGER, '10', XMLDB_UNSIGNED, null, null, null, 'timelastmodified');

        // Conditionally launch add field timelastreleased
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Define index lastreleased (not unique) to be added to local_plugins_plugin
        $index = new xmldb_index('lastreleased', XMLDB_INDEX_NOTUNIQUE, array('timelastreleased'));

        // Conditionally launch add index lastreleased
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // fill the new field with data
        $dbfamily = $DB->get_dbfamily();
        if ($dbfamily === 'mysql') {
            $DB->execute("UPDATE {local_plugins_plugin} p SET p.timelastreleased =
                (SELECT MAX(v.timecreated)
                FROM {local_plugins_vers} v
                WHERE v.visible = 1 AND v.approved = 1 AND v.pluginid = p.id)
                WHERE p.visible = 1 AND p.approved = 1");
        }
        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011090601, 'local', 'plugins');
    }

    if ($oldversion < 2011092601) {

        // Define table local_plugins_log to be created
        $table = new xmldb_table('local_plugins_log');

        // Adding fields to table local_plugins_log
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('bulkid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('time', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('timeday', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, null, null);
        $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, XMLDB_NOTNULL, null, null);
        $table->add_field('action', XMLDB_TYPE_CHAR, '50', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pluginid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('tableid', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, null, null, null);
        $table->add_field('info', XMLDB_TYPE_TEXT, 'medium', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_plugins_log
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));

        // Adding indexes to table local_plugins_log
        $table->add_index('plugin', XMLDB_INDEX_NOTUNIQUE, array('pluginid'));
        $table->add_index('user', XMLDB_INDEX_NOTUNIQUE, array('userid', 'timeday'));
        $table->add_index('actiontable', XMLDB_INDEX_NOTUNIQUE, array('action', 'tableid'));
        $table->add_index('bulkid', XMLDB_INDEX_NOTUNIQUE, array('bulkid'));
        $table->add_index('timeday', XMLDB_INDEX_NOTUNIQUE, array('timeday'));

        // Conditionally launch create table for local_plugins_log
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Pref-fill log table with data from existing entities
        require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
        $comment = 'This log entry has been created automatically and reflects the state as of '.userdate(time()).' when the logging of plugins was first launched';
        $commentinaccurate = $comment. '. Please note that date of this action may be inaccurate';
        $minplugintime = $DB->get_field_sql('SELECT min(timecreated - 1) from {local_plugins_plugin}');
        $defuser = null;

        // create automatic log entries for categories, sets, awards, software versions and review criteria
        $objects = array_merge(
            array_values(local_plugins_helper::get_categories()),
            array_values(local_plugins_helper::get_sets()),
            array_values(local_plugins_helper::get_awards()),
            array_values(local_plugins_helper::get_software_versions()),
            array_values(local_plugins_helper::get_review_criteria())
        );
        foreach ($objects as $obj) {
            if (isset($obj->timecreated)) {
                local_plugins_log::start_new_bulk($obj->timecreated, $defuser);
                local_plugins_log::log_added($obj, $comment);
            } else {
                local_plugins_log::start_new_bulk($minplugintime, $defuser);
                local_plugins_log::log_added($obj, $commentinaccurate);
            }
        }

        // create automatic log entries for plugins, versions and reviews
        $rs = $DB->get_recordset_select('local_plugins_plugin', '1=1');
        foreach ($rs as $pluginrecord) {
            $plugin = new local_plugins_plugin($pluginrecord);
            $contributors = array_values($plugin->contributors);
            $plugincreator = $contributors[0]->userid;

            local_plugins_log::start_new_bulk($plugin->timecreated, $plugincreator);
            local_plugins_log::log_added($plugin, $comment);
            foreach ($plugin->versions as $version) {
                local_plugins_log::start_new_bulk($version->timecreated, $version->userid);
                local_plugins_log::log_added($version, $comment);
            }
            foreach ($plugin->reviews as $review) {
                local_plugins_log::start_new_bulk($review->timereviewed, $review->userid);
                local_plugins_log::log_added($review, $comment);
            }
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011092601, 'local', 'plugins');
    }

    if ($oldversion < 2011112101) {

        // Define table local_plugins_vers_updates to be created
        $table = new xmldb_table('local_plugins_vers_updates');

        // Adding fields to table local_plugins_vers_updates
        $table->add_field('id', XMLDB_TYPE_INTEGER, '11', XMLDB_UNSIGNED, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('versionid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);
        $table->add_field('updateableid', XMLDB_TYPE_INTEGER, '11', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_plugins_vers_updates
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('versionid', XMLDB_KEY_FOREIGN, array('versionid'), 'local_plugins_vers', array('id'));
        $table->add_key('updateableid', XMLDB_KEY_FOREIGN, array('updateableid'), 'local_plugins_vers', array('id'));

        // Adding indexes to table local_plugins_vers_updates
        $table->add_index('versupdate', XMLDB_INDEX_UNIQUE, array('versionid', 'updateableid'));

        // Conditionally launch create table for local_plugins_vers_updates
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // fill the new table with data
        $dbfamily = $DB->get_dbfamily();
        if ($dbfamily === 'mysql') {
            $DB->execute("INSERT INTO {local_plugins_vers_updates} (versionid, updateableid)
                SELECT v.id, v.previousversionid
                FROM {local_plugins_vers} v, {local_plugins_vers} pv
                WHERE v.previousversionid = pv.id and v.pluginid = pv.pluginid");
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011112101, 'local', 'plugins');
    }

    if ($oldversion < 2011112300) {

        // Define key previousversionid (foreign) to be dropped form local_plugins_vers
        $table = new xmldb_table('local_plugins_vers');
        $key = new xmldb_key('previousversionid', XMLDB_KEY_FOREIGN, array('previousversionid'), 'local_plugins_vers', array('id'));

        // Launch drop key previousversionid
        $dbman->drop_key($table, $key);

        // Define field previousversionid to be dropped from local_plugins_vers
        $table = new xmldb_table('local_plugins_vers');
        $field = new xmldb_field('previousversionid');

        // Conditionally launch drop field previousversionid
        if ($dbman->field_exists($table, $field)) {
            $dbman->drop_field($table, $field);
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011112300, 'local', 'plugins');
    }

    if ($oldversion < 2011120101) {

        // Define field trackingwidgets to be added to local_plugins_plugin
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('trackingwidgets', XMLDB_TYPE_TEXT, 'medium', null, null, null, null, 'bugtrackerurl');

        // Conditionally launch add field trackingwidgets
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2011120101, 'local', 'plugins');
    }

    if ($oldversion < 2012120600) {

        // Define field id to be added to local_plugins_stats_cache
        $table = new xmldb_table('local_plugins_stats_cache');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null, null);
        $table->add_field('pluginid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'id');
        $table->add_field('versionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null, 'pluginid');
        $table->add_field('month', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null, 'versionid');
        $table->add_field('year', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null, 'month');
        $table->add_field('downloads', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'year');
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'downloads');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $table->add_index('plugin', XMLDB_INDEX_NOTUNIQUE, array('pluginid'));
        $table->add_index('version', XMLDB_INDEX_NOTUNIQUE, array('versionid'));
        $table->add_index('pluginver', XMLDB_INDEX_NOTUNIQUE, array('pluginid', 'versionid'));
        $table->add_index('timeupdate', XMLDB_INDEX_NOTUNIQUE, array('timeupdated'));
        $table->add_index('monthyear', XMLDB_INDEX_NOTUNIQUE, array('month', 'year'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2012120600, 'local', 'plugins');
    }

    if ($oldversion < 2013022000) {

        // Define table local_plugins_subscription to be created
        $table = new xmldb_table('local_plugins_subscription');

        // Adding fields to table local_plugins_subscription
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pluginid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('type', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);

        // Adding keys to table local_plugins_subscription
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('pluginid', XMLDB_KEY_FOREIGN, array('pluginid'), 'local_plugins_plugin', array('id'));

        // Adding indexes to table local_plugins_subscription
        $table->add_index('userid-pluginid', XMLDB_INDEX_NOTUNIQUE, array('userid', 'pluginid'));

        // Conditionally launch create table for local_plugins_subscription
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // plugins savepoint reached
        upgrade_plugin_savepoint(true, 2013022000, 'local', 'plugins');

    }

    if ($oldversion < 2013041000) {

        // Define field discussionurl to be added to local_plugins_plugin.
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('discussionurl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'bugtrackerurl');

        // Conditionally launch add field discussionurl.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Plugins savepoint reached.
        upgrade_plugin_savepoint(true, 2013041000, 'local', 'plugins');

    }

    if ($oldversion < 2013041001) {

        // Define table local_plugins_usersite to be created.
        $table = new xmldb_table('local_plugins_usersite');

        // Adding fields to table local_plugins_usersite.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sitename', XMLDB_TYPE_CHAR, '254', null, XMLDB_NOTNULL, null, null);
        $table->add_field('siteurl', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('version', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        // Adding keys to table local_plugins_usersite.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        // Adding indexes to table local_plugins_usersite.
        $table->add_index('id-userid', XMLDB_INDEX_UNIQUE, array('id', 'userid'));

        // Conditionally launch create table for local_plugins_usersite.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Plugins savepoint reached.
        upgrade_plugin_savepoint(true, 2013041001, 'local', 'plugins');
    }

    if ($oldversion < 2013092500) {

        // Define field timefirstapproved to be added to local_plugins_plugin.
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('timefirstapproved', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timelastreleased');

        // Conditionally launch add field timefirstapproved.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Plugins savepoint reached.
        upgrade_plugin_savepoint(true, 2013092500, 'local', 'plugins');
    }

    if ($oldversion < 2013101500) {

        // fill the new timefirstapproved field which are null with data for ones currently approved
        $dbfamily = $DB->get_dbfamily();
        if ($dbfamily === 'mysql') {
            $DB->execute("UPDATE {local_plugins_plugin} p SET p.timefirstapproved = p.timecreated
                WHERE p.timefirstapproved is NULL and p.approved = 1");
        }
        // Plugins savepoint reached.
        upgrade_plugin_savepoint(true, 2013101500, 'local', 'plugins');
    }

    if ($oldversion < 2014050502) {
        // Add the field ip to the table local_plugins_vers_download.
        $table = new xmldb_table('local_plugins_vers_download');
        $field = new xmldb_field('ip', XMLDB_TYPE_CHAR, '45', null, null, null, null, 'downloadmethod');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2014050502, 'local', 'plugins');
    }

    if ($oldversion < 2014050503) {
        // Add the field exclude to the table local_plugins_vers_download.
        $table = new xmldb_table('local_plugins_vers_download');
        $field = new xmldb_field('exclude', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0', 'ip');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2014050503, 'local', 'plugins');
    }

    if ($oldversion < 2014050504) {
        // Add index ip (not unique) to the table local_plugins_vers_download.
        $table = new xmldb_table('local_plugins_vers_download');
        $index = new xmldb_index('ip', XMLDB_INDEX_NOTUNIQUE, array('ip'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2014050504, 'local', 'plugins');
    }

    if ($oldversion < 2014050508) {
        // Drop the 'statupdate' and 'lastcron' config
        set_config('lastcron', null, 'local_plugins');
        set_config('statupdate', null, 'local_plugins');
        upgrade_plugin_savepoint(true, 2014050508, 'local', 'plugins');
    }

    if ($oldversion < 2014120100) {
        // Add the new table local_plugins_favourite
        $table = new xmldb_table('local_plugins_favourite');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pluginid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_key('fk_pluginid', XMLDB_KEY_FOREIGN, array('pluginid'), 'local_plugins_plugin', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2014120100, 'local', 'plugins');
    }

    if ($oldversion < 2014120102) {
        // Add index uq_userid_pluginid (unique) to prevent accidentally inserted duplicates
        $table = new xmldb_table('local_plugins_favourite');
        $index = new xmldb_index('uq_userid_pluginid', XMLDB_INDEX_UNIQUE, array('userid', 'pluginid'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2014120102, 'local', 'plugins');
    }

    if ($oldversion < 2015051500) {
        // Add fields to hold the aggregated stats.
        $table = new xmldb_table('local_plugins_plugin');

        $field = new xmldb_field('aggdownloads', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'visible');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('aggfavs', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'aggdownloads');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('aggsites', XMLDB_TYPE_INTEGER, '20', null, null, null, null, 'aggfavs');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Plugins savepoint reached.
        upgrade_plugin_savepoint(true, 2015051500, 'local', 'plugins');
    }

    if ($oldversion < 2015052500) {
        // Populate the current value of the new aggfavs field.

        $sql = "SELECT pluginid, COUNT(*) AS favs
                  FROM {local_plugins_favourite}
                 WHERE status = 1
              GROUP BY pluginid";

        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $record) {
            $DB->set_field('local_plugins_plugin', 'aggfavs', $record->favs, array('id' => $record->pluginid));
        }

        $rs->close();

        upgrade_plugin_savepoint(true, 2015052500, 'local', 'plugins');
    }

    if ($oldversion < 2015052501) {
        // Populate the current value of the new aggdownloads field.

        // Get the plugin ids we have cached stats for.
        $sql = "SELECT DISTINCT pluginid
                  FROM {local_plugins_stats_cache}
                 WHERE pluginid IS NOT NULL AND month = 0 AND year = 0";

        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $record) {
            // How many times the given plugin has been downloaded in total?
            $sql = "SELECT SUM(downloads)
                      FROM {local_plugins_stats_cache}
                     WHERE pluginid = :pluginid AND month = 0 AND year = 0";

            $total = (int)$DB->get_field_sql($sql, array('pluginid' => $record->pluginid));

            $DB->set_field('local_plugins_plugin', 'aggdownloads', $total, array('id' => $record->pluginid));
        }

        $rs->close();

        upgrade_plugin_savepoint(true, 2015052501, 'local', 'plugins');
    }

    if ($oldversion < 2015052600) {
        // Add a new table local_plugins_plugin_usage.
        $table = new xmldb_table('local_plugins_plugin_usage');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('pluginid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('month', XMLDB_TYPE_INTEGER, '2', null, XMLDB_NOTNULL, null, null);
        $table->add_field('year', XMLDB_TYPE_INTEGER, '4', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sites', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('timeupdated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        $table->add_index('plugin', XMLDB_INDEX_NOTUNIQUE, array('pluginid'));
        $table->add_index('timeupdate', XMLDB_INDEX_NOTUNIQUE, array('timeupdated'));
        $table->add_index('monthyear', XMLDB_INDEX_NOTUNIQUE, array('month', 'year'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2015052600, 'local', 'plugins');
    }

    if ($oldversion < 2015062200) {
        // Drop all the plugin_screenshotthumbs files.
        $fs = get_file_storage();
        $context = context_system::instance();
        $fs->delete_area_files($context->id, 'local_plugins', 'plugin_screenshotthumbs');

        upgrade_plugin_savepoint(true, 2015062200, 'local', 'plugins');
    }

    if ($oldversion < 2015093000) {
        // Add field "info" to the table "local_plugins_vers_download"
        $table = new xmldb_table('local_plugins_vers_download');
        $field = new xmldb_field('info', XMLDB_TYPE_TEXT, null, null, null, null, null, 'exclude');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2015093000, 'local', 'plugins');
    }

    if ($oldversion < 2015112000) {
        // Create new table local_plugins_stats_raw as a copy of local_plugins_vers_download.
        $table = new xmldb_table('local_plugins_stats_raw');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('versionid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timedownloaded', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('downloadmethod', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'file');
        $table->add_field('ip', XMLDB_TYPE_CHAR, '45', null, null, null, null);
        $table->add_field('exclude', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('info', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('versionid', XMLDB_KEY_FOREIGN, array('versionid'), 'local_plugins_vers', array('id'));
        $table->add_key('userid', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $table->add_index('ip', XMLDB_INDEX_NOTUNIQUE, array('ip'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2015112000, 'local', 'plugins');
    }

    if ($oldversion < 2015112001) {
        // Copy valid download request logs after October 01, 2015 into the new table (HQ-701).
        $DB->execute("INSERT INTO {local_plugins_stats_raw} (versionid, timedownloaded, userid, downloadmethod, ip, exclude, info)
                           SELECT versionid, timedownloaded, userid, downloadmethod, ip, exclude, info
                             FROM {local_plugins_vers_download}
                            WHERE timedownloaded >= 1443657600");

        upgrade_plugin_savepoint(true, 2015112001, 'local', 'plugins');
    }

    if ($oldversion < 2015112002) {
        $DB->delete_records('local_plugins_stats_cache');
        upgrade_plugin_savepoint(true, 2015112002, 'local', 'plugins');
    }

    if ($oldversion < 2016041101) {
        // Add the field status to the table local_plugins_review.
        $table = new xmldb_table('local_plugins_review');
        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'timelastmodified');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $DB->set_field('local_plugins_review', 'status', 1);
        upgrade_plugin_savepoint(true, 2016041101, 'local', 'plugins');
    }

    if ($oldversion < 2016042000) {
        // Add field timelastapprovedchange to the table local_plugins_plugin.
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('timelastapprovedchange', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'timefirstapproved');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2016042000, 'local', 'plugins');
    }

    if ($oldversion < 2016042001) {
        // Add field approvedby to the table local_plugins_plugin.
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('approvedby', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'approved');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Add foreign key fk_approvedby.
        $key = new xmldb_key('fk_approvedby', XMLDB_KEY_FOREIGN, array('approvedby'), 'user', array('id'));
        $dbman->add_key($table, $key);

        upgrade_plugin_savepoint(true, 2016042001, 'local', 'plugins');
    }

    if ($oldversion < 2016042007) {
        // Populate the value of the fields timelastapprovedchange and approvedby.
        $pluginset = $DB->get_recordset_select('local_plugins_plugin', 'timelastapprovedchange IS NULL OR approvedby IS NULL');

        foreach ($pluginset as $plugin) {
            $timelastapprovedchange = 0;
            $approvedby = null;
            $logs = $DB->get_records('local_plugins_log', ['pluginid' => $plugin->id, 'action' => 'plugin-plugin-edit'], 'time');
            foreach ($logs as $log) {
                $info = unserialize($log->info);
                if (isset($info['oldvalue']['status']) and isset($info['newvalue']['status'])
                        and $info['oldvalue']['status'] !== $info['newvalue']['status']
                        and in_array($info['oldvalue']['status'], ['Available', 'Needs more work', 'Waiting for approval'])
                        and in_array($info['newvalue']['status'], ['Available', 'Needs more work', 'Waiting for approval'])
                        and $log->time > $timelastapprovedchange) {
                    $timelastapprovedchange = $log->time;
                    if (in_array($info['newvalue']['status'], ['Available', 'Needs more work'])) {
                        // The approvedby holds id of the reviewer, not the id
                        // of the one who requested re-approval.
                        $approvedby = $log->userid;
                    }
                }
            }
            if ($timelastapprovedchange) {
                $DB->set_field('local_plugins_plugin', 'timelastapprovedchange', $timelastapprovedchange, ['id' => $plugin->id]);
            }
            if ($approvedby) {
                $DB->set_field('local_plugins_plugin', 'approvedby', $approvedby, ['id' => $plugin->id]);
            }
        }

        $pluginset->close();

        upgrade_plugin_savepoint(true, 2016042007, 'local', 'plugins');
    }

    if ($oldversion < 2016083000) {

        // Add local_plugins_desc table.
        $table = new xmldb_table('local_plugins_desc');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('title', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sortorder', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '999');

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // Add local_plugins_desc_values table.
        $table = new xmldb_table('local_plugins_desc_values');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('descid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('pluginid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('value', XMLDB_TYPE_CHAR, '255', null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_desc', XMLDB_KEY_FOREIGN, array('descid'), 'local_plugins_desc', array('id'));
        $table->add_key('fk_plugin', XMLDB_KEY_FOREIGN, array('pluginid'), 'local_plugins_plugin', array('id'));

        $table->add_index('ix_values', XMLDB_INDEX_NOTUNIQUE, array('value'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2016083000, 'local', 'plugins');
    }

    if ($oldversion < 2016111200) {

        // Add a field "type" to the local_plugins_plugin table.
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL, null, null, 'name');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add a field "notes" to the local_plugins_plugin table.
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('notes', XMLDB_TYPE_TEXT, null, null, null, null, null, 'discussionurl');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2016111200, 'local', 'plugins');
    }

    if ($oldversion < 2016111201) {

        // Create index for the new type column (not unique).
        $table = new xmldb_table('local_plugins_plugin');
        $index = new xmldb_index('ix_type', XMLDB_INDEX_NOTUNIQUE, array('type'));

        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2016111201, 'local', 'plugins');
    }

    if ($oldversion < 2016112500) {

        // Pre-populate the new type column.
        $sql = "SELECT p.id, p.frankenstyle, c.plugintype AS typecat
                  FROM {local_plugins_plugin} p
             LEFT JOIN {local_plugins_category} c ON p.categoryid = c.id";

        foreach ($DB->get_records_sql($sql) as $record) {
            if (empty($record->frankenstyle)) {
                if ($record->typecat !== '-') {
                    throw new moodle_exception('unexpected_categorisation', 'local_plugins', '', null, json_encode($record));
                }
                $DB->set_field('local_plugins_plugin', 'type', '_other_', ['id' => $record->id]);

            } else {
                list($ptype, $pname) = core_component::normalize_component($record->frankenstyle);
                if ($record->typecat !== '-' and $record->typecat !== '' and $record->typecat !== $ptype) {
                    throw new moodle_exception('unexpected_categorisation', 'local_plugins', '', null, json_encode($record));
                }
                $DB->set_field('local_plugins_plugin', 'type', $ptype, ['id' => $record->id]);
            }
        }

        upgrade_plugin_savepoint(true, 2016112500, 'local', 'plugins');
    }

    if ($oldversion < 2017011900) {

        // Add the field approvalissue to the table local_plugins_plugin.
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('approvalissue', XMLDB_TYPE_CHAR, '50', null, null, null, null, 'aggsites');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2017011900, 'local', 'plugins');
    }

    if ($oldversion < 2017021400) {
        // Add table local_plugins_vers_precheck.
        $table = new xmldb_table('local_plugins_vers_precheck');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('versionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('timestart', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeend', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('console', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('debuglog', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('buildurl', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('smurfresult', XMLDB_TYPE_CHAR, '1333', null, null, null, null);
        $table->add_field('smurfxml', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('smurfhtml', XMLDB_TYPE_TEXT, null, null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $table->add_key('fk_local_plugins_vers', XMLDB_KEY_FOREIGN, array('versionid'), 'local_plugins_vers', array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2017021400, 'local', 'plugins');
    }

    if ($oldversion < 2017053000) {
        // Add field moodlever to the table local_plugins_plugin_usage.
        $table = new xmldb_table('local_plugins_plugin_usage');
        $field = new xmldb_field('moodlever', XMLDB_TYPE_CHAR, '20', null, null, null, null, 'year');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2017053000, 'local', 'plugins');
    }

    if ($oldversion < 2018060100) {
        // Drop table local_plugins_vers_download. It was copied over to local_plugins_stats_raw in 2015, we do not need
        // it any more and it contains personal data.

        $table = new xmldb_table('local_plugins_vers_download');

        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        upgrade_plugin_savepoint(true, 2018060100, 'local', 'plugins');
    }

    if ($oldversion < 2018112800) {
        // Add a unique field 'shortname' to the table 'local_plugins_awards'.
        $table = new xmldb_table('local_plugins_awards');
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'name');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $key = new xmldb_key('uq_shortname', XMLDB_KEY_UNIQUE, array('shortname'));
        $dbman->add_key($table, $key);

        // Add a unique field 'shortname' to the table 'local_plugins_set'.
        $table = new xmldb_table('local_plugins_set');
        $field = new xmldb_field('shortname', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'name');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        $key = new xmldb_key('uq_shortname', XMLDB_KEY_UNIQUE, array('shortname'));
        $dbman->add_key($table, $key);

        upgrade_plugin_savepoint(true, 2018112800, 'local', 'plugins');
    }

    if ($oldversion < 2018112802) {

        unset_config('local_plugins_pluginpathredirect');
        unset_config('local_plugins_pathredirect');

        upgrade_plugin_savepoint(true, 2018112802, 'local', 'plugins');
    }

   if ($oldversion < 2019012100) {
        // Add table 'local_plugins_amos_results'.
        $table = new xmldb_table('local_plugins_amos_results');

        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('versionid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('moodlebranch', XMLDB_TYPE_CHAR, '10', null, null, null, null);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('timeresult', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('status', XMLDB_TYPE_INTEGER, '3', null, XMLDB_NOTNULL, null, '0');
        $table->add_field('result', XMLDB_TYPE_TEXT, null, null, null, null, null);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('fk_local_plugins_vers', XMLDB_KEY_FOREIGN, ['versionid'], 'local_plugins_vers', ['id']);

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2019012100, 'local', 'plugins');
    }

    if ($oldversion < 2019012101) {
        // Add field 'statusamos' the table 'local_plugins_plugin'.
        $table = new xmldb_table('local_plugins_plugin');
        $field = new xmldb_field('statusamos', XMLDB_TYPE_INTEGER, '5', null, XMLDB_NOTNULL, null, '0', 'approvalissue');

        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2019012101, 'local', 'plugins');
    }

    if ($oldversion < 2020030500) {
        $DB->set_field_select('local_plugins_set', 'onfrontpage', 1, "shortname IS NOT NULL AND shortname <> ''");
        $DB->set_field_select('local_plugins_awards', 'onfrontpage', 1, "shortname IS NOT NULL AND shortname <> ''");
    }

    return true;
}

<?php
// This file is part of Moodle - http://moodle.org/
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
 * Defines {@link \local_plugins\privacy\provider} class.
 *
 * @package     local_plugins
 * @category    privacy
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy API implementation for the Moodle plugins directory plugin.
 *
 * @copyright  2018 David Mudrák <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\user_preference_provider {

    /**
     * Describe all the places where the Moodle plugins directory plugin stores some personal data.
     *
     * @param collection $collection Collection of items to add metadata to.
     * @return collection Collection with our added items.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table('local_plugins_plugin', [
           'name' => 'privacy:metadata:db:plugin:name',
           'approved' => 'privacy:metadata:db:plugin:approved',
           'timefirstapproved' => 'privacy:metadata:db:plugin:timefirstapproved',
        ], 'privacy:metadata:db:plugin');

        $collection->add_database_table('local_plugins_vers', [
           'pluginid' => 'privacy:metadata:db:vers:pluginid',
           'timecreated' => 'privacy:metadata:db:vers:timecreated',
        ], 'privacy:metadata:db:vers');

        $collection->add_database_table('local_plugins_contributor', [
           'pluginid' => 'privacy:metadata:db:contributor:pluginid',
           'maintainer' => 'privacy:metadata:db:contributor:maintainer',
           'type' => 'privacy:metadata:db:contributor:type',
           'timecreated' => 'privacy:metadata:db:contributor:timecreated',
        ], 'privacy:metadata:db:contributor');

        $collection->add_database_table('local_plugins_set_plugin', [
           'setid' => 'privacy:metadata:db:setplugin:setid',
           'timeadded' => 'privacy:metadata:db:setplugin:timeadded',
        ], 'privacy:metadata:db:setplugin');

        $collection->add_database_table('local_plugins_review', [
           'versionid' => 'privacy:metadata:db:review:versionid',
           'timereviewed' => 'privacy:metadata:db:review:timereviewed',
           'status' => 'privacy:metadata:db:review:status',
        ], 'privacy:metadata:db:review');

        $collection->add_database_table('local_plugins_plugin_awards', [
           'awardid' => 'privacy:metadata:db:pluginawards:awardid',
           'pluginid' => 'privacy:metadata:db:pluginawards:pluginid',
           'timeawarded' => 'privacy:metadata:db:pluginawards:timeawarded',
        ], 'privacy:metadata:db:pluginawards');

        $collection->add_database_table('local_plugins_log', [
           'bulkid' => 'privacy:metadata:db:log:bulkid',
           'time' => 'privacy:metadata:db:log:time',
           'ip' => 'privacy:metadata:db:log:ip',
           'action' => 'privacy:metadata:db:log:action',
           'pluginid' => 'privacy:metadata:db:log:pluginid',
           'info' => 'privacy:metadata:db:log:info',
        ], 'privacy:metadata:db:log');

        $collection->add_database_table('local_plugins_stats_raw', [
           'versionid' => 'privacy:metadata:db:statsraw:versionid',
           'timedownloaded' => 'privacy:metadata:db:statsraw:timedownloaded',
           'downloadmethod' => 'privacy:metadata:db:statsraw:downloadmethod',
           'ip' => 'privacy:metadata:db:statsraw:ip',
           'exclude' => 'privacy:metadata:db:statsraw:exclude',
           'info' => 'privacy:metadata:db:statsraw:info',
        ], 'privacy:metadata:db:statsraw');

        $collection->add_database_table('local_plugins_subscription', [
           'pluginid' => 'privacy:metadata:db:subscription:pluginid',
           'type' => 'privacy:metadata:db:subscription:type',
        ], 'privacy:metadata:db:subscription');

        $collection->add_database_table('local_plugins_usersite', [
           'sitename' => 'privacy:metadata:db:usersite:sitename',
           'siteurl' => 'privacy:metadata:db:usersite:siteurl',
           'version' => 'privacy:metadata:db:usersite:version',
        ], 'privacy:metadata:db:usersite');

        $collection->add_database_table('local_plugins_favourite', [
           'pluginid' => 'privacy:metadata:db:favourite:pluginid',
           'timecreated' => 'privacy:metadata:db:favourite:timecreated',
           'timemodified' => 'privacy:metadata:db:favourite:timemodified',
           'status' => 'privacy:metadata:db:favourite:status',
        ], 'privacy:metadata:db:favourite');

        $collection->add_user_preference('local_plugins_moodle_version', 'privacy:metadata:preference:moodleversion');
        $collection->add_user_preference('local_plugins_plugin_category', 'privacy:metadata:preference:plugincategory');

        $collection->add_subsystem_link('core_comment', [], 'privacy:metadata:subsystem:comment');

        return $collection;
    }

    /**
     * Get the list of contexts that contain personal data for the specified user.
     *
     * @param int $userid ID of the user.
     * @return contextlist List of contexts containing the user's personal data.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {

        $contextlist = new contextlist();
        $contextlist->add_system_context();

        return $contextlist;
    }

    /**
     * Export personal data stored in the given contexts.
     *
     * @param approved_contextlist $contextlist List of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        $syscontextapproved = false;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->id == SYSCONTEXTID) {
                $syscontextapproved = true;
                break;
            }
        }

        if (!$syscontextapproved) {
            return;
        }

        $user = $contextlist->get_user();
        $writer = writer::with_context(\context_system::instance());
        $subcontext = [get_string('pluginname', 'local_plugins')];

        $sql = "SELECT c.id, c.maintainer, c.type, c.timecreated, c.pluginid, p.name
                  FROM {local_plugins_contributor} c
                  JOIN {local_plugins_plugin} p ON c.pluginid = p.id
                 WHERE c.userid = :userid";

        $records = $DB->get_records_sql($sql, ['userid' => $user->id]);
        if ($records) {
            $writer->export_data($subcontext, (object)['contributions' => array_values(array_map(function($record) {
                unset($record->id);
                if ($record->maintainer == 2) {
                    $record->contriblevel = 'maintainer';
                } else if ($record->maintainer == 1) {
                    $record->contriblevel = 'lead_maintainer';
                } else {
                    $record->contriblevel = 'contributor';
                }
                unset($record->maintainer);
                $record->timecreated = transform::datetime($record->timecreated);
                return $record;
            }, $records))]);
            unset($records);
        }

        $records = $DB->get_records('local_plugins_plugin', ['approvedby' => $user->id], '',
            'id, name, approved, timefirstapproved');
        if ($records) {
            $writer->export_related_data($subcontext, 'approvals', array_values(array_map(function($record) {
                unset($record->id);
                $record->timefirstapproved = transform::datetime($record->timefirstapproved);
                return $record;
            }, $records)));
            unset($records);
        }

        $sql = "SELECT v.id, v.pluginid, v.timecreated AS timereleased, v.releasename, v.version, p.name
                 FROM {local_plugins_vers} v
                 JOIN {local_plugins_plugin} p ON v.pluginid = p.id
                WHERE v.userid = :userid";

        $records = $DB->get_records_sql($sql, ['userid' => $user->id]);
        if ($records) {
            $writer->export_related_data($subcontext, 'releases', array_values(array_map(function($record) {
                unset($record->id);
                $record->timereleased = transform::datetime($record->timereleased);
                return $record;
            }, $records)));
            unset($records);
        }

        $records = $DB->get_records('local_plugins_set_plugin', ['userid' => $user->id], '', 'id, setid, timeadded');
        if ($records) {
            $writer->export_related_data($subcontext, 'sets', array_values(array_map(function($record) {
                unset($record->id);
                $record->timeadded = transform::datetime($record->timeadded);
                return $record;
            }, $records)));
            unset($records);
        }

        $records = $DB->get_records('local_plugins_review', ['userid' => $user->id], '', 'id, versionid, timereviewed, status');
        if ($records) {
            $writer->export_related_data($subcontext, 'reviews', array_values(array_map(function($record) {
                unset($record->id);
                $record->timereviewed = transform::datetime($record->timereviewed);
                $record->published = transform::yesno($record->status);
                unset($record->status);
                return $record;
            }, $records)));
            unset($records);
        }

        $records = $DB->get_records('local_plugins_plugin_awards', ['userid' => $user->id], '',
            'id, awardid, pluginid, timeawarded');
        if ($records) {
            $writer->export_related_data($subcontext, 'awards', array_values(array_map(function($record) {
                unset($record->id);
                $record->timeawarded = transform::datetime($record->timeawarded);
                return $record;
            }, $records)));
            unset($records);
        }

        $sql = "SELECT l.id, l.bulkid, l.time, l.ip, l.action, l.pluginid, l.info, p.name
                  FROM {local_plugins_log} l
                  JOIN {local_plugins_plugin} p ON l.pluginid = p.id
                 WHERE l.userid = :userid";

        $records = $DB->get_records_sql($sql, ['userid' => $user->id]);
        if ($records) {
            $writer->export_related_data($subcontext, 'changelog', array_values(array_map(function($record) {
                unset($record->id);
                $record->time = transform::datetime($record->time);
                $record->info = unserialize($record->info);
                return $record;
            }, $records)));
            unset($records);
        }

        $records = $DB->get_records('local_plugins_stats_raw', ['userid' => $user->id], '',
            'id, versionid, timedownloaded, downloadmethod, ip, exclude, info');
        if ($records) {
            $writer->export_related_data($subcontext, 'downloads', array_values(array_map(function($record) {
                unset($record->id);
                $record->timedownloaded = transform::datetime($record->timedownloaded);
                $record->exclude = transform::yesno($record->exclude);
                return $record;
            }, $records)));
            unset($records);
        }

        $sql = "SELECT s.id, s.pluginid, s.type, p.name
                  FROM {local_plugins_subscription} s
                  JOIN {local_plugins_plugin} p ON s.pluginid = p.id
                 WHERE s.userid = :userid";

        $records = $DB->get_records_sql($sql, ['userid' => $user->id]);
        if ($records) {
            $writer->export_related_data($subcontext, 'subscriptions', array_values(array_map(function($record) {
                unset($record->id);
                return $record;
            }, $records)));
            unset($records);
        }

        $records = $DB->get_records('local_plugins_usersite', ['userid' => $user->id], '', 'id, sitename, siteurl, version');
        if ($records) {
            $writer->export_related_data($subcontext, 'sites', array_values(array_map(function($record) {
                unset($record->id);
                return $record;
            }, $records)));
            unset($records);
        }

        $sql = "SELECT f.id, f.pluginid, f.timecreated, f.timemodified, f.status, p.name
                  FROM {local_plugins_favourite} f
                  JOIN {local_plugins_plugin} p ON f.pluginid = p.id
                 WHERE f.userid = :userid";

        $records = $DB->get_records_sql($sql, ['userid' => $user->id]);
        if ($records) {
            $writer->export_related_data($subcontext, 'favourites', array_values(array_map(function($record) {
                unset($record->id);
                $record->favourite = transform::yesno($record->status);
                $record->timecreated = transform::datetime($record->timecreated);
                $record->timemodified = transform::datetime($record->timemodified);
                unset($record->status);
                return $record;
            }, $records)));
            unset($records);
        }

        $sql = "SELECT DISTINCT p.id, p.name
                  FROM {comments} c
                  JOIN {local_plugins_plugin} p ON p.id = c.itemid
                 WHERE contextid = :contextid AND component = :component AND commentarea = :commentarea AND userid = :userid";

        $params = [
            'contextid' => SYSCONTEXTID,
            'component' => 'local_plugins',
            'commentarea' => 'plugin_general',
            'userid' => $user->id,
        ];

        foreach ($DB->get_records_sql($sql, $params) as $plugin) {
            \core_comment\privacy\provider::export_comments(\context_system::instance(), 'local_plugins', 'plugin_general',
                $plugin->id, array_merge($subcontext, [get_string('comments'),
                get_string('privacy:plugin', 'local_plugins', $plugin)]));
        }
    }

    /**
     * Delete personal data for all users in the context.
     *
     * @param context $context Context to delete personal data from.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        // You will probably use some variant of `$DB->delete_records()` here to remove user data from your tables.
        // If you have plugin files, do not forget to clean the relevant files areas too.
    }

    /**
     * Delete personal data for the user in a list of contexts.
     *
     * @param approved_contextlist $contextlist List of contexts to delete data from.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (!count($contextlist)) {
            return;
        }

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);
        $user = $contextlist->get_user();
        $fs = get_file_storage();

        // You will probably use some variant of `$DB->delete_records()` here to remove user data from your tables.
        // If you have plugin files, do not forget to clean the relevant files areas too.
    }

    /**
     * Export all user preferences controlled by this plugin.
     *
     * @param int $userid ID of the user we are exporting data for
     */
    public static function export_user_preferences(int $userid) {

        $moodleversion = get_user_preferences('local_plugins_moodle_version', null, $userid);

        if ($moodleversion !== null) {
            writer::export_user_preference('local_plugins', 'local_plugins_moodle_version', $moodleversion,
                get_string('privacy:metadata:preference:moodleversion', 'local_plugins'));
        }
        $plugincategory = get_user_preferences('local_plugins_plugin_category', null, $userid);

        if ($plugincategory !== null) {
            writer::export_user_preference('local_plugins', 'local_plugins_plugin_category', $plugincategory,
                get_string('privacy:metadata:preference:plugincategory', 'local_plugins'));
        }
    }
}

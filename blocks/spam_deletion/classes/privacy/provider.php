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
 * Defines {@link \block_spam_deletion\privacy\provider} class.
 *
 * @package     block_spam_deletion
 * @category    privacy
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_spam_deletion\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Privacy API implementation for the Spam deletion plugin.
 *
 * @copyright  2018 David Mudrák <david@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
     * Describe all the places where the Spam deletion plugin stores some personal data.
     *
     * @param collection $collection Collection of items to add metadata to.
     * @return collection Collection with our added items.
     */
    public static function get_metadata(collection $collection) : collection {

        $collection->add_database_table('block_spam_deletion_votes', [
           'spammerid' => 'privacy:metadata:db:votes:spammerid',
           'voterid' => 'privacy:metadata:db:votes:voterid',
           'postid' => 'privacy:metadata:db:votes:postid',
           'commentid' => 'privacy:metadata:db:votes:commentid',
           'messageid' => 'privacy:metadata:db:votes:messageid',
           'weighting' => 'privacy:metadata:db:votes:weighting',
        ], 'privacy:metadata:db:votes');

        $collection->add_external_location_link('akismet', [
           'blog' => 'privacy:metadata:external:akismet:blog',
           'user_ip' => 'privacy:metadata:external:akismet:user_ip',
           'user_agent' => 'privacy:metadata:external:akismet:user_agent',
           'referrer' => 'privacy:metadata:external:akismet:referrer',
           'comment_type' => 'privacy:metadata:external:akismet:comment_type',
           'comment_author' => 'privacy:metadata:external:akismet:comment_author',
           'comment_author_email' => 'privacy:metadata:external:akismet:comment_author_email',
           'comment_author_url' => 'privacy:metadata:external:akismet:comment_author_url',
           'comment_content' => 'privacy:metadata:external:akismet:comment_content',
           'blog_lang' => 'privacy:metadata:external:akismet:blog_lang',
           'blog_charset' => 'privacy:metadata:external:akismet:blog_charset',
        ], 'privacy:metadata:external:akismet');

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
        $subcontext = [get_string('pluginname', 'block_spam_deletion')];

        $sql = "SELECT id, spammerid, voterid, postid, commentid, messageid, weighting
                  FROM {block_spam_deletion_votes}
                 WHERE (spammerid = :useridspammer) OR (voterid = :useridvoter)";

        $params = [
            'useridspammer' => $user->id,
            'useridvoter' => $user->id,
        ];

        $votes = $DB->get_records_sql($sql, $params);
        if ($votes) {
            $writer->export_data($subcontext, (object) ['votes' => array_values(array_map(function($record) use ($user) {
                $record->voter_is_you = transform::yesno($record->voterid == $user->id);
                $record->spammer_is_you = transform::yesno($record->spammerid == $user->id);
                unset($record->id);
                unset($record->spammerid);
                unset($record->voterid);
                return $record;
            }, $votes))]);
            unset($votes);
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
}

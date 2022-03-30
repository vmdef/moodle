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
 * Detect if the submitted comment is a block and refuse it eventually.
 *
 * Based on the original script (c) 2013 Dan Poltawski
 *
 * @package     block_spam_deletion
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Check if there is a comment submitted in this request.
if (!optional_param('content', null, PARAM_RAW) || !optional_param('action', null, PARAM_ALPHA)) {
    return;
}

// Return early for site admins, guest users and visitors (e.g. posting after the session has expired).
if (empty($USER->id) || isguestuser() || is_siteadmin()) {
    return;
}

$message = required_param('content', PARAM_RAW);

// Most of our comments are supposed to be in English (typically those in the Plugins directory).
$expectedlang = 'en';

// Load the plugin configuration.
$cfg = get_config('block_spam_deletion');

// Perform the Akismet check.
if (!empty($cfg->akismet_key) and !empty($cfg->akismet_account_age)) {
    if ($USER->firstaccess > (time() - $cfg->akismet_account_age)) {
        $akismet = new \block_spam_deletion\akismet($cfg->akismet_key);
        if ($akismet->is_user_posting_spam($message, $expectedlang)) {
            block_spam_deletion_block_comment_and_die($message, 'A');
        }
    }
}

// Are there some non-spammy comments older than the configured duration?
$params = array('userid' => $USER->id, 'timestamp' => (time() - $cfg->firstcomments_duration));
$countoldcomments = $DB->count_records_select("comments", "userid = :userid AND timecreated < :timestamp", $params);

// If so, stop here, they've got some non-spammy comments.
if ($countoldcomments) {
    return;
}

// Perform first comments spam detections.
$detector = new \block_spam_deletion\detector($cfg);

// Check number of unique URLs in the comment.
if (count($detector->find_external_urls($message)) > $cfg->commentlinks_count) {
    block_spam_deletion_block_comment_and_die($message, 'L');
}

// Check presence of blacklisted words in the comment.
if ($detector->contains_bad_words($message)) {
    block_spam_deletion_block_comment_and_die($message, 'F');
}

// Check the comments count limit.
if (!empty($cfg->throttle_commentcount) and !empty($cfg->throttle_comment_duration)) {
    $params = array('userid' => $USER->id, 'timestamp' => (time() - $cfg->throttle_comment_duration));
    $countrecentcomments = $DB->count_records_select("comments", "userid = :userid AND timecreated > :timestamp", $params);

    if ($countrecentcomments >= $cfg->throttle_commentcount) {
        block_spam_deletion_block_comment_and_die($message, 'C');
    }
}

// Okay, this seems to be ham - process the comment normally.
return;


/**
 * Print a 'friendly' error message informing the user their comment has been blocked and die.
 *
 * @param text $submittedcontent the content which was blocked from posting
 * @param text $errorcode for debugging
 */
function block_spam_deletion_block_comment_and_die($submittedcontent, $errorcode) {
    global $PAGE, $OUTPUT, $SITE, $CFG;

    // Obfuscate the error code a bit.
    $errorcode = random_string(4).$errorcode;

    // Record count of blocked comments and suspend account if necessary.
    $accountsuspended = block_spam_deletion_record_blocked_comment($errorcode);

    if (optional_param('client_id', null, PARAM_ALPHANUM)) {
        // The comment coming via AJAX.
        if ($accountsuspended) {
            $message = get_string('accountsuspended', 'block_spam_deletion');
        } else {
            $message = get_string('commentblocked', 'block_spam_deletion', [
                'errorcode' => $errorcode,
                'comment' => s($submittedcontent),
            ]);
        }

        echo json_encode(array('error' => $message));
        die();

    } else {
        $PAGE->set_context(context_system::instance());
        $PAGE->set_url('/');
        $PAGE->set_title(get_string('error'));
        $PAGE->set_heading($SITE->fullname);

        echo $OUTPUT->header();
        if ($accountsuspended) {
            echo $OUTPUT->heading(get_string('accountsuspendedtitle', 'block_spam_deletion'));
            echo $OUTPUT->box(get_string('accountsuspended', 'block_spam_deletion'));
        } else {
            echo $OUTPUT->heading(get_string('messageblockedtitle', 'block_spam_deletion'));
            echo $OUTPUT->box(get_string('commentblocked', 'block_spam_deletion', [
                'errorcode' => $errorcode,
                'comment' => html_writer::tag('pre', s($submittedcontent), array('class' => 'notifytiny')),
            ]));
        }
        echo $OUTPUT->footer();
        die();
    }
}

/**
 * Record a user has had their comment blocked - and suspend them if necessary.
 *
 * @param string $errorcode
 * @return bool true if user is suspended (on final chance)
 */
function block_spam_deletion_record_blocked_comment($errorcode) {
    global $USER, $DB;

    $blockcount = get_user_preferences('block_spam_deletion_blocked_comments_count', 0);
    $blockcount++;

    $limit = get_config('block_spam_deletion', 'autosuspend_comment_count');

    // If the limit of blocked comments is reached, suspend the account automatically.
    if ($limit and $blockcount > $limit) {
        // Remove blockcount preference in case they get un-suspended.
        set_user_preference('block_spam_deletion_blocked_comments_count', null);

        // Suspend the user and update their profile description.
        $updateuser = new stdClass();
        $updateuser->id = $USER->id;
        $updateuser->suspended = 1;
        $updateuser->description = get_string('blockedspamusersuspendedaccount', 'block_spam_deletion', [
            'datetime' => date('l jS F g:i A'),
            'errorcode' => s($errorcode),
        ]);
        $DB->update_record('user', $updateuser);

        // Kill the users session.
        \core\session\manager::kill_user_sessions($USER->id);

        return true;

    } else {
        set_user_preference('block_spam_deletion_blocked_comments_count', $blockcount);
        return false;
    }
}

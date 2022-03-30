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
 * Script to detect a spammy post and block it from being posted.
 *
 * The script is supposed to be included from config.php (see README) and it
 * should either return or kill the current request processing.
 *
 * @package    block_spam_deletion
 * @copyright  2013 Dan Poltawski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// Check if a forum post has been submitted in this request.
if (!optional_param('_qf__mod_forum_post_form', 0, PARAM_BOOL)) {
    return;
}

// Return early for site admins, guest users and visitors (e.g. posting after the session has expired).
if (empty($USER->id) || isguestuser() || is_siteadmin()) {
    return;
}

$postsubject = optional_param('subject', null, PARAM_RAW);
$postcontent = optional_param_array('message', array(), PARAM_RAW);
if (!isset($postcontent['text'])) {
    return;
}
$message = $postsubject."\n".$postcontent['text'];

// Get know the expected language of the post.
$courseid = optional_param('course', SITEID, PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), 'lang', MUST_EXIST);
$expectedlang = empty($course->lang) ? $CFG->lang : $course->lang;

// Load the plugin configuration.
$cfg = get_config('block_spam_deletion');

// Perform the Akismet check.
if (!empty($cfg->akismet_key) and !empty($cfg->akismet_account_age)) {
    if ($USER->firstaccess > (time() - $cfg->akismet_account_age)) {
        $akismet = new \block_spam_deletion\akismet($cfg->akismet_key);
        if ($akismet->is_user_posting_spam($message, $expectedlang)) {
            block_spam_deletion_block_post_and_die($message, 'A');
        }
    }
}

// Are there some non-spammy posts older than the configured duration?
$params = array('userid' => $USER->id, 'timestamp' => (time() - $cfg->firstposts_duration));
$countoldposts = $DB->count_records_select("forum_posts", "userid = :userid AND created < :timestamp", $params);

// If so, stop here, they've got some non-spammy posts.
if ($countoldposts) {
    return;
}

// Perform first posts spam detections.
$detector = new \block_spam_deletion\detector($cfg);

// Check number of unique URLs in the post.
if (count($detector->find_external_urls($message)) > $cfg->links_count) {
    block_spam_deletion_block_post_and_die($message, 'L');
}

// Check presence of blacklisted words in the post.
if ($detector->contains_bad_words($message)) {
    block_spam_deletion_block_post_and_die($message, 'F');
}

// Check the number of unexpected characters for the given course.
if (!empty($cfg->invalidchars_percentage) and $cfg->invalidchars_percentage < 100) {
    $oldcharset = get_string_manager()->get_string('oldcharset', 'langconfig', null, $expectedlang);

    if (!empty($oldcharset)) {
        if ($detector->invalid_char_percent($message, $oldcharset) > $cfg->invalidchars_percentage) {
            block_spam_deletion_block_post_and_die($message, 'X');
        }
    }
}

// Check the posts count limit.
if (!empty($cfg->throttle_postcount) and !empty($cfg->throttle_duration)) {
    $params = array('userid' => $USER->id, 'timestamp' => (time() - $cfg->throttle_duration));
    $countrecentposts = $DB->count_records_select("forum_posts", "userid = :userid AND created > :timestamp", $params);

    if ($countrecentposts >= $cfg->throttle_postcount) {
        block_spam_deletion_block_post_and_die($message, 'C');
    }
}

// Okay, this seems to be ham - process the forum post normally.
return;


/**
 * Print a 'friendly' error message informing the user their post has been blocked and die.
 *
 * It sucks a bit that we die() becase the user can't easily edit their post if they are real, but
 * this seems to be the best way to make it clear.
 *
 * @param text $submittedcontent the content which was blocked from posting
 * @param text $errorcode for debugging
 */
function block_spam_deletion_block_post_and_die($submittedcontent, $errorcode) {
    global $PAGE, $OUTPUT, $SITE, $CFG;

    // Obfuscate the error code a bit.
    $errorcode = random_string(4).$errorcode;

    // Record count of blocked posts and suspend account if necessary.
    $accountsuspended = block_spam_deletion_record_blocked_post($errorcode);

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
        echo $OUTPUT->box(get_string('messageblockederror', 'block_spam_deletion', [
            'errorcode' => $errorcode,
        ]));
    }
    echo $OUTPUT->box(html_writer::tag('pre', s($submittedcontent), array('class' => 'notifytiny m-y-2')));
    echo $OUTPUT->footer();
    die;
}

/**
 * Record a user has had their post blocked - and suspend them if necessary.
 *
 * @param string $errorcode
 * @return bool true if user is suspended (on final chance)
 */
function block_spam_deletion_record_blocked_post($errorcode) {
    global $USER, $DB;

    $blockcount = get_user_preferences('block_spam_deletion_blocked_posts_count', 0);
    $blockcount++;

    $limit = get_config('block_spam_deletion', 'autosuspend_count');

    // If the limit of blocked posts is reached, suspend the account automatically.
    if ($limit and $blockcount > $limit) {
        // Remove blockcount preference in case they get un-suspended.
        set_user_preference('block_spam_deletion_blocked_posts_count', null);

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
        set_user_preference('block_spam_deletion_blocked_posts_count', $blockcount);
        return false;
    }
}

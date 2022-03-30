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
 * Spam deletion block settings
 *
 * @package    block_spam_deletion
 * @copyright  2014 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Common settings.

    $settings->add(new admin_setting_heading('block_spam_deletion/common_heading',
        get_string('commonsettings', 'block_spam_deletion'),
        get_string('commonsettings_desc', 'block_spam_deletion')
    ));

    $settings->add(new admin_setting_description('block_spam_deletion/common_errorcodes',
        get_string('errorcodes', 'block_spam_deletion'),
        get_string('errorcodes_desc', 'block_spam_deletion')
    ));

    $settings->add(new admin_setting_configtextarea('block_spam_deletion/badwords',
        get_string('badwordslist', 'block_spam_deletion'),
        get_string('badwordslistdesc', 'block_spam_deletion'),
        get_string('badwords', 'block_spam_deletion')
    ));

    $settings->add(new admin_setting_configtextarea('block_spam_deletion/links_whitelist',
        get_string('linkswhitelist', 'block_spam_deletion'),
        get_string('linkswhitelist_desc', 'block_spam_deletion'),
        implode("\n", array_unique(array(
            $CFG->wwwroot,
            'https://moodle.org',
            'http://dev.moodle.org',
            'https://download.moodle.org',
            'https://tracker.moodle.org',
            'https://lang.moodle.org',
            'https://learn.moodle.net',
        )))
    ));

    // First posts settings.

    $settings->add(new admin_setting_heading('block_spam_deletion/firstposts_heading',
        get_string('firstpostssettings', 'block_spam_deletion'),
        get_string('firstpostssettings_desc', 'block_spam_deletion')
    ));

    $settings->add(new admin_setting_configduration('block_spam_deletion/firstposts_duration',
        get_string('firstpostsduration', 'block_spam_deletion'),
        get_string('firstpostsduration_desc', 'block_spam_deletion'),
        DAYSECS
    ));

    $settings->add(new admin_setting_configtext('block_spam_deletion/links_count',
        get_string('linkscount', 'block_spam_deletion'),
        get_string('linkscount_desc', 'block_spam_deletion'),
        2,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext('block_spam_deletion/throttle_postcount',
        get_string('postthrottlecount', 'block_spam_deletion'),
        get_string('postthrottlecountdesc', 'block_spam_deletion'),
        4,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configduration('block_spam_deletion/throttle_duration',
        get_string('postthrottleduration', 'block_spam_deletion'),
        get_string('postthrottledurationdesc', 'block_spam_deletion'),
        HOURSECS
    ));

    $settings->add(new admin_setting_configtext('block_spam_deletion/invalidchars_percentage',
        get_string('invalidcharspercentage', 'block_spam_deletion'),
        get_string('invalidcharspercentagedesc', 'block_spam_deletion'),
        25,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext('block_spam_deletion/autosuspend_count',
        get_string('autosuspendcount', 'block_spam_deletion'),
        get_string('autosuspendcount_desc', 'block_spam_deletion'),
        3,
        PARAM_INT
    ));

    // First comments settings.

    $settings->add(new admin_setting_heading('block_spam_deletion/firstcomments_heading',
        get_string('firstcommentssettings', 'block_spam_deletion'),
        get_string('firstcommentssettings_desc', 'block_spam_deletion')
    ));

    $settings->add(new admin_setting_configduration('block_spam_deletion/firstcomments_duration',
        get_string('firstcommentsduration', 'block_spam_deletion'),
        get_string('firstcommentsduration_desc', 'block_spam_deletion'),
        DAYSECS
    ));

    $settings->add(new admin_setting_configtext('block_spam_deletion/commentlinks_count',
        get_string('commentlinkscount', 'block_spam_deletion'),
        get_string('commentlinkscount_desc', 'block_spam_deletion'),
        2,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext('block_spam_deletion/throttle_commentcount',
        get_string('commentthrottlecount', 'block_spam_deletion'),
        get_string('commentthrottlecountdesc', 'block_spam_deletion'),
        4,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configduration('block_spam_deletion/throttle_comment_duration',
        get_string('commentthrottleduration', 'block_spam_deletion'),
        get_string('commentthrottledurationdesc', 'block_spam_deletion'),
        HOURSECS
    ));

    $settings->add(new admin_setting_configtext('block_spam_deletion/autosuspend_comment_count',
        get_string('autosuspendcountcomment', 'block_spam_deletion'),
        get_string('autosuspendcountcomment_desc', 'block_spam_deletion'),
        3,
        PARAM_INT
    ));


    $settings->add(new admin_setting_heading('block_spam_deletion/akismet_heading',
        get_string('akismetsettings', 'block_spam_deletion'),
        ''
    ));

    $settings->add(new admin_setting_configtext('block_spam_deletion/akismet_key',
        get_string('akismetkey', 'block_spam_deletion'),
        '',
        ''
    ));

    $settings->add(new admin_setting_configduration('block_spam_deletion/akismet_account_age',
        get_string('akismetaccountage', 'block_spam_deletion'),
        get_string('akismetaccountagedesc', 'block_spam_deletion'),
        HOURSECS
    ));
}

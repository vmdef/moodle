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
 * Defines message providers (types of messages being sent) for the plugins plugin.
 *
 * @package local_plugins
 * @copyright  2012 onwards  Aparup Banerjee  http://moodle.com
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

//require_once($CFG->dirroot.'/local/plugins/lib/base.class.php');

$messageproviders = array (
    // Notifications related to the approval process.
    'registration' => array ( //local_plugins::NOTIFY_REGISTRATION
        'capability'  => 'local/plugins:notifiedunapprovedactivity', //local_plugins::CAP_NOTIFIEDUNAPPROVEDACTIVITY
        'defaults' => array(
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDOFF,
        ),
    ),
    // Availability change notification (for contributors) : "Approval notification"
    'availability' => array ( //local_plugins::NOTIFY_AVAILABILITY,
        'capability' => 'local/plugins:view', //local_plugins::CAP_VIEW
    ),
    // Change to version release information notifications. (subscribable)
    'version' => array( //local_plugins::NOTIFY_VERSION,
        'capability' => 'local/plugins:view', //local_plugins::CAP_VIEW
    ),
    // Comment notification. (subscribable)
    'comment' => array ( //local_plugins::NOTIFY_COMMENT,
        'capability' => 'local/plugins:comment', //local_plugins::CAP_COMMENT
    ),
    // Notifications for a contributor.
    'contributor' => array( //local_plugins::NOTIFY_CONTRIBUTOR,
        'capability'  => 'local/plugins:createplugins', //local_plugins::CAP_CREATEPLUGINS,
        'defaults' => array(
            'popup' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_LOGGEDIN + MESSAGE_DEFAULT_LOGGEDOFF,
        ),
    ),
    // Notifications about reviews. (subscribable)
    'review' => array ( //local_plugins::NOTIFY_REVIEW,
        'capability' => 'local/plugins:view', //local_plugins::CAP_VIEW
    ),
    // Notifications about awards. (subscribable)
    'award' => array ( //local_plugins::NOTIFY_AWARD,
        'capability' => 'local/plugins:view', //local_plugins::CAP_VIEW
    )
);

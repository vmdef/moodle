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
 * Provides some callbacks and interfaces for the moodle core.
 *
 * @package     local_moodleorg
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extends the settings navigation (admin block).
 *
 * @param settings_navigation $settings
 * @return bool
 */
function local_moodleorg_extend_settings_navigation(settings_navigation $settings, context $context) {
    global $CFG;

    if (!empty($CFG->block_partners_downloads_ads)) {
        // We are not on moodle.org, so disable links.
        return;
    }

    $context = context_system::instance();

    if (has_capability('local/moodleorg:manageads', $context)) {
        $settings->add(
            get_string('manageads', 'local_moodleorg'),
            new moodle_url('/local/moodleorg/admin/ads.php')
        );
    }

    if (has_capability('local/moodleorg:managedonations', $context)) {
        $settings->add(
            get_string('managedonations', 'local_moodleorg'),
            new moodle_url('/local/moodleorg/admin/donations.php')
        );
    }
}

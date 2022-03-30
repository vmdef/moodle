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
 * This script was used in MDLSITE-6095 to generate the user pictures.
 *
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');

$PAGE->set_url('/local/moodleorg/admin/userpics.php');
$PAGE->set_context(context_system::instance());

require_capability('moodle/site:config', context_system::instance());

$sql = "SELECT DISTINCT u.id, u.picture, u.imagealt, u.firstname, u.lastname, u.firstnamephonetic, u.lastnamephonetic, u.middlename, u.alternatename, u.email
          FROM {user} u
          JOIN {cohort_members} cm ON cm.userid = u.id
          JOIN {cohort} c ON cm.cohortid = c.id
         WHERE u.deleted = 0
           AND u.picture > 0";

$users = $DB->get_records_sql($sql);

echo $OUTPUT->header();

foreach ($users as $user) {
    echo $OUTPUT->user_picture($user);
}

echo $OUTPUT->footer();

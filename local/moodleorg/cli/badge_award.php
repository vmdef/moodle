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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Award badge to users.
 *
 * @package     local_moodleorg
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/badges/lib/awardlib.php');

$usage = "Award badge to users.

Usage:
    # php badge_award.php --badgeid=<badgeid> --issuerid=<issuerid> --issuerrole=<issuerrole> \
    #                     --recipientid=<recipientid>[,<recipientid>...]
    # php badge_award.php [--help|-h]

Options:
    -h --help                                           Print this help.
    --badgeid=<badgeid>                                 Badge record id.
    --issuerid=<issuerid>                               Award badge on behalf of user with this id.
    --issuerrole=<issuerrole>                           Role id (if numeric) or shortname. Defaults to 'badgeawarder'.
    --recipientid=<recipientid>[,<recipientid>...]      List of user ids to receive the badge.
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'badgeid' => null,
    'issuerid' => null,
    'issuerrole' => 'badgeawarder',
    'recipientid' => null,
], [
    'h' => 'help'
]);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL . '  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

$badgeid = clean_param($options['badgeid'], PARAM_INT);
$issuerid = clean_param($options['issuerid'], PARAM_INT);
$recipientids = array_filter(clean_param_array(array_map('trim', explode(',', $options['recipientid'])), PARAM_INT));

if (empty($badgeid) || empty($issuerid) || empty($recipientids)) {
    cli_writeln($usage);
    cli_error('Missing mandatory argument.', 2);
}

$badge = new badge($badgeid);

if (!$badge->is_active()) {
    cli_error('Badge not active', 3);
}

if (is_numeric($options['issuerrole'])) {
    $role = $DB->get_record('role', ['id' => $options['issuerrole']]);
} else {
    $role = $DB->get_record('role', ['shortname' => $options['issuerrole']]);
}

if (empty($role)) {
    cli_writeln($usage);
    cli_error('No such role found.');
}

foreach ($recipientids as $recipientid) {
    if (!$DB->record_exists('user', ['id' => $recipientid, 'deleted' => 0])) {
        cli_problem('Recipient ' . $recipientid . ' not found or is deleted, skipping.');
        continue;
    }

    if (process_manual_award($recipientid, $issuerid, $role->id, $badgeid)) {
        // If badge was successfully awarded, review manual badge criteria.
        $data = new stdClass();
        $data->crit = $badge->criteria[BADGE_CRITERIA_TYPE_MANUAL];
        $data->userid = $recipientid;
        badges_award_handle_manual_criteria_review($data);
        cli_writeln('Badge awarded to recipientid ' . $recipientid);

    } else {
        cli_problem('Unable to award badge to recipientid ' . $recipientid);
        continue;
    }
}

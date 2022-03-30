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
 * Add members to the given cohort.
 *
 * @package     local_moodleorg
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->dirroot . '/cohort/lib.php');

$usage = "Add given users as members of the given cohort.

Usage:
    # php cohort_add_members.php --cohortid=<cohortid> --userid=<userid>[,userid,userid,...]
    # php cohort_add_members.php [--help|-h]

Options:
    -h --help                               Print this help.
    --cohortid=<cohortid>                   Identifier of the cohort (record id).
    --userid=<userid>[,userid,userid,...]   Comma-separated list of user identifiers (record id).
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
    'cohortid' => null,
    'userid' => null,
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

$cohortid = clean_param($options['cohortid'], PARAM_INT);
$userids = array_filter(clean_param_array(array_map('trim', explode(',', $options['userid'])), PARAM_INT));

if (empty($cohortid) || empty($userids)) {
    cli_writeln($usage);
    cli_error('Missing mandatory argument.', 2);
}

foreach ($userids as $userid) {
    cohort_add_member($cohortid, $userid);
}

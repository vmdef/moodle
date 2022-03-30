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
 * Provides the {@link \local_moodleorg\task\update_phm} class.
 *
 * @package     local_moodleorg
 * @category    task
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodleorg\task;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/moodleorg/locallib.php');

/**
 * Populate the Particular helpful Moodlers cohort
 *
 * The task is looking for new Particularly helpful Moodlers (PHM) among
 * the authors of posts in forums. The forum must use
 * one of mapped scales for rating in order to be searched.
 *
 * Based on original CLI script (c) 2012 Dan Poltawski <dan@moodle.com>
 *
 * @copyright 2018 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_phm extends \core\task\scheduled_task {

    /**
     * Return the task's name
     *
     * @return string
     */
    public function get_name() {
        // This is for internal usage only, no need to localize.
        return 'Update Particular helpful Moodlers cohort members';
    }

    /**
     * Execute the actual task
     */
    public function execute() {
        global $CFG;

        // Check if the PHM badge is configured in the config.php file.
        // The id of the badge for the given year should be stored in a setting like
        // `$CFG->local_moodleorg_phm2016badgeid = 22;`.
        $badgecfg = 'local_moodleorg_phm'.date('Y').'badgeid';

        if (!isset($CFG->{$badgecfg})) {
            mtrace(sprintf("No PHM badge configured to be automatically awarded - define \$CFG->%s in the config.php", $badgecfg));
            return;
        }

        mtrace("Generating the list of particularly helpful moodlers ...");

        $phms = local_moodleorg_get_phms(['verbose' => false]);

        // Remove users who have been abusive and put on a blacklist: Dawn Alderson.
        if (isset($phms[1674524])) {
            unset($phms[1674524]);
        }

        mtrace("Updating the PHM cohort members ...");

        $cohortmanager = new \local_moodleorg_phm_cohort_manager();

        foreach ($phms as $userid => $phmdetails) {
            $cohortmanager->add_member($userid);
        }

        $oldmembers = $cohortmanager->old_users();
        $newmembers = $cohortmanager->new_users();

        mtrace(sprintf("Removing %d old members %s", count($oldmembers), implode(',', array_keys($oldmembers))));
        mtrace(sprintf("Adding %d new members %s", count($newmembers), implode(',', array_keys($newmembers))));

        $cohortmanager->remove_old_users();

        // Notify community managers.
        local_moodleorg_notify_phm_cohort_status($phms, $newmembers, $oldmembers);

        $badgeid = $CFG->{$badgecfg};
        mtrace(sprintf("Automatically awarding badge id %d", $badgeid));
        $newlyawarded = $cohortmanager->award_badge($badgeid);
        if (empty($newlyawarded)) {
            mtrace(" ... no new users to award the PHM badge to");
        } else {
            mtrace(sprintf("Badge succesfully awarded to %d new users", count($newlyawarded)));
        }
    }
}

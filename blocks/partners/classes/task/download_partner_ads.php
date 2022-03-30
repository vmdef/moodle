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
 * A scheduled task for updating partner advert data.
 *
 * @package    block_partners
 * @copyright  2014 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_partners\task;

/**
 * A scheduled task for updating partner advert data.
 *
 * @package    block_partners
 * @copyright  2014 Andrew Davis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class download_partner_ads extends \core\task\scheduled_task {
    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('downloadpartnerads', 'block_partners');
    }

    /**
     * Run partner ad update. This simply deletes the old data and inserts the new.
     */
    public function execute() {
        global $CFG, $DB;

        if (empty($CFG->block_partners_downloads_ads)) {
            mtrace('block_partners_downloads_ads is disabled but its scheduled task is enabled');
            return;
        }
        if (empty($CFG->block_partners_ad_url)
            || empty($CFG->block_partners_ad_token)) {

            mtrace('Partner ad downloading is enabled but not configured');
            return;
        }

        // Only get partner countries when they don't exist (lots of data which doesn't
        // change much).
        if ($DB->count_records('block_partners_countries') == 0) {
            mtrace('Refreshing partner countries');
            $countries = $this->call_ad_webservice('local_moodleorg_get_countries');
            if (!empty($countries) && count($countries) > 0) {
                $DB->insert_records('block_partners_countries', $countries);
                mtrace ('countires inserted');
            } else {
                mtrace ('No countries found');
            }
        } else {
            mtrace('Skipping the countries table as it is already populated');
        }

        mtrace('Refreshing partner ads');
        $ads = $this->call_ad_webservice('local_moodleorg_get_partner_ads');
        if (!empty($ads) && count($ads) > 0) {
            $transaction = $DB->start_delegated_transaction();
            $DB->delete_records('block_partners_ads');
            $DB->insert_records('block_partners_ads', $ads);
            $transaction->allow_commit();
            mtrace ('partner ads updated');
        } else {
            mtrace ('No partner ads found');
        }
    }

    /**
     * Call a webservice function with configured url/token
     *
     * @param string $functionname the name of the webservice function
     * @return array json_decoded response
     */
    private function call_ad_webservice($functionname) {
        global $CFG;

        $token = $CFG->block_partners_ad_token;
        $domainname = $CFG->block_partners_ad_url;
        $params = array();
        $serverurl = "$domainname/webservice/rest/server.php?wstoken=$token&wsfunction=$functionname&moodlewsrestformat=json";

        require_once($CFG->libdir . '/filelib.php');
        $curl = new \curl();
        $response = $curl->post($serverurl, $params);
        $response = json_decode($response);
        return $response;
    }
}

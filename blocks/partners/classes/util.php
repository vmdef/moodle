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
 * Partner block utilities
 *
 * @package   block_partners
 * @copyright 2015 Dan Poltawski <dan@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_partners;

use stdClass;

defined('MOODLE_INTERNAL') || die();

class util {
    /** @var string Name of the countries table */
    protected $countrytable = 'block_partners_countries';
    /** @var string Name of the adverts table */
    protected $adtable = 'block_partners_ads';

    /**
     * Constructor.
     */
    public function __construct() {
        global $CFG;

        // Use legacy local_moodleorg's tables if the admin has turned block_partners_downloads_ads off - this
        // only really applies to moodle.org itself. Everything else fetches from moodle.org and uses the block
        // tables.

        if (empty($CFG->block_partners_downloads_ads)) {
            $this->countrytable = 'countries';
            $this->adtable = 'register_ads';
        }
    }

    /**
     * Gets countrycode based on ip address of request, or $USER->country.
     * Also checks CloudFlare IP if available.
     * Finally checks for territory country mappings.
     *
     * @return string|false countrycode detected
     */
    public function get_detected_countrycode() {
        global $USER, $DB;

        $country = false;

        if (!empty($USER->country)) {
            $country = $USER->country;
        } else if (isset($_SERVER["HTTP_CF_IPCOUNTRY"])) {
            $country = $_SERVER["HTTP_CF_IPCOUNTRY"];
        } else {
            $ipaslong = ip2long($_SERVER['REMOTE_ADDR']);

            if ($ipaslong === false) {
                // Invalid IP (TODO: ipv6 MDLSITE-3780).
                return false;
            }

            if ($newcountry = $DB->get_field_select($this->countrytable, 'code2',
                'ipfrom <= ? AND ? <= ipto', array($ipaslong, $ipaslong))) {
                $country = $newcountry;
            }
        }

        // Final check through partner territories.
        if ($country != false) {
            $country = $this->check_territory($country);
        }

        return $country;
    }

    /**
     * Checks if countrycode is in a Partner territory.
     *
     * @return string
     */
    public function check_territory($country) {
        
        $territorygroups = array(
        // Caribbean group countries.
            "AG" => "CB",
            "AI" => "CB",
            "BQ" => "CB",
            "AW" => "CB",
            "BB" => "CB",
            "BL" => "CB",
            "BS" => "CB",
            "CU" => "CB",
            "DM" => "CB",
            "DO" => "CB",
            "GD" => "CB",
            "GP" => "CB",
            "HT" => "CB",
            "JM" => "CB",
            "KN" => "CB",
            "KY" => "CB",
            "LC" => "CB",
            "MF" => "CB",
            "MQ" => "CB",
            "MS" => "CB",
            "PR" => "CB",
            "TC" => "CB",
            "TT" => "CB",
            "VC" => "CB",
            "VG" => "CB",
            "VI" => "CB",
            "CW" => "CB",
            "SX" => "CB"
        );
        
        if (array_key_exists($country, $territorygroups)) {
            $country = $territorygroups[$country];
        }
        
        return $country;
    }

    /**
     * Gets advert records for specified country (or none).
     *
     * If $mimumumresults is specified, then the adverts will be adverts
     * from different countries (randomly).
     *
     * @param string $countycode countrycode of adverts to retrieve
     * @param int $mimumum Minimum number of adverts to fill. If not reached,
     *      adverts will be padded with non-country ads.
     * @return array advert records
     */
    public function get_ads($countrycode = null, $mimumum = 0) {
        global $DB;

        if ($countrycode === null) {
            return $DB->get_records($this->adtable);
        }

        $adverts = $DB->get_records($this->adtable, array('country' => $countrycode));
        shuffle($adverts);
        $advertcount = count($adverts);

        if ($advertcount >= $mimumum) {
            return $adverts;
        }

        // Fill remaining advert slots.
        $noncountryadverts = $DB->get_records_select($this->adtable, 'country != ? AND country != ?', array($countrycode, 'XX'));
        shuffle($noncountryadverts);

        do {
            if (empty($noncountryadverts)) {
                debugging("Not enough adverts to fill requested slots ($mimumum)", DEBUG_DEVELOPER);
                break;
            }
            $adverts[] = array_pop($noncountryadverts);
            $advertcount++;
        } while($advertcount < $mimumum);

        return $adverts;
    }

    /**
     * Returns the list of partner ads record for displaying at the moodle.org front page
     *
     * This is similar to the {@link self::get_ads()} method with the
     * difference that moodle.com and moots ads are not included. Only real
     * partners' logos should.
     *
     * @param int $count number of ads to return
     * @param string $countycode countrycode of adverts to retrieve, defaults to auto-detect
     * @return array advert records with ->link and ->imgsrc properties added
     */
    public function get_grey_ads($count = 6, $countrycode = null) {
        global $CFG, $DB;

        if ($count <= 0) {
            return [];
        }

        if ($countrycode === null or $countrycode === 'XX') {
            // Detect the user's country - moodle.org front page is always country specific.
            $countrycode = $this->get_detected_countrycode();
        }

        // Load all country-specific ads and randomize it.
        $found = $DB->get_records($this->adtable, array('country' => $countrycode));
        shuffle($found);

        // Populate the list of suitable ads, that is real partners' logos, not moodle.com or moots ads.
        $suitable = [];
        // Track partners' logos to prevent duplicates across multiple territories.
        $pending = [];
        foreach ($found as $ad) {
            if ($this->suitable_grey_ad($ad)) {
                // Tracking ad title first word, eg Moodlerooms or Webanywhere.
                $newad = explode(' ', trim($ad->title))[0];
                if (!in_array($newad, $pending)) {
                    $suitable[] = $ad;
                    $pending[] = $newad;
                }
                if (count($suitable) == $count) {
                    // We have enough, no need to check others.
                    return $suitable;
                }
            }
        }

        // Fill remaining advert slots.
        $noncountryadverts = $DB->get_records_select($this->adtable, 'country != ? AND country != ?', [$countrycode, 'XX']);
        shuffle($noncountryadverts);

        foreach ($noncountryadverts as $ad) {
            if ($this->suitable_grey_ad($ad)) {
                // Tracking ad title first word, eg Moodlerooms or Webanywhere.
                $newad = explode(' ', trim($ad->title))[0];
                if (!in_array($newad, $pending)) {
                    $suitable[] = $ad;
                    $pending[] = $newad;
                }
                if (count($suitable) == $count) {
                    // We have enough, no need to check others.
                    return $suitable;
                }
            }
        }

        // This should not really happen - we still do not have enough ads. For
        // the purpose of the front page, it is better to return empty list
        // than less than requested as the different number could break the
        // design.
        return [];
    }

    /**
     * Check if the given ads table record can be displayed as a grey logo
     *
     * @param stdClass $ad
     * @return bool success status - note the passed record is extended with new properties
     */
    protected function suitable_grey_ad(stdClass $ad) {
        global $CFG;

        if ($ad->partner === 'moodle' or strpos($ad->partner, 'moot') === 0) {
            return false;
        }

        if (strpos($ad->partner, 'https://') === 0 or strpos($ad->partner, 'http://') === 0) {
            // This is most likely an ad for some moot or other service (like cloud).
            return false;
        }

        if (file_exists($CFG->dirroot.'/blocks/partners/image/'.$ad->image.'/logo-grey.png')) {
            $ad->imgsrc = $CFG->wwwroot.'/blocks/partners/image/'.urlencode($ad->image).'/logo-grey.png';

        } else {
            $ad->imgsrc = 'https://partners.moodle.com/image/'.urlencode($ad->image).'/logo-grey.png';
        }

        $ad->link = 'https://partners.moodle.com/image/click.php?p='.urlencode($ad->partner).'&ad='.urlencode($ad->partner);

        return true;
    }
}

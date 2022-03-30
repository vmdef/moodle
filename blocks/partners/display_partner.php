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
 * This file allows a partner ad (selected randomly for the given country) to be displayed on any site without the need
 * to install the whole block. It can display the ad in an iframe for instance.
 *
 * @package     block_partners
 * @copyright   2016 Karen Holland <karen@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot . '/blocks/partners/classes/util.php');

$ad = new display_partner();
$ad->get_content();
echo ($ad->content);

class display_partner {
    var $content;

    function init() {
        $this->title = get_string('pluginname', 'block_partners');
    }

    function get_content() {

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();

        $rand = rand(0,99);
        $util = new block_partners\util();
        try {
            if ($rand < 5) {
                $ads = $util->get_ads();
            } else if ($rand < 15) {
                // Get non-partner ad (moodle HQ promo).
                $ads = $util->get_ads('XX');
            } else {
                $countrycode = $util->get_detected_countrycode();
                $ads = $util->get_ads($countrycode);
            }
        } catch (dml_read_exception $e) {
            // For legacy reasons, we try and query local_moodleorg tables when
            // $CFG->block_partners_downloads_ads is disabled - don't die horribly if this fails.
            debugging('Prevented block_partners from dieing horribly', DEBUG_DEVELOPER);
            $ads = array();
        }

        if (empty($ads)) {    // Show default.
            $advertisement =
            '<a title="moodle.com" '.
            'href="https://partners.moodle.com/image/click.php?p=moodle&amp;ad=moodle">'.
            '<img src="https://moodle.org/blocks/partners/image/moodle/block.gif" '.
            '     height="169" width="175" /></a>';

        } else {        // Choose one at random

            $count = count($ads);
            $keys = array_keys($ads);
            $rand = rand(0, $count-1);

            $ad = $ads[$keys[$rand]];

            if ($ad->country != 'XX') {
                $ad->title .= " ($ad->country)";
            }

            $ad->title = str_replace('&', '&amp;', $ad->title); // XHTML-ize some partner names. Eloy 20071213.

            $pretext = '';

            if (strpos($ad->partner, 'https://') === 0 || strpos($ad->partner, 'http://') === 0) {
                $link  = $ad->partner;
            } else {
                $pretext = '<div class="mb-1"><small class="text-muted">' .
                    get_string('moodlecertifiedservicesprovider', 'block_partners') .
                    '</small></div>';
                $link = 'https://partners.moodle.com/image/click.php?p='.$ad->partner.'&amp;ad='.$ad->image;
            }

            if (strpos($ad->image, 'https://') === 0 || strpos($ad->image, 'http://') === 0) {
                $image  = $ad->image;
            } else {
                $image = 'https://moodle.org/blocks/partners/image/'.$ad->image.'/block.gif';
            }

            $advertisement =  $pretext.
                              '<a title="'.s($ad->title).'" '.  'href="'.$link.'" target="_parent">'.
                              '<img src="'.$image.'" alt="'.s($ad->title).'" height="169" width="175" />'.
                              '</a>';
        }

        $this->content = $advertisement;

        return $this->content;
    }
}

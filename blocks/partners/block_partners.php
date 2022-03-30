<?php

class block_partners extends block_base {
    function init() {
        $this->title = get_string('pluginname', 'block_partners');
    }

    function has_config() {
        return true;
    }

    function get_content() {

        if ($this->content !== NULL) {
            return $this->content;
        }

        if (empty($this->instance)) {
            return '';
        }

        $this->content = new stdClass();
        $this->content->footer = '';

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
                              '<a title="'.s($ad->title).'" '.  'href="'.$link.'">'.
                              '<img src="'.$image.'" alt="'.s($ad->title).'" height="169" width="175" />'.
                              '</a>';
        }

        $this->content->text = $advertisement;
        $this->content->footer = '';

        return $this->content;
    }

    function hide_header() {
        return true;
    }

    function applicable_formats() {
        return array('all' => true);
    }
}

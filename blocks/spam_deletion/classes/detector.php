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
 * Provides {@link block_spam_deletion\detector} class.
 *
 * Based on original script detect.php by Dan Poltawski.
 *
 * @package    block_spam_deletion
 * @copyright  2013 Dan Poltawski
 * @copyright  2015 David Mudrak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_spam_deletion;

use stdClass;
use core_text;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class to detect spammy forum posts to block them from being posted.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class detector {

    /** @var stdClass */
    protected $config;

    /**
     * Constructor.
     *
     * @param stdClass $config plugin configuration
     */
    public function __construct(stdClass $config) {
        $this->config = $config;
    }

    /**
     * Returns a list of unique non-whitelisted URLs found in the message.
     *
     * We must not rely on actual href="..." or similar substring as the
     * spammer might then bypass the check with using Makrdown or simply plain
     * text format.
     *
     * @param string $message
     * @return array
     */
    public function find_external_urls($message) {
        global $CFG;

        // Work with a copy of the passed value (in case we will need it yet later).
        $text = $message;

        // Ignore all links to our draftfile.php as those are typically embedded media.
        $urldraftfile = "$CFG->wwwroot/draftfile.php";
        $text = str_ireplace($urldraftfile, '', $text);

        // Ignore all whitelisted URLS.
        if (!empty($this->config->links_whitelist)) {
            $whitelisted = preg_split("/\r\n|\n|\r/", $this->config->links_whitelist);
            $text = str_ireplace($whitelisted, '', $text);
        }

        // What URLs are left now?
        // Credits: http://www.regexguru.com/2008/11/detecting-urls-in-a-block-of-text/
        $found = preg_match_all("^\b(https?|ftp|file)://[-A-Z0-9+&@#/%?=~_|!:,.;]*[A-Z0-9+&@#/%=~_|]^i", $text, $matches);

        if (!$found) {
            // No URLs or regex error occured.
            return array();
        }

        $urls = array_map('strtolower', $matches[0]);

        return array_unique($urls);
    }

    /**
     * Does the message contain words that may indicate spam?
     *
     * @param string $message
     * @return bool
     */
    public function contains_bad_words($message) {

        if (empty($this->config->badwords)) {
            return false;
        }

        $badwords = explode(',', $this->config->badwords);
        $patternparts = array();

        foreach ($badwords as $badword) {
            $patternparts[] = '\b'.preg_quote(trim($badword)).'\b';
        }

        $pattern = '/'.implode('|', $patternparts).'/i';

        if (preg_match($pattern, $message)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Estimates the ratio of invalid characters for the given charset.
     *
     * This has been developed as a protection against cases like Korean spammers
     * posting into Spannish forums at moodle.org.
     *
     * @param string $message
     * @param string $charset expected charset
     * @return int
     */
    public function invalid_char_percent($message, $charset) {

        // Remove existing ? and 'space' from content so we can count without them at end.
        $text = preg_replace('(\?+|\s+)', '', $message);

        // Prevent division by zero later.
        if (empty($text)) {
            return 0;
        }

        $intermediary = core_text::convert($text, 'UTF-8', $charset);
        $output = core_text::convert($intermediary, $charset, 'UTF-8');

        // Count unknown characters.
        $missingcharscount = substr_count($output, '?');

        // Return the percentage ratio of unknown characters.
        return (int)round(($missingcharscount / core_text::strlen($text)) * 100, 0);
    }
}

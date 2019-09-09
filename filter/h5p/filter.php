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
 * H5P filter
 *
 * @package    filter_h5p
 * @copyright  2019 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * H5P filter
 *
 * This filter will replace any occurrence of [h5p:nnn] with the corresponding H5P content
 *
 * @package    filter_h5p
 * @copyright  2019 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_h5p extends moodle_text_filter {
    /**
     * Function filter replaces any h5p-sources.
     *
     * @param  string $text    HTML content to process
     * @param  array  $options options passed to the filters
     * @return string
     */
    public function filter($text, array $options = array()) {
        global $OUTPUT;

        $alloweddomains = get_config('filter_h5p', 'alloweddomains');
        $alloweddomains = array_map('trim', explode("\n", $alloweddomains));

        if (!is_string($text) or empty($text)) {
            // Non string data can not be filtered anyway.
            return $text;
        }

        if (stripos($text, '[h5p:') === false) {
            return $text;
        }

        // Looking for H5P tags.
        preg_match_all('/\[h5p:(.[^]]*)/i', $text, $matches, PREG_PATTERN_ORDER, 0);

        foreach ($matches[1] as $match) {
            // If $match is a valid URL, it should be a valid H5P one.
            if (filter_var($match, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)) {

                if (!empty($alloweddomains)) {
                    $hostname = parse_url($match, PHP_URL_HOST);
                    if (!\core\ip_utils::is_domain_in_allowed_list($hostname, $alloweddomains)) {
                        continue;
                    }
                }

                $params = (object) array(
                        'contenturl' => $match,
                );
                $embed = $OUTPUT->render_from_template('filter_h5p/embed', $params);
                $text = str_replace('[h5p:' . $match . ']', $embed, $text);
            }
        }

        return $text;
    }
}

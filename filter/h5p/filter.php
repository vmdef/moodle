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

        $allowedsources = get_config('filter_h5p', 'allowedsources');
        $allowedsources = array_map('trim', explode("\n", $allowedsources));
        if (empty($allowedsources)) {
            return $text;
        }

        if (!is_string($text) or empty($text)) {
            // Non string data can not be filtered anyway.
            return $text;
        }

        if (stripos($text, 'http') === false) {
            return $text;
        }

        // Ingore URLs inside tags.
        $filterignoretagsopen  = array('<a\s[^>]+?>', '<span[^>]+?class="nolink"[^>]*?>', '<iframe\s[^>]+?>');
        $filterignoretagsclose = array('</a>', '</span>', '</iframe>');
        $ignoretags = [];
        filter_save_ignore_tags($text, $filterignoretagsopen, $filterignoretagsclose, $ignoretags);

        foreach ($allowedsources as $source) {

            // Convert wildcards.
            $sourceid = str_replace('[id]', '\d+', $source);
            $escapeperiods = str_replace('.', '\.', $sourceid);
            $replacewildcard = str_replace('*', '.*', $escapeperiods);
            $ultimatepattern = '(' . $replacewildcard . ')';

            if (preg_match_all('#'.$ultimatepattern.'#i', $text, $matches)) {

                $uniquematches = array_unique($matches[1]);
                foreach ($uniquematches as $match) {
                    $params = (object) array(
                            'contenturl' => $match,
                            'height' => get_config('filter_h5p', 'frameheight'),
                            'width' => get_config('filter_h5p', 'framewidth')
                    );
                    $embed = $OUTPUT->render_from_template('filter_h5p/embed', $params);
                    $text = str_replace($match, $embed, $text);
                }
            }
        }

        if (!empty($ignoretags)) {
            $ignoretags = array_reverse($ignoretags); // Reversed so "progressive" str_replace() will solve some nesting problems.
            $text = str_replace(array_keys($ignoretags), $ignoretags, $text);
        }

        return $text;
    }
}

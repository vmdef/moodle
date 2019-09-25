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
     * @var boolean $loadresizerjs This is whether to request the resize.js script.
     */
    private static $loadresizerjs = true;

    /**
     * Function filter replaces any h5p-sources.
     *
     * @param  string $text    HTML content to process
     * @param  array  $options options passed to the filters
     * @return string
     */
    public function filter($text, array $options = array()) {
        global $CFG;

        if (!is_string($text) or empty($text)) {
            // Non string data can not be filtered anyway.
            return $text;
        }

        if (stripos($text, 'http') === false) {
            return $text;
        }

        $allowedsources = get_config('filter_h5p', 'allowedsources');
        $allowedsources = array_map('trim', explode("\n", $allowedsources));
        // Allow by default local site H5P content.
        // TODO Add a setting
        $allowedsources[] = $CFG->wwwroot.'/h5p/embed.php?id=[id]';
        if (empty($allowedsources)) {
            return $text;
        }

        $params = array(
                'resize' => get_config('filter_h5p', 'resize'),
                'height' => get_config('filter_h5p', 'frameheight'),
                'width' => get_config('filter_h5p', 'framewidth')
        );

        foreach ($allowedsources as $source) {
            // Convert wildcards.
            $sourceid = str_replace('[id]', '\d+', $source);
            $escapeperiods = str_replace('.', '\.', $sourceid);
            $escapequestionmark = str_replace('?', '\?', $escapeperiods);
            $replacewildcard = str_replace('*', '.*', $escapequestionmark);
            $ultimatepattern = '#(' . $replacewildcard . ')#';

            $h5pcontenturl = new filterobject($source, null, null, false,
                   false, null, [$this, 'filterobject_prepare_replacement_callback'], $params);

            $h5pcontenturl->workregexp = $ultimatepattern;
            $h5pcontents[] = $h5pcontenturl;
        }

        return filter_phrases($text, $h5pcontents, null, null, false, true);
    }

    /**
     * Callback used by filterobject / filter_phrases.
     *
     * @param boolean $resize Resize the iframe
     * @param int $height iframe height
     * @param int $width iframe width
     * @return array [$hreftagbegin, $hreftagend, $replacementphrase] for filterobject.
     */
    public function filterobject_prepare_replacement_callback($resize, $height, $width) {
        $h5piframeattr = "\"$1\" width=\"$width\" height=\"$height\" allowfullscreen=\"allowfullscreen\"
                style=\"border: 0;\">";

        $endtag = "</iframe>";

        if ($resize && self::$loadresizerjs) {
            $endtag .= '<script src="https://h5p.org/sites/all/modules/h5p/library/js/h5p-resizer.js"></script>';
            self::$loadresizerjs = false;
        }

        return ["<iframe src=", $endtag, $h5piframeattr];
    }
}

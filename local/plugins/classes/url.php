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
 * Provides {@link local_plugins_url} class
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Allows to generate nice/clean URLs
 *
 * @copyright   2016 David Mudrak <david@moodle.com>
 * @copyright   2011 Marina Glancy <marina@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_url extends moodle_url {

    /**
     * Constructor.
     *
     * @param moodle_url|string $url
     * @param array $params these params override current params or add new
     * @param string $anchor the anchor to use as part of the URL if there is one.
     */
    public function __construct($url, array $params = null, $anchor = null) {

        // Modify only URLs provided as strings, not moodle_url instances.
        if (!is_string($url)) {
            parent::__construct($url, $params, $anchor);
            return;
        }

        // Modify only URLs related to the plugins directory.
        if (stripos($url, '/local/plugins') !== 0) {
            parent::__construct($url, $params, $anchor);
            return;
        }

        // Rewrite URLs that receive ?plugin=something as an argument.
        if (is_array($params) && array_key_exists('plugin', $params) && count($params) == 1) {
            if (strtolower($url) === '/local/plugins/view.php') {
                parent::__construct('/plugins/'.$params['plugin'], null, $anchor);
                return;
            }

            if (strtolower($url) === '/local/plugins/pluginversions.php') {
                parent::__construct('/plugins/'.$params['plugin'].'/versions', null, $anchor);
                return;
            }

            if (strtolower($url) === '/local/plugins/stats.php') {
                parent::__construct('/plugins/'.$params['plugin'].'/stats', null, $anchor);
                return;
            }

            if (strtolower($url) === '/local/plugins/translations.php') {
                parent::__construct('/plugins/'.$params['plugin'].'/translations', null, $anchor);
                return;
            }

            if (strtolower($url) === '/local/plugins/devzone.php') {
                parent::__construct('/plugins/'.$params['plugin'].'/devzone', null, $anchor);
                return;
            }
        }

        // Particular version.
        if (strtolower($url) === '/local/plugins/pluginversion.php' && is_array($params) && count($params) == 3) {
            if (!empty($params['id']) && is_numeric($params['id']) && !empty($params['plugin']) && !empty($params['releasename'])) {
                parent::__construct('/plugins/'.$params['plugin'].'/'.$params['releasename'].'/'.$params['id'], null, $anchor);
                return;
            }
        }

        // Rewrite all remaining URLs staring with /local/plugins to /plugins.
        if (preg_match('|^/local/plugins(/?)(.*)$|i', $url, $matches)) {
            if (empty($matches[2]) || $matches[1] === '/') {
                parent::__construct('/plugins'.$matches[1].$matches[2], $params, $anchor);
                return;
            }
        }

        // Let it be, let it be, let it - oh, let it be.
        parent::__construct($url, $params, $anchor);
    }

    /**
     * Generate a slug from the given text.
     *
     * @param string $text
     * @return string
     */
    public static function slug(string $text): string {

        return strtolower(trim(preg_replace('~[^0-9a-z\.]+~i', '-',
            html_entity_decode(preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1',
            htmlentities($text, ENT_QUOTES, 'UTF-8')), ENT_QUOTES, 'UTF-8')), '-'));
    }
}

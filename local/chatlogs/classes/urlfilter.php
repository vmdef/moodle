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
 * Wrapper around urltolink filter to access its abilities.
 *
 * @package     local_chatlogs
 * @copyright   2015 Dan Poltawski <dan@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_chatlogs;

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot.'/filter/urltolink/filter.php');

/**
 * Lang import controller
 *
 * @package    tool_langimport
 * @copyright  2014 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class urlfilter extends \filter_urltolink {
    public function __construct() {
        self::$globalconfig = new \stdClass();
        self::$globalconfig->embedimages = false;
    }
    public function convert_urls_into_links(&$text) {
        parent::convert_urls_into_links($text);
    }
}

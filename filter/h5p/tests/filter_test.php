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
 * Unit tests for the filter_h5p
 *
 * @package    filter_h5p
 * @category   test
 * @copyright  2019 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot.'/filter/h5p/filter.php');

/**
 * Unit tests for the H5P filter.
 *
 * @copyright 2019 Victor Deniz <victor@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_h5p_testcase extends advanced_testcase {

    public function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);

        // Enable h5p filter at top level.
        filter_set_global_state('h5p', TEXTFILTER_ON);
    }

    /**
     * Check that h5p tags with urls from allowed domains are filtered.
     */
    public function test_filter_urls() {

        $filterplugin = new filter_h5p(null, array());

        // Texts to filter.
        $h5pcontenttag = array (
            'Unfiltered: [h5p:http:://example.com]',
            'Unfiltered: [h5p:http://google.es/h5p/embed/3425234]',
            'Filtered :  [h5p:https://h5p.org/h5p/embed/547225]',
            'Filtered:   [h5p:https://moodle.h5p.com/content/1290729733828858779/embed]'
        );

        foreach ($h5pcontenttag as $text) {
            $filter = $filterplugin->filter($text);
            if (stripos($text, 'Unfiltered') === false) {
                $this->assertNotEquals($text, $filter);
                $this->assertContains('iframe', $filter);
            } else {
                $this->assertEquals($text, $filter);
            }
        }

    }
}

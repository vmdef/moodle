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
 * moodleorg specific renderers.
 *
 * @package   theme_moodleorg
 * @copyright 2019 Moodle
 * @author    Mathew May
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moodleorg\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;
use html_writer;
use moodle_url;

class donationslider implements renderable, templatable {


    public function __construct() {

    }
    /**
     * Export this data so it can be used as the context for a mustache template.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();

        $data->rand15 = rand(1,5);

        // Links
        $data->linkcommunity = new moodle_url('/community');
        $data->linkfeatures = new moodle_url('/features');
        $data->linksites = new moodle_url('/sites');
        $data->linkstats = new moodle_url('/stats');

        // Get the story slide texts.
        $rand1_4 = rand(1,4);
        $shl = 'heroslide_story'.$rand1_4.'_headline';
        $sd = 'heroslide_story'.$rand1_4.'_description';
        $data->story = get_string($shl, 'local_moodleorg');
        $data->storydescription = get_string($sd, 'local_moodleorg');
        return $data;
    }
}
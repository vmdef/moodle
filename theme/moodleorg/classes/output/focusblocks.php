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
 * @copyright 2018 Moodle
 * @author    Bas Brands
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moodleorg\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;

require_once($CFG->dirroot. "/local/moodleorg/locallib.php");

class focusblocks implements renderable, templatable {

    /**
     * @var array of news items.
     */
    private $communities;

    public function __construct() {
        $this->communities = $this->get_focus_box_community_data();
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = (object) [
            'communities' => $this->communities,
        ];

        return $data;
    }

    public function get_focus_box_community_data() {
        global $CFG, $DB;

        // Populate the ordered list of communities to display. The intention
        // is to display the user's detected community first, then the English
        // community and then some known big communities.
        $curlang = \current_language();
        $links = '';
        $maps = array();

        foreach (array($curlang, 'en', 'es') as $lang) {
            $map = \local_moodleorg_get_mapping($lang);
            if (!is_null($map)) {
                $maps[$map->lang] = $map;
            }
        }

        $data = (object) array(
            'count' => 0,
            'list' => array(),
        );

        foreach ($maps as $map) {
            $data->list[] = (object) array(
                'name' => $DB->get_field('course', 'fullname', array('id' => $map->courseid)),
                'url' => (string) new \moodle_url('/course/view.php', array('id' => $map->courseid)),
                'lang' => $map->lang,
            );
        }

        $data->count = count($data->list);

        return $data;
    }
}

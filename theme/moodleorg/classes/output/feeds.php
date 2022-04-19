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

class feeds implements renderable, templatable {

    /**
     * Export this data so it can be used as the context for a mustache template.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = (object) [];

        $data->useful = $this->get_frontpage_useful_posts_data();
        if (!empty($data->useful)) {
            $data->hasuseful = true;
        }
        $data->events = $this->get_frontpage_events_data();
        if (!empty($data->events)) {
            $data->hasevents = true;
        }
        $data->resources = $this->get_frontpage_resources_data();
        if (!empty($data->resources)) {
            $data->hasresources = true;
        }

        if (isset($_GET['debugcaches']) and $_GET['debugcaches'] == 1) {
            $data->showdebug = true;
        }
        return $data;
    }

    private function get_frontpage_useful_posts_data() {
        global $OUTPUT;
        $mapping = local_moodleorg_get_mapping();

        if (empty($mapping)) {
            return null;
        }

        $useful = new \frontpage_column_useful($mapping);
        $result = $useful->get();

        foreach ($result->items as &$item) {
            $item->userpicture = $OUTPUT->user_picture($item->user,
                array('size' => 35, 'courseid' => $item->courseid));
            $item->userfullname = fullname($item->user);
        }
        return $result;
    }

    private  function get_frontpage_events_data() {
        $mapping = local_moodleorg_get_mapping();

        if (empty($mapping)) {
            return null;
        }

        $events = new \frontpage_column_events($mapping);

        return $events->get();
    }

    private function get_frontpage_resources_data() {

        $mapping = local_moodleorg_get_mapping();

        if (empty($mapping)) {
            $mapping = unserialize('O:8:"stdClass":3:{s:4:"lang";s:2:"en";s:8:"courseid";s:1:"5";s:7:"scaleid";s:2:"88";}');
            //return null;
        }

        $resources = new \frontpage_column_resources($mapping);
        return $resources->get();
    }
}
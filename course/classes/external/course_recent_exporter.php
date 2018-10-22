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
 * Class for exporting the data needed to render a recent course.
 *
 * @package    core_course
 * @copyright  2018 Victor Deniz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_course\external;
defined('MOODLE_INTERNAL') || die();

use renderer_base;
use moodle_url;

/**
 * Class for exporting the data needed to render a recent course.
 *
 * @copyright  2018 Victor Deniz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_recent_exporter extends \core\external\exporter {

    /**
     * Returns a list of objects that are related to this persistent.
     *
     */
    protected static function define_related() {
        // We cache the context so it does not need to be retrieved from the course.
        return array('context' => '\\context');
    }

    /**
     * Get the additional values to inject while exporting.
     *
     * @param renderer_base $output The renderer
     * @return array Additional properties with values
     */
    protected function get_other_values(renderer_base $output) {
        $course = $this->data;
        $courseimageurl = self::get_course_image($course);
        if (!$courseimageurl) {
            $courseimageurl = self::get_course_pattern($course);
        }
        return array(
                'viewurl' => (new moodle_url('/course/view.php', array('id' => $this->data->id)))->out(false),
                'courseimageurl' => $courseimageurl
        );
    }

    /**
     * Return the list of properties.
     *
     * @return array Properties.
     */
    public static function define_properties() {
        return array(
                'id' => array(
                        'type' => PARAM_INT,
                ),
                'category' => array(
                        'type' => PARAM_INT,
                ),
                'shortname' => array(
                        'type' => PARAM_TEXT,
                ),
                'fullname' => array(
                        'type' => PARAM_TEXT,
                ),
                'userid' => array(
                        'type' => PARAM_INT,
                ),
                'timeaccess' => array(
                        'type' => PARAM_INT,
                )
        );
    }

    /**
     * Return the list of additional properties.
     *
     * @return array Additional properties.
     */
    public static function define_other_properties() {
        return array(
                'viewurl' => array(
                        'type' => PARAM_URL,
                ),
                'courseimageurl' => array(
                        'type' => PARAM_RAW,
                )
        );
    }

    /**
     * Get the course image if added to course.
     *
     * @param object $course
     * @return string url of course image
     */
    public static function get_course_image($course) {
        $courseinlist = new \core_course_list_element($course);
        foreach ($courseinlist->get_course_overviewfiles() as $file) {
            if ($file->is_valid_image()) {
                $url = new moodle_url("/pluginfile.php".'/'.$file->get_contextid(). '/'. $file->get_component(). '/'.
                        $file->get_filearea(). $file->get_filepath(). $file->get_filename());
                return $url->__toString();
            }
        }
        return false;
    }

    /**
     * Get the course pattern datauri.
     *
     * The datauri is an encoded svg that can be passed as a url.
     *
     * @param object $course
     * @return string datauri
     */
    public static function get_course_pattern($course) {
        $color = self::coursecolor($course->id);
        $pattern = new \core_geopattern();
        $pattern->setColor($color);
        $pattern->patternbyid($course->id);
        return $pattern->datauri();
    }

    /**
     * Get the course color.
     *
     * @param int $courseid
     * @return string hex color code.
     */
    protected static function coursecolor($courseid) {
        // The colour palette is hardcoded for now. It would make sense to combine it with theme settings.
        $basecolors = ['#81ecec', '#74b9ff', '#a29bfe', '#dfe6e9', '#00b894', '#0984e3', '#b2bec3', '#fdcb6e', '#fd79a8',
                '#6c5ce7'];
        $color = $basecolors[$courseid % 10];
        return $color;
    }
}

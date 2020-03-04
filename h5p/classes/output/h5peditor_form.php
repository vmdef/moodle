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
 * Provides {@link \core_h5p\output\h5peditor_form} class.
 *
 * @package   core_h5p
 * @copyright 2020 Victor Deniz <victor@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p\output;

use core_h5p\factory;
use core_h5p\helper;
use H5PCore;

defined('MOODLE_INTERNAL') || die();

/**
 * Displays the H5P Editor
 *
 * @copyright 2020 Victor Deniz <victor@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class h5peditor_form implements \renderable, \templatable {

    /** @var int */
    protected $contentid;

    /** @var string */
    protected $library;

    /**
     * Constructor.
     */
    public function __construct() {
        $this->contentid = optional_param('contentid', 0, PARAM_INT);
        $this->library = optional_param('library', null, PARAM_TEXT);
    }

    /**
     * Exports the data required for the H5P Editor.
     *
     * @param renderer_base $output
     * @return string
     */
    public function export_for_template(\renderer_base $output) {
        $defaulvalues = [
          'id' => $this->contentid,
          'library' => $this->library
        ];

        $this->data_preprocessing($defaulvalues);

        return $defaulvalues;
    }


    protected function data_preprocessing(&$defaultvalues) {
        global $DB;

        $factory = new factory();
        $core = $factory->get_core();

        $content = null;
        if (!empty($defaultvalues['id'])) {
            // Load content.
            $content = $core->loadContent($defaultvalues['id']);
            if ($content === null) {
                print_error('invalidcontentid');
            }
        }

        // Current H5P library.
        $library = ($content === null) ? 0 : H5PCore::libraryToString($content['library']);

        // Set editor defaults.
        $defaultvalues['h5plibrary'] = $library;

        // Combine params and metadata in one JSON object.
        $params = ($content === null ? '{}' : $core->filterParameters($content));
        $maincontentdata = array('params' => json_decode($params));

        if (isset($content['metadata'])) {
            $maincontentdata['metadata'] = $content['metadata'];
        }
        $defaultvalues['h5pparams'] = json_encode($maincontentdata, true);

        // Add to page required editor assets.
        $mformid = "h5peditor-form";
        $contentid = ($content === null) ? null : $defaultvalues['id'];
        //helper::add_editor_assets_to_page($contentid, $mformid);
    }
}
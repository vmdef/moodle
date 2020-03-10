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
 * Provides {@link \core_h5p\output\h5peditor} class.
 *
 * @package   core_h5p
 * @copyright 2020 Victor Deniz <victor@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p\output;

use core_h5p\editor;

defined('MOODLE_INTERNAL') || die();

/**
 * Displays the H5P Editor
 *
 * @copyright 2020 Victor Deniz <victor@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class h5peditor implements \renderable, \templatable {

    /** @var array H5P editor form values */
    protected $context;

    /**
     * Constructor.
     */
    public function __construct(int $contentid = null, string $library = null) {
        $this->context['id'] = $contentid;
        $this->context['h5plibrary'] = $library;
        $this->context['actionurl'] = new \moodle_url('/h5p/editor.php');

        $editor = new editor();
        $editor->data_preprocessing($this->context);
    }

    /**
     * Exports the data required for the H5P Editor.
     *
     * @param \renderer_base $output
     * @return array
     */
    public function export_for_template(\renderer_base $output) {

        return $this->context;
    }
}

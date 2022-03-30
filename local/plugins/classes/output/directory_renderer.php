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
 * Provides {@link local_plugins\directory_renderer} class
 *
 * @package local_plugins
 * @subpackage output
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use templatable;

/**
 * New generation of the UI rendering for the plugins directory
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class directory_renderer extends plugin_renderer_base {

    /**
     * Renders the start of the HTML document.
     *
     * @return string
     */
    public function header() {

        // If we use URL redirection, moodle will form the path- class from the URL, not from the
        // actual location of the component. In that case we add the path-local-plugins manually
        $this->page->add_body_class('path-local-plugins');

        return parent::header();
    }

    /**
     * Render the plugins directory front page
     *
     * @param templatable $page
     * @return string
     */
    protected function render_front_page(templatable $page) {
        $data = $page->export_for_template($this);
        return parent::render_from_template('local_plugins/frontpage', $data);
    }
}

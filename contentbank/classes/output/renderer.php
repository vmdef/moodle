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
 * Renderer class for core_contentbank
 *
 * @package    core_contentbank
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_contentbank\output;

use plugin_renderer_base;

/**
 * Renderer class for core_contentbank.
 *
 * @package    core_contentbank
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the view content page.
     *
     * @param view_content_page $page
     *
     * @return string html for the page.
     */
    public function render_view_content_page(view_content_page $page) {
        $data = $page->export_for_template($this);
        return $this->render_from_template('core_contentbank/view_content', $data);
    }
}

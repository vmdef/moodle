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
 * Provides the {@link local_plugins_ui_renderer} class.
 *
 * @package     local_plugins
 * @category    output
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * User interface renderer.
 *
 * This is a new renderer that is supposed to eventually replace the legacy
 * one. There is nothing special about the 'ui' subtype, it just allowed to
 * keep the current renderers work and I can start using this new one.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_ui_renderer extends local_plugins_renderer {

    /**
     * Render the moodle.org/plugins/reviews/ page.
     *
     * @param array $reviews local_plugins_review[]
     * @return string
     */
    public function page_reviews(array $reviews) {

        $out = $this->output->header();
        $out .= $this->output->heading(get_string('pluginreviews', 'local_plugins'));
        foreach ($reviews as $review) {
            $data = $review->export_for_template($this);
            $out .= $this->render_from_template('local_plugins/review_summary', $data);
        }
        $out .= $this->output->footer();

        return $out;
    }
}

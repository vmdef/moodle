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
 * Class renderer
 *
 * @package     theme_moodleorg
 * @copyright   2019 Bas Brands
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moodleorg\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Class renderer
 *
 * @package     theme_moodleorg
 * @copyright   2019 Bas Brands
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Renderer for the heroslider slides
     *
     * @param heroslider $heroslider
     * @return bool|string
     */
    protected function render_heroslider(heroslider $heroslider) {
        $context = $heroslider->export_for_template($this);
        return $this->render_from_template('theme_moodleorg/widget_heroslider', $context);
    }

    /**
     * Renderer for the news
     *
     * @param news $view
     * @return bool|string
     */
    protected function render_news(news $news) {
        $context = $news->export_for_template($this);
        return $this->render_from_template('theme_moodleorg/widget_news', $context);
    }

    /**
     * Renderer for the focusblocks
     *
     * @param focusblocks $view
     * @return bool|string
     */
    protected function render_focusblocks(focusblocks $focusblocks) {
        $context = $focusblocks->export_for_template($this);
        return $this->render_from_template('theme_moodleorg/widget_focusblocks', $context);
    }

    /**
     * Renderer for the feeds
     *
     * @param feeds $view
     * @return bool|string
     */
    protected function render_feeds(feeds $feeds) {
        $context = $feeds->export_for_template($this);
        return $this->render_from_template('theme_moodleorg/widget_feeds', $context);
    }

    /**
     * Renderer for the donationslider slides
     *
     * @param donationslider $donationslider
     * @return bool|string
     */
    protected function render_donationslider(donationslider $donationslider) {
        $context = $donationslider->export_for_template($this);
        return $this->render_from_template('theme_moodleorg/widget_donationslider', $context);
    }
}

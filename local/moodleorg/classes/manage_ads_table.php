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
 * Provides local_moodleorg\manage_ads_table class
 *
 * @package     local_moodleorg
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodleorg;

use table_sql;
use moodle_url;
use html_writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Displays the list of registered ads.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_ads_table extends table_sql {

    /**
     * Displays the image column
     *
     * @param object $data
     * @return string
     */
    protected function col_image($data) {
        global $CFG;

        $image = s($data->image);

        $name = '<div><small class="muted">'.$image.'/block.gif</small></div>';

        if (file_exists($CFG->dirroot.'/blocks/partners/image/'.clean_param($image, PARAM_SAFEDIR).'/block.gif')) {
            $url = 'https://moodle.org/blocks/partners/image/'.urlencode($image).'/block.gif';
            $img = html_writer::empty_tag('img', [
                'src' => $url,
                'style' => 'max-height:60;max-width:60px',
                'title' => $image,
                'alt' => 'Ad picture'
            ]);

        } else {
            $img = '<p class="alert alert-error">Missing file!</p>';
        }

        return $name.$img;
    }

    /**
     * Displays the country column
     *
     * @param object $data
     * @return string
     */
    protected function col_country($data) {

        if (get_string_manager()->string_exists($data->country, 'core_countries')) {
            return s($data->country).' <div class="muted"><small>'.get_string($data->country, 'core_countries').'</small></div>';

        } else {
            return s($data->country);
        }
    }

    /**
     * Displays the actions column
     *
     * @param double $data
     * @return string
     */
    protected function col_actions($data) {
        global $PAGE;

        $actions = [
            'edit' => html_writer::link(
                new moodle_url($PAGE->url, ['edit' => $data->id]),
                get_string('edit'),
                ['class' => 'btn']
            ),
            'delete' => html_writer::link(
                new moodle_url($PAGE->url, ['delete' => $data->id]),
                get_string('delete'),
                ['class' => 'btn']
            ),
        ];

        return implode(' ', $actions);
    }

    /**
     * Default data rendering
     *
     * @param string $column
     * @param object $data
     * @return string
     */
    public function other_cols($column, $data) {
        return s($data->{$column});
    }
}

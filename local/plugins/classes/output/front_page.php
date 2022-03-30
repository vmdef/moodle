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
 * Provides {@link local_plugins\output\front_page} model
 *
 * @package local_plugins
 * @subpackage output
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Represents data to be displayed on the front page.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class front_page implements renderable, templatable {

    /** @var local_plugins\output\browser instance to display */
    protected $browser;

    /**
     * Constructor
     *
     * @param local_plugins\output\browser $browser instance to display at the front page
     */
    public function __construct(browser $browser) {
        $this->browser = $browser;
    }

    /**
     * Export data for the template
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $data = (object)[
            'browser' => $this->browser->export_for_template($output),
        ];

        return $data;
    }

    /**
     * Populates the search form.
     *
     * @return array
     */
    protected function define_search_form() {

        $searchform = [
            'query' => [
                'name' => 'q',
                'id' => uniqid('id'),
                'value' => '',
            ],
            'includelabels' => [],
            'excludelabels' => [],
        ];

        foreach ($this->load_descriptors() as $descriptor) {
            $searchform['includelabels'][] = [
                'name' => 'i'.$descriptor['id'].'[]',
                'id' => uniqid('id'),
                'descriptor' => $descriptor,
            ];
        }

        return $searchform;
    }

    /**
     * Loads all known descriptors and their values.
     *
     * @return array usable for mustache
     */
    protected function load_descriptors() {
        global $DB;

        $sql = "SELECT DISTINCT d.id, d.title, v.value
                  FROM {local_plugins_desc} d
             LEFT JOIN {local_plugins_desc_values} v ON v.descid = d.id
              ORDER BY d.sortorder, v.value";

        $recordset = $DB->get_recordset_sql($sql);
        $descriptors = [];

        foreach ($recordset as $record) {
            if (!isset($descriptors[$record->id])) {
                $descriptors[$record->id] = [
                    'id' => $record->id,
                    'title' => $record->title,
                    'values' => [],
                ];
            }
            if ($record->value !== null) {
                $descriptors[$record->id]['values'][$record->value] = [
                    'value' => $record->value,
                    'selected' => null,
                ];
            }
        }

        $recordset->close();

        // Transform the structure so that it can be JSONised.

        $descriptors = array_values($descriptors);

        foreach ($descriptors as &$descriptor) {
            $descriptor['values'] = array_values($descriptor['values']);
        }

        return $descriptors;
    }
}

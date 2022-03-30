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
 * Provides the {@link local_plugins\external\get_plugins_batch} trait.
 *
 * @package     local_plugins
 * @category    external
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\external;

defined('MOODLE_INTERNAL') || die();

use context_system;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use local_plugins\output\browser;
use local_plugins\output\filter;

/**
 * Trait implementing the external function local_plugins_get_plugins_batch
 */
trait get_plugins_batch {

    /**
     * Describes the structure of parameters for the get_plugins_batch function.
     *
     * @return external_function_parameters;
     */
    public static function get_plugins_batch_parameters() {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'Search query', VALUE_DEFAULT, ''),
            'batch' => new external_value(PARAM_INT, 'Requested batch index', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Returns data needed to render a batch of plugins while browsing through them
     *
     * @param string $query Search query
     * @param int $batch Requested batch index
     * @return stdClass
     */
    public static function get_plugins_batch($query, $batch) {
        global $PAGE;

        $params = self::validate_parameters(self::get_plugins_batch_parameters(), compact('query', 'batch'));
        extract($params, EXTR_OVERWRITE);

        // Plugins directory operates on the system context level.
        $context = context_system::instance();

        // Set up rendering environment (we allow anonymous access to self::validate_context() is not an option here.
        $PAGE->reset_theme_and_output();
        $PAGE->set_context($context);
        require_capability('local/plugins:view', $context);

        // Create new browser instance and populate it with search results.
        $browser = new browser();
        $filter = new filter($query);
        $browser->search($filter, $batch, has_capability('local/plugins:viewunapproved', $context));

        // Export data to be returned to the caller.
        $data = $browser->export_for_template($PAGE->get_renderer('local_plugins', 'directory'));

        // We do not need the filter form definition be sent with every request.
        unset($data->control);

        return $data;
    }

    /**
     * Describes the structure of the value returned by the function.
     *
     * See {@link local_plugins\output\browser::export_for_template()}
     *
     * @return external_single_structure
     */
    public static function get_plugins_batch_returns() {
        return new external_single_structure([
            'grid' => new external_single_structure([
                'plugins' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'ID of the plugin in the database'),
                        'index' => new external_value(PARAM_INT, 'Index (order) of the plugin within the results'),
                        'name' => new external_value(PARAM_TEXT, 'Name (human readable) of the plugin'),
                        'frankenstyle' => new external_value(PARAM_COMPONENT, 'Component name (frankenstyle) of the plugin'),
                        'plugintype' => new external_single_structure([
                            'type' => new external_value(PARAM_ALPHAEXT, 'Plugin type code'),
                            'name' => new external_value(PARAM_TEXT, 'Human readable plugin type name'),
                        ]),
                        'approved' => new external_value(PARAM_INT, 'Approval status: pending 0, approved 1, unapproved -1'),
                        'url' => new external_value(PARAM_URL, 'URL of the plugin page in the directory'),
                        'shortdescription' => new external_value(PARAM_TEXT, 'Short description of the plugin'),
                        'timelastreleased' => new external_single_structure([
                            'absdate' => new external_value(PARAM_RAW, 'Last released date (human readable, absolute)'),
                            'reldate' => new external_value(PARAM_RAW, 'Last released date (human readable, relative)'),
                            'iso8601date' => new external_value(PARAM_RAW, 'Last released date (machine readable)'),
                        ]),
                        'aggsites' => new external_value(PARAM_TEXT, 'Number of sites having the plugin installed'),
                        'aggdownloads' => new external_value(PARAM_TEXT, 'Number of plugins downloads'),
                        'aggfavs' => new external_value(PARAM_TEXT, 'Number of users who favourites the plugin'),
                        'has_logo' => new external_value(PARAM_BOOL, 'Does the plugin have own logo'),
                        'logo' => new external_single_structure([
                            'tinyicon' => new external_value(PARAM_URL, 'URL of the logo in the tinyicon preview size'),
                            'thumb' => new external_value(PARAM_URL, 'URL of the logo in the thumb preview size'),
                        ]),
                        'has_screenshots' => new external_value(PARAM_BOOL, 'Does the plugin have some screenshot(s) attached'),
                        'mainscreenshot' => new external_single_structure([
                            'bigthumb' => new external_value(PARAM_URL, 'URL of the main screenshot in the bigthumb preview size'),
                        ]),
                        'screenshots' => new external_multiple_structure(
                            new external_single_structure([
                                'bigthumb' => new external_value(PARAM_URL, 'URL of the other screenshot in the bigthumb preview size'),
                            ]),
                            'List of additional screenshots of the plugin'
                        ),
                        'maintainers' => new external_multiple_structure(
                            new external_single_structure([
                                'id' => new external_value(PARAM_INT, 'User ID'),
                                'firstname' => new external_value(PARAM_TEXT, 'User first name'),
                                'lastname' => new external_value(PARAM_TEXT, 'User last name'),
                                'url' => new external_value(PARAM_TEXT, 'User last name'),
                            ]),
                            'List of plugin maintainers and links to their profiles'
                        ),
                        'descriptors' => new external_multiple_structure(
                            new external_single_structure([
                                'descriptorid' => new external_value(PARAM_INT, 'ID of the descriptor'),
                                'value' => new external_value(PARAM_RAW, 'Descriptor value'),
                            ]),
                            'List of descriptor labels attached to the plugin'
                        ),
                    ])
                ),
                'screenshotloading' => new external_value(PARAM_URL, 'URL of the screenshot loading placeholder'),
                'source' => new external_value(PARAM_ALPHANUMEXT, 'The source of the browser data, such as "search"'),
                'query' => new external_value(PARAM_RAW, 'The search/filter query for this results set'),
                'batch' => new external_value(PARAM_INT, 'This batch index'),
                'batchsize' =>  new external_value(PARAM_INT, 'The size of the batch'),
            ]),
        ]);
    }
}

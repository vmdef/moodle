<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace local_plugins\external;

use external_api;
use external_description;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use external_format_value;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/plugins/lib/setup.php');

/**
 * External function 'local_plugins_get_maintained_plugins' implementation.
 *
 * @package     local_plugins
 * @category    external
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_maintained_plugins extends external_api {

    /**
     * Describes parameters of the {@see self::execute()} method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {

        return new external_function_parameters([]);
    }

    /**
     * Load and return the list of plugins the user is maintainer of.
     *
     * @return array
     */
    public static function execute(): array {
        global $DB, $USER;

        // Set up and validate appropriate context.
        $context = \context_system::instance();
        self::validate_context($context);

        // Check capabilities.
        require_capability('local/plugins:editownplugins', $context);

        $response = [];

        $sql = "SELECT p.*
                  FROM {local_plugins_plugin} p
                  JOIN {local_plugins_contributor} c ON c.pluginid = p.id
                 WHERE c.userid = :userid
                   AND (c.maintainer = 1 OR c.maintainer = 2)";

        foreach (\local_plugins_helper::load_plugins_from_result($DB->get_records_sql($sql, ['userid' => $USER->id])) as $plugin) {
            $data = static::describe_plugin($plugin, $context);

            $data['currentversions'] = [];

            foreach ($plugin->latestversions as $latestversion) {
                $data['currentversions'][] = static::describe_version($latestversion, $context);
            }

            $response[] = $data;
        }

        return $response;
    }

    /**
     * Describes the return value of the {@see self::execute()} method.
     *
     * @return external_description
     */
    public static function execute_returns(): external_description {

        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Internal plugin identifier'),
                'name' => new external_value(PARAM_TEXT, 'Name of the plugin'),
                'shortdescription' => new external_value(PARAM_TEXT, 'Short description'),
                'description' => new external_value(PARAM_RAW, 'Description'),
                'descriptionformat' => new external_format_value('description'),
                'frankenstyle' => new external_value(PARAM_PLUGIN, 'Full component frankenstyle name'),
                'type' => new external_value(PARAM_ALPHANUMEXT, 'Plugin type'),
                'websiteurl' => new external_value(PARAM_URL, 'Website URL'),
                'sourcecontrolurl' => new external_value(PARAM_URL, 'Source control URL'),
                'bugtrackerurl' => new external_value(PARAM_URL, 'Bug tracker URL'),
                'discussionurl' => new external_value(PARAM_URL, 'Discussion URL'),
                'timecreated' => new external_value(PARAM_INT, 'Timestamp of plugin submission'),
                'approved' => new external_value(PARAM_INT, 'Approval status'),
                'visible' => new external_value(PARAM_BOOL, 'Visibility status'),
                'aggdownloads' => new external_value(PARAM_INT, 'Stats aggregataion - downloads'),
                'aggfavs' => new external_value(PARAM_INT, 'Stats aggregataion - favourites'),
                'aggsites' => new external_value(PARAM_INT, 'Stats aggregataion - sites'),
                'statusamos' => new external_value(PARAM_INT, 'AMOS registration status'),
                'viewurl' => new external_value(PARAM_URL, 'View URL'),
                'currentversions' => new external_multiple_structure(
                    new external_single_structure([
                        'id' => new external_value(PARAM_INT, 'Internal version identifier'),
                        'version' => new external_value(PARAM_INT, 'Version number'),
                        'releasename' => new external_value(PARAM_TEXT, 'Release name'),
                        'releasenotes' => new external_value(PARAM_RAW, 'Release notes'),
                        'releasenotesformat' => new external_format_value('releasenotes'),
                        'maturity' => new external_value(PARAM_INT, 'Maturity code'),
                        'changelogurl' => new external_value(PARAM_URL, 'Change log URL'),
                        'altdownloadurl' => new external_value(PARAM_URL, 'Alternative download URL'),
                        'md5sum' => new external_value(PARAM_TEXT, 'MD5 hash of the ZIP content'),
                        'vcssystem' => new external_value(PARAM_ALPHA, 'Version control system'),
                        'vcssystemother' => new external_value(PARAM_TEXT, 'Name of the other version control system'),
                        'vcsrepositoryurl' => new external_value(PARAM_URL, 'Version control system URL'),
                        'vcsbranch' => new external_value(PARAM_TEXT, 'Name of the branch with this version'),
                        'vcstag'  => new external_value(PARAM_TEXT, 'Name of the tag with this version'),
                        'timecreated' => new external_value(PARAM_INT, 'Timestamp of version release'),
                        'approved' => new external_value(PARAM_INT, 'Approval status'),
                        'visible'  => new external_value(PARAM_BOOL, 'Visibility status'),
                        'supportedmoodle' => new external_value(PARAM_TEXT, 'Comma separated list of support Moodle versions'),
                        'downloadurl' => new external_value(PARAM_URL, 'Download URL'),
                        'viewurl' => new external_value(PARAM_URL, 'View URL'),
                        'smurfresult' => new external_value(PARAM_TEXT, 'Code prechecks results summary'),
                    ])
                ),
            ])
        );
    }

    /**
     * Convert the given plugin record to response data.
     *
     * @param \local_plugins_plugin $plugin
     * @return array
     */
    protected static function describe_plugin(\local_plugins_plugin $plugin, \context $context): array {

        $data = [
            'id' => $plugin->id,
            'name' => external_format_string($plugin->name, $context, true),
            'shortdescription' => external_format_string($plugin->shortdescription, $context, true),
            ];

        [$data['description'], $data['descriptionformat']] = external_format_text($plugin->description,
            $plugin->descriptionformat, $context, 'local_plugins', \local_plugins::FILEAREA_PLUGINDESCRIPTION,
            $plugin->id, \local_plugins_helper::editor_options_plugin_description());

        foreach ([
            'frankenstyle', 'type', 'websiteurl', 'sourcecontrolurl', 'bugtrackerurl', 'discussionurl',
            'timecreated', 'approved', 'visible', 'aggdownloads', 'aggfavs', 'aggsites', 'statusamos',
        ] as $property) {
            $data[$property] = $plugin->$property;
        }

        foreach (['websiteurl', 'sourcecontrolurl', 'bugtrackerurl', 'discussionurl'] as $urlfield) {
            $data[$urlfield] = clean_param($data[$urlfield], PARAM_URL);
        }

        $data['viewurl'] = $plugin->viewlink->out();

        return $data;
    }

    /**
     * Convert the given plugin version to response data.
     *
     * @param \local_plugins_version $version
     * @param \context $context
     * @return array
     */
    protected static function describe_version(\local_plugins_version $version, \context $context): array {

        $data = [
            'id' => $version->id,
            'version' => $version->version,
            'releasename' => external_format_string($version->releasename, $context),
        ];

        [$data['releasenotes'], $data['releasenotesformat']] = external_format_text($version->releasenotes,
            $version->releasenotesformat, $context, 'local_plugins', \local_plugins::FILEAREA_VERSIONRELEASENOTES,
            $version->id, \local_plugins_helper::editor_options_version_releasenotes());

        foreach ([
            'maturity', 'changelogurl', 'altdownloadurl', 'md5sum', 'vcssystem', 'vcssystemother', 'vcsrepositoryurl',
            'vcsbranch', 'vcstag', 'timecreated', 'timelastmodified', 'approved', 'visible', 'smurfresult',
        ] as $property) {
            $data[$property] = $version->$property;
        }

        $data['supportedmoodle'] = [];

        foreach ($version->moodle_versions as $moodleversion) {
            $data['supportedmoodle'][] = $moodleversion->releasename;
        }

        $data['supportedmoodle'] = implode(',', $data['supportedmoodle']);

        foreach (['changelogurl', 'altdownloadurl', 'vcsrepositoryurl'] as $urlfield) {
            $data[$urlfield] = clean_param($data[$urlfield], PARAM_URL);
        }

        $data['downloadurl'] = $version->downloadlinkredirector->out();
        $data['viewurl'] = $version->viewlink->out();

        return $data;
    }
}

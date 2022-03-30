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
require_once($CFG->dirroot . '/local/plugins/lib/archive_validator.php');
require_once($CFG->dirroot . '/local/plugins/pluginversion_form.php');

/**
 * External function 'local_plugins_add_version' implementation.
 *
 * @package     local_plugins
 * @category    external
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_version extends external_api {

    /**
     * Describes parameters of the {@see self::execute()} method.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {

        return new external_function_parameters([
            // The pluginid or frankenstyle must be provided (in this order of precedence).
            'pluginid' => new external_value(PARAM_INT, 'Internal identifier of the plugin', VALUE_DEFAULT, null),
            'frankenstyle' => new external_value(PARAM_PLUGIN, 'Full component name of the plugin', VALUE_DEFAULT, null),
            // ZIP can be specified by draft area itemid (with single file in it), content or URL (in this order of precedence).
            'zipdrafitemtid' => new external_value(PARAM_INT, 'Itemid of user draft area with uploaded ZIP', VALUE_DEFAULT, null),
            'zipcontentsbase64' => new external_value(PARAM_RAW, 'ZIP file contents encoded with MIME base64', VALUE_DEFAULT, null),
            'zipurl' => new external_value(PARAM_URL, 'ZIP file URL', VALUE_DEFAULT, null),
            // Following params may be auto-detected from the ZIP content.
            'version' => new external_value(PARAM_INT, 'Version number', VALUE_DEFAULT, null),
            'releasename' => new external_value(PARAM_TEXT, 'Release name', VALUE_DEFAULT, null),
            'releasenotes' => new external_value(PARAM_RAW, 'Release notes', VALUE_DEFAULT, null),
            'releasenotesformat' => new external_format_value('releasenotes', VALUE_DEFAULT, FORMAT_MOODLE),
            'maturity' => new external_value(PARAM_INT, 'Maturity code', VALUE_DEFAULT, null),
            'supportedmoodle' => new external_value(PARAM_TEXT, 'Comma separated list of supported Moodle versions',
                VALUE_DEFAULT, null),
            // Other optional properties.
            'changelogurl' => new external_value(PARAM_URL, 'Change log URL', VALUE_DEFAULT, null),
            'altdownloadurl' => new external_value(PARAM_URL, 'Alternative download URL', VALUE_DEFAULT, null),
            'vcssystem' => new external_value(PARAM_ALPHA, 'Version control system', VALUE_DEFAULT, null),
            'vcssystemother' => new external_value(PARAM_TEXT, 'Name of the other version control system', VALUE_DEFAULT, null),
            'vcsrepositoryurl' => new external_value(PARAM_URL, 'Version control system URL', VALUE_DEFAULT, null),
            'vcsbranch' => new external_value(PARAM_TEXT, 'Name of the branch with this version', VALUE_DEFAULT, null),
            'vcstag'  => new external_value(PARAM_TEXT, 'Name of the tag with this version', VALUE_DEFAULT, null),
        ]);
    }

    /**
     * Release a new version of the plugin.
     *
     * @param int|null $pluginid
     * @param string|null $frankenstyle
     * @param string|null $zipcontentsbase64
     * @param string|null $zipurl
     * @param int|null $zipdrafitemtid
     * @param int|null $version
     * @param string|null $releasename
     * @param string|null $releasenotes
     * @param int|null $releasenotesformat
     * @param int|null $maturity
     * @param string|null $supportedmoodle
     * @param string|null $changelogurl
     * @param string|null $altdownloadurl
     * @param string|null $vcssystem
     * @param string|null $vcssystemother
     * @param string|null $vcsrepositoryurl
     * @param string|null $vcsbranch
     * @param string|null $vcstag
     * @return array
     */
    public static function execute(?int $pluginid, ?string $frankenstyle, ?int $zipdrafitemtid, ?string $zipcontentsbase64,
            ?string $zipurl, ?int $version, ?string $releasename, ?string $releasenotes, ?int $releasenotesformat, ?int $maturity,
            ?string $supportedmoodle, ?string $changelogurl, ?string $altdownloadurl, ?string $vcssystem, ?string $vcssystemother,
            ?string $vcsrepositoryurl, ?string $vcsbranch, ?string $vcstag) {

        global $DB;

        // Re-validate parameters in rare case this method was called directly.
        [
            'pluginid' => $pluginid,
            'frankenstyle' => $frankenstyle,
            'zipcontentsbase64' => $zipcontentsbase64,
            'zipurl' => $zipurl,
            'zipdrafitemtid' => $zipdrafitemtid,
            'version' => $version,
            'releasename' => $releasename,
            'releasenotes' => $releasenotes,
            'releasenotesformat' => $releasenotesformat,
            'maturity' => $maturity,
            'supportedmoodle' => $supportedmoodle,
            'changelogurl' => $changelogurl,
            'altdownloadurl' => $altdownloadurl,
            'vcssystem' => $vcssystem,
            'vcssystemother' => $vcssystemother,
            'vcsrepositoryurl' => $vcsrepositoryurl,
            'vcsbranch' => $vcsbranch,
            'vcstag' => $vcstag,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pluginid' => $pluginid,
            'frankenstyle' => $frankenstyle,
            'zipcontentsbase64' => $zipcontentsbase64,
            'zipurl' => $zipurl,
            'zipdrafitemtid' => $zipdrafitemtid,
            'version' => $version,
            'releasename' => $releasename,
            'releasenotes' => $releasenotes,
            'releasenotesformat' => $releasenotesformat,
            'maturity' => $maturity,
            'supportedmoodle' => $supportedmoodle,
            'changelogurl' => $changelogurl,
            'altdownloadurl' => $altdownloadurl,
            'vcssystem' => $vcssystem,
            'vcssystemother' => $vcssystemother,
            'vcsrepositoryurl' => $vcsrepositoryurl,
            'vcsbranch' => $vcsbranch,
            'vcstag' => $vcstag,
        ]);

        $context = \context_system::instance();
        self::validate_context($context);

        require_capability('local/plugins:editownplugins', $context);

        $plugin = static::get_plugin_from_parameters($pluginid, $frankenstyle);

        if (!$plugin->can_edit()) {
            throw new \local_plugins_exception('exc_cannotedit');
        }

        $draftitemid = static::prepare_zip_draftitemid($zipdrafitemtid, $zipcontentsbase64, $zipurl);
        $validator = \local_plugins_archive_validator::create_from_draft($draftitemid, null, $frankenstyle, null, ['renameroot' => true]);
        $pluginversion = \local_plugins_helper::quick_upload_version($plugin, $draftitemid, $validator);

        if (!($pluginversion instanceof \local_plugins_version)) {
            throw new \local_plugins_exception('exc_errorcreatingversion');
        }

        try {
            if (!empty($validator->errors_list)) {
                throw new \local_plugins_exception('exc_zipvalidationerrors', '', null, json_encode($validator->errors_list));
            }

            if (empty($version) && isset($validator->versioninformation['version'])) {
                $version = $validator->versioninformation['version'];
            }

            if (empty($version) || (string) $version !== (string) $validator->versioninformation['version']) {
                throw new \local_plugins_exception('exc_zipvalidationerrors', '', null,
                    json_encode(['Invalid version or versions mismatch.']));
            }

            if ($releasename === null && isset($validator->versioninformation['releasename'])) {
                $releasename = $validator->versioninformation['releasename'];
            }

            if ($releasenotes === null) {
                if (isset($validator->versioninformation['changesfile'])) {
                    // Prefer CHANGES file as the source for the Release notes.
                    $releasenotes = $validator->versioninformation['changesfile'];
                    $releasenotesformat = $validator->versioninformation['changesfileformat'];

                } else if (isset($validator->versioninformation['releasenotes'])) {
                    // Use README file eventually.
                    $releasenotes = $validator->versioninformation['releasenotes'];
                    $releasenotesformat = $validator->versioninformation['releasenotesformat'];
                }
            }

            if ($maturity === null && isset($validator->versioninformation['maturity'])) {
                $maturity = $validator->versioninformation['maturity'];
            }

            if ($supportedmoodle === null && isset($validator->versioninformation['softwareversions'])) {
                $softwareversions = $validator->versioninformation['softwareversions'];

            } else {
                $supportedmoodle = array_map('trim', explode(',', $supportedmoodle));
                $softwareversions = array_filter(array_map(function ($branchcode): ?int {
                    if ((string) $branchcode !== (string) clean_param($branchcode, PARAM_INT)) {
                        throw new \local_plugins_exception('exc_zipvalidationerrors', '', null,
                            json_encode(['Invalid supported Moodle version: ' . $branchcode]));
                    }

                    if ($softwareversion = \local_plugins_helper::get_moodle_version_by_branch_code($branchcode)) {
                        return $softwareversion->id;

                    } else {
                        return null;
                    }
                }, $supportedmoodle));
            }

            if (empty($softwareversions)) {
                throw new \local_plugins_exception('exc_zipvalidationerrors', '', null,
                    json_encode(['Invalid list of supported Moodle versions.']));
            }

            $pluginversion->update([
                'version' => $version,
                'releasename' => $releasename,
                'maturity' => $maturity,
                'releasenotes' => $releasenotes,
                'releasenotesformat' => $releasenotesformat,
                'changelogurl' => $changelogurl,
                'altdownloadurl' => $altdownloadurl,
                'vcssystem' => $vcssystem,
                'vcssystemother' => $vcssystemother,
                'vcsrepositoryurl' => $vcsrepositoryurl,
                'vcsbranch' => $vcsbranch,
                'vcstag' => $vcstag,
                'softwareversion' => $softwareversions,
            ]);

        } catch (\Throwable $t) {
            $pluginversion->delete(false);
            throw $t;
        }

        \local_plugins_log::log_added($pluginversion, 'Added using add_version external function.');

        if ($plugin->approved == \local_plugins_plugin::PLUGIN_APPROVED
                && $pluginversion->approved == \local_plugins_plugin::PLUGIN_APPROVED) {
            \local_plugins\local\amos\exporter::request_strings_update($plugin);
        }

        $response = [
            'id' => $pluginversion->id,
            'md5sum' => $pluginversion->md5sum,
            'timecreated' => $pluginversion->timecreated,
            'downloadurl' => (string) $pluginversion->downloadlink,
            'viewurl' => (string) $pluginversion->viewlink,
            'warnings' => $validator->warnings_list,
        ];

        return $response;
    }

    /**
     * Get the plugin from the input parameters.
     *
     * @param int|null $pluginid
     * @param string|null $frankenstyle
     * @return \local_plugins_plugin
     */
    protected static function get_plugin_from_parameters(?int $pluginid, ?string $frankenstyle): \local_plugins_plugin {

        $plugin = null;

        if ($pluginid) {
            $plugin = \local_plugins_helper::get_plugin($pluginid);

        } else if ($frankenstyle) {
            $plugin = \local_plugins_helper::get_plugin_by_frankenstyle($frankenstyle);
        }

        if (empty($plugin)) {
            throw new \local_plugins_exception('exc_pluginnotfound');
        }

        return $plugin;
    }

    /**
     * Return the user draft area itemid with the version ZIP present.
     *
     * @param ?int $zipdrafitemtid
     * @param ?string $zipcontentsbase64
     * @param ?string $zipurl
     * @return int
     */
    protected static function prepare_zip_draftitemid(?int $zipdrafitemtid, ?string $zipcontentsbase64, ?string $zipurl): int {
        global $USER;

        $fs = get_file_storage();
        $contextid = \context_user::instance($USER->id)->id;

        if ($zipdrafitemtid) {
            // The user has uploaded the ZIP to a new draft area (via webservice/upload.php).
            $files = $fs->get_area_files($contextid, 'user', 'draft', $zipdrafitemtid);

            if (empty($files)) {
                throw new \local_plugins_exception('exc_nofilesindraftarea');

            } else if (count($files) > 2) {
                // One file for the root directory, the other for the ZIP file.
                throw new \local_plugins_exception('exc_toomanyfilesindraftarea');

            } else {
                return $zipdrafitemtid;
            }

        } else {
            $fileinfo = (object) [
                'component' => 'user',
                'contextid' => $contextid,
                'userid' => $USER->id,
                'filearea' => 'draft',
                'filename' => 'plugin.zip',
                'filepath' => '/',
                'itemid' => file_get_unused_draft_itemid(),
            ];

            if (strlen($zipcontentsbase64) > 0) {
                $zipcontents = base64_decode($zipcontentsbase64, true);

                if (!$zipcontents) {
                    throw new \local_plugins_exception('exc_invalidbase64');
                }

                $filestored = $fs->create_file_from_string($fileinfo, $zipcontents);

                return $filestored->get_itemid();

            } else if (strlen($zipurl) > 0) {
                $filestored = $fs->create_file_from_url($fileinfo, $zipurl, null, false);

                return $filestored->get_itemid();
            }
        }

        throw new \local_plugins_exception('exc_zipnotspecified');
    }

    /**
     * Describes the return value of the {@see self::execute()} method.
     *
     * @return external_description
     */
    public static function execute_returns(): external_description {

        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Internal identifier of the newly created version'),
            'md5sum' => new external_value(PARAM_TEXT, 'MD5 hash of the ZIP content'),
            'timecreated' => new external_value(PARAM_INT, 'Timestamp of version release'),
            'downloadurl' => new external_value(PARAM_URL, 'Download URL'),
            'viewurl' => new external_value(PARAM_URL, 'View URL'),
            'warnings' => new external_multiple_structure(
                new external_value(PARAM_RAW, 'Validation warnings')
            ),
        ]);
    }
}

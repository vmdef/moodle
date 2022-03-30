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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Provides {@link local_plugins\local\amos\exporter} class.
 *
 * @package     local_plugins
 * @subpackage  amos
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\local\amos;

use local_plugins_plugin;
use local_plugins_version;
use local_plugins_softwareversion;

defined('MOODLE_INTERNAL') || die();

/**
 * Allows to register the plugin English strings with AMOS.
 *
 * @copyright 2012 David Mudrak <david@moodle.com> - original implementation
 * @copyright 2019 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class exporter {

    const PLUGIN_PENDING = 0;
    const PLUGIN_PROCESSING = -1;
    const PLUGIN_OK = 1;
    const PLUGIN_PROBLEM = -2;

    const STATUS_OK = 1;
    const STATUS_SKIPPED = 2;
    const STATUS_REMOTE_EXCEPTION = -1;
    const STATUS_ERROR = -2;
    const STATUS_UNKNOWN = -3;
    const STATUS_PROTOCOL_ERROR = -4;
    const STATUS_UNKNOWN_ERROR = -5;

    /**
     * Mark a plugin as dirty and request its recent versions be re-checked and eventually registered with AMOS.
     *
     * @param local_plugins_plugin $plugin
     */
    public static function request_strings_update(local_plugins_plugin $plugin) {
        $plugin->update(['statusamos' => static::PLUGIN_PENDING]);
    }

    /**
     * Get a next plugin that needs its strings registered with AMOS.
     *
     * The plugin must be approved and visible. It must have at least one approved and visible version. It must be in a
     * category with a valid plugin type defined. It must have it statusamos set to pending.
     *
     * @return local_plugins_plugin|bool false if none found
     */
    public static function get_next_pending_plugin() {
        global $DB;

        $sql = "SELECT p.id
                  FROM {local_plugins_plugin} p
                  JOIN {local_plugins_category} c ON p.categoryid = c.id
                  JOIN {local_plugins_vers} v ON v.pluginid = p.id
                 WHERE p.statusamos = :status
                   AND c.plugintype <> ''
                   AND c.plugintype <> '-'
                   AND p.approved = 1
                   AND p.visible = 1
                   AND v.approved = 1
                   AND v.visible = 1
              ORDER BY p.timelastmodified DESC";

        $found = $DB->get_records_sql($sql, ['status' => static::PLUGIN_PENDING], 0, 1);

        if (empty($found)) {
            return false;

        } else {
            $plugin = reset($found);
            return \local_plugins_helper::get_plugin($plugin->id);
        }
    }

    /**
     * Process a plugin and store the results in the local_plugins_amos_results table.
     *
     * @param local_plugins_plugin $plugin
     * @return bool
     */
    public static function process_plugin(local_plugins_plugin $plugin) {

        $success = true;
        $processversions = [];

        // Process latest versions in reverse order from oldest one to the most recent one.
        foreach (array_reverse($plugin->latestversions) as $latestversion) {
            $sincemoodleversion = static::get_lowest_supported_moodle_version($latestversion);
            printf("- latest plugin version %s (%s) supports Moodle %s (%s) and higher\n",
                $latestversion->releasename, $latestversion->version,
                $sincemoodleversion->releasename, $sincemoodleversion->version);

            $processversions[] = [
                'plugin' => $latestversion,
                'moodle' => $sincemoodleversion,
            ];
        }

        foreach ($processversions as $processversion) {
            mtrace('- processing version ' . html_to_text($processversion['plugin']->get_formatted_fullname_and_moodle_version()));

            $result = [
                'versionid' => $processversion['plugin']->id,
                'moodlebranch' => null,
                'timecreated' => time(),
                'timeresult' => null,
                'status' => null,
                'result' => null,
            ];

            // This is where we actually contact AMOS.
            $response = static::process_plugin_version($processversion['plugin'], $processversion['moodle']);

            $result['timeresult'] = time();

            if (is_object($response) && isset($response->exception)) {
                // Remote exception thrown by AMOS.
                mtrace('  AMOS status: REMOTE_EXCEPTION');
                $result['status'] = static::STATUS_REMOTE_EXCEPTION;
                $result['result'] = json_encode($response);
                static::store_export_result($result);
                $success = false;

            } else if (is_array($response)) {
                // AMOS import results.
                foreach ($response as $info) {
                    $result['moodlebranch'] = $info->moodlebranch . '+';
                    $result['result'] = json_encode($info);

                    if ($info->status === 'ok') {
                        mtrace('  AMOS status: OK');
                        $result['status'] = static::STATUS_OK;

                    } else if ($info->status === 'error') {
                        mtrace('  AMOS status: ERROR');
                        $result['status'] = static::STATUS_ERROR;
                        $success = false;

                    } else {
                        mtrace('  AMOS status: UNKNOWN');
                        $result['status'] = static::STATUS_UNKNOWN;
                        $success = false;
                    }
                    static::store_export_result($result);
                }

            } else if ($response === true) {
                // There was no need to actually call AMOS.
                mtrace('  AMOS status: SKIPPED');
                $result['status'] = static::STATUS_SKIPPED;
                $result['result'] = json_encode([
                    'message' => 'Moodle 1.9 and lower not supported by AMOS',
                ]);
                static::store_export_result($result);

            } else if (is_null($response)) {
                // Unable to parse the response.
                mtrace('  AMOS status: PROTOCOL_ERROR');
                $result['status'] = static::STATUS_PROTOCOL_ERROR;
                static::store_export_result($result);
                $success = false;

            } else {
                mtrace('  AMOS status: UNKNOWN_ERROR');
                $result['status'] = static::STATUS_UNKNOWN_ERROR;
                static::store_export_result($result);
                $success = false;
            }
        }

        if ($success) {
            static::set_plugin_processing_result($plugin, static::PLUGIN_OK);
        } else {
            static::set_plugin_processing_result($plugin, static::PLUGIN_PROBLEM);
        }

        return $success;
    }

    /**
     * Set the plugin's AMOS export status.
     *
     * @param local_plugins_plugin $plugin
     * @param int $status
     */
    public static function set_plugin_processing_result(local_plugins_plugin $plugin, $status) {
        global $DB;

        $plugin->update(['statusamos' => $status]);
    }

    /**
     * Stores a result of the AMOS export call.
     *
     * @param array $result
     * @return int
     */
    protected static function store_export_result($result) {
        global $DB;

        return $DB->insert_record('local_plugins_amos_results', $result);
    }

    /**
     * Process a plugin version already stored in the Plugins directory
     *
     * Returned value is an array of result objects with properties componentname, moodlebranch,
     * language, status and optional message. If a remote exception is thrown, it is returned as
     * as an object with the property exception set. If there was a problem with parsing the server
     * response, null is returned. True is returned if there is nothing to send to AMOS. False is
     * returned if the version is not considered a valid plugin.
     *
     * @param local_plugins_version $pluginversion
     * @param local_plugins_softwareversion $moodleversion
     * @return array|stdClass|bool|null True is all good, false means a problem with the plugin (record, ZIP, category, ...)
     */
    protected static function process_plugin_version(local_plugins_version $pluginversion,
            local_plugins_softwareversion $moodleversion) {

        $params = static::prepare_request_params($pluginversion, $moodleversion);

        if ($params === false) {
            // Possible problem with category or frankenstyle.
            return false;
        }

        if (isset($params['components'][0]['moodlebranch']) && $params['components'][0]['moodlebranch'] == '1.9') {
            // Skip AMOS string exporting for 1.9 versions (not supported).
            array_shift($params['components']);
        }

        if (empty($params['components'])) {
            // No versions left so all good.
            return true;
        }

        $client = new client();

        return $client->call('local_amos_update_strings_file', $params);
    }

    /**
     * Prepares request for the AMOS web service.
     *
     * @param local_plugins_version $pluginversion
     * @param local_plugins_softwareversion $moodleversion
     * @return array|false
     */
    protected static function prepare_request_params(local_plugins_version $pluginversion,
            local_plugins_softwareversion $moodleversion) {
        global $DB;

        // Check the plugin category.
        $plugintype = $pluginversion->plugin->category->plugintype;

        if (empty($plugintype) or $plugintype === '-') {
            return false;
        }

        // Check frankenstyle name.
        $frankenstyle = $pluginversion->plugin->frankenstyle;

        if (strpos($frankenstyle, $plugintype.'_') !== 0) {
            return false;
        }

        // Prepare the web service request.
        $request = [];

        // Get the author's user info.
        $author = $DB->get_record('user', ['id' => $pluginversion->userid, 'deleted' => 0],
            'id, email' . \core_user\fields::for_name()->get_sql()->selects, IGNORE_MISSING);

        if (!$author) {
            $request['userinfo'] = 'Unknown user';

        } else {
            $request['userinfo'] = fullname($author).' <'.$author->email.'>';
        }

        // Get the AMOS commit message.
        $request['message'] = sprintf('Strings for '.$pluginversion->plugin->name);

        if (!empty($pluginversion->releasename)) {
            $request['message'] .= ' '.$pluginversion->releasename;

        } else if (!empty($pluginversion->version)) {
            $request['message'] .= ' '.$pluginversion->version;
        }

        // Get the content of the language file(s) included in the package.
        $sourcecode = source_code::instance($pluginversion);
        $stringfiles = $sourcecode->get_included_string_files();
        unset($sourcecode);

        $request['components'] = array();

        foreach ($stringfiles as $componentname => $stringfile) {
            if (!is_array($stringfile)) {
                continue;
            }
            foreach ($stringfile as $stringfilename => $stringfilecontent) {
                $request['components'][] = array(
                    'componentname' => $componentname,
                    'moodlebranch' => $moodleversion->releasename,
                    'language' => 'en',
                    'stringfilename' => basename($stringfilename),
                    'stringfilecontent' => $stringfilecontent,
                );
            }
        }

        return $request;
    }

    /**
     * Return the lowest Moodle version supported by this plugin version.
     *
     * @param local_plugins_version $version
     * @return local_plugins_softwareversion
     */
    protected static function get_lowest_supported_moodle_version(local_plugins_version $version): local_plugins_softwareversion {

        $lowestmoodle = null;

        foreach ($version->plugin->get_moodle_versions() as $mversion) {
            // Does the given plugin version supports the Moodle version?
            if (isset($version->supportedsoftware[$mversion->id])) {
                // Check that the given plugin version is the latest for the found Moodle version.
                $latestversion = $version->plugin->get_mostrecentversion($mversion->id);
                if ($latestversion && $latestversion->id == $version->id) {
                    // Make sure it is the lowest one.
                    if ($lowestmoodle === null || $lowestmoodle->version > $mversion->version) {
                        $lowestmoodle = $mversion;
                    }
                }
            }
        }

        return $lowestmoodle;
    }
}

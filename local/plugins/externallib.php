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
 * external API for local_plugins web services
 *
 * @package    local_plugins
 * @category   external
 * @copyright  2012 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/local/plugins/lib/setup.php');
require_once($CFG->dirroot . '/local/plugins/lib/download_resolver.php');

/**
 * external API for local_plugins web services
 *
 * @package    local_plugins
 * @category   external
 * @copyright  2012 onwards Eloy Lafuente (stronk7) {@link http://stronk7.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_external extends external_api {

    /**
     * Returns the description of the get_available_plugins parameters supported
     *
     * @return external_function_parameters object encapsulating all the supported parameters
     */
    public static function get_available_plugins_parameters() {
        return new external_function_parameters(array()); // The method has no parameters
    }

    /**
     * Returns the description of the get_available_plugins results
     *
     * @return external_description object encapsulating the information to return by the function
     */
    public static function get_available_plugins_returns() {
        return new external_single_structure(
            array(
                'timestamp' => new external_value(PARAM_INT, 'timestamp'),
                'plugins'   => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id'        => new external_value(PARAM_INT, 'plugin id'),
                            'name'      => new external_value(PARAM_TEXT, 'plugin name'),
                            'component' => new external_value(PARAM_COMPONENT, 'plugin component'),
                            'source'    => new external_value(PARAM_URL, 'plugin source url'),
                            'doc'       => new external_value(PARAM_URL, 'plugin documentation url'),
                            'bugs'      => new external_value(PARAM_URL, 'plugin tracker url'),
                            'discussion'      => new external_value(PARAM_URL, 'plugin discussion url'),
                            'timelastreleased' => new external_value(PARAM_INT, 'plugin last release timestamp'),
                            'versions'  => new external_multiple_structure(
                                new external_single_structure(
                                    array(
                                        'id'          => new external_value(PARAM_INT, 'version id'),
                                        'version'     => new external_value(PARAM_TEXT, 'plugin version'),
                                        'release'     => new external_value(PARAM_TEXT, 'version release'),
                                        'maturity'    => new external_value(PARAM_INT, 'version maturity'),
                                        'downloadurl' => new external_value(PARAM_URL, 'version download url'),
                                        'downloadmd5' => new external_value(PARAM_ALPHANUM, 'version download md5 checksum'),
                                        'vcssystem'   => new external_value(PARAM_TEXT, 'version vcs'),
                                        'vcssystemother' => new external_value(PARAM_TEXT, 'version vcs name, if other'),
                                        'vcsrepositoryurl' => new external_value(PARAM_TEXT, 'version vcs url'),
                                        'vcsbranch' => new external_value(PARAM_TEXT, 'version vcs branch'),
                                        'vcstag' => new external_value(PARAM_TEXT, 'version vcs tag'),
                                        'timecreated' => new external_value(PARAM_INT, 'version release timestamp'),
                                        'supportedmoodles' => new external_multiple_structure(
                                            new external_single_structure(
                                                array(
                                                  'version' => new external_value(PARAM_INT, 'moodle min version supported'),
                                                  'release' => new external_value(PARAM_TEXT, 'moodle major release')
                                                )
                                            )
                                        )
                                    )
                                )
                            )
                        )
                    )
                )
            )
        );
    }

    /**
     * Returns the available plugins and their details (observing the parameters and results definitions)
     *
     * @return array information structure containing the details of the available plugins
     */
    public static function get_available_plugins() {
        global $CFG, $DB;

        // Ensure the current user is allowed to run this function
        $context = context_system::instance();
        self::validate_context($context);
        require_capability('local/plugins:editanyplugin', $context);

        // Initialise the results array
        $results = array();
        $results['timestamp'] = time();

        // Get and iterate over all the available plugins, versions and supported Moodle releases
        $sql = "SELECT p.id AS pid, p.name AS pname, p.frankenstyle AS pcomponent, p.sourcecontrolurl AS psource,
                       p.documentationurl AS pdoc, p.bugtrackerurl AS pbugs, p.discussionurl AS pdiscussion,
                       p.timelastreleased AS ptimelastreleased,
                       v.id AS vid, v.version AS vversion, v.releasename AS vrelease, v.maturity AS vmaturity,
                       v.md5sum AS vdownloadmd5, v.vcssystem, v.vcssystemother, v.vcsrepositoryurl, v.vcsbranch,
                       v.vcstag, v.timecreated AS vtimecreated,
                       m.releasename AS mrelease, m.version AS mversion
                  FROM {local_plugins_plugin} p
                  JOIN {local_plugins_vers} v ON v.pluginid = p.id
                  JOIN {local_plugins_supported_vers} s ON s.versionid = v.id
                  JOIN {local_plugins_software_vers} m ON m.id = s.softwareversionid
                 WHERE m.name = 'Moodle'
                   AND p.approved = 1
                   AND p.visible = 1
                   AND v.approved = 1
                   AND v.visible = 1
              ORDER BY p.id ASC, v.id ASC, m.version ASC"; // Note the order matters here!

        $rs = $DB->get_recordset_sql($sql);
        $plugins = array();

        foreach ($rs as $record) {

            if (!isset($plugins[$record->pid])) {
                $plugins[$record->pid] = array(
                    'id' => $record->pid,
                    'name' => $record->pname,
                    'component' => $record->pcomponent,
                    'source' => $record->psource,
                    'doc' => $record->pdoc,
                    'bugs' => $record->pbugs,
                    'discussion' => $record->pdiscussion,
                    'timelastreleased' => $record->ptimelastreleased,
                    'versions' => array(),
                );
            }

            if (!isset($plugins[$record->pid]['versions'][$record->vid])) {
                $plugins[$record->pid]['versions'][$record->vid] = array(
                    'id' => $record->vid,
                    'version' => $record->vversion,
                    'release' => $record->vrelease,
                    'maturity' => $record->vmaturity,
                    'downloadmd5' => $record->vdownloadmd5,
                    'downloadurl' => null,
                    'vcssystem' => $record->vcssystem,
                    'vcssystemother' => $record->vcssystemother,
                    'vcsrepositoryurl' => $record->vcsrepositoryurl,
                    'vcsbranch' => $record->vcsbranch,
                    'vcstag' => $record->vcstag,
                    'timecreated' => $record->vtimecreated,
                    'supportedmoodles' => array(),
                );
            }

            // This will replace the downloadurl value in every iteration so it
            // will contain the URL using the latest supported Moodle at the
            // end without breaking the backward compatibility of the order of
            // supportedmoodles items (it does not probably matters but...)
            $plugins[$record->pid]['versions'][$record->vid]['downloadurl'] = local_plugins_download_resolver::get_download_link(
                $record->vid,
                local_plugins_download_resolver::get_download_filename(
                    $record->pcomponent,
                    $record->pname,
                    $record->vversion,
                    $record->mrelease
                ),
                $CFG->local_plugins_downloadredirectorurl
            )->out();

            $plugins[$record->pid]['versions'][$record->vid]['supportedmoodles'][] = array(
                'release' => $record->mrelease,
                'version' => $record->mversion,
            );
        }
        $rs->close();

        $results['plugins'] = $plugins;

        return $results;
    }
}

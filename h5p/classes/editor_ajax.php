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
 * \mod_hvp\editor_ajax class
 *
 * @package    mod_hvp
 * @copyright  2016 Joubel AS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p;

defined('MOODLE_INTERNAL') || die();


/**
 * Moodle's implementation of the H5P Editor Ajax interface.
 * Makes it possible for the editor's core ajax functionality to communicate with the
 * database used by Moodle.
 *
 * @package    mod_hvp
 * @copyright  2016 Joubel AS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor_ajax implements \H5PEditorAjaxInterface {

    /**
     * Gets latest library versions that exists locally
     *
     * @return array Latest version of all local libraries
     * @throws \dml_exception
     */
    // @codingStandardsIgnoreLine
    public function getLatestLibraryVersions() {
        global $DB;

        $maxmajorversionsql = "
            SELECT hl.machinename, MAX(hl.majorversion) AS majorversion
            FROM {h5p_libraries} hl
            WHERE hl.runnable = 1
            GROUP BY hl.machinename";

        $maxminorversionsql = "
            SELECT hl2.machinename, hl2.majorversion, MAX(hl2.minorversion) AS minorversion
            FROM ({$maxmajorversionsql}) hl1
            JOIN {h5p_libraries} hl2
            ON hl1.machinename = hl2.machinename
            AND hl1.majorversion = hl2.majorversion
            GROUP BY hl2.machinename, hl2.majorversion";

        /*return $DB->get_records_sql("
            SELECT hl4.id, hl4.machinename, hl4.title, hl4.majorversion,
                hl4.minorversion, hl4.patchversion, hl4.hasicon, hl4.restricted
            FROM {h5p_libraries} hl4
            JOIN ({$maxminorversionsql}) hl3
            ON hl4.machinename = hl3.machinename
            AND hl4.majorversion = hl3.majorversion
            AND hl4.minorversion = hl3.minorversion"
        );*/
        // TODO Add fields hasicon, restricted
        return $DB->get_records_sql("
            SELECT hl4.id, hl4.machinename, hl4.title, hl4.majorversion,
                hl4.minorversion, hl4.patchversion
            FROM {h5p_libraries} hl4
            JOIN ({$maxminorversionsql}) hl3
            ON hl4.machinename = hl3.machinename
            AND hl4.majorversion = hl3.majorversion
            AND hl4.minorversion = hl3.minorversion"
        );
    }

    /**
     * Get locally stored Content Type Cache. If machine name is provided
     * it will only get the given content type from the cache
     *
     * @param null $machinename
     *
     * @return array|mixed|null|object Returns results from querying the database
     * @throws \dml_exception
     */
    // @codingStandardsIgnoreLine
    public function getContentTypeCache($machinename = null) {
        global $DB;

        // TODO Create table h5p_libraries_hub_cache
/*        if ($machinename) {
            return $DB->get_record_sql(
                "SELECT id, is_recommended
                   FROM {hvp_libraries_hub_cache}
                  WHERE machine_name = ?",
                array($machinename)
            );
        }

        return $DB->get_records("hvp_libraries_hub_cache");*/
        return null;
    }

    /**
     * Gets recently used libraries for the current author
     *
     * @return array machine names. The first element in the array is the
     * most recently used.
     */
    // @codingStandardsIgnoreLine
    public function getAuthorsRecentlyUsedLibraries() {
        global $DB;
        global $USER;
        $recentlyused = array();

        // TODO Create table h5p_events
/*        $results = $DB->get_records_sql(
            "SELECT library_name, max(created_at) AS max_created_at
            FROM {hvp_events}
           WHERE type='content' AND sub_type = 'create' AND user_id = ?
        GROUP BY library_name
        ORDER BY max_created_at DESC", array($USER->id));

        foreach ($results as $row) {
            $recentlyused[] = $row->library_name;
        }*/

        return $recentlyused;
    }

    /**
     * Checks if the provided token is valid for this endpoint
     *
     * @param string $token The token that will be validated for.
     *
     * @return bool True if successful validation
     */
    // @codingStandardsIgnoreLine
    public function validateEditorToken($token) {
        return \H5PCore::validToken('editorajax', $token);
    }

    /**
     * Get translations for a language for a list of libraries
     *
     * @param array $libraries An array of libraries, in the form "<machineName> <majorVersion>.<minorVersion>
     * @param string $language_code
     *
     * @return array
     * @throws \dml_exception
     */
    public function getTranslations($libraries, $language_code) {
        global $DB;

        $translations = array();

        foreach ($libraries as $library) {
            $parsedLib = \H5PCore::libraryFromString($library);

            $sql         = "
                    SELECT language_json
                    FROM {hvp_libraries} lib
                      LEFT JOIN {hvp_libraries_languages} lang
                    ON lib.id = lang.library_id
                    WHERE lib.machine_name = :machine_name AND
                      lib.major_version = :major_version AND
                      lib.minor_version = :minor_version AND
                      lang.language_code = :language_code";
            $translation = $DB->get_field_sql($sql, array(
                'machine_name'  => $parsedLib['machineName'],
                'major_version' => $parsedLib['majorVersion'],
                'minor_version' => $parsedLib['minorVersion'],
                'language_code' => $language_code,
            ));

            if ($translation !== false) {
                $translations[$library] = $translation;
            }
        }

        return $translations;
    }
}

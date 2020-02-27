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
 * Class \core_h5p\editor_ajax
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>, base on code by Joubel AS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p;

use H5PEditorAjaxInterface;

defined('MOODLE_INTERNAL') || die();

/**
 * Moodle's implementation of the H5P Editor Ajax interface.
 *
 * Makes it possible for the editor's core ajax functionality to communicate with the
 * database used by Moodle.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>, base on code by Joubel AS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor_ajax implements H5PEditorAjaxInterface {

    /**
     * Gets latest library versions that exists locally
     *
     * @return array Latest version of all local libraries
     */
    public function getLatestLibraryVersions() {
        global $DB;

        $maxmajorversionsql = "SELECT hl.machinename, MAX(hl.majorversion) AS majorversion
                                 FROM {h5p_libraries} hl
                                WHERE hl.runnable = 1
                             GROUP BY hl.machinename";

        $maxminorversionsql = "SELECT hl2.machinename, hl2.majorversion, MAX(hl2.minorversion) AS minorversion
                                 FROM ({$maxmajorversionsql}) hl1
                                 JOIN {h5p_libraries} hl2 ON hl1.machinename = hl2.machinename
                                      AND hl1.majorversion = hl2.majorversion
                             GROUP BY hl2.machinename, hl2.majorversion";

        $sql = " SELECT hl4.id, hl4.machinename, hl4.title, hl4.majorversion, hl4.minorversion, hl4.patchversion
                   FROM {h5p_libraries} hl4
                   JOIN ({$maxminorversionsql}) hl3 ON hl4.machinename = hl3.machinename
                        AND hl4.majorversion = hl3.majorversion
                        AND hl4.minorversion = hl3.minorversion";

        return $DB->get_records_sql($sql);
    }

    /**
     * Get locally stored Content Type Cache.
     *
     * If machine name is provided it will only get the given content type from the cache.
     *
     * @param null $machinename
     *
     * @return array|mixed|null|object Returns results from querying the database
     */
    public function getContentTypeCache($machinename = null) {
        return [];
    }

    /**
     * Gets recently used libraries for the current author
     *
     * @return array machine names. The first element in the array is the
     * most recently used.
     */
    public function getAuthorsRecentlyUsedLibraries() {
        return [];
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
     * @param string $languagecode
     *
     * @return array
     */
    public function getTranslations($libraries, $languagecode) {
        global $DB;

        $translations = array();

        foreach ($libraries as $library) {
            $parsedLib = \H5PCore::libraryFromString($library);

            $sql = "SELECT languagejson
                      FROM {h5p_libraries} lib
                 LEFT JOIN {h5p_libraries_languages} lang ON lib.id = lang.libraryid
                     WHERE lib.machinename = :machinename
                           AND lib.majorversion = :majorversion
                           AND lib.minorversion = :minorversion
                           AND lang.languagecode = :languagecode";

            $params = [
                'machinename'  => $parsedLib['machineName'],
                'majorversion' => $parsedLib['majorVersion'],
                'minorversion' => $parsedLib['minorVersion'],
                'languagecode' => $languagecode,
            ];

            $translation = $DB->get_field_sql($sql, $params);

            if ($translation !== false) {
                $translations[$library] = $translation;
            }
        }

        return $translations;
    }
}

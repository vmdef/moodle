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
 * @package     local_plugins
 * @copyright   2013 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class providing various features related to the package download
 *
 * Historically, all the logic required to calculate the download URL was part
 * of the {@link local_plugins_version} class. To use it, one needed to
 * instantiate a version object. However, it turned out that we need to call
 * these even without real objects (typically in the web service when we are
 * processing many plugins in a bulk).
 */
class local_plugins_download_resolver {

    /**
     * Generates the name of the plugin version ZIP package to download
     *
     * Note that our file download.php effectively ignores the name as it picks
     * the plugin version using the versionid part of the the URL.
     *
     * @param string|null $frankentyle component name of the plugin, if available (plugin.frankenstyle)
     * @param string $name name of the plugin, used if the frankenstyle is empty (plugin.name)
     * @param string $version the version of the plugin to download (vers.version)
     * @param string|null $moodlerelease latest moodle release supported by this version (software_vers.releasename)
     * @return string
     */
    public static function get_download_filename($frankenstyle, $name, $version, $moodlerelease) {

        if (!empty($frankenstyle)) {
            $name = $frankenstyle;
        } else {
            $name = str_replace(' ', '_', $name);
        }
        $name = clean_param($name, PARAM_ALPHANUMEXT);
        if (!is_null($moodlerelease)) {
            $name .= '_moodle' . clean_param($moodlerelease, PARAM_ALPHANUM);
        }
        $name .= '_'.urlencode($version).'.zip';

        return $name;
    }

    /**
     * Returns URL to our local download.php to serve a given plugin version package
     *
     * Calls to download.php are tracked and are used to calculate download
     * statistics about the plugin version. Note that the file download.php
     * effectively ignores the filename as it picks the plugin version using the
     * versionid part of the URL.
     *
     * The $providerurl allows to generate URLs to alternate provider of the
     * package, such as download.moodle.org that behaves as a proxy for ZIP
     * packages.
     *
     * @param int $versionid the plugin version id (vers.id)
     * @param string $filename the package file name as appears in the URL
     * @param string|null $providerurl the optional URL of the site providing the package
     * @return local_plugins_url
     */
    public static function get_download_link($versionid, $filename, $providerurl = null) {

        if (!empty($providerurl) and $providerurl === clean_param($providerurl, PARAM_URL)) {
            // Serve the ZIP via external provider (proxy, content distributed
            // network etc).
            return new moodle_url($providerurl . $versionid . '/' . $filename);

        } else {
            // Serve the ZIP via our own download.php script.
            return new local_plugins_url('/local/plugins/download.php/'.$versionid.'/'.$filename);
        }
    }
}

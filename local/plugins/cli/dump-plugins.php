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
 * Extract the latest versions of all plugins into disk
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

$sql = "SELECT p.id AS pluginid, p.name AS pluginname, p.frankenstyle,
               v.id AS versionid, v.version, v.timecreated,
               sw.releasename AS moodleversion
          FROM {local_plugins_plugin} p
     LEFT JOIN {local_plugins_vers} v ON (v.pluginid = p.id)
     LEFT JOIN {local_plugins_supported_vers} sv ON (sv.versionid = v.id)
     LEFT JOIN {local_plugins_software_vers} sw ON (sw.id = sv.softwareversionid AND sw.name = 'Moodle')
         WHERE p.visible = 1 AND p.approved = 1 AND v.visible = 1 AND v.approved = 1
      ORDER BY p.timelastreleased DESC, sw.releasename DESC, v.version DESC, v.timecreated DESC";

$rs = $DB->get_recordset_sql($sql);
$plugins = [];

foreach ($rs as $record) {
    if (!isset($plugins[$record->pluginid])) {
        $plugins[$record->pluginid] = (object) [
            'id' => $record->pluginid,
            'name' => $record->pluginname,
            'frankenstyle' => $record->frankenstyle,
            'versions' => []
        ];
    }

    if (empty($plugins[$record->pluginid]->versions)) {
        $plugins[$record->pluginid]->versions[$record->versionid] = (object) [
            'id' => $record->versionid,
            'version' => $record->version,
            'moodle' => $record->moodleversion,
        ];
    }
}

$rs->close();

$fp = get_file_packer('application/zip');

foreach ($plugins as $plugin) {
    if ($plugin->frankenstyle) {
        $dirname = $plugin->frankenstyle;
    } else {
        $dirname = 'id_'.$plugin->id;
    }

    if (empty($plugin->versions)) {
        continue;
    }

    $version = reset($plugin->versions);

    $zip = $CFG->dataroot.'/local_plugins/'.$plugin->id.'/'.$version->id.'.zip';

    if (!is_readable($zip)) {
        cli_problem('file not readable: '.$zip);
        continue;
    }

    $target = '/tmp/plugins/'.$dirname;
    mkdir($target, $CFG->directorypermissions, true);
    $fp->extract_to_pathname($zip, $target);

    cli_writeln($target);
}

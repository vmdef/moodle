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
 * Generates a list of plugins, to be modified and used as needed
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

date_default_timezone_set('UTC');

$sql = "SELECT p.name, p.frankenstyle, p.timelastmodified, p.aggdownloads, p.aggfavs, p.aggsites,
               MIN(sw.releasename) AS moodlemin, MAX(sw.releasename) AS moodlemax
          FROM {local_plugins_plugin} p
          JOIN {local_plugins_vers} v ON v.pluginid = p.id
          JOIN {local_plugins_supported_vers} sv ON sv.versionid = v.id
          JOIN {local_plugins_software_vers} sw ON sv.softwareversionid = sw.id
         WHERE p.frankenstyle IS NOT NULL
               AND p.approved = 1
               AND p.visible = 1
               AND v.approved = 1
               AND v.visible = 1
               AND sw.name = :swname
      GROUP BY p.name, p.frankenstyle, p.timelastmodified, p.aggdownloads, p.aggfavs, p.aggsites
      ORDER BY p.name";

$rs = $DB->get_recordset_sql($sql, ['swname' => 'Moodle']);

$writer = new dataformat_csv\writer();
$writer->send_http_headers();

$writer->write_header([
    'name' => 'Plugin name',
    'component' => 'Component',
    'type' => 'Plugin type',
    'timelastmodified' => 'Time last modified',
    'aggdownloads' => 'Downloads',
    'aggfavs' => 'Favourites',
    'aggsites' => 'Sites',
    'moodlemin' => 'Moodle MIN',
    'moodlemax' => 'Moodle MAX',
]);

$c = 0;

foreach ($rs as $record) {
    $timelastmodified = date('Y-m-d H:i:s', $record->timelastmodified);
    list($ptype, $pname) = normalize_component($record->frankenstyle);
    $row = [
        'name' => $record->name,
        'component' => $record->frankenstyle,
        'type' => $ptype,
        'timelastmodified' => $timelastmodified,
        'aggdownloads' => $record->aggdownloads,
        'aggfavs' => $record->aggfavs,
        'aggsites' => $record->aggsites,
        'moodlemin' => $record->moodlemin,
        'moodlemax' => $record->moodlemax,
    ];
    $writer->write_record($row, $c++);
}

$rs->close();

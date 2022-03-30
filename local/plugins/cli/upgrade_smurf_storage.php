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
 * Convert the precheck smurfxml, smurfxml, console and debuglog contents from DB to file storage.
 *
 * @package     local_plugins
 * @subpackage  cli
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');
require_once($CFG->libdir.'/filelib.php');

$usage = "Convert the precheck smurfxml, smurfxml, console and debuglog contents from DB to file storage.
";

list($options, $unrecognised) = cli_get_params([
    'help' => false,
], [
    'h' => 'help'
]);

if ($unrecognised) {
    $unrecognised = implode(PHP_EOL.'  ', $unrecognised);
    cli_error(get_string('cliunknowoption', 'core_admin', $unrecognised));
}

if ($options['help']) {
    cli_writeln($usage);
    exit(2);
}

$zip = new zip_packer();

$sql = "SELECT *
		  FROM {local_plugins_vers_precheck}
         WHERE smurfhtml IS NOT NULL";

$rs = $DB->get_recordset_sql($sql, null, 0, 100);

foreach ($rs as $record) {

    $smurfzippath = $CFG->dataroot.'/local_plugins/precheck/smurf/'.$record->versionid.'/'.$record->id.'.zip';
    make_writable_directory(dirname($smurfzippath));

    $result = $zip->archive_to_pathname([
        'console.txt' => [$record->console],
        'debuglog.txt' => [$record->debuglog],
        'smurf.xml' => [$record->smurfxml],
        'smurf.html' => [$record->smurfhtml],
    ], $smurfzippath, false);

    if ($result) {
        $update = [
            'id' => $record->id,
            'console' => null,
            'debuglog' => null,
            'smurfxml' => null,
            'smurfhtml' => null,
        ];
        $DB->update_record('local_plugins_vers_precheck', $update);
        echo "ok\t".$smurfzippath.PHP_EOL;

    } else {
        cli_error("error\t".$smurfzippath);
    }
}

$rs->close();

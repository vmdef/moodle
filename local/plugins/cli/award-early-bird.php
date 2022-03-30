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
 * Used to award selected plugins with the Early bird award
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);
require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

//$VERSIONID = 24; // Moodle 3.2
//$DEADLINE = 1480982397; // Mon, 05 Dec 2016 23:59:59 UTC
//$AWARDID = 7; // Early-bird 3.2

//$VERSIONID = 25; // Moodle 3.3
//$DEADLINE = 1494892799; // Mon, 15 May 2017 23:59:59 GMT
//$AWARDID = 8; // Early-bird 3.3

//$VERSIONID = 27; // Moodle 3.4
//$DEADLINE = 1510617599; // Monday, 13 November 2017 23:59:59 GMT as per https://www.epochconverter.com/
//$AWARDID = 9; // Early-bird 3.4

//$VERSIONID = 29; // Moodle 3.5
//$DEADLINE = 1526601599; // Thursday, 17 May 2018 23:59:59 GMT as per https://www.epochconverter.com/
//$AWARDID = 10; // Early-bird 3.5

//$VERSIONID = 30; // Moodle 3.6
//$DEADLINE = 1543881599; // Monday, 3 December 2018 23:59:59 as per https://www.epochconverter.com/
//$AWARDID = 12; // Early-bird 3.6

//$VERSIONID = 31; // Moodle 3.7
//$DEADLINE = 1559779199; // Wednesday, 5 June 2019 23:59:59 as per https://www.epochconverter.com/
//$AWARDID = 13; // Early-bird 3.7

//$VERSIONID = 34; // Moodle 3.8
//$DEADLINE = 1575417599; // Tuesday, 3 December 2019 23:59:59 as per https://www.epochconverter.com/
//$AWARDID = 14; // Early-bird 3.8

//$VERSIONID = 35; // Moodle 3.9
//$DEADLINE = 1593475199; // Monday, 29 June 2020 23:59:59 as per https://www.epochconverter.com/
//$AWARDID = 15; // Early-bird 3.9

//$VERSIONID = 36; // Moodle 3.10
//$DEADLINE = 1606175999; // Monday, 23 November 2020 23:59:59 GMT as per https://www.epochconverter.com/
//$AWARDID = 16; // Early-bird 3.10

$VERSIONID = 37; // Moodle 3.11
$DEADLINE = 1622505599; // Monday, 31 May 2021 23:59:59 GMT as per https://www.epochconverter.com/
$AWARDID = 17; // Early-bird 3.11

list($options, $unrecognised) = cli_get_params(['execute' => false], ['e' => 'execute']);

$sql = "SELECT p.id AS pluginid, p.name AS pluginname, p.frankenstyle, v.id AS versionid, a.timeawarded
          FROM {local_plugins_plugin} p
          JOIN {local_plugins_vers} v ON v.pluginid = p.id
          JOIN {local_plugins_supported_vers} s ON s.versionid = v.id
     LEFT JOIN {local_plugins_plugin_awards} a ON a.pluginid = p.id AND a.awardid = :awardid
         WHERE s.softwareversionid = :versionid
           AND v.timelastmodified <= :deadline
           AND v.visible = 1
      ORDER BY p.frankenstyle, v.timelastmodified DESC";

$rs = $DB->get_recordset_sql($sql, [
    'versionid' => $VERSIONID,
    'deadline' => $DEADLINE,
    'awardid' => $AWARDID,
]);

foreach ($rs as $record) {
    cli_writeln($record->pluginname.' -- '.$record->frankenstyle);
    cli_writeln('  https://moodle.org/plugins/view.php?id='.$record->pluginid);

    if ($record->timeawarded) {
        cli_writeln('  early bird already awarded');

    } else {
        cli_writeln('  early bird to be awarded');
        if ($options['execute']) {
            if (!$DB->record_exists('local_plugins_plugin_awards',
                    ['awardid' => $AWARDID, 'pluginid' => $record->pluginid])) {
                $DB->insert_record('local_plugins_plugin_awards', [
                    'awardid' => $AWARDID,
                    'pluginid' => $record->pluginid,
                    'versionid' => null,
                    'timeawarded' => time(),
                    'userid' => 1797093,    // Plugin bot
                ]);
                cli_writeln('  done');
            }
        }
    }
}
$rs->close();

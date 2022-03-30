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
 * Display all plugin logos.
 *
 * The output is supposed to be used during the marketing campaigns for plugins
 * directory.
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');

use local_plugins\output\front_page;

$PAGE->set_url(new local_plugins_url('/local/plugins/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('base');

require_capability('local/plugins:viewunapproved', $PAGE->context);

echo $OUTPUT->header();

$sql = "SELECT p.id, l.filename AS logofilename, l.filepath AS logofilepath
          FROM {local_plugins_plugin} p
     LEFT JOIN {files} l ON (l.component='local_plugins' AND l.filearea='plugin_logo'
               AND l.contextid = ".SYSCONTEXTID." AND l.itemid = p.id AND l.filename <> '.')
         WHERE p.approved = 1
      ORDER BY p.aggsites DESC, p.timelastreleased DESC ";

$recordset = $DB->get_recordset_sql($sql);
$urls = [];

foreach ($recordset as $record) {
    if (isset($urls[$record->id])) {
        continue;
    }

    if ($record->logofilename === null) {
        $urls[$record->id] = false;

    } else {
        $logourl = local_plugins_url::make_pluginfile_url(SYSCONTEXTID, 'local_plugins', 'plugin_logo',
            $record->id, $record->logofilepath, $record->logofilename);
        $urls[$record->id] = (new local_plugins_url($logourl, ['preview' => 'tinyicon']))->out();
    }
}

$recordset->close();

foreach ($urls as $url) {
    echo '<img src="'.$url.'">';
}

echo $OUTPUT->footer();

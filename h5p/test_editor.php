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
 * Test page for testing the H5P Editor
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_h5p\local\library\autoloader;
use core_h5p\editor_ajax;

require_once(__DIR__ . '/../config.php');
require_once("$CFG->libdir/filestorage/file_storage.php");

require_login(null, false);

$contentid = optional_param('contentid', null, PARAM_INT);
$library = optional_param('library', null, PARAM_TEXT);

$context = context_system::instance();
$fs = new file_storage();

require_capability('moodle/h5p:updatelibraries', $context);

$pagetitle = 'H5P Editor manual testing';
$url = new \moodle_url("/h5p/test_editor.php");

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

if (empty($contentid)) {
    $contentid = null;
}

if ($contentid !== null || !empty($library)) {
    $ui = new core_h5p\output\h5peditor($contentid, $library);
}

echo $OUTPUT->header();

if ($contentid === null && empty($library)) {
    // Create a form.
    echo html_writer::start_tag('form', array('method' => 'post', 'action' => ''));
    echo html_writer::start_div();

    $h5pcontents = $DB->get_records('h5p', null, 'id', 'id, pathnamehash, jsoncontent');

    foreach ($h5pcontents as $h5pcontent) {
        $file = $fs->get_file_by_hash($h5pcontent->pathnamehash);
        if ($file) {
            $filecontextid = $file->get_contextid();
            list($filecontext, $course, $cm) = get_context_info_array($filecontextid);
            $coursename = $course->fullname ?? $SITE->fullname;
            $modulename = ($course) ? ' - Module: ' . ($cm->name ?? 'ERROR: No module') : '';
            $params = json_decode($h5pcontent->jsoncontent);
            $h5pcontenttitle = ' - ' . ($params->metadata->title ?? 'ERROR: No title');
            $options[$h5pcontent->id] = $coursename . $modulename . $h5pcontenttitle;
        }
    }

    echo html_writer::label('Select a content to edit', 'contentid');
    echo html_writer::select($options, 'contentid');

    // Button to submit form.
    echo html_writer::start_div('', array('style' => 'margin-top: 20px'));
    echo html_writer::tag('button', 'Edit');
    echo html_writer::end_div();

    $options2 = [];
    autoloader::register();
    $editor_ajax = new editor_ajax();
    $libraries = $editor_ajax->getLatestLibraryVersions();
    foreach ($libraries as $library) {
        $key = H5PCore::libraryToString(['name'=>$library->machine_name, 'majorVersion' => $library->major_version,
                                         'minorVersion' => $library->minor_version]);
        $options2[$key] = $library->title;
    }

    echo html_writer::label('Select a content to create', 'library');
    echo html_writer::select($options2, 'library');

    // Button to submit form.
    echo html_writer::start_div('', array('style' => 'margin-top: 20px'));
    echo html_writer::tag('button', 'Create');
    echo html_writer::end_div();

    // Close form.
    echo html_writer::end_div();
    echo html_writer::end_tag('form');
} else {
    echo $PAGE->get_renderer('core_h5p')->render($ui);
}
echo $OUTPUT->footer();
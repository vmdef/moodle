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
 * Editor view page.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz {victor@moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_h5p\form\editor_form;

require_once(__DIR__ . '/../config.php');

require_login();

$context = context_system::instance();

$returnurl = new moodle_url('/');
$url = new \moodle_url('/h5p/editor.php');
$title = $heading = get_string('h5peditor', 'core_h5p');

$PAGE->set_context($context);
$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($heading);

$data = $data ?? [];

$mform = new editor_form();
$mform->data_preprocessing($data);

if ($mform->is_cancelled()) {
    redirect($returnurl);

} else if ($data = $mform->get_data()) {
    $mform->data_postprocessing($data);
}

echo $OUTPUT->header();
$mform->set_data($data);
$mform->display();
echo $OUTPUT->footer();

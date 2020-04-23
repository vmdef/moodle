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
 * Create or update contents through the specific content type editor
 *
 * @package    core_contentbank
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use contenttype_h5p\form\editor;

require('../config.php');

require_login();

$contextid = required_param('contextid', PARAM_INT);
$pluginname = required_param('plugin', PARAM_PLUGIN);
$context = context::instance_by_id($contextid, MUST_EXIST);

$returnurl = new \moodle_url('/contentbank/index.php', ['contextid' => $context->id]);

// Check plugin is enabled.
$plugin = core_plugin_manager::instance()->get_plugin_info("contenttype_$pluginname");
if (!$plugin || !$plugin->is_enabled()) {
    print_error('unsupported', 'core_contentbank', $returnurl);
}

// Create content type instance.
$classname = "\\contenttype_$pluginname\\contenttype";
if (class_exists($classname)) {
    $contenttype = new $classname($context);
} else {
    print_error('unsupported', 'core_contentbank', $returnurl);
}

// Checks the user can edit this content type.
if (!$contenttype->can_edit()) {
    print_error('unsupported', 'core_contentbank', $returnurl);
}

$values = [
    'contextid' => $contextid,
    'plugin' => $pluginname,
];

$title = get_string('contentbank');
\core_contentbank\helper::get_page_ready($context, $title, true);
if ($PAGE->course) {
    require_login($PAGE->course->id);
}

$PAGE->set_url(new \moodle_url('/contentbank/edit.php', $values));
$PAGE->set_context($context);
$PAGE->navbar->add(get_string('edit'));
$PAGE->set_title($title);

// TODO: method to get the form
$contenttypeform = "$CFG->dirroot/contentbank/contenttype/$pluginname/classes/form/editor.php";
if (file_exists($contenttypeform)) {
    require_once($contenttypeform);
} else {
    print_error('noformdesc');
}

$editor_form = new editor(null, $values);

if ($editor_form->is_cancelled()) {
    redirect($returnurl);
} else if ($data = $editor_form->get_data()) {
    $editor_form->save_content($data);
    redirect($returnurl);
}

echo $OUTPUT->header();
$editor_form->display();
echo $OUTPUT->footer();

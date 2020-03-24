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
 * Editor form action.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz {victor@moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_h5p\editor;

require_once(__DIR__ . '/../config.php');

// Get form params.
$contentid = required_param('contentid', PARAM_INT);
$h5pparams = required_param('h5pparams', PARAM_TEXT);
$h5plibrary = required_param('h5plibrary', PARAM_TEXT);
$save = optional_param('save', null, PARAM_NOTAGS);
$cancel = optional_param('cancel', null, PARAM_NOTAGS);

require_login();

// URLs to go after the form has been processed.
$saveurl = $CFG->wwwroot;
$cancelurl = $CFG->wwwroot;

// Object with the necessary data to udpate/create the content.
$content = new stdClass();
$content->id = empty($contentid) ? null : $contentid;
$content->params = $h5pparams;
$content->h5plibrary = $h5plibrary;

$editor = new editor();

if (!empty($save)) {
    // Remove metadata wrapper from form data.
    $params = json_decode($content->params);

    if (empty($params->metadata)) {
        $params->metadata = new stdClass();
    }

    if (empty($params->metadata->title)) {
        // Use a default string if not available.
        $params->metadata->title = 'Untitled';
    }

    $content->params = json_encode($params);
    $content->title = $params->metadata->title;

    $editor->save_content($content);
    redirect($saveurl);
} else {
    redirect($cancelurl);
}

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
 * The donation layout for the moodleorg theme.
 *
 * @package   theme_moodleorg
 * @copyright 2019 Moodle
 * @author    Mathew May
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$bodyattributes = $OUTPUT->body_attributes();

$renderer = $PAGE->get_renderer('theme_moodleorg');

$donationslider = new \theme_moodleorg\output\donationslider();

$widgets = (object) [
        'donationslider' => $renderer->render($donationslider),];

$templatecontext = [
        'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
        'output' => $OUTPUT,
        'widgets' => $widgets,
        'bodyattributes' => $bodyattributes,
];

echo $OUTPUT->render_from_template('theme_moodleorg/layout_donations', $templatecontext);
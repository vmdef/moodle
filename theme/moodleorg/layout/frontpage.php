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
 * The frontpage layout for the moodleorg theme.
 *
 * @package   theme_moodleorg
 * @copyright 2019 Bas Brands
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


$domain = theme_moodleorg_get_domain();

$widgets = (object) [];

if ($domain == 'org') {
    $renderer = $PAGE->get_renderer('theme_moodleorg');

    $heroslider = new \theme_moodleorg\output\heroslider();
    $news = new \theme_moodleorg\output\news();
    $focusblocks = new \theme_moodleorg\output\focusblocks();
    $feeds = new \theme_moodleorg\output\feeds();

    $widgets = (object) [
        'heroslider' => $renderer->render($heroslider),
        'news' => $renderer->render($news),
        'focusblocks' => $renderer->render($focusblocks),
        'feeds' => $renderer->render($feeds) ];
}

$extraclasses = [];
$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$blockspre = $OUTPUT->blocks('side-pre');
$blockspost = $OUTPUT->blocks('side-post');

$hassidepre = $PAGE->blocks->region_has_content('side-pre', $OUTPUT);
$hassidepost = $PAGE->blocks->region_has_content('side-post', $OUTPUT);
$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'output' => $OUTPUT,
    'widgets' => $widgets,
    'sidepreblocks' => $blockspre,
    'sidepostblocks' => $blockspost,
    'haspreblocks' => $hassidepre,
    'haspostblocks' => $hassidepost,
    'bodyattributes' => $bodyattributes,
    'siteadmin' => is_siteadmin()
];

echo $OUTPUT->render_from_template('theme_moodleorg/layout_frontpage', $templatecontext);
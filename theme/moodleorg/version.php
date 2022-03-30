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
 * Theme Moodle org.
 *
 * @package    theme_moodleorg
 * @copyright  2019 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

$plugin->version = 2022033000;
$plugin->release = '4.0';
$plugin->requires = 2022032200;
$plugin->component = 'theme_moodleorg';
$plugin->dependencies = [
    'theme_classic' => 2022011400,
];

if (isset($CFG->theme_moodleorg_domain) && $CFG->theme_moodleorg_domain === 'org') {
    // At moodle.org we also need local_moodleorg to display the front page.
    $plugin->dependencies['local_moodleorg'] = 2022033000;
}

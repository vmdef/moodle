<?php
// This file is part of the strathclyde theme for Moodle
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
 * Theme strathclyde version file.
 *
 * @package    theme_moodleorg
 * @copyright  2018 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Footnote setting.
    $name = 'theme_moodleorg/footnote';
    $title = get_string('footnote', 'theme_moodleorg');
    $description = get_string('footnotedesc', 'theme_moodleorg');
    $default = get_string('footnotedesc_default', 'theme_moodleorg');
    $setting = new admin_setting_confightmleditor($name, $title, $description, $default);
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

    // Footer sitemap
    $name = 'theme_moodleorg/footersitesmap';
    $title = get_string('footersitesmap', 'theme_moodleorg');
    $description = get_string('footersitesmap_desc', 'theme_moodleorg');
    $default = get_string('footersitesmap_default', 'theme_moodleorg');
    $setting = new admin_setting_configtextarea($name, $title, $description, $default, PARAM_TEXT, '50', '10');
    $setting->set_updatedcallback('theme_reset_all_caches');
    $settings->add($setting);

}

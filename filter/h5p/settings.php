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
 * H5P filter settings
 *
 * @package    filter_h5p
 * @copyright  2019 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    $settings->add(new admin_setting_configtextarea('filter_h5p/alloweddomains', get_string('alloweddomainslist', 'filter_h5p'),
            get_string('alloweddomainslistdesc', 'filter_h5p'), "h5p.org\n*.h5p.com"));
    $settings->add(new admin_setting_configtext('filter_h5p/frameheight',
            get_string('frameheight', 'filter_h5p'), get_string('frameheightdesc', 'filter_h5p'), 638, PARAM_INT));
    $settings->add(new admin_setting_configtext('filter_h5p/framewidth',
            get_string('framewidth', 'filter_h5p'), get_string('framewidthdesc', 'filter_h5p'), 1090, PARAM_INT));
}

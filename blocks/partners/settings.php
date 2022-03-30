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
 * Settings for the partners block
 *
 * @copyright 2014 Andrew Davis
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package   block_partners
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_configcheckbox('block_partners_downloads_ads',
                                                    get_string('downloadpartnerads', 'block_partners'),
                                                    get_string('downloadpartneradsdescription', 'block_partners'), 1));

    $settings->add(new admin_setting_configtext('block_partners_ad_url',
                                                get_string('partneradsurl', 'block_partners'),
                                                get_string('partneradsurldescription', 'block_partners'), '', PARAM_URL));

    $settings->add(new admin_setting_configtext('block_partners_ad_token',
                                                get_string('partneradstoken', 'block_partners'),
                                                get_string('partneradstokendescription', 'block_partners'), '', PARAM_ALPHANUM));
}



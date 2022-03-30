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
 * Provides local_moodleorg\manage_ads_form class
 *
 * @package     local_moodleorg
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_moodleorg;

use moodleform;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

/**
 * Represents the ad record form.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_ads_form extends moodleform {

    /**
     * Defines the form fields.
     */
    protected function definition() {

        $mform = $this->_form;
        $customdata = $this->_customdata;

        $mform->addElement('hidden', 'edit', $this->_customdata['edit']);
        $mform->setType('edit', PARAM_INT);

        $mform->addElement('text', 'partner', 'Partner ID');
        $mform->setType('partner', PARAM_NOTAGS);
        $mform->addRule('partner', null, 'required', null, 'server');

        $mform->addElement('text', 'title', 'Title', ['maxlength' => 255, 'size' => 60]);
        $mform->setType('title', PARAM_NOTAGS);
        $mform->addRule('title', null, 'required', null, 'server');

        // Adding additional Partner territories, PAR-39.
        $territories = ['CB' => 'Caribbean'];
        $countries = get_string_manager()->get_list_of_countries(true) + $territories;
        asort($countries);
        $countries = array_merge(['XX' => 'Any country'], $countries);
        $mform->addElement('select', 'country', 'Country', $countries);
        $mform->addRule('country', null, 'required', null, 'server');

        $mform->addElement('text', 'image', 'Image folder');
        $mform->setType('image', PARAM_SAFEDIR);
        $mform->addRule('image', null, 'required', null, 'server');

        $this->add_action_buttons();
    }
}

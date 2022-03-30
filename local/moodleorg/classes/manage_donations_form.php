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
 * Provides local_moodleorg\manage_donations_form class
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
 * Represents the donation record form.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class manage_donations_form extends moodleform {

    /**
     * Defines the form fields.
     */
    protected function definition() {

        $mform = $this->_form;
        $customdata = $this->_customdata;

        $mform->addElement('hidden', 'edit', $this->_customdata['edit']);
        $mform->setType('edit', PARAM_INT);

        $mform->addElement('text', 'userid', 'User ID');
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('text', 'name', 'Name', ['maxlength' => 255, 'size' => 60]);
        $mform->setType('name', PARAM_NOTAGS);

        $mform->addElement('text', 'org', 'Organisation', ['maxlength' => 255, 'size' => 60]);
        $mform->setType('org', PARAM_NOTAGS);

        $mform->addElement('text', 'url', 'URL', ['maxlength' => 255, 'size' => 60]);
        $mform->setType('url', PARAM_NOTAGS);

        $mform->addElement('text', 'amount', 'Amount', ['maxlength' => 100]);
        $mform->setType('amount', PARAM_RAW);
        $mform->addRule('amount', null, 'required', null, 'server');
        $mform->setDefault('amount', '0.00');

        $mform->addElement('date_selector', 'timedonated', 'Donated');
        $mform->addRule('timedonated', null, 'required', null, 'server');

        $this->add_action_buttons();
    }
}

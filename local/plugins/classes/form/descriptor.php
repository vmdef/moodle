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
 * Provides {@link local_plugins\form\descriptor} class
 *
 * @package     local_plugins
 * @subpackage  form
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');

use moodleform;

/**
 * Allows to define a new descriptor.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class descriptor extends moodleform {

    /**
     * Defines the form fields.
     */
    public function definition() {

        $mform = $this->_form;
        $mform->setDisableShortforms();

        $mform->addElement('hidden', 'descid');
        $mform->setType('descid', PARAM_INT);

        $mform->addElement('text', 'title', get_string('descriptortitle', 'local_plugins'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required', null, 'client');

        $mform->addElement('text', 'sortorder', get_string('descriptorsort', 'local_plugins'));
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 999);

        if (empty($this->_customdata['descid'])) {
            $mform->addElement('textarea', 'values', get_string('descriptorvalues', 'local_plugins'));
            $mform->setType('values', PARAM_RAW);
        }

        $this->add_action_buttons(false);
    }
}

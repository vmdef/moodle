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
 * Provides {@link core_contentbank\form\edit_content} class.
 *
 * @package    core_contentbank
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_contentbank\form;

require_once($CFG->libdir.'/formslib.php');

/**
 * Defines the form for editing a content.
 *
 * @package    core_contentbank
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class edit_content extends \moodleform {

    function standard_hidden_elements(){
        $mform =& $this->_form;
        $mform->addElement('hidden', 'contextid', 0); // automatic sesskey protection
        $this->_form->setType('contextid', PARAM_INT);

        $mform->addElement('hidden', 'plugin', ''); // automatic sesskey protection
        $this->_form->setType('plugin', PARAM_PLUGIN);
    }

}

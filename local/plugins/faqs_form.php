<?php

/**
 * This file contains moodle forms used to create and edit plugins as
 * well as specific plugin fields.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class local_plugins_edit_plugin_faqs_form extends moodleform {
    protected function definition() {

        $form = $this->_form;

        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);
        // description
        $form->addElement('editor', 'faqs_editor', get_string('faqs', 'local_plugins'), null, local_plugins_helper::editor_options_plugin_faqs());
        $form->setType('faqs_editor', PARAM_RAW);

        $this->add_action_buttons(true);
    }
}
<?php

/**
 * This file contains moodle forms used to create and edit software
 * versions.
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

class local_plugins_softwareversions_form extends moodleform {
    protected function definition() {

        $form = $this->_form;

        $form->addElement('header', 'softwareversionheader', $this->_customdata['formheading']);

        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);

        $form->addElement('hidden', 'action');
        $form->setType('action', PARAM_ALPHA);

        $form->addElement('select', 'name', get_string('software', 'local_plugins'), local_plugins_helper::get_supportable_software_applications());
        $form->setType('name', PARAM_TEXT);
        $form->addRule('name', get_string('required'), 'required');
        $form->addHelpButton('name', 'supportablesoftware', 'local_plugins');

        $form->addElement('text', 'version', get_string('softwareversionnumber', 'local_plugins'), array('size'=>24));
        $form->setType('version', PARAM_TEXT);
        $form->addRule('version', get_string('required'), 'required');
        $form->addHelpButton('version', 'softwareversionnumber', 'local_plugins');

        $form->addElement('text', 'releasename', get_string('softwareversionname', 'local_plugins'), array('size'=>52));
        $form->setType('releasename', PARAM_TEXT);
        $form->addRule('releasename', get_string('required'), 'required');
        $form->addHelpButton('releasename', 'softwareversionname', 'local_plugins');

        $this->add_action_buttons(true);
    }
}
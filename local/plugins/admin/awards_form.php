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

class local_plugins_awards_form extends moodleform {
    protected function definition() {
        $form = $this->_form;

        $form->addElement('header', 'awardheader', $this->_customdata['formheading']);

        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);

        $form->addElement('hidden', 'action');
        $form->setType('action', PARAM_ALPHA);

        $form->addElement('text', 'name', get_string('name', 'local_plugins'));
        $form->setType('name', PARAM_TEXT);
        $form->addRule('name', get_string('required'), 'required');

        $form->addElement('text', 'shortname', get_string('shortname', 'local_plugins'));
        $form->setType('shortname', PARAM_ALPHANUMEXT);

        $form->addElement('checkbox', 'onfrontpage', get_string('onfrontpage', 'local_plugins'));
        $form->setType('onfrontpage', PARAM_BOOL);
        $form->disabledIf('onfrontpage', 'shortname', 'eq', '');

        $form->addElement('editor', 'description_editor', get_string('description', 'local_plugins'), null, local_plugins_helper::editor_options_award_description());
        $form->setType('description_editor', PARAM_RAW);

        $form->addElement('filemanager', 'icon_filemanager', get_string('awardicon', 'local_plugins'), null, local_plugins_helper::filemanager_options_award_icon());
        $form->addHelpButton('icon_filemanager', 'awardicon', 'local_plugins');

        $this->add_action_buttons(true);
    }
}
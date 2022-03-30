<?php

/**
 * This file contains moodle forms used to create and edit plugins
 * contributor
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Marina Glancy
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class local_plugins_usersite_form extends moodleform {
    protected function definition() {
        $form = $this->_form;
        $form->addElement('header', '_formheading', $this->_customdata['formheading']);

        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);
        $form->addElement('hidden', 'action');
        $form->setType('action', PARAM_ALPHA);
        $form->setDefault('action', $this->_customdata['action']);

        $form->addElement('text', 'sitename', get_string('sitename', 'local_plugins'));
        $form->setType('sitename', PARAM_TEXT);
        $form->addHelpButton('sitename', 'sitename', 'local_plugins');
        $form->addRule('sitename', get_string('required'), 'required');

        $form->addElement('text', 'siteurl', get_string('siteurl', 'local_plugins'), array('size' => 50));
        $form->setType('siteurl', PARAM_TEXT);
        $form->addHelpButton('siteurl', 'siteurl', 'local_plugins');
        $form->addRule('siteurl', get_string('required'), 'required');


//        $form->addElement('text', 'version', get_string('version', 'local_plugins'), array('size' =>5));

        // Supported versions
        $moodlesoftwareversions = local_plugins_helper::get_software_versions_options(local_plugins_helper::get_moodle_versions());
//        $form->addElement('header', 'softwareversionheading', get_string('supportedsoftware', 'local_plugins'));
        $cnt = 0;
        foreach ($moodlesoftwareversions as $moodleversion) {
            $select = $form->addElement('select', "version", format_string($moodleversion->name), $moodleversion->releasenames, array('id' => 'id_version_'.(++$cnt)));
        }
        $form->setType('version', PARAM_INT);
        $form->addHelpButton('version', 'version', 'local_plugins');
        $form->addRule('version', get_string('required'), 'required');

        $buttonarray = array();
        if ($this->_customdata['action'] == 'edit') {
            $buttonarray[] = &$form->createElement('submit', 'submitbutton', get_string('update'));
        } else {
            $buttonarray[] = &$form->createElement('submit', 'submitbutton', get_string('add'));
        }

        $buttonarray[] = &$form->createElement('cancel');
        $form->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $form->closeHeaderBefore('buttonar');
    }

    public function set_data($usersite) {
        $data = new stdClass();
        $data->id = $usersite->id;
        $data->sitename = $usersite->sitename;
        $data->siteurl = $usersite->siteurl;
        $data->version = $usersite->version;
        parent::set_data($data);
    }

    /**
     * Validates the user site data
     *
     * @global moodle_database $DB
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;
        $errors = parent::validation($data, $files);
        //@todo siteurl http validation
        foreach (array('siteurl') as $key) {
            if (!empty($data[$key]) && ($data[$key] != clean_param($data[$key], PARAM_URL) || strpos($data[$key],':')=== FALSE) ) {
                $errors[$key] = get_string('invalidurl', 'local_plugins');
            }
        }

        return $errors;
    }
}

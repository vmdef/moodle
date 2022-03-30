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

class local_plugins_contributor_form extends moodleform {
    protected function definition() {
        $form = $this->_form;
        $form->addElement('header', '_formheading', $this->_customdata['formheading']);

        $form->addElement('hidden', 'pluginid');
        $form->setType('pluginid', PARAM_INT);

        $contributor = $this->_customdata['contributor'];
        if (empty($contributor->id)) {
            $form->addElement('text', 'contributor', get_string('username', 'local_plugins'));
            $form->setType('contributor', PARAM_TEXT);
            $form->addHelpButton('contributor', 'username', 'local_plugins');
            $form->addRule('contributor', get_string('required'), 'required');
        } else {
            $form->addElement('static', 'contributor_static', get_string('username', 'local_plugins'), '<b>'.$contributor->username.'</b>');
            $form->addElement('hidden', 'id');
            $form->setType('id', PARAM_INT);
        }

        $form->addElement('text', 'type', get_string('contributorrole', 'local_plugins'), array('size' => 50));
        $form->setType('type', PARAM_TEXT);
        $form->addHelpButton('type', 'contributorrole', 'local_plugins');

        if ($this->can_reset_maintainer($contributor)) {
            $form->addElement('checkbox', 'maintainer', get_string('maintainer', 'local_plugins'));
            $form->setType('maintainer', PARAM_BOOL);
            $form->addHelpButton('maintainer', 'maintainer', 'local_plugins');
        } else {
            if ($contributor->is_lead_maintainer()) {
                $label = get_string('leadmaintainer', 'local_plugins');
            } else {
                $label = get_string('yes');
            }
            $form->addElement('static', 'maintainer_static', get_string('maintainer', 'local_plugins'), '<b>'. $label. '</b>');
            $form->addHelpButton('maintainer_static', 'maintainer', 'local_plugins');
        }

        $buttonarray = array();
        if (empty($contributor->id)) {
            $buttonarray[] = &$form->createElement('submit', 'submitbutton', get_string('add'));
        } else {
            $buttonarray[] = &$form->createElement('submit', 'submitbutton', get_string('edit'));
        }
        if ((empty($contributor->id) || !$contributor->is_lead_maintainer()) && $contributor->plugin->can_change_lead_maintainer()) {
            $buttonarray[] = &$form->createElement('submit', 'leadmaintainer', get_string('makeleadmaintainer', 'local_plugins'));
        }
        if (!empty($contributor->id) && !$contributor->is_lead_maintainer()) {
            // if contributor is a lead maintainer it can not be deleted
            $buttonarray[] = &$form->createElement('submit', 'deletebutton', get_string('delete'));
        }
        $buttonarray[] = &$form->createElement('cancel');
        $form->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $form->closeHeaderBefore('buttonar');
    }

    public function can_reset_maintainer($contributor) {
        if (!empty($contributor->id) && $contributor->is_lead_maintainer()) {
            // protect the user from removing maintainer privilege from the lead maintainer
            return false;
        }
        return true;
    }

    public function set_data(local_plugins_contributor $contributor) {
        $data = new stdClass;
        $data->pluginid = $contributor->pluginid;

        if (!empty($contributor->id)) {
            $data->id = $contributor->id;
            $data->type = $contributor->type;
            $data->maintainer = $contributor->is_maintainer();
        } else {
            $data->type = '';
            $data->maintainer = false;
        }
        parent::set_data($data);
    }

    /**
     * Validates the username of new contributor
     *
     * @global moodle_database $DB
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        if (array_key_exists('contributor', $data) && !empty($data['contributor'])) {
            $userid = local_plugins_helper::search_for_user($data['contributor']);
            if (!$userid) {
                // user does not exist
                $errors['contributor'] = get_string('exc_usernotfound', 'local_plugins');
            } else {
                if ($DB->record_exists('local_plugins_contributor', array('pluginid' => $data['pluginid'], 'userid' => $userid))) {
                    // user is already contributor
                    $errors['contributor'] = get_string('exc_useriscontributor', 'local_plugins');
                }
            }
        }
        return $errors;
    }
}

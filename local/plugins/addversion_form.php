<?php

/**
 * This file contains moodle forms used to create and edit plugin
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
require_once($CFG->dirroot . '/local/plugins/pluginversion_form.php');
require_once($CFG->dirroot. '/local/plugins/lib/archive_validator.php');

class local_plugins_upload_version_form extends moodleform {
    protected function definition() {
        $required = get_string('required', 'local_plugins');

        $form = $this->_form;

        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);

        $form->addElement('hidden', 'archiveparsed');
        $form->setType('archiveparsed', PARAM_INT);

        local_plugins_edit_version_form::populate_form($form, $this->_customdata, 'add', 'version_');

        $buttonarray = array();
        $buttonarray[] = &$form->createElement('submit', 'continue', get_string('continue'));
        $buttonarray[] = &$form->createElement('submit', 'backbutton', get_string('back'));
        $form->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $form->closeHeaderBefore('buttonar');
        $form->addElement('hidden', 'curstep');
        $form->setType('curstep', PARAM_INT);
    }

    function prepare_to_display() {
        /* This is a multi-screen form. Each screen has the number ($step)
         * $step == 1 : Moodle requirements select is advanced and not required. Show version upload
         * $step == 2 (not able to parse from zip) - Moodle reqs is required. show version upload
         * $step == 3 (warnings confirmation) - Moodle reqs and version upload are static and hidden
         * $step == 4 : Moodle reqs and version upload are static and hidden. Version fields are shown
         *
         * flow:
         * 1->[2]->4->end (zip parsed with no warnings)
         * 1->[2]->3->4->end (zip with warnings, user decides to ignore them)
         * 1->[2]->2->... (zip with errors),
         * 1->[2]->3->2->...  (zip with warnings, user stepped back to correct them)
         * ([2] means that step 2 is shown if needed only)
         */
        if ($this->is_submitted()) {
            $this->validate_defined_fields(); // validate form only if it was submitted
        }
        $step = $this->nextstep; // set during validation

        $form = $this->_form;
        $step1advancedelemnets = array('version_options[renameroot]', 'version_options[autoremove]',
            'version_options[renamereadme]', 'version_softwareversion[Moodle]');
        $step1elements = array('version_archive_filemanager', 'version_softwareversion[Moodle]', 'versioninfoheading', 'version_options[renameroot]', 'version_options[autoremove]', 'version_options[renamereadme]');
        if ($step < 4) {
            $form->getElement('archiveparsed')->setValue(0);
        }

        if (!empty($this->validator)) {
            // fill with data found during validation
            $this->validator->populate_form($form, 'version_', $step == 4);
        }

        foreach ($form->_elements as $element) {

            $elname = $element->getName();

            if ($elname === 'version_softwareversion[Moodle]') {
                // Optional only at the first screen (giving a chance to be read from ZIP).
                if ($step > 1) {
                    $form->addRule('version_softwareversion[Moodle]', get_string('required'), 'required', null, 'client');
                }

                // show on all steps, just freeze in step 3
                if ($step == 3) {
                    $element->freeze();
                }

            } else if (in_array($elname, $step1elements)) {
                // elements to show only in steps 1, 2 and 3
                if ($step == 3 || $step == 4) {
                    // replace them with hidden elements
                    if ($elname === 'version_archive_filemanager') {
                        //filemanager does not support freezing!
                        $form->removeElement($elname);
                        $form->addElement('hidden', $elname, $element->getValue());

                    } else if (preg_match("/^version_options/", $elname) && $step == 4) {
                        $form->removeElement($elname);
                        $form->addElement('hidden', $elname, $element->getValue());

                    } else {
                        $element->freeze();
                        $element->setPersistantFreeze(true);
                    }
                }

            } else if ($step < 4 and $elname === 'version_vcstag') {
                if ($element->getValue() !== null) {
                    // It was set explicitly to the value returned by the {@link local_plugins_vcs_manager} in addversion.php.
                    $element->freeze();
                    $element->setPersistantFreeze(true);

                } else {
                    $form->removeElement($element->getName());
                }

            } else if ($step < 4 && (preg_match("/^version_/", $element->getName()) || $element->getType() == 'header')) {
                // remove most of elements from steps 1, 2, 3
                $form->removeElement($element->getName());
            }
        }
        if ($step == 1) {
            foreach ($step1advancedelemnets as $elname) {
                $form->setAdvanced($elname);
            }
        }
        if ($step != 3) {
            // freeze button 'Fix'
            $buttons = $form->getElement('buttonar')->getElements();
            $buttons[1]->setValue('');
            $buttons[1]->_flagFrozen = true;
            if ($step == 4) {
                // change button 'Continue' to 'Register'
                $buttons[0]->setValue(get_string('addnewversion', 'local_plugins'));
            }
        }
        if ($step == 4) {
            $required = get_string('required', 'local_plugins');
            $form->addRule('version_version', $required, 'required');
        }
        $form->getElement('curstep')->setValue($step);
    }

    var $nextstep = 1;

    function display() {
        $this->prepare_to_display();
        $renderer = local_plugins_get_renderer();
        if ($this->nextstep == 1) {
            echo $renderer->settings_text('local_plugins_addversiontext', get_string('addversiontextdefault', 'local_plugins'), array('class' => 'addversiontext'));
        }
        if ($this->nextstep < 4 && !empty($this->validator)) {
            echo $renderer->display_validation_messages($this->validator);
        }
        parent::display();
    }

    var $validator = null;

    /**
     * Defines validation rules for this form.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *               or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    function validation($data, $files) {
        $err = parent::validation($data, $files);
        foreach (array('version_changelogurl', 'version_altdownloadurl', 'version_vcsrepositoryurl') as $key) {
            if (!empty($data[$key]) && $data[$key] != clean_param($data[$key], PARAM_URL)) {
                $err[$key] = get_string('invalidurl', 'local_plugins');
            }
        }
        // validate fields and RULES added in prepare_to_display(), because they are not validated automatically
        if ($data['curstep'] == 4) {
            foreach (array('version_version') as $key) {
                if (!array_key_exists($key, $data) || empty($data[$key])) {
                    $err[$key] = get_string('required');
                }
            }
        }
        //Uploaded archive validation
        $plugin = local_plugins_helper::get_plugin($data['id']);
        $requires = local_plugins_edit_version_form::get_moodle_requirements($data, 'version_');
        $options = array();
        if (array_key_exists('version_options', $data)) {
            $options = $data['version_options'];
            $step2onerror = true;
        }
        $this->validator = local_plugins_archive_validator::create_from_draft($data['version_archive_filemanager'],
                $plugin->category, $plugin->frankenstyle, $requires, $options);
        $errorlevel = $this->validator->highest_error_level;

        if ($data['curstep'] == 4) {
            $err = local_plugins_edit_version_form::validate_updateable($data, $err, $this->_customdata, 'version_');
        }

        // set next step
        if (!empty($err)) {
            $this->nextstep = $data['curstep'];
        } else if ($errorlevel == local_plugins_archive_validator::ERROR_LEVEL_CLASSIFICATION) {
            $this->nextstep = 2;
            $err['curstep'] = 1; // validation errors are shown above (there may be several error and/or warning messages)
        } else if ($errorlevel == local_plugins_archive_validator::ERROR_LEVEL_FILE || $errorlevel == local_plugins_archive_validator::ERROR_LEVEL_CONTENT) {
            $this->nextstep = 2;
            $err['curstep'] = 1; // validation errors are shown above (there may be several error and/or warning messages)
        } else if ($data['curstep'] == 4) {
            $this->nextstep = 5; // ready to register!
        } else {
            // no errors found, but we need to proceed to another step
            if ($errorlevel == local_plugins_archive_validator::ERROR_LEVEL_WARNING && $data['curstep']<3) {
                $this->nextstep = 3;
            } else if ($data['curstep'] == 3 && array_key_exists('backbutton', $data)) {
                $this->nextstep = 2;
            } else {
                $this->nextstep = 4;
            }
            $err['curstep'] = 1; // go to the next step (form is not yet completed)
        }
        return $err;
    }

    function get_data() {
        $data = parent::get_data();
        $data = local_plugins_edit_version_form::retrieve_software_versions($data, 'version_');
        if (!isset($data->version_updateableid)) {
            $data->version_updateableid = array();
        }
        return $data;
    }
}
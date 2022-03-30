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
// Required for the local_plugins_edit_version_form::populate_form method.
require_once($CFG->dirroot . '/local/plugins/pluginversion_form.php');
require_once($CFG->dirroot. '/local/plugins/lib/archive_validator.php');

class local_plugins_register_plugin_form extends moodleform {

    protected function definition() {
        $form = $this->_form;
        $forcecategory = $this->_customdata['forcecategory'];

        $form->addElement('header', '_pluginheading', get_string('plugininformation', 'local_plugins'));

        // name
        $form->addElement('text', 'name', get_string('name', 'local_plugins'), array('size'=>52));
        $form->setType('name', PARAM_TEXT);

        // Category ID
        $element = $form->addElement('select', 'categoryid', get_string('category', 'local_plugins'), array('' => '') + local_plugins_helper::get_category_options());
        if ($forcecategory) {
            $element->setSelected($forcecategory->id);
            $element->freeze();
            $form->addElement('hidden', 'forcecategoryid', $forcecategory->id);
            $form->setType('forcecategoryid', PARAM_INT);
        }
        $form->setType('categoryid', PARAM_INT);

        // frankenstyle
        if (empty($forcecategory) || !empty($forcecategory->plugin_frankenstyle_prefix)) {
            $form->addElement('text', 'frankenstyle', get_string('pluginfrankenstyle', 'local_plugins'), array('size'=>52));
            $form->setType('frankenstyle', PARAM_ALPHANUMEXT);
            $form->addHelpButton('frankenstyle', 'pluginfrankenstyle', 'local_plugins');
        }

        // shortdescription
        $form->addElement('textarea', 'shortdescription', get_string('shortdescription', 'local_plugins'), array('rows'=>3,'cols'=>75));
        $form->setType('shortdescription', PARAM_TEXT);
        $form->addHelpButton('shortdescription', 'pluginshortdescription', 'local_plugins');

        $form->addElement('hidden', 'archiveparsed');
        $form->setType('archiveparsed', PARAM_INT);

/*
        // description
        $form->addElement('editor', 'description_editor', get_string('description', 'local_plugins'), null, local_plugins_helper::editor_options_plugin_description());
        $form->setType('description_editor', PARAM_RAW);

        // documentationurl
        $form->addElement('text', 'documentationurl', get_string('documentationurl', 'local_plugins'), array('size'=>52));
        $form->setType('documentationurl', PARAM_TEXT);

        // websiteurl
        $form->addElement('text', 'websiteurl', get_string('websiteurl', 'local_plugins'), array('size'=>52));
        $form->setType('websiteurl', PARAM_TEXT);
        $form->addHelpButton('websiteurl', 'websiteurl', 'local_plugins');

        // sourcecontrolurl
        $form->addElement('text', 'sourcecontrolurl', get_string('sourcecontrolurl', 'local_plugins'), array('size'=>52));
        $form->setType('sourcecontrolurl', PARAM_TEXT);
        $form->addHelpButton('sourcecontrolurl', 'sourcecontrolurl', 'local_plugins');

        // bugtrackerurl
        $form->addElement('text', 'bugtrackerurl', get_string('bugtrackerurl', 'local_plugins'), array('size'=>52));
        $form->setType('bugtrackerurl', PARAM_TEXT);

        // discussionurl
        $form->addElement('text', 'discussionurl', get_string('discussionurl', 'local_plugins'), array('size'=>52));
        $form->setType('discussionurl', PARAM_TEXT);

        // screenshots
        $form->addElement('filemanager', 'screenshots_filemanager', get_string('screenshots', 'local_plugins'), null, local_plugins_helper::filemanager_options_plugin_screenshots());
        $form->addHelpButton('screenshots_filemanager', 'screenshots', 'local_plugins');

        // logo
        $form->addElement('filemanager', 'logo_filemanager', get_string('logo', 'local_plugins'), null, local_plugins_helper::filemanager_options_plugin_logo());
        $form->addHelpButton('logo_filemanager', 'logo', 'local_plugins');
*/
        local_plugins_edit_version_form::populate_form($form, $this->_customdata, 'new', 'version_');

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
         * $step == 1 : category, frankenstyle and Moodle requirements are advanced and not required. Show version upload
         * $step == 2 (not able to parse from zip) - category and Moodle reqs are required, frankenstyle is normal. show version upload
         * $step == 3 (warnings confirmation) - category, frankenstyle, Moodle reqs and version upload are static and hidden
         * $step == 4 : category, frankenstyle, Moodle reqs and version upload are static and hidden. Plugin name and version fields are shown
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
        $step1advancedelemnets = array('categoryid', 'frankenstyle', 'version_options[renameroot]', 'version_options[autoremove]',
            'version_options[renamereadme]', 'version_softwareversion[Moodle]');
        $step1elements = array('version_archive_filemanager', 'version_softwareversion[Moodle]', '_pluginheading', 'categoryid', 'frankenstyle', 'forcecategoryid', "version_options[renameroot]", 'version_options[autoremove]', 'version_options[renamereadme]');
        if ($step < 4) {
            $form->getElement('archiveparsed')->setValue(0);
        }
        if (!empty($this->validator)) {
            // fill with data found during validation
            $this->validator->populate_form($form, 'version_', $step == 4);
        }
        foreach ($form->_elements as $element) {
            //echo $element->getType()." : ". $element->getName()."\n";
            if ($element->getName() == 'version_softwareversion[Moodle]') {
                // Optional only at the first screen (giving a chance to be read from ZIP).
                if ($step > 1) {
                    $form->addRule('version_softwareversion[Moodle]', get_string('required'), 'required', null, 'client');
                }

                // show on all steps, just freeze in step 3
                if ($step == 3) {
                    $element->freeze();
                }
            } else if (in_array($element->getName(), $step1elements)) {
                // elements to show only in steps 1, 2 and 3
                if ($step == 3 || $step == 4) {
                    // replace them with hidden elements
                    if ($element->getName() == 'version_archive_filemanager') {
                        //filemanager does not support freezing!
                        $form->removeElement($element->getName());
                        $form->addElement('hidden', $element->getName(), $element->getValue());
                        //TODO retrieve $filename from draftid!
                        //$filename = $element->getValue();
                        //$form->insertElementBefore(MoodleQuickForm::createElement('static', $element->getName().'_static', $element->getLabel(), $filename), 'buttonar');
                    } else if (preg_match("/^version_options/", $element->getName()) && $step == 4) {
                        $form->removeElement($element->getName());
                        $form->addElement('hidden', $element->getName(), $element->getValue());
                    } else {
                        $element->freeze();
                        $element->setPersistantFreeze(true);
                    }
                }
            } else if ($step<4 && (preg_match("/^version_/", $element->getName())
                    || $element->getName() == 'name'
                    || $element->getName() == 'shortdescription'
                    || $element->getType() == 'header')) {
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
                $buttons[0]->setValue(get_string('createnewplugin', 'local_plugins'));
            }
        }
        if ($step == 4) {
            $required = get_string('required', 'local_plugins');
            $form->addRule('name', $required, 'required');
            $form->addRule('shortdescription', $required, 'required');
            $form->addRule('version_version', $required, 'required');
        }
        $form->getElement('curstep')->setValue($step);
    }

    var $nextstep = 1;

    function display() {
        $this->prepare_to_display();
        $renderer = local_plugins_get_renderer();
        if ($this->nextstep == 1) {
            echo $renderer->settings_text('local_plugins_registerplugintext', get_string('registerplugintextdefault', 'local_plugins'), array('class' => 'registerplugintext'));
        }
        if ($this->nextstep < 4 && !empty($this->validator)) {
            echo $renderer->display_validation_messages($this->validator);
        }
        parent::display();
    }

    var $validator = null;

    function validation($data, $files) {
        /* validation:
         * no archive or more than one or wrong type or empty, etc. -> step=2 - go to step2, otherwise go to step1
         * was unable to determine category, reqs and frankenstyle -> go to step2
         * archive did not pass validation -> go to step2
         * step<4 and archive has warnings -> go to step3
         * validate required fields, urls, etc. If all passed move to step4, otherwise stay on the same step
         */
        $err = parent::validation($data, $files);
        // validate fields and RULES added in prepare_to_display(), because they are not validated automatically
        if ($data['curstep'] == 4) {
            foreach (array('name', 'shortdescription','version_version') as $key) {
                if (!array_key_exists($key, $data) || empty($data[$key])) {
                    $err[$key] = get_string('required');
                }
            }
        }
        
        // Category and frankenstyle validation
        $category = $frankenstyle = null;
        $step2onerror = false; // if there is error with zip, we should redirect user to step 1 or 2
        if (array_key_exists('categoryid', $data)) {
            $category = local_plugins_helper::get_category($data['categoryid']);
            $step2onerror = true;
        }
        if (array_key_exists('frankenstyle', $data) && strlen(trim($data['frankenstyle']))) {
            $frankenstyle = trim($data['frankenstyle']);
            if (!empty($category) && $frankenstyle == $category->plugin_frankenstyle_prefix) {
                $frankenstyle = null;
            } else {
                $step2onerror = true;
            }
        }
        $requires = local_plugins_edit_version_form::get_moodle_requirements($data, 'version_');
        if (!empty($requires)) {
            $step2onerror = true;
        }
        $options = array();
        if (array_key_exists('version_options', $data)) {
            $options = $data['version_options'];
            $step2onerror = true;
        }
        $this->validator = local_plugins_archive_validator::create_from_draft($data['version_archive_filemanager'], $category, $frankenstyle, $requires, $options);
        $category = $this->validator->category;
        $frankenstyle = $this->validator->frankenstyle;
        $errorlevel = $this->validator->highest_error_level;
        if (!empty($category) || !empty($frankenstyle)) {
            $step2onerror = true;
        }

        if (!empty($category) && !$category->can_create_plugin()) {
            $err['categoryid'] = get_string('invalidcategory', 'local_plugins');
        } else if (!empty($category) && empty($category->plugin_frankenstyle_prefix) && !empty($frankenstyle)) {
            $err['frankenstyle'] = get_string('mustbeemptyfrankenstyle', 'local_plugins', $category->formatted_name);
        } else if (!empty($frankenstyle)) {
            if (!empty($category)) {
                $frankenstyleregexp = local_plugins_helper::validate_frankenstyle_regexp($category->plugin_frankenstyle_prefix);
            } else {
                $frankenstyleregexp = local_plugins_helper::validate_frankenstyle_regexp(true);
            }
            if (!preg_match($frankenstyleregexp, $frankenstyle)) {
                // frankenstyle is not well-formed
                $err['frankenstyle'] = get_string('invalidfrankenstyle', 'local_plugins', $frankenstyleregexp);
            } else if (local_plugins_helper::get_plugin_by_frankenstyle($frankenstyle)) {
                // plugin with the same frankenstyle is already registered
                $err['frankenstyle'] = get_string('frankenstyleexists', 'local_plugins', $frankenstyle);
            }
        }
        // set next step
        if (array_key_exists('frankenstyle', $err) || array_key_exists('categoryid', $err)) {
            $this->nextstep = 2;
        } else if (!empty($err)) {
            $this->nextstep = $data['curstep'];
        } else if ($errorlevel == local_plugins_archive_validator::ERROR_LEVEL_CLASSIFICATION) {
            $this->nextstep = 2;
            $err['curstep'] = 1; // validation errors are shown above (there may be several error and/or warning messages)
        } else if ($errorlevel == local_plugins_archive_validator::ERROR_LEVEL_FILE || $errorlevel == local_plugins_archive_validator::ERROR_LEVEL_CONTENT) {
            if ($data['curstep'] == 2 || $step2onerror) {
                $this->nextstep = 2;
            } else {
                $this->nextstep = 1;
            }
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
        return $data;
    }

    function ready_to_register_plugin() {
        return ($this->is_submitted() && $this->is_validated() && $this->nextstep == 5);
    }
}
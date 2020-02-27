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
 * H5P editor form
 *
 * @package    core_h5p
 * @copyright  2016 Joubel AS <contact@joubel.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace core_h5p\form;

use core_h5p\factory;
use H5PCore;
use core_h5p\framework;
use tool_dataprivacy\context_instance;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir."/formslib.php");

class editor_form extends \moodleform {
    // TODO: h5p context
    private $context;

    public function definition() {
        global $CFG, $COURSE, $PAGE;

        // TODO: get proper context
        $this->context = \context_system::instance();

        $mform =& $this->_form;
        // Name.
        $mform->addElement('hidden', 'name', '');
        $mform->setType('name', PARAM_TEXT);

        // Action.
        $h5paction = array();
        $h5paction[] = $mform->createElement('radio', 'h5paction', '', get_string('upload', 'core_h5p'), 'upload');
        $h5paction[] = $mform->createElement('radio', 'h5paction', '', get_string('create', 'core_h5p'), 'create');
        $mform->addGroup($h5paction, 'h5pactiongroup', get_string('action', 'core_h5p'), array('<br/>'), false);
        $mform->setDefault('h5paction', 'create');
        // Upload.
        $mform->addElement('filepicker', 'h5pfile', get_string('h5pfile', 'core_h5p'), null,
            array('maxbytes' => $COURSE->maxbytes, 'accepted_types' => '*'));
        // Editor placeholder.
        if ($CFG->theme == 'boost' || in_array('boost', $PAGE->theme->parents)) {
            $h5peditor   = [];
            $h5peditor[] = $mform->createElement('html',
                '<div class="h5p-editor">' . get_string('javascriptloading', 'core_h5p') . '</div>');
            $mform->addGroup($h5peditor, 'h5peditor', get_string('editor', 'core_h5p'));
        } else {
            $mform->addElement('static', 'h5peditor', get_string('editor', 'core_h5p'),
                '<div class="h5p-editor">' . get_string('javascriptloading', 'core_h5p') . '</div>');
        }
        // Hidden fields.
        $mform->addElement('hidden', 'h5plibrary', '');
        $mform->setType('h5plibrary', PARAM_RAW);
        $mform->addElement('hidden', 'h5pparams', '');
        $mform->setType('h5pparams', PARAM_RAW);
        $mform->addElement('hidden', 'h5pmaxscore', '');
        $mform->setType('h5pmaxscore', PARAM_INT);
        //$core = \mod_hvp\framework::instance();
        $factory = new factory();
        $core = $factory->get_core();

        $displayoptions = $core->getDisplayOptionsForEdit();
        if (isset($displayoptions[H5PCore::DISPLAY_OPTION_FRAME])) {
            // Display options group.
            $mform->addElement('header', 'displayoptions', get_string('displayoptions', 'core_h5p'));
            $mform->addElement('checkbox', H5PCore::DISPLAY_OPTION_FRAME, get_string('enableframe', 'core_h5p'));
            $mform->setType(H5PCore::DISPLAY_OPTION_FRAME, PARAM_BOOL);
            $mform->setDefault(H5PCore::DISPLAY_OPTION_FRAME, true);
            if (isset($displayoptions[H5PCore::DISPLAY_OPTION_DOWNLOAD])) {
                $mform->addElement('checkbox', H5PCore::DISPLAY_OPTION_DOWNLOAD, get_string('enabledownload', 'core_h5p'));
                $mform->setType(H5PCore::DISPLAY_OPTION_DOWNLOAD, PARAM_BOOL);
                $mform->setDefault(H5PCore::DISPLAY_OPTION_DOWNLOAD, $displayoptions[H5PCore::DISPLAY_OPTION_DOWNLOAD]);
                $mform->disabledIf(H5PCore::DISPLAY_OPTION_DOWNLOAD, 'frame');
            }
            if (isset($displayoptions[H5PCore::DISPLAY_OPTION_EMBED])) {
                $mform->addElement('checkbox', H5PCore::DISPLAY_OPTION_EMBED, get_string('enableembed', 'core_h5p'));
                $mform->setType(H5PCore::DISPLAY_OPTION_EMBED, PARAM_BOOL);
                $mform->setDefault(H5PCore::DISPLAY_OPTION_EMBED, $displayoptions[H5PCore::DISPLAY_OPTION_EMBED]);
                $mform->disabledIf(H5PCore::DISPLAY_OPTION_EMBED, 'frame');
            }
            if (isset($displayoptions[H5PCore::DISPLAY_OPTION_COPYRIGHT])) {
                $mform->addElement('checkbox', H5PCore::DISPLAY_OPTION_COPYRIGHT, get_string('enablecopyright', 'core_h5p'));
                $mform->setType(H5PCore::DISPLAY_OPTION_COPYRIGHT, PARAM_BOOL);
                $mform->setDefault(H5PCore::DISPLAY_OPTION_COPYRIGHT, $displayoptions[H5PCore::DISPLAY_OPTION_COPYRIGHT]);
                $mform->disabledIf(H5PCore::DISPLAY_OPTION_COPYRIGHT, 'frame');
            }
        }

        $this->add_action_buttons();

    }
    /**
     * Sets display options within default values
     *
     * @param $defaultvalues
     */
    private function set_display_options(&$defaultvalues) {
        // Individual display options are not stored, must be extracted from disable.
        if (isset($defaultvalues['disable'])) {
            //$h5pcore = \mod_hvp\framework::instance('core');
            $factory = new factory();
            $h5pcore = $factory->get_core();
            $displayoptions = $h5pcore->getDisplayOptionsForEdit($defaultvalues['disable']);
            if (isset ($displayoptions[H5PCore::DISPLAY_OPTION_FRAME])) {
                $defaultvalues[H5PCore::DISPLAY_OPTION_FRAME] = $displayoptions[H5PCore::DISPLAY_OPTION_FRAME];
            }
            if (isset($displayoptions[H5PCore::DISPLAY_OPTION_DOWNLOAD])) {
                $defaultvalues[H5PCore::DISPLAY_OPTION_DOWNLOAD] = $displayoptions[H5PCore::DISPLAY_OPTION_DOWNLOAD];
            }
            if (isset($displayoptions[H5PCore::DISPLAY_OPTION_EMBED])) {
                $defaultvalues[H5PCore::DISPLAY_OPTION_EMBED] = $displayoptions[H5PCore::DISPLAY_OPTION_EMBED];
            }
            if (isset($displayoptions[H5PCore::DISPLAY_OPTION_COPYRIGHT])) {
                $defaultvalues[H5PCore::DISPLAY_OPTION_COPYRIGHT] = $displayoptions[H5PCore::DISPLAY_OPTION_COPYRIGHT];
            }
        }
    }
    /**
     * Sets max grade in default values from grade item
     *
     * @param $content
     * @param $defaultvalues
     */
    private function set_max_grade($content, &$defaultvalues) {
        // Set default maxgrade.
        if (isset($content) && isset($content['id'])
            && isset($defaultvalues) && isset($defaultvalues['course'])) {
            // Get the gradeitem and set maxgrade.
            $gradeitem = grade_item::fetch(array(
                'itemtype' => 'mod',
                'itemmodule' => 'hvp',
                'iteminstance' => $content['id'],
                'courseid' => $defaultvalues['course']
            ));
            if (isset($gradeitem) && isset($gradeitem->grademax)) {
                $defaultvalues['maximumgrade'] = $gradeitem->grademax;
            }
        }
    }
    public function data_preprocessing(&$defaultvalues) {
        global $DB, $PAGE;
        //$core = \mod_hvp\framework::instance();
        $factory = new factory();
        $core = $factory->get_core();

        $content = null;
        if (!empty($defaultvalues['id'])) {
            // Load Content.
            $content = $core->loadContent($defaultvalues['id']);
            if ($content === null) {
                print_error('invalidhvp');
            }
        }
        $this->set_max_grade($content, $defaultvalues);
        // Aaah.. we meet again h5pfile!
        $draftitemid = file_get_submitted_draft_itemid('h5pfile');
        file_prepare_draft_area($draftitemid, $this->context->id, 'mod_hvp', 'package', 0);
        $defaultvalues['h5pfile'] = $draftitemid;
        $this->set_display_options($defaultvalues);
        // Determine default action.
        if (!get_config('core_h5p', 'hub_is_enabled') && $content === null &&
            $DB->get_field_sql("SELECT id FROM {h5p_libraries} WHERE runnable = 1", null, IGNORE_MULTIPLE) === false) {
            $defaultvalues['h5paction'] = 'upload';
        }
        // Set editor defaults.
        $defaultvalues['h5plibrary'] = ($content === null ? 0 : H5PCore::libraryToString($content['library']));
        // Combine params and metadata in one JSON object.
        $params = ($content === null ? '{}' : $core->filterParameters($content));
        $maincontentdata = array('params' => json_decode($params));
        if (isset($content['metadata'])) {
            $maincontentdata['metadata'] = $content['metadata'];
        }
        $defaultvalues['h5pparams'] = json_encode($maincontentdata, true);
        // Add required editor assets.
        require_once('locallib.php');
        $mformid = $this->_form->getAttribute('id');
        \hvp_add_editor_assets($content === null ? null : $defaultvalues['id'], $mformid);
    }
    /**
     * Validate uploaded H5P
     *
     * @param $data
     * @param $errors
     */
    private function validate_upload($data, &$errors) {
        global $CFG;
        if (empty($data['h5pfile'])) {
            // Field missing.
            $errors['h5pfile'] = get_string('required');
        } else {
            $files = $this->get_draft_files('h5pfile');
            if (count($files) < 1) {
                // No file uploaded.
                $errors['h5pfile'] = get_string('required');
            } else {
                // Prepare to validate package.
                $file = reset($files);
                //$interface = \mod_hvp\framework::instance('interface');
                $factory = new factory();
                $interface = $factory->get_framework();
                $path = $CFG->tempdir . uniqid('/hvp-');
                $interface->getUploadedH5pFolderPath($path);
                $path .= '.h5p';
                $interface->getUploadedH5pPath($path);
                $file->copy_content_to($path);

                //$h5pvalidator = \mod_hvp\framework::instance('validator');
                $h5pvalidator = $factory->get_validator();
                if (! $h5pvalidator->isValidPackage()) {
                    // Errors while validating the package.
                    $errors = array_map(function ($message) {
                        return $message->message;
                    }, $interface->getMessages('error'));
                    $messages = array_merge($interface->getMessages('info'), $errors);
                    $errors['h5pfile'] = implode('<br/>', $messages);
                } else {
                    foreach ($h5pvalidator->h5pC->mainJsonData['preloadedDependencies'] as $dep) {
                        if ($dep['machineName'] === $h5pvalidator->h5pC->mainJsonData['mainLibrary']) {
                            if ($h5pvalidator->h5pF->libraryHasUpgrade($dep)) {
                                // We do not allow storing old content due to security concerns.
                                $errors['h5pfile'] = get_string('olduploadoldcontent', 'core_h5p');
                            }
                        }
                    }
                }
            }
        }
    }
    /**
     * Validate new H5P
     *
     * @param $data
     */
    private function validate_created(&$data, &$errors) {
        // Validate library and params used in editor.
        //$core = \mod_hvp\framework::instance();
        $factory = new factory();
        $core = $factory->get_core();
        // Get library array from string.
        $library = H5PCore::libraryFromString($data['h5plibrary']);
        if (!$library) {
            $errors['h5peditor'] = get_string('librarynotselected', 'core_h5p');
        } else {
            // Check that library exists.
            $library['libraryId'] = $core->h5pF->getLibraryId($library['machineName'],
                $library['majorVersion'],
                $library['minorVersion']);
            if (!$library['libraryId']) {
                $errors['h5peditor'] = get_string('nosuchlibrary', 'core_h5p');
            } else {
                $data['h5plibrary'] = $library;
                if ($core->h5pF->libraryHasUpgrade($library)) {
                    // We do not allow storing old content due to security concerns.
                    $errors['h5peditor'] = get_string('anunexpectedsave', 'core_h5p');
                } else {
                    // Verify that parameters are valid.
                    if (empty($data['h5pparams'])) {
                        $errors['h5peditor'] = get_string('noparameters', 'core_h5p');
                    } else {
                        $params = json_decode($data['h5pparams']);
                        if ($params === null) {
                            $errors['h5peditor'] = get_string('invalidparameters', 'core_h5p');
                        } else {
                            $data['h5pparams'] = $params;
                        }
                    }
                }
            }
        }
    }
    /**
     * Validates editor form
     *
     * @param array $data
     * @param array $files
     *
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        // Validate max grade as a non-negative numeric value.
/*        if (!is_numeric($data['maximumgrade']) || $data['maximumgrade'] < 0) {
            $errors['maximumgrade'] = get_string('maximumgradeerror', 'core_h5p');
        }*/
        if ($data['h5paction'] === 'upload') {
            // Validate uploaded H5P file.
            unset($errors['name']); // Will be set in data_postprocessing().
            $this->validate_upload($data, $errors);
        } else {
            $this->validate_created($data, $errors);
        }
        return $errors;
    }
    /**
     * Allows modules to modify the data returned by form get_data().
     * This method is also called in the bulk activity completion form.
     *
     * Only available on moodleform_mod.
     *
     * @param stdClass $data passed by reference
     */
    public function data_postprocessing($data) {
        // Determine disabled content features.
        $options = array(
            H5PCore::DISPLAY_OPTION_FRAME     => isset($data->frame) ? $data->frame : 0,
            H5PCore::DISPLAY_OPTION_DOWNLOAD  => isset($data->export) ? $data->export : 0,
            H5PCore::DISPLAY_OPTION_EMBED     => isset($data->embed) ? $data->embed : 0,
            H5PCore::DISPLAY_OPTION_COPYRIGHT => isset($data->copyright) ? $data->copyright : 0,
        );
        //$core = \mod_hvp\framework::instance();
        $factory = new factory();
        $core = $factory->get_core();

        $data->disable = $core->getStorableDisplayOptions($options, 0);
        // Remove metadata wrapper from form data.
        $params = json_decode($data->h5pparams);
        if ($params !== null) {
            $data->params = json_encode($params->params);
            if (isset($params->metadata)) {
                $data->metadata = $params->metadata;
            }
        }
        // Cleanup.
        unset($data->h5pparams);
        if ($data->h5paction === 'upload') {
            if (empty($data->metadata)) {
                $data->metadata = new stdClass();
            }
            if (empty($data->metadata->title)) {
                // Fix for legacy content upload to work.
                // Fetch title from h5p.json or use a default string if not available.
                //$h5pvalidator = \mod_hvp\framework::instance('validator');
                $factory = new factory();
                $h5pvalidator = $factory->get_validator();
                $data->metadata->title = empty($h5pvalidator->h5pC->mainJsonData['title']) ? 'Uploaded Content' : $h5pvalidator->h5pC->mainJsonData['title'];
            }
            $data->name = $data->metadata->title; // Sort of a hack,
            // but there is no JavaScript that sets the value when there is no editor...
        }
    }
    /**
     * This should not be overridden, but we have to in order to support Moodle <3.2
     * and older Totara sites.
     *
     * Moodle 3.1 LTS is supported until May 2019, after that this can be dropped.
     * (could cause issues for new features if they add more to this in Core)
     *
     * @return object submitted data; NULL if not valid or not submitted or cancelled
     */
    public function get_data() {
        $data = parent::get_data();
        // TODO: according to method documentation, it is not necessary
        if ($data) {
/*            // Check if moodleform_mod class has already taken care of the data for us.
            // If not this is an older Moodle or Totara site that we need to treat differently.
            $class = new ReflectionClass('moodleform_mod');
            $method = $class->getMethod('get_data');
            if ($method->class !== 'moodleform_mod') {
                // Moodleform_mod class doesn't override get_data so we need to convert it ourselves.
                // Convert the grade pass value - we may be using a language which uses commas,
                // rather than decimal points, in numbers. These need to be converted so that
                // they can be added to the DB.
                if (isset($data->gradepass)) {
                    $data->gradepass = unformat_float($data->gradepass);
                }*/
// TODO: data_postprocessing is not invoked in moodleform
                $this->data_postprocessing($data);
/*            }*/
        }
        return $data;
    }
    // TODO: added to call data_preprocessing
    function set_data($default_values) {
        if (is_object($default_values)) {
            $default_values = (array)$default_values;
        }

        $this->data_preprocessing($default_values);
        parent::set_data($default_values);
    }
}

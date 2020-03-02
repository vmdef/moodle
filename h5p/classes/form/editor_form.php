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
 * H5P editor form class.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz {victor@moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p\form;

use core_h5p\factory;
use core_h5p\helper;
use stdClass;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir."/formslib.php");

class editor_form extends \moodleform {
    private $context;

    public function definition() {
        global $CFG;

        $this->context = \context_system::instance();

        $mform =& $this->_form;

        // Name.
        $mform->addElement('hidden', 'name', '');
        $mform->setType('name', PARAM_TEXT);

        // Action selector.
        $h5paction = array();
        $h5paction[] = $mform->createElement('radio', 'h5paction', '', get_string('upload', 'core_h5p'), 'upload');
        $h5paction[] = $mform->createElement('radio', 'h5paction', '', get_string('create', 'core_h5p'), 'create');
        $mform->addGroup($h5paction, 'h5pactiongroup', get_string('action', 'core_h5p'), array('<br/>'), false);
        $mform->setDefault('h5paction', 'create');

        // Upload.
        $mform->addElement('filepicker', 'h5pfile', get_string('h5pfile', 'core_h5p'), null,
            array('maxbytes' => $CFG->maxbytes, 'accepted_types' => '*'));

        // Editor placeholder.
        $h5peditor   = [];
        $h5peditor[] = $mform->createElement('html',
            '<div class="h5p-editor">' . get_string('javascriptloading', 'core_h5p') . '</div>');
        $mform->addGroup($h5peditor, 'h5peditor', get_string('editor', 'core_h5p'));

        // Hidden fields.
        $mform->addElement('hidden', 'h5plibrary', '');
        $mform->setType('h5plibrary', PARAM_RAW);
        $mform->addElement('hidden', 'h5pparams', '');
        $mform->setType('h5pparams', PARAM_RAW);


        $this->add_action_buttons();
    }

    public function data_preprocessing(&$defaultvalues) {
        global $DB;

        $factory = new factory();
        $core = $factory->get_core();

        $content = null;
        if (!empty($defaultvalues['id'])) {
            // Load content.
            $content = $core->loadContent($defaultvalues['id']);
            if ($content === null) {
                print_error('invalidcontentid');
            }
        }

        $draftitemid = file_get_submitted_draft_itemid('h5pfile');
        file_prepare_draft_area($draftitemid, $this->context->id, 'core_h5p', 'package', 0);
        $defaultvalues['h5pfile'] = $draftitemid;

        // Is there any content type installed?.
        $isanycontenttype = $DB->get_field_sql("SELECT id FROM {h5p_libraries} WHERE runnable = 1", null, IGNORE_MULTIPLE);

        // If no H5P content is being edited and no content types have been installed, it is only possible to upload.
        if ($content === null &&  $isanycontenttype === false) {
            $defaultvalues['h5paction'] = 'upload';
        }

        // Current H5P library.
        $library = ($content === null) ? 0 : H5PCore::libraryToString($content['library']);

        // Set editor defaults.
        $defaultvalues['h5plibrary'] = $library;

        // Combine params and metadata in one JSON object.
        $params = ($content === null ? '{}' : $core->filterParameters($content));
        $maincontentdata = array('params' => json_decode($params));

        if (isset($content['metadata'])) {
            $maincontentdata['metadata'] = $content['metadata'];
        }
        $defaultvalues['h5pparams'] = json_encode($maincontentdata, true);

        // Add to page required editor assets.
        $mformid = $this->_form->getAttribute('id');
        $contentid = ($content === null) ? null : $defaultvalues['id'];
        helper::add_editor_assets_to_page($contentid, $mformid);
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
                $factory = new factory();
                $h5pvalidator = $factory->get_validator();
                $data->metadata->title = empty($h5pvalidator->h5pC->mainJsonData['title']) ? 'Uploaded Content' : $h5pvalidator->h5pC->mainJsonData['title'];
            }
            $data->name = $data->metadata->title;
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
                $factory = new factory();
                $interface = $factory->get_framework();
                $path = $CFG->tempdir . uniqid('/hvp-');
                $interface->getUploadedH5pFolderPath($path);
                $path .= '.h5p';
                $interface->getUploadedH5pPath($path);
                $file->copy_content_to($path);
                $interface->set_file($file);

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
}
<?php

/**
 * This file contains forms that are used to create and edit categories.
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

class local_plugins_categories_form extends moodleform {
    protected function definition() {
        $form = $this->_form;

        $form->addElement('header', '_formheading', $this->_customdata['formheading']);

        $form->addElement('hidden', 'id', null);
        $form->setType('id', PARAM_INT);

        $form->addElement('hidden', 'initialsetup', false);
        $form->setType('initialsetup', PARAM_BOOL);

        $form->addElement('text', 'name', get_string('name', 'local_plugins'));
        $form->setType('name', PARAM_TEXT);
        $form->addRule('name', get_string('required'), 'required');

        $form->addElement('text', 'plugintype', get_string('categoryplugintype', 'local_plugins'));
        $form->setType('plugintype', PARAM_TEXT);
        $form->addHelpButton('plugintype', 'categoryplugintype', 'local_plugins');

        $form->addElement('select', 'parentid', get_string('parentcategory', 'local_plugins'), local_plugins_helper::get_category_parent_options($this->_customdata['editcategoryid']));
        $form->setType('parentid', PARAM_INT);
        $form->addHelpButton('parentid', 'parentcategory', 'local_plugins');

        $form->addElement('textarea', 'shortdescription', get_string('shortdescription', 'local_plugins'), array('rows'=>3,'cols'=>75));
        $form->setType('shortdescription', PARAM_TEXT);
        $form->addRule('shortdescription', get_string('required'), 'required');
        $form->addHelpButton('shortdescription', 'categoryshortdescription', 'local_plugins');

        $form->addElement('text', 'sortorder', get_string('sortorder', 'local_plugins'));
        $form->setType('sortorder', PARAM_INT);

        $form->addElement('filemanager', 'defaultlogo_filemanager', get_string('defaultlogo', 'local_plugins'), null, local_plugins_helper::filemanager_options_plugin_logo());
        $form->addHelpButton('defaultlogo_filemanager', 'defaultlogo', 'local_plugins');

        $form->addElement('editor', 'description_editor', get_string('description', 'local_plugins'), null, local_plugins_helper::editor_options_category_description());
        $form->setType('description_editor', PARAM_RAW);

        $form->addElement('editor', 'installinstructions_editor', get_string('installinstructions', 'local_plugins'), null, local_plugins_helper::editor_options_category_description());
        $form->setType('installinstructions_editor', PARAM_RAW);
        $form->addHelpButton('installinstructions_editor', 'installinstructions', 'local_plugins');

        $this->add_action_buttons(true);
    }

    public function set_category_data(local_plugins_category $category = null, $initialsetup = false) {

        $data = new stdClass;
        $data->id = null;
        $data->initialsetup = $initialsetup;
        $data->description = '';
        $data->descriptionformat = FORMAT_HTML;
        $data->installinstructions = '';
        $data->installinstructionsformat = FORMAT_HTML;
        $data->sortorder = 0;

        if (!empty($category)) {
            $data->id = $category->id;
            $data->parentid = $category->parentid;
            $data->name = $category->name;
            $data->shortdescription = $category->shortdescription;
            $data->description = $category->description;
            $data->descriptionformat = $category->descriptionformat;
            $data->installinstructions = $category->installinstructions;
            $data->installinstructionsformat = $category->installinstructionsformat;
            $data->sortorder = $category->sortorder;
            $data->plugintype = $category->plugintype;
        }

        $context = context_system::instance();

        // Prepare the description editor
        $filearea = local_plugins::FILEAREA_CATEGORYDESCRIPTION;
        $options = local_plugins_helper::editor_options_category_description();
        $data = file_prepare_standard_editor($data, 'description', $options, $context, 'local_plugins', $filearea, $data->id);

        // Prepare the install instructions editor
        $filearea = local_plugins::FILEAREA_CATEGORYINSTALLINSTRUCTIONS;
        $options = local_plugins_helper::editor_options_category_installinstructions();
        $data = file_prepare_standard_editor($data, 'installinstructions', $options, $context, 'local_plugins', $filearea, $data->id);

        // Prepare the default logo file manager
        $filearea = local_plugins::FILEAREA_CATEGORYDEFAULTLOGO;
        $options = local_plugins_helper::filemanager_options_plugin_logo();
        $data = file_prepare_standard_filemanager($data, 'defaultlogo', $options, $context, 'local_plugins', $filearea, $data->id);

        parent::set_data($data);
    }

    /**
     *
     * @global moodle_database $DB
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        if (empty($errors['name'])) {
            $params = array('name' => $data['name']);
            $where = 'name = :name';
            if (!empty($data['id'])) {
                $params['id'] = (int)$data['id'];
                $where .= ' AND id != :id';
            }
            if ($DB->record_exists_select('local_plugins_category', $where, $params)) {
                $errors['name'] = get_string('exc_categorynamealreadyexists', 'local_plugins');
            }
        }
        if (!empty($data['plugintype']) && $data['plugintype'] != '-') {
            $regexp = '/^[a-z]{3,30}$/';
            if (!preg_match($regexp, $data['plugintype'])) {
                $errors['plugintype'] = get_string('invalidfrankenstyle', 'local_plugins', $regexp);
            }
        }

        return $errors;
    }
}
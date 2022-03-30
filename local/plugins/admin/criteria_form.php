<?php

/**
 * This file contains moodle forms used in creating and editing reviews.
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

class local_plugins_review_edit_criterion_form extends moodleform {

    /**
     *
     * @global moodle_database $DB
     */
    public function definition() {
        global $DB;

        $form = $this->_form;
        $form->addElement('header', '_formheading', $this->_customdata['formheading']);

        $form->addElement('hidden', 'id', null);
        $form->setType('id', PARAM_INT);

        $form->addElement('hidden', 'action', null);
        $form->setType('action', PARAM_ALPHA);

        // name
        $form->addElement('text', 'name', get_string('name', 'local_plugins'), array('size' => 52));
        $form->setType('name', PARAM_TEXT);
        $form->addRule('name', get_string('required'), 'required');

        // description
        $form->addElement('editor', 'description_editor', get_string('description', 'local_plugins'), null, local_plugins_helper::editor_options_review_criterion_description());
        $form->setType('description_editor', PARAM_RAW);

        // scale
        $form->addElement('select', 'scaleid', get_string('scale', 'local_plugins'), local_plugins_helper::get_review_scale_options());
        $form->setDefault('scaleid', 0);
        $form->setType('scaleid', PARAM_INT);
        $form->addHelpButton('scaleid', 'criterionscale', 'local_plugins');

        // cohort
        $form->addElement('select', 'cohortid', get_string('cohort', 'local_plugins'), local_plugins_helper::get_review_cohort_options());
        $form->setType('cohortid', PARAM_INT);
        $form->addHelpButton('cohortid', 'criterioncohort', 'local_plugins');

        $this->add_action_buttons(true);
    }

    public function set_criterion_data(local_plugins_review_criterion $criterion = null) {

        $data = new stdClass;
        if (!empty($criterion)) {
            $data->id = $criterion->id;
            $data->name = $criterion->name;
            $data->description = $criterion->description;
            $data->descriptionformat = $criterion->descriptionformat;
            $data->scaleid = $criterion->scaleid;
            $data->cohortid = $criterion->cohortid;
        } else {
            $data->id = null;
            $data->description = '';
            $data->descriptionformat = FORMAT_HTML;
            $data->action = 'new';
        }

        $context = context_system::instance();
        $filearea = local_plugins::FILEAREA_REVIEWCRITERIADESC;
        $options = local_plugins_helper::editor_options_review_criterion_description();
        $data = file_prepare_standard_editor($data, 'description', $options, $context, 'local_plugins', $filearea, $data->id);

        parent::set_data($data);
    }
}
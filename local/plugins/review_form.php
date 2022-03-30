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

class local_plugins_review_form extends moodleform {

    public function definition() {

        $mform = $this->_form;
        $criteria = $this->_customdata['criteria'];

        $mform->addElement('hidden', 'version');
        $mform->setType('version', PARAM_INT);

        $mform->addElement('hidden', 'review', '');
        $mform->setType('review', PARAM_INT);

        if (is_array($criteria) && count($criteria) > 0) {
            foreach ($criteria as $criterion) {
                if ($criterion->can_add_outcome()) {
                    $mform->addElement('header', 'criterion_heading_'.$criterion->id, $criterion->formatted_name);
                    $mform->addElement('html', html_writer::tag('div', $criterion->formatted_description, array('class' => 'review-criterion-description')));
                    $mform->addElement('editor', $criterion->formelementname.'_editor', get_string('review', 'local_plugins'), null, local_plugins_helper::editor_options_review_outcome_review());
                    $mform->setType($criterion->formelementname.'_editor', PARAM_RAW);
                    if ($criterion->has_grade()) {
                        $mform->addElement('select', $criterion->formelementname. 'grade', get_string('grade', 'local_plugins'), $criterion->grade_options());
                    }
                }
            }
        }

        $this->add_action_buttons(true, get_string('submit'));
    }
}
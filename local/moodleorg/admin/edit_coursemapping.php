<?php
require_once(dirname(dirname(dirname(dirname(__FILE__)))) . '/config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/grouplib.php');
$courseid = required_param('courseid', PARAM_INT);

class local_moodleorg_useful_coursemap_form extends moodleform {
    public function definition () {
        global $DB, $CFG;
        $mform = $this->_form;

        $course = $this->_customdata['course'];

        $mform->addElement('hidden', 'courseid');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('static', 'coursename', 'Course:', $course->fullname.' ('.$course->shortname.')');
        $scales = get_scales_menu($course->id);
        $scales[0] = 'Do not use a scale. Retrieve recent posts.';
        $mform->addElement('select','scaleid', 'Scale for retrieving posts:', $scales);
        $mform->setDefault('scaleid', 0);

        $mform->addElement('select', 'lang', get_string('language'), get_string_manager()->get_list_of_translations());
        if (isset($course->lang)) {
            $mform->setDefault('lang', $course->lang);
        } else {
            $mform->setDefault('lang', $CFG->lang);
        }

        $mform->addElement('text', 'coursemanagerslist', 'Course manager ids (seperated by commas)');
        $mform->setType('coursemanagerslist', PARAM_RAW);
        $mform->addRule('coursemanagerslist', 'The course managers must be userids seperated by commas only.', 'regex', '/^(\d+,?)*$/');
        $this->add_action_buttons();
    }
}

require_capability('moodle/site:config', context_system::instance());
admin_externalpage_setup('local_moodleorg_coursemapping');
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$coursemapping = $DB->get_record('moodleorg_useful_coursemap', array('courseid' => $courseid));

$mform = new local_moodleorg_useful_coursemap_form(null, array('course' => $course));
if (empty($coursemapping->id)) {
    $mform->set_data(array('courseid' => $courseid));
} else {
    $mform->set_data($coursemapping);
}
$returnurl = new moodle_url('/local/moodleorg/admin/coursemapping.php');

if ($mform->is_cancelled()) {
    redirect($returnurl);
} else if ($formdata = $mform->get_data()) {
    $data = new stdClass;
    $data->courseid = $courseid;

    $data->scaleid = !empty($formdata->scaleid) ? $formdata->scaleid : null;
    $data->coursemanagerslist= !empty($formdata->coursemanagerslist) ? $formdata->coursemanagerslist : null;
    $data->lang = $formdata->lang;

    if (empty($coursemapping->id)) {
        $DB->insert_record('moodleorg_useful_coursemap', $data);
    } else {
        $data->id = $coursemapping->id;
        $DB->update_record('moodleorg_useful_coursemap', $data);
    }
    redirect($returnurl);
} else {
    echo $OUTPUT->header('Map courses');
    $mform->display();
    echo $OUTPUT->footer();
}

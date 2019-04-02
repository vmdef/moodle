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
 * Unit tests for core targets.
 *
 * @package   core
 * @category  analytics
 * @copyright 2019 Victor Deniz <victor@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

require_once($CFG->dirroot . '/completion/criteria/completion_criteria.php');
require_once($CFG->dirroot . '/completion/criteria/completion_criteria_activity.php');
require_once($CFG->dirroot . '/lib/grade/grade_item.php');
require_once($CFG->dirroot . '/lib/grade/grade_grade.php');
require_once($CFG->dirroot . '/lib/grade/grade_category.php');
require_once($CFG->dirroot . '/lib/grade/constants.php');

/**
 * Unit tests for core targets.
 *
 * @package   core
 * @category  analytics
 * @copyright 2019 Victor Deniz <victor@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_analytics_targets_testcase extends advanced_testcase {

    /**
     * Provides course params for the {@link self::test_core_target_course_completion_analysable()} method.
     *
     * @return array
     */
    public function analysable_provider() {

        $now = new DateTime("now", core_date::get_server_timezone_object());
        $year = $now->format('Y');
        $month = $now->format('m');

        return [
            'coursenotyetstarted' => [
                'params' => [
                    'enablecompletion' => 1,
                    'startdate' => mktime(0, 0, 0, 10, 24, $year + 1)
                ],
                'isvalid' => get_string('coursenotyetstarted')
            ],
            'coursenostudents' => [
                'params' => [
                    'enablecompletion' => 1,
                    'startdate' => mktime(0, 0, 0, 10, 24, $year - 2),
                    'enddate' => mktime(0, 0, 0, 10, 24, $year - 1)
                ],
                'isvalid' => get_string('nocoursestudents')
            ],
            'coursenosections' => [
                'params' => [
                    'enablecompletion' => 1,
                    'format' => 'social',
                    'students' => true
                ],
                'isvalid' => get_string('nocoursesections')
            ],
            'coursenoendtime' => [
                'params' => [
                    'enablecompletion' => 1,
                    'format' => 'topics',
                    'enddate' => 0,
                    'students' => true
                ],
                'isvalid' => get_string('nocourseendtime')
            ],
            'courseendbeforestart' => [
                'params' => [
                    'enablecompletion' => 1,
                    'enddate' => mktime(0, 0, 0, 10, 23, $year - 2),
                    'students' => true
                ],
                'isvalid' => get_string('errorendbeforestart', 'analytics')
            ],
            'coursetoolong' => [
                'params' => [
                    'enablecompletion' => 1,
                    'startdate' => mktime(0, 0, 0, 10, 24, $year - 2),
                    'enddate' => mktime(0, 0, 0, 10, 23, $year),
                    'students' => true
                ],
                'isvalid' => get_string('coursetoolong', 'analytics')
            ],
            'coursealreadyfinished' => [
                'params' => [
                    'enablecompletion' => 1,
                    'startdate' => mktime(0, 0, 0, 10, 24, $year - 2),
                    'enddate' => mktime(0, 0, 0, 10, 23, $year - 1),
                    'students' => true
                ],
                'isvalid' => get_string('coursealreadyfinished'),
                'fortraining' => false
            ],
            'coursenotyetfinished' => [
                'params' => [
                    'enablecompletion' => 1,
                    'startdate' => mktime(0, 0, 0, $month - 1, 24, $year),
                    'enddate' => mktime(0, 0, 0, $month + 2, 23, $year),
                    'students' => true
                ],
                'isvalid' => get_string('coursenotyetfinished')
            ],
            'coursenocompletion' => [
                'params' => [
                    'enablecompletion' => 0,
                    'startdate' => mktime(0, 0, 0, $month - 2, 24, $year),
                    'enddate' => mktime(0, 0, 0, $month - 1, 23, $year),
                    'students' => true
                ],
                'isvalid' => get_string('completionnotenabledforcourse', 'completion')
            ],
        ];
    }

    /**
     * Provides enrolment params for the {@link self::test_core_target_course_completion_samples()} method.
     *
     * @return array
     */
    public function sample_provider() {
        $now = time();
        return [
            'enrolmentendbeforecourse' => [
                'coursestart' => $now,
                'courseend' => $now + (WEEKSECS * 8),
                'timestart' => $now,
                'timeend' => $now - DAYSECS,
                'isvalid' => false
            ],
            'enrolmenttoolong' => [
                'coursestart' => $now,
                'courseend' => $now + (WEEKSECS * 8),
                'timestart' => $now - (YEARSECS + (WEEKSECS * 8)),
                'timeend' => $now + (WEEKSECS * 8),
                'isvalid' => false
            ],
            'enrolmentstartaftercourse' => [
                'coursestart' => $now,
                'courseend' => $now + (WEEKSECS * 8),
                'timestart' => $now + (WEEKSECS * 9),
                'timeend' => $now + (WEEKSECS * 10),
                'isvalid' => false
            ],
        ];
    }

    /**
     * Test valid analysable conditions.
     *
     * @dataProvider analysable_provider
     * @param mixed $courseparams Course data
     * @param true|string $isvalid True when analysable is valid, string when it is not
     * @param boolean $fortraining True if the course is for training the model
     */
    public function test_core_target_course_completion_analysable($courseparams, $isvalid, $fortraining = true) {
        global $DB;

        $this->resetAfterTest(true);

        try {
            $course = $this->getDataGenerator()->create_course($courseparams);
        } catch (moodle_exception $e) {
            $course = $this->getDataGenerator()->create_course();
            $courserecord = $courseparams;
            $courserecord['id'] = $course->id;
            unset($courserecord['students']);

            $DB->update_record_raw('course', $courserecord);
            $course = get_course($course->id);
        }
        $user = $this->getDataGenerator()->create_user();

        if (!empty($courseparams['enablecompletion'])) {
            $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id, 'completion' => 1]);
            $cm = get_coursemodule_from_id('assign', $assign->cmid);

            $criteriadata = (object) [
                'id' => $course->id,
                'criteria_activity' => [
                    $cm->id => 1
                ]
            ];
            $criterion = new completion_criteria_activity();
            $criterion->update_config($criteriadata);
        }

        $target = new \core\analytics\target\course_completion();

        // Test valid analysables.

        if (!empty($courseparams['students'])) {
            // Enroll user in course.
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $analysable = new \core_analytics\course($course);
        $this->assertEquals($isvalid, $target->is_valid_analysable($analysable, $fortraining));
    }

    /**
     * Test valid sample conditions.
     *
     * @dataProvider sample_provider
     * @param int $coursestart Course start date
     * @param int $courseend Course end date
     * @param int $timestart Enrol start date
     * @param int $timeend Enrol end date
     * @param boolean $isvalid True when sample is valid, false when it is not
     */
    public function test_core_target_course_completion_samples($coursestart, $courseend, $timestart, $timeend, $isvalid) {

        $this->resetAfterTest(true);

        $courserecord = new stdClass();
        $courserecord->startdate = $coursestart;
        $courserecord->enddate = $courseend;

        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course($courserecord);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, null, 'manual', $timestart, $timeend);

        $target = new \core\analytics\target\course_completion();
        $analyser = new \core\analytics\analyser\student_enrolments(1, $target, [], [], []);
        $analysable = new \core_analytics\course($course);

        $class = new ReflectionClass('\core\analytics\analyser\student_enrolments');
        $method = $class->getMethod('get_all_samples');
        $method->setAccessible(true);

        list($sampleids, $samplesdata) = $method->invoke($analyser, $analysable);
        $target->add_sample_data($samplesdata);
        $sampleid = reset($sampleids);

        $this->assertEquals($isvalid, $target->is_valid_sample($sampleid, $analysable));
    }

    /**
     * Test the target value calculation of the course_gradetopass target.
     */
    public function test_core_target_course_gradetopass_calculate() {
        global $DB;

        $this->resetAfterTest(true);

        $dg = $this->getDataGenerator();
        $course1 = $dg->create_course();
        $student1 = $dg->create_user();
        $student2 = $dg->create_user();
        $student3 = $dg->create_user();
        $studentrole = $DB->get_record('role', array('shortname' => 'student'));
        $dg->enrol_user($student1->id, $course1->id, $studentrole->id);
        $dg->enrol_user($student2->id, $course1->id, $studentrole->id);
        $dg->enrol_user($student3->id, $course1->id, $studentrole->id);

        $courseitem = grade_item::fetch_course_item($course1->id);
        // Student1 fails.
        $courseitem->update_final_grade($student1->id, 30);
        // Student2 pass.
        $courseitem->update_final_grade($student2->id, 60);
        // Student 3 has no grade

        $target = new \core\analytics\target\course_gradetopass();
        $analyser = new \core\analytics\analyser\student_enrolments(1, $target, [], [], []);
        $analysable = new \core_analytics\course($course1);

        $class = new ReflectionClass('\core\analytics\analyser\student_enrolments');
        $method = $class->getMethod('get_all_samples');
        $method->setAccessible(true);

        list($sampleids, $samplesdata) = $method->invoke($analyser, $analysable);
        $target->add_sample_data($samplesdata);

        // Users in array $sampleids are sorted by user id, so student1 is the first sample.
        $sampleid = reset($sampleids);

        $class = new ReflectionClass('\core\analytics\target\course_gradetopass');
        $method = $class->getMethod('calculate_sample');
        $method->setAccessible(true);

        // Method calculate_sample() returns 1 when the user has not successfully graded to pass the course.
        $this->assertEquals(1, $method->invoke($target, $sampleid, $analysable));

        // Student2.
        $sampleid = next($sampleids);

        // Method calculate_sample() returns 0 when the user has successfully graded to pass the course.
        $this->assertEquals(0, $method->invoke($target, $sampleid, $analysable));

        // Student3.
        $sampleid = next($sampleids);

        // Method calculate_sample() returns 1 when the user has not been graded.
        $this->assertEquals(1, $method->invoke($target, $sampleid, $analysable));
    }
}


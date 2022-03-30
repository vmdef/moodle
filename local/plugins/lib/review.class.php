<?php

/**
 * This file contains the local_plugins_review class and the other classes
 * that are essential to the review process.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @property-read int $id;
 * @property-read int $userid;
 * @property-read int $versionid;
 * @property-read int $softwareid;
 * @property-read int $timereviewed;
 */
class local_plugins_review extends local_plugins_class_base implements local_plugins_loggable, templatable {

    protected $id;
    protected $userid;
    protected $versionid;
    protected $softwareid;
    protected $timereviewed;
    protected $status;

    protected $outcomes;

    protected $plugin;
    protected $version;
    protected $user;

    public function __construct($properties) {
        global $DB;
        parent::__construct($properties);
        if (empty($this->user)) {
            $this->user = $DB->get_record('user', array('id' => $this->userid), '*', MUST_EXIST);
        }
        if (empty($this->plugin)) {
            if (empty($this->version)) {
                $this->plugin = local_plugins_helper::get_plugin_by_version($this->versionid);
            } else {
                $this->plugin = $this->version->plugin;
            }
        }
        if (empty($this->version)) {
            $this->version = $this->plugin->get_version($this->versionid);
        }
    }

    protected function load_review_outcomes() {
        global $DB;
        $criteria = local_plugins_helper::get_review_criteria();
        $outcomes = $DB->get_records('local_plugins_review_outcome', array('reviewid' => $this->id));
        $this->outcomes = array();
        foreach ($outcomes as $outcome) {
            $outcome->reviewobject = $this;
            $outcome->criterion = $criteria[$outcome->criteriaid];
            $this->outcomes[$outcome->id] = new local_plugins_review_outcome($outcome);
        }
        return $this->outcomes;
    }

    protected function get_version() {
        if (empty($this->version)) {
            $this->version = $this->plugin->versions[$this->versionid];
        }
        return $this->version;
    }

    protected function get_formatted_timereviewed() {
        return userdate($this->timereviewed);
    }

    protected function get_outcomes() {
        if (!is_array($this->outcomes)) {
            $this->load_review_outcomes();
        }
        return $this->outcomes;
    }

    protected function get_viewreviewlink() {
        return new local_plugins_url('/local/plugins/reviews.php', array('version' => $this->versionid, 'review' => $this->id));
    }

    protected function get_editreviewlink() {
        return new local_plugins_url('/local/plugins/review.php', array('version' => $this->versionid, 'review' => $this->id));
    }

    /**
     * Sets the approval field.
     *
     * @param int $status
     */
    public function set_approval_status($status) {
        global $DB;
        $DB->set_field('local_plugins_review', 'status', $status, ['id' => $this->id]);
    }

    public function update(stdClass $data) {
        global $DB;
        $changes = false;
        $updatedata = new stdClass;
        $updatedata->id = $this->id;
        $updatedata->timelastmodified = time();
        $DB->update_record('local_plugins_review', $updatedata);
        return true;
    }

    public function add_outcome(stdClass $outcome) {
        global $DB;
        $outcome->reviewid = $this->id;
        $outcome->id = $DB->insert_record('local_plugins_review_outcome', $outcome, true);
        $this->outcomes[$outcome->id] = new local_plugins_review_outcome($outcome);
        return $this->outcomes[$outcome->id];
    }

    /**
     *
     * @global moodle_database $DB 
     */
    public function delete() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        //TODO delete files for each outcome
        /*$fs = get_file_storage();
        foreach ($this->outcomes as $outcome) {
            $fs->delete_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_REVIEWOUTCOME, $outcome->id);
        }*/
        $DB->delete_records('local_plugins_review_outcome', array('reviewid' => $this->id));
        $DB->delete_records('local_plugins_review', array('id' => $this->id));
        $transaction->allow_commit();
        $transaction = null;
        return true;
    }

    /**
     * Can the user edit the review?
     *
     * Curators can edit any plugin any time. Otherwise it must be the author
     * of the review and it must not be published yet.
     *
     * @return bool
     */
    public function can_edit() {
        global $USER;

        $context = context_system::instance();

        if (has_capability(local_plugins::CAP_EDITANYREVIEW, $context)) {
            return true;
        }

        if ($this->status == 1) {
            return false;
        }

        return (isloggedin() && $USER->id == $this->userid && has_capability(local_plugins::CAP_EDITOWNREVIEW, $context));
    }

    /**
     * Can the current user approve/unapprove the review.
     *
     * @return bool
     */
    public function can_approve() {
        global $USER;
        return has_capability('local/plugins:approvereviews', context_system::instance());
    }

    /**
     * returns true if current user is allowed to see profile of the reviewer
     */
    public function can_view_reviewer_profile() {
        global $CFG;
        if (!empty($CFG->forceloginforprofiles) && (!isloggedin() || isguestuser())) {
            return false;
        }
        $canviewgeneral = has_capability('moodle/user:viewdetails', context_system::instance());
        global $USER;
        return ($canviewgeneral || $USER->id == $this->userid || has_coursecontact_role($this->userid));
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_action_prefix() {
        return local_plugins_log::LOG_PREFIX_PLUGIN_REVIEW;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_identifier($forplugin) {
        $a = new stdClass();
        $a->version = html_writer::link($this->get_version()->viewlink, $this->get_version()->formatted_releasename);
        $a->user = html_writer::link(new local_plugins_url('/user/profile.php', array('id' => $this->userid)), fullname($this->user));
        $rinfo = get_string('logidentifierreview', 'local_plugins', $a);
        if ($forplugin) {
            return $rinfo;
        } else {
            $a = new stdClass();
            $a->pluginidentifier = $this->plugin->log_identifier(false);
            $a->object = $rinfo;
            return get_string('logidentifierpluginobject', 'local_plugins', $a);
        }
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_data() {
        $outcomes = array();
        $alloutcomes = $this->load_review_outcomes();
        $criteria = local_plugins_helper::get_review_criteria();
        foreach ($criteria as $criterion) {
            foreach ($alloutcomes as $outcome) {
                if ($outcome->criteriaid == $criterion->id) {
                    $outcomes['review: '.$criterion->formatted_name] = $outcome->formatted_review;
                    $outcomes['grade: '.$criterion->formatted_name] = $criterion->prepare_grade($outcome->grade);
                    continue 2;
                }
            }
            $outcomes['review: '.$criterion->formatted_name] = null;
            $outcomes['grade: '.$criterion->formatted_name] = null;
        }
        return $outcomes;
    }

    /**
     * @see local_plugins_subscribable
     */
    public function get_subscription_type() {
        return local_plugins::NOTIFY_REVIEW;
    }

    /**
     * Export review data for mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output) {
        global $USER;

        $canviewreviewerprofile = $this->can_view_reviewer_profile();

        $data = [
            'plugin' => [
                'name' => $this->plugin->formatted_name,
                'link' => $this->plugin->viewlink,
            ],
            'version' => [
                'name' => $this->get_version()->get_formatted_releasename(),
                'link' => $this->get_version()->viewlink,
            ],
            'timereviewed' => [
                'absdate' => $this->formatted_timereviewed,
                'iso8601date' => date('c', $this->timereviewed),
                'reldate' => \local_plugins\human_time_diff::for($this->timereviewed, null, false),
            ],
            'reviewer' => [
                'picture' => $output->user_picture($this->user, ['size' => 18, 'link' => $canviewreviewerprofile]),
                'fullname' => s(fullname($this->user)),
            ],
            'viewreviewlink' => $this->get_viewreviewlink()->out(false),
            'outcomes' => [],
            'grades' => [],
        ];

        if (!empty($this->plugin->screenshots)) {
            // Can't use reset($this->plugin->screenshots) here as it is a read-only property of the object.
            foreach ($this->plugin->screenshots as $screenshot) {
                $data['plugin']['screenshot'] = [
                    'src' => (new moodle_url($screenshot->src, ['preview' => 'bigthumb']))->out(false),
                ];
                break;
            }
        }

        if ($canviewreviewerprofile) {
            $data['reviewer']['fullname'] = html_writer::link(
                new local_plugins_url('/user/profile.php', ['id' => $this->userid]),
                s(fullname($this->user))
            );
        }

        if ($USER->id == $this->userid or has_capability('local/plugins:approvereviews', context_system::instance())) {
            $data['status'.$this->status] = true;
        }

        foreach ($this->get_outcomes() as $outcome) {
            if (!empty($outcome->review)) {
                $data['outcomes'][] = [
                    'criterion' => $outcome->criterion->formatted_name,
                    'review' => $outcome->formatted_review,
                    'truncated' => $outcome->truncated_review,
                ];
            }
            if ($outcome->is_graded()) {
                $data['grades'][] = [
                    'criterion' => $outcome->criterion->formatted_name,
                    'grade' => $outcome->criterion->prepare_grade($outcome->grade),
                    'percent' => round(100.0 * $outcome->grade),
                ];
            }
        }

        return $data;
    }
}

/**
 * Class handling one user review outline
 *
 * @property-read int $id
 * @property-read int $reviewid
 * @property-read int $criteriaid
 * @property-read string $review
 * @property-read int $reviewformat
 * @property-read int $grade
 */
class local_plugins_review_outcome extends local_plugins_class_base {

    protected $id;
    protected $reviewid;
    protected $criteriaid;
    protected $review;
    protected $reviewformat;
    protected $grade;

    protected function get_formatted_review() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_REVIEWOUTCOME;
        $fileoptions = local_plugins_helper::editor_options_review_outcome_review();
        $description = file_rewrite_pluginfile_urls($this->review, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => false,
            'trusted' => false,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->reviewformat, $formatoptions);
    }

    protected function get_truncated_review() {
        $review = clean_param($this->review, PARAM_TEXT);
        if (core_text::strlen($review) > 503) {
            $review = strip_tags($review);
            $review = core_text::substr($review, 0, 500);
            $review .= '...';
        }
        return $review;
    }

    public function update($properties) {
        global $DB;
        $properties = (array)$properties;
        $changes = false;
        $fields = array('review', 'reviewformat', 'grade');

        $updatedata = new stdClass;
        $updatedata->id = $this->id;
        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] !== $this->$field) {
                $updatedata->$field = $properties[$field];
                $changes = true;
            }
        }
        if ($changes) {
            $DB->update_record('local_plugins_review_outcome', $updatedata);
        }
        return true;
    }

    protected function get_criterion() {
        return local_plugins_helper::get_review_criterion($this->criteriaid);
    }

    public function is_graded() {
        return ($this->criterion->parse_grade($this->grade) !== null);
    }
}

/**
 * Class to handle one review criterion
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $descriptionformat
 * @property int $scaleid
 * @property int $cohortid
 */
class local_plugins_review_criterion extends local_plugins_class_base implements local_plugins_loggable {

    protected $id;
    protected $name;
    protected $description;
    protected $descriptionformat;
    protected $scaleid;
    protected $cohortid;

    protected function get_formatted_name() {
        return format_string($this->name, true, array('context' => context_system::instance()));
    }

    protected function get_formatted_description() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_REVIEWCRITERIADESC;
        $fileoptions = local_plugins_helper::editor_options_review_criterion_description();
        $description = file_rewrite_pluginfile_urls($this->description, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => false,
            'trusted' => false,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->descriptionformat, $formatoptions);
    }

    protected function get_formelementname() {
        return 'criteria'.$this->id;
    }

    public function update($properties) {
        global $DB;
        $properties = (array)$properties;
        $changes = false;
        $fields = array('name', 'description', 'descriptionformat', 'scaleid', 'cohortid');

        $criterion = new stdClass;
        $criterion->id = $this->id;
        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] != $this->$field) {
                $criterion->$field = $properties[$field];
                $changes = true;
            }
        }
        if (!$changes) {
            return true;
        }
        $DB->update_record('local_plugins_review_test', $criterion);
        foreach ($criterion as $property => $value) {
            $this->$property = $value;
        }
        return true;
    }

    public function delete() {
        global $DB, $PAGE;
        $fs = get_file_storage();
        //TODO delete files for each outcome
        /*
        $outcomes = $DB->get_records('local_plugins_review_outcome', array('criteriaid'=>$this->id));
        foreach ($outcomes as $outcome) {
            $fs->delete_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_REVIEWOUTCOME, $outcome->id);
        }*/

        //log changing of reviews that contained review or grade on this outcome and remove outcomes
        $records = $DB->get_recordset_sql('SELECT r.*
            FROM {local_plugins_review_outcome} o, {local_plugins_review} r
            WHERE o.reviewid = r.id AND o.criteriaid=:criteriaid AND
            ((o.review IS NOT NULL AND o.review <> :emptystr) OR o.grade IS NOT NULL)',
                array('criteriaid' => $this->id, 'emptystr' => ''));
        if (count($records)) {
            $reviews = array();
            foreach ($records as $record) {
                $reviews[] = new local_plugins_review($record);
            }
            local_plugins_log::remember_state($reviews);
            $DB->delete_records('local_plugins_review_outcome', array('criteriaid' => $this->id));
            local_plugins_log::log_edited($reviews);
        }

        // remove criterion
        $DB->delete_records('local_plugins_review_test', array('id'=>$this->id));
        $fs->delete_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_REVIEWCRITERIADESC, $this->id);
    }

    public function has_grade() {
        return !empty($this->scaleid);
    }

    /**
     * String representation of the grade, i.e. "4/10"
     * Is used to build grade dropdowns
     *
     * @return string
     */
    public function prepare_grade($grade) {
        if (!$this->has_grade() || $grade === null) {
            return null;
        }
        if ($grade < 0) {
            $grade = 0;
        }
        if ($grade > 1) {
            $grade = 1;
        }
        return round($grade*$this->scaleid). '/'. $this->scaleid;
    }

    /**
     * Function parses the float grade from string or from number
     *
     * @param $preparedgrade
     * @return double
     */
    public function parse_grade($preparedgrade) {
        if (!$this->has_grade() || $preparedgrade === null || $preparedgrade === '') {
            return null;
        }
        if (is_string($preparedgrade) && preg_match('|^(\d+)/(\d+)$|', $preparedgrade, $matches) && intval($matches[2])) {
            return intval($matches[1])/intval($matches[2]);
        }
        $preparedgrade = floatval($preparedgrade);
        if ($preparedgrade >= 0 && $preparedgrade <=1) {
            return round($preparedgrade*$this->scaleid)/ $this->scaleid;
        }
        return null;
    }

    public function grade_options() {
        $options = array( '' => get_string('nograde'));
        if (!$this->has_grade() || !$this->scaleid) {
            return $options;
        }
        if (!empty($this->scaleid) && $this->scaleid>0) {
            for ($i=0; $i<=$this->scaleid; $i++) {
                $options[ $this->prepare_grade($i/$this->scaleid) ] = $i;
            }
        }
        return $options;
    }

    /**
     * Returns true if the current user is allowed to add a review on this criterion.
     */
    public function can_add_outcome() {
        $context = context_system::instance();
        if (!isloggedin() || isguestuser() || !has_capability(local_plugins::CAP_PUBLISHREVIEWS, $context)) {
            return false;
        }
        if (has_capability(local_plugins::CAP_EDITANYREVIEW, $context)) {
            return true;
        }
        if (empty($this->cohortid)) {
            return true;
        }

        global $USER, $DB;
        static $usercohorts;
        if (!is_array($usercohorts)) {
            $usercohorts = array();
        }
        if (!array_key_exists($this->cohortid, $usercohorts)) {
            $usercohorts[$this->cohortid] = $DB->record_exists('cohort_members', array('cohortid'=>$this->cohortid, 'userid'=>$USER->id));
        }
        return $usercohorts[$this->cohortid];
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_action_prefix() {
        return local_plugins_log::LOG_PREFIX_REVIEWCRITERION;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_identifier($forplugin) {
        return get_string('logidentifiercriterion', 'local_plugins', $this->formatted_name);
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_data() {
        $cohort = null;
        if ($this->cohortid) {
            $cohorts = local_plugins_helper::get_review_cohort_options();
            if (array_key_exists($this->cohortid, $cohorts)) {
                $cohort = $cohorts[$this->cohortid];
            }
        }
        return array(
            'name' => $this->formatted_name,
            'description' => $this->formatted_description,
            'scale' => $this->scaleid,
            'cohort' => $cohort,
        );
    }
}
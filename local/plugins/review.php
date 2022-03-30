<?php

/**
 * This file allows the user to create of edit a review for a version
 * of a plugin.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/review_form.php');

$versionid = required_param('version', PARAM_INT);
$reviewid = optional_param('review', false, PARAM_INT);
$approve = optional_param('approve', null, PARAM_INT);
$plugin = local_plugins_helper::get_plugin_by_version($versionid);
$version = $plugin->get_version($versionid);
$context = context_system::instance();

if (!$plugin->can_view()) {
    local_plugins_error(null, null, 403);
}
if (!$version->can_view()) {
    local_plugins_error(get_string('exc_invalidreview', 'local_plugins'), get_string('exc_cannotviewversion', 'local_plugins'), 403);
}

if ($approve !== null) {
    require_login();
    require_capability('local/plugins:approvereviews', $context);
    require_sesskey();
    $review = $version->get_review($approve);
    $review->set_approval_status(required_param('status', PARAM_INT));
    redirect($review->viewreviewlink);
}

$PAGE->set_url($version->writereviewlink);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name. ': '. get_string('writereview', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add($version->formatted_releasename, $version->reviewlink, navigation_node::TYPE_CUSTOM, $version->version);
$PAGE->navbar->add(get_string('writereview', 'local_plugins'));

require_login();
require_capability(local_plugins::CAP_PUBLISHREVIEWS, $context);

if (empty($reviewid)) {
    $reviewid = local_plugins_helper::get_user_review_id_if_exists($version);
}

if ($reviewid) {
    $review = $version->get_review($reviewid);
    if (!$review->can_edit()) {
        redirect($review->viewreviewlink);
    }
} else {
    $review = false;
}

navigation_node::override_active_url($plugin->viewreviewslink);

local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$renderer = local_plugins_get_renderer($plugin);
$criteria = local_plugins_helper::get_review_criteria();

$mform = new local_plugins_review_form($PAGE->url, array('criteria' => local_plugins_helper::get_review_criteria()));
$data = new stdClass;
$data->version = $versionid;
if (!empty($review)) {
    $data->review = $review->id;
    foreach ($review->outcomes as $outcome) {
        $criterion = $outcome->criterion;
        $data->{$criterion->formelementname} = $outcome->review;
        $data->{$criterion->formelementname.'format'} = $outcome->reviewformat;
        $data->{$criterion->formelementname.'grade'} = $criterion->prepare_grade($outcome->grade);
        $data = file_prepare_standard_editor($data, $criterion->formelementname, local_plugins_helper::editor_options_review_outcome_review(), $context, 'local_plugins', local_plugins::FILEAREA_REVIEWOUTCOME, $outcome->id);
    }
}
$mform->set_data($data);

if ($mform->is_cancelled()) {
    redirect($version->reviewlink);
} else if ($mform->is_submitted() && $mform->is_validated()) {
    $data = $mform->get_data();
    if (empty($data->review) && empty($review)) {
        $review = new stdClass;
        $review = $version->add_review($review);
        $added = true;
    } else {
        local_plugins_log::remember_state($review);
        $review->update($data); // at the moment only update timelastmodified
        $added = false;
    }

    $existingoutcomes = array();
    $outcomes = $review->outcomes;
    foreach ($outcomes as $outcome) {
        $existingoutcomes[$outcome->criteriaid] = $outcome;
    }
    foreach ($criteria as $criterion) {
        if ($criterion->can_add_outcome()) {
            if (array_key_exists($criterion->id, $existingoutcomes)) {
                $outcome = $existingoutcomes[$criterion->id];
            } else {
                $outcome = new stdClass;
                $outcome->criteriaid = $criterion->id;
                $outcome->review = '';
                $outcome->reviewformat = $data->{$criterion->formelementname.'_editor'}['format'];
                $outcome = $review->add_outcome($outcome);
            }
            $outcomedata = new stdClass;
            $outcomedata->review_editor = $data->{$criterion->formelementname.'_editor'};
            $outcomedata = file_postupdate_standard_editor($outcomedata, 'review', local_plugins_helper::editor_options_review_outcome_review(), $context, 'local_plugins', local_plugins::FILEAREA_REVIEWOUTCOME, $outcome->id);
            if ($criterion->has_grade()) {
                $outcomedata->grade = $criterion->parse_grade($data->{$criterion->formelementname.'grade'});
            }
            $outcome->update($outcomedata);
        }
    }
    local_plugins_log::log_changed($review, $added);
    //TODO allow to remove review and log it
    redirect($version->reviewlink, get_string('reviewpublished', 'local_plugins'), 3); //TODO redirect to preview of this review
}

echo $renderer->header(get_string('writereviewon', 'local_plugins', html_writer::link($version->viewlink, $version->pluginversionname)));
$mform->display();
echo $renderer->footer();
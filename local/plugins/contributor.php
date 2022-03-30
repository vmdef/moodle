<?php

/**
 * This file allows user (plugin maintainer or admin) to
 * add/edit/remove plugin contributor
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Marina Glancy
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/contributor_form.php');

$pluginid = required_param('pluginid', PARAM_INT);
$id = optional_param('id', null, PARAM_INT);

$plugin = local_plugins_helper::get_plugin($pluginid);
$context = context_system::instance();

require_login();
if (!$plugin->can_manage_contributors()) { 
    throw new local_plugins_exception('exc_cannotmanagecontributors', $plugin->viewlink);
}
if ($id) {
    $contributor = local_plugins_helper::get_contributor($id);
    if ($contributor->pluginid != $pluginid) {
        // contributor id and plugin id do not match
        throw new local_plugins_exception('exc_permissiondenied', $plugin->viewlink);
    }
} else {
    if (!$plugin->can_add_contributors()) {
        throw new local_plugins_exception('exc_cannotaddcontributors', $plugin->viewlink, $CFG->local_plugins_maxcontributors);
    }
    $contributor = new local_plugins_contributor(array('pluginid' => $pluginid, 'id' => 0));
}

if (!$id) {
    $formheading = get_string('addcontributor', 'local_plugins');
} else if ($id) {
    $formheading = get_string('editcontributor', 'local_plugins');
}

$PAGE->set_url(new local_plugins_url('/local/plugins/contributor.php', array('pluginid' => $pluginid, 'id' => $id)));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name. ': '. $formheading);
$PAGE->set_heading($PAGE->title);
$PAGE->navbar->add($formheading);

navigation_node::override_active_url($plugin->viewlink);
local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation)->make_active();
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);
$renderer = local_plugins_get_renderer($plugin);

$formparameters = array();
$formparameters['formheading'] = $formheading;
$formparameters['contributor'] = $contributor;
$mform = new local_plugins_contributor_form(null, $formparameters);
$mform->set_data($contributor);

if ($mform->is_cancelled()) {
    redirect($plugin->viewlink);
} else if ($mform->is_submitted() && $mform->is_validated() && confirm_sesskey()) {
    local_plugins_log::remember_state($plugin);
    $data = $mform->get_data();
    $newdata = array('type' => $data->type);
    if (!empty($data->leadmaintainer)) {
        // the button "Make lead maintainer" was pressed
        $newdata['maintainer'] = local_plugins_contributor::LEAD_MAINTAINER;
    } else if (isset($data->maintainer) && $data->maintainer) {
        // the checkbox "Maintainer" is checked
        $newdata['maintainer'] = local_plugins_contributor::MAINTAINER;
    } else if ($mform->can_reset_maintainer($contributor)) {
        // the checkbox "Maintainer" was present on the form but was not checked
        $newdata['maintainer'] = 0;
    } // else maintainer field will not be changed
    
    if (empty($contributor->id) && (isset($data->submitbutton) || isset($data->leadmaintainer))) {
        $newdata['userid'] = local_plugins_helper::search_for_user($data->contributor);
        $contributor = $plugin->add_contributor($newdata);
    }
    if (!empty($contributor->id)) {
        if (isset($data->deletebutton)) {
            $contributor->delete();
        } else if (isset($data->submitbutton) || isset($data->leadmaintainer)) {
            $contributor->update($newdata);
        }
    }
    $plugin->get_contributors(true);
    local_plugins_log::log_edited($plugin);
    redirect($plugin->viewlink);
}

echo $renderer->header();
$mform->display();
echo $renderer->footer();






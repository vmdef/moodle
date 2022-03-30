<?php

/**
 * This file displays the home page for the plugin
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

$context = context_system::instance();

$plugin = local_plugins_helper::get_plugin_from_params(IGNORE_MISSING);

if (empty($plugin)) {
    local_plugins_error();
}

if (!$plugin->can_view()) {
    local_plugins_error(null, null, 403);
}

$addtoset = optional_param('addtoset', 0, PARAM_INT);
$removefromset = optional_param('removefromset', 0, PARAM_INT);
$addaward = optional_param('addaward', 0, PARAM_INT);
$revokeaward = optional_param('revokeaward', 0, PARAM_INT);

$PAGE->set_url($plugin->viewlink);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name);
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$renderer = local_plugins_get_renderer($plugin);

if (!empty($addtoset) || !empty($removefromset) || !empty($addaward) || !empty($revokeaward)) {
    require_login();
    require_sesskey();
    local_plugins_log::remember_state($plugin);
    if (!empty($addtoset)) {
        require_capability(local_plugins::CAP_ADDTOSETS, $context);
        $plugin->add_to_set($addtoset);
        local_plugins_log::log_edited($plugin);
    } else if (!empty($removefromset)) {
        require_capability(local_plugins::CAP_ADDTOSETS, $context);
        $plugin->remove_from_set($removefromset);
        local_plugins_log::log_edited($plugin);
    } else if (!empty($addaward)) {
        require_capability(local_plugins::CAP_HANDOUTAWARDS, $context);
        $plugin->add_award($addaward);
        local_plugins_log::log_edited($plugin);
    } else if (!empty($revokeaward)) {
        require_capability(local_plugins::CAP_HANDOUTAWARDS, $context);
        $plugin->revoke_award($revokeaward);
        local_plugins_log::log_edited($plugin);
    }
    redirect($plugin->viewlink);
}

echo $renderer->header(null, true, ['showgetbuttons' => true, 'showlabels' => true, 'showprecheck' => true]);
echo $renderer->render($plugin);
echo $renderer->footer();
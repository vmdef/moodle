<?php

/**
 * This file displays reviews for a version of a plugin or all
 * the reviews for the plugin.
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

$plugin = local_plugins_helper::get_plugin_from_params(IGNORE_MISSING);
$reviewid = optional_param('review', false, PARAM_INT);

if ($plugin) {
    $version = $plugin->mostrecentversion;
    $versionid = null;
    $url = $plugin->viewreviewslink;
} else {
    $versionid = required_param('version', PARAM_INT);
    $plugin = local_plugins_helper::get_plugin_by_version($versionid);
    $version = $plugin->versions[$versionid];
    $url = $version->reviewlink;
    navigation_node::override_active_url($plugin->viewreviewslink);
    $PAGE->navbar->add($version->formatted_releasename, $version->reviewlink, navigation_node::TYPE_CUSTOM, $version->version);
}

if (!$plugin->can_view()) {
    local_plugins_error();
}

$context = context_system::instance();

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name. ': '. get_string('reviews', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$renderer = local_plugins_get_renderer($plugin);

echo $renderer->header(null, true);
echo $renderer->reviews($plugin, $versionid, $reviewid);
echo $renderer->footer();
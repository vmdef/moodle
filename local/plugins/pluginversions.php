<?php

/**
 * This file displays the different versions of a plugin that are available.
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
require_once($CFG->dirroot. '/local/plugins/lib/archive_validator.php');

$plugin = local_plugins_helper::get_plugin_from_params();

if (!$plugin->can_view()) {
    local_plugins_error(null, null, 403);
}

$PAGE->set_url($plugin->viewversionslink);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name. ': '. get_string('downloadversions', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$renderer = local_plugins_get_renderer($plugin);

echo $renderer->header();
echo $renderer->current_versions($plugin->latestversions);
echo $renderer->current_unavailable_versions($plugin->unavailablelatestversions);
echo $renderer->previous_versions($plugin->previousversions);
echo $renderer->footer();

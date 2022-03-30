<?php
// This file is part of Moodle - https://moodle.org/
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
 * Provides the plugin translations info page.
 *
 * @package     local_plugins
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// @codingStandardsIgnoreLine Login check not expected here.
require_once(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$plugin = local_plugins_helper::get_plugin_from_params();

$PAGE->set_url($plugin->translationslink);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('translationstab', 'local_plugins'). ': '. $plugin->formatted_name);
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

$output = local_plugins_get_renderer($plugin);

echo $output->header(null, true);
echo $output->heading(get_string('translationsamos', 'local_plugins'), 3);

if ($plugin->approved != local_plugins_plugin::PLUGIN_APPROVED) {
    echo $output->box(get_string('translationunapproved', 'local_plugins'));
    echo $output->footer();
    exit();
}

$statsman = new local_plugins_translation_stats_manager($plugin);
$hasstats = $statsman->has_stats();

if (!$statsman->has_stats()) {
    echo $output->box(get_string('translationunavailable', 'local_plugins'));
    echo $output->footer();
    exit();
}

echo $output->translation_contribute($plugin, $statsman);
echo $output->translation_stats($plugin, $statsman);
echo $output->footer();
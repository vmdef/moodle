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
 * Front page of the plugin directory, provides the main UI to search and
 * browse plugins.
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrak <david@moodle.com>
 * @copyright   2011 Sam Hemelryk <sam@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins;

use block_contents;
use context_system;
use local_plugins_helper;
use local_plugins_url;

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$url = new local_plugins_url('/local/plugins/index.php');
if ($mversion = optional_param('moodle_version', 0, PARAM_INT)) {
    local_plugins_helper::get_user_moodle_version(); // remember the selected version in preferences
    $redirect = optional_param('redirect', null, PARAM_RAW);
    $usereferer = optional_param('refer', true, PARAM_BOOL);
    if (empty($redirect) && isset($_SERVER["HTTP_REFERER"])) {
        if ($usereferer) {
            $redirect = $_SERVER["HTTP_REFERER"];
        }
    }
    if (!empty($redirect) && $redirect != $url) {
        local_plugins_redirect($redirect);
    }
}

$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');
$PAGE->set_subpage('plugins-index');

require_capability('local/plugins:view', $PAGE->context);

$query = optional_param('q', '', PARAM_RAW);

$browser = new output\browser();
$filter = new output\filter($query);
$browser->search($filter, 0, has_capability('local/plugins:viewunapproved', $PAGE->context));

$frontpage = new output\front_page($browser);

$output = $PAGE->get_renderer('local_plugins', 'directory');
$legacyrenderer = $PAGE->get_renderer('local_plugins');

$bc = new block_contents();
$bc->content = $legacyrenderer->frontpage_info_stats();
$bc->attributes['class'] = "frontpage-info-stats";
$PAGE->blocks->add_fake_block($bc, $PAGE->blocks->get_default_region());

$reports = local_plugins_helper::get_reports_viewable_by_user(true);
$outputreports = $legacyrenderer->report_summary($reports['quickaccess'], true);

if (!empty($outputreports)) {
    $bc = new block_contents();
    $bc->title = get_string('reports', 'local_plugins');
    $bc->attributes['class'] = 'block block_local_plugins_reports';
    $bc->content = $outputreports;
    $PAGE->blocks->add_fake_block($bc, $PAGE->blocks->get_default_region());
}

echo $output->header();
echo $output->render($frontpage);
echo $output->footer();

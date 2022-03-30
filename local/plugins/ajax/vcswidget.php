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
 * Server-side counterpart of the vcswidget.js module
 *
 * Provides AJAX access to the {@link vcs_manager::get_available_tags()}
 * results for the given plugin.
 *
 * @package     local_plugins
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
header("Content-Type:text/plain");

require(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$plugin = local_plugins_helper::get_plugin_from_params(MUST_EXIST);
$PAGE->set_context(context_system::instance());

require_login();
require_sesskey();
if (isguestuser()) {
    throw new local_plugins_exception('exc_noguestsallowed');
}
if (!$plugin->can_edit()) {
    print_error('exc_cannotviewplugin', 'local_plugins');
}

$renderer = local_plugins_get_renderer($plugin);
$vcsman = new local_plugins_vcs_manager($plugin);

if ($vcsman->uses_github()) {
    $tags = $vcsman->get_available_tags();
    $response = (object)array(
        'success' => true,
        'widget' => $renderer->widget_add_vcs_version($vcsman->get_vcs_url(), $tags,
            new local_plugins_url($plugin->addversionlink, array('cc' => '1')), array('id' => $plugin->id, 'sesskey' => sesskey())),
    );
    echo json_encode($response);
}

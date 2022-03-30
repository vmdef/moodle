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
 * Displays list of reviews available in the Plugins directory.
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$url = new local_plugins_url('/local/plugins/reviews/');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('reviews', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

$page = 0;
$perpage = 100; // No time for proper pagination support at the moment.
$reviewerfields = \core_user\fields::for_userpic()->get_sql('u', false, 'reviewer', 'reviewerid', false)->selects;

$sql = "SELECT r.*, $reviewerfields, p.id AS pluginid
          FROM {local_plugins_review} r
          JOIN {user} u ON u.id = r.userid
          JOIN {local_plugins_vers} v ON v.id = r.versionid
          JOIN {local_plugins_plugin} p ON p.id = v.pluginid
         WHERE p.approved = ".local_plugins_plugin::PLUGIN_APPROVED."
      ORDER BY r.timereviewed DESC";

$reviewrecords = $DB->get_records_sql($sql, [], $page * $perpage, $perpage);

// Preload plugins to avoid at least some SQL queries in the loop.
$pluginids = [];
foreach ($reviewrecords as $reviewrecord) {
    $pluginids[$reviewrecord->pluginid] = true;
}
$pluginrecords = $DB->get_records_list('local_plugins_plugin', 'id', array_keys($pluginids));
$plugins = local_plugins_helper::load_plugins_from_result($pluginrecords);

// Populate the list of reviews.
$reviews = [];
foreach ($reviewrecords as $reviewrecord) {
    if (isset($reviews[$reviewrecord->pluginid.':'.$reviewrecord->reviewerid])) {
        // We only display here the most recent review of a plugin by a reviewer.
        continue;
    }
    if ($reviewrecord->status == 0) {
        // Only display own or published reviews.
        if ($USER->id != $reviewrecord->userid) {
            if (!has_capability('local/plugins:approvereviews', context_system::instance())) {
                continue;
            }
        }
    }
    $reviewrecord->user = user_picture::unalias($reviewrecord, null, 'reviewerid', 'reviewer');
    $reviewrecord->plugin = $plugins[$reviewrecord->pluginid];
    $reviews[$reviewrecord->pluginid.':'.$reviewrecord->reviewerid] = new local_plugins_review($reviewrecord);
}

$ui = $PAGE->get_renderer('local_plugins', 'ui');
echo $ui->page_reviews($reviews);

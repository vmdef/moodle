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
 * Displays the stats for the approval queue.
 *
 * @package     local_plugins
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

require_capability(local_plugins::CAP_VIEWQUEUESTATS, context_system::instance());

$PAGE->set_url(new local_plugins_url('/local/plugins/queue.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('queuestats', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

$output = local_plugins_get_renderer();

$manager = new local_plugins_queue_stats_manager();

echo $output->header();
echo $output->queue_stats_page($manager);
echo $output->footer();
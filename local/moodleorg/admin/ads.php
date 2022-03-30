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
 * Allows to manage ads displayed via the partners block on our community sites.
 *
 * @package     local_moodleorg
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_moodleorg\manage_ads_table;
use local_moodleorg\manage_ads_form;

require(__DIR__.'/../../../config.php');

$edit = optional_param('edit', null, PARAM_INT);
$delete = optional_param('delete', null, PARAM_INT);
$confirm = optional_param('confirm', false, PARAM_BOOL);

$context = context_system::instance();

$PAGE->set_pagelayout('admin');
$PAGE->set_context($context);
$PAGE->set_url('/local/moodleorg/admin/ads.php');
$PAGE->set_title(get_string('manageads', 'local_moodleorg'));
$PAGE->set_heading($PAGE->title);

require_capability('local/moodleorg:manageads', $context);

if ($delete) {
    if ($confirm) {
        require_sesskey();
        $DB->delete_records('register_ads', ['id' => $delete]);
        redirect($PAGE->url);

    } else {
        $entry = $DB->get_record('register_ads', ['id' => $delete], '*', MUST_EXIST);
        $message = 'Do you really want to remove the ad for partner "'.s($entry->partner).'", country "'.s($entry->country).'" ?';

        echo $OUTPUT->header();
        echo $OUTPUT->heading($PAGE->title);
        echo $OUTPUT->confirm($message, new moodle_url($PAGE->url, ['delete' => $delete, 'confirm' => 1]), $PAGE->url);
        echo $OUTPUT->footer();
        die();
    }
}

if ($edit) {
    $form = new manage_ads_form($PAGE->url, ['edit' => $edit]);

    if ($form->is_cancelled()) {
        redirect($PAGE->url);

    } else if ($data = $form->get_data()) {
        if ($data->edit > 0) {
            $data->id = $data->edit;
            $DB->update_record('register_ads', $data);
            redirect($PAGE->url);

        } else {
            $DB->insert_record('register_ads', $data);
            redirect($PAGE->url);
        }

    } else {
        if ($edit > 0) {
            $form->set_data($DB->get_record('register_ads', ['id' => $edit], '*', MUST_EXIST));
        }

        echo $OUTPUT->header();
        echo $OUTPUT->heading($PAGE->title);
        $form->display();
        echo $OUTPUT->footer();
        die();
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading($PAGE->title);

echo $OUTPUT->single_button(new moodle_url($PAGE->url, ['edit' => -1]), 'Add new entry', 'get');

$table = new manage_ads_table('local_moodleorg-manage_ads-table');
$from = '{register_ads}';
$table->set_sql('id,country,partner,title,image', $from, '1=1');
$table->define_columns(['image', 'partner', 'title', 'country', 'actions']);
$table->define_headers(['Image', 'Partner ID', 'Title', 'Country', 'Actions']);
$table->column_style('country', 'min-width', '6em');
$table->column_style('actions', 'min-width', '10em');
$table->collapsible(false);
$table->sortable(true, 'country', SORT_ASC);
$table->define_baseurl($PAGE->url);
$table->out(100, false, true);

echo $OUTPUT->footer();

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
 * Plugins descriptors management page
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_plugins\form\descriptor;

require(__DIR__.'/../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$search = optional_param('search', null, PARAM_RAW_TRIMMED);
$page = optional_param('page', 0, PARAM_INT);
$perpage = 10;

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new local_plugins_url('/local/plugins/admin/descriptors.php'));
$PAGE->set_title(get_string('managedescriptors', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');
$PAGE->requires->js_call_amd('local_plugins/descman', 'init', []);

require_login();
require_capability('local/plugins:managedescriptors', $PAGE->context);

// Process new descriptor form.

$form = new descriptor();

if ($data = $form->get_data()) {
    if (empty($data->descid)) {
        $descid = $DB->insert_record('local_plugins_desc', ['title' => $data->title, 'sortorder' => $data->sortorder]);
        $rows = explode("\n", $data->values);
        foreach ($rows as $row) {
            $items = array_map('trim', explode(',', $row));
            foreach ($items as $item) {
                $item = clean_param($item, PARAM_TAG);
                if ($item !== '') {
                    $DB->insert_record('local_plugins_desc_values', ['descid' => $descid, 'pluginid' => null, 'value' => $item]);
                }
            }
        }
    } else {
        $DB->update_record('local_plugins_desc', ['id' => $data->descid, 'title' => $data->title, 'sortorder' => $data->sortorder]);
    }
    redirect($PAGE->url);
}

// Process submitted data (assigned desciptor values).

if ($data = data_submitted()) {
    require_sesskey();

    // Convert submitted data into a tree structure.
    $values = [];

    if (!empty($data->pluginids)) {
        foreach ($data->pluginids as $pluginid) {
            foreach ($data->{'plugin'.$pluginid.'descids'} as $descid) {
                if (isset($data->{'plugin'.$pluginid.'desc'.$descid})) {
                    $values[$pluginid][$descid] = array_values($data->{'plugin'.$pluginid.'desc'.$descid});
                } else {
                    $values[$pluginid][$descid] = [];
                }
            }
        }
    }

    // Update the database.
    foreach ($values as $pluginid => $descriptors) {
        $plugin = local_plugins_helper::get_plugin($pluginid);
        // Attach current descriptions to the plugin object.
        $plugin->get_descriptors();
        local_plugins_log::remember_state($plugin);

        foreach ($descriptors as $descid => $newvalues) {
            $plugin->set_descriptors($descid, $newvalues);
        }

        local_plugins_log::log_edited($plugin);
    }

    $url = $PAGE->url;

    if ($search) {
        $url->param('search', $search);
    }

    if ($page) {
        $url->param('page', $page);
    }

    redirect(new local_plugins_url($url));
}

// Load descriptors and their values.

$sql = "SELECT DISTINCT d.id, d.title, v.value
          FROM {local_plugins_desc} d
     LEFT JOIN {local_plugins_desc_values} v ON v.descid = d.id
      ORDER BY d.sortorder, v.value";

$recordset = $DB->get_recordset_sql($sql);
$desctitles = [];
$descvalues = [];

foreach ($recordset as $record) {
    if (!isset($desctitles[$record->id])) {
        $desctitles[$record->id] = $record->title;
    }

    if ($record->value !== null) {
        $descvalues[$record->id][$record->value] = $record->value;
    }
}

$recordset->close();

// Load plugins to be shown on the current page.

$params = [];
$sql = "SELECT p.id, p.name, p.frankenstyle, p.shortdescription,
               v.value AS descvalue, d.id AS descid, d.title AS desctitle
          FROM {local_plugins_plugin} p
     LEFT JOIN {local_plugins_desc_values} v ON v.pluginid = p.id
     LEFT JOIN {local_plugins_desc} d ON v.descid = d.id
         WHERE p.approved = 1 ";

if (!empty($search)) {
    // Load only plugins matching the search phrase.
    $searchsql = [];
    foreach (['name', 'frankenstyle', 'shortdescription', 'description'] as $field) {
        $searchsql[] = $DB->sql_like('p.'.$field, '?', false, false);
        $params[] = '%'.$DB->sql_like_escape($search).'%';
    }
    $searchsql[] = $DB->sql_like('v.value', '?', false, false);
    $params[] = '%'.$DB->sql_like_escape($search).'%';
    $searchsql[] = 'p.id = ?';
    $params[] = $search;
    $sql .= " AND ( ".implode(" OR ", $searchsql)." ) ";
}

$sql .= " ORDER BY p.timelastreleased DESC, p.id DESC ";

$recordset = $DB->get_recordset_sql($sql, $params);
$plugins = [];
$index = 0;

foreach ($recordset as $record) {
    if (!isset($plugins[$record->id])) {
        if (($index >= $page * $perpage) and ($index < ($page + 1) * $perpage)) {
            // Display the plugin on this page.
            $plugins[$record->id] = (object)[
                'id' => $record->id,
                'index' => $index,
                'name' => $record->name,
                'frankenstyle' => $record->frankenstyle,
                'shortdescription' => $record->shortdescription,
                'descriptors' => [],
            ];

        } else {
            // Not on this page.
            $plugins[$record->id] = false;
        }

        $index++;
    }

    if ($plugins[$record->id] !== false and $record->descid !== null and $record->descvalue !== null) {
        if (!isset($plugins[$record->id]->descriptors[$record->descid])) {
            $plugins[$record->id]->descriptors[$record->descid] = [];
        }
        $plugins[$record->id]->descriptors[$record->descid][] = $record->descvalue;
    }
}

$recordset->close();

$output = $OUTPUT;

$pagingbar = $output->paging_bar(count($plugins), $page, $perpage, new local_plugins_url($PAGE->url, ['search' => $search]));

echo $output->header();
echo $output->heading($PAGE->heading);

echo '<div data-widget="desc-form-target">';

echo '<div style="display:inline-block;">';
echo '<form method="get" class="form-search"><div class="input-append"><input name="search" type="text" class="search-query"
    value="'.s($search).'"><button type="submit" class="btn">'.get_string('search').'</button></div></form>';
echo '</div>';

echo '<div style="display:inline-block;padding:15px;min-height:40px;">';
echo $pagingbar;
echo '</div>';

echo '<div class="pull-right">';
$options = [0 => get_string('descriptoradd', 'local_plugins')];
if ($desctitles) {
    $options['editdesc'] = [
        get_string('edit') => $desctitles,
    ];
}
echo html_writer::select($options, 'descedit', '', ['' => get_string('managedescriptors', 'local_plugins')],
    ['data-widget' => 'desc-manage-trigger', 'data-contextid' => $PAGE->context->id]);
echo '</div>';

echo '</div>';

if (empty($plugins)) {
    echo '<div class="card" style="clear:both"><strong>No plugin found</strong></div>';

} else {
    echo '<div class="" style="clear:both"><form method="post">
            <input name="search" value="'.s($search).'" type="hidden">
            <input name="page" value="'.$page.'" type="hidden">
            <input name="sesskey" value="'.sesskey().'" type="hidden">';

    echo '<div class="pull-right" style="margin:0 0 1em">
        <button class="btn btn-success" type="submit">'.get_string('savechanges').'</button>
        </div>';

    foreach ($plugins as $plugin) {

        if ($plugin === false) {
            continue;
        }

        if (empty($plugin->frankenstyle)) {
            $link = new local_plugins_url('/local/plugins/view.php', ['id' => $plugin->id]);
        } else {
            $link = new local_plugins_url('/local/plugins/'.$plugin->frankenstyle);
        }

        echo '<div class="row">';

        echo '<div class="col-md-6">';
        echo '<h3 style="display:block;line-height:20px"><a href="'.$link.'" target="_blank">'.s($plugin->name).'</a></h3>';
        echo '<div><small class="muted">#'.($plugin->index + 1).' | '.
            s($plugin->frankenstyle).'</small></div>';
        echo '<p>'.s($plugin->shortdescription).'</p>';
        echo '</div>';

        echo '<div class="col-md-6">';

        echo '<input type="hidden" name="pluginids[]" value="'.$plugin->id.'">';

        echo '<div class="card" style="padding:8px">';
        foreach ($desctitles as $descid => $desctitle) {
            echo '<input type="hidden" name="plugin'.$plugin->id.'descids[]" value="'.$descid.'">';
            echo '<div>';
            if (!isset($descvalues[$descid])) {
                $descvalues[$descid] = [];
            }
            if (isset($plugin->descriptors[$descid])) {
                $selected = $plugin->descriptors[$descid];
            } else {
                $selected = null;
            }
            echo html_writer::select($descvalues[$descid], 'plugin'.$plugin->id.'desc'.$descid.'[]', $selected,
                null, ['id' => 'plugin'.$plugin->id.'desc'.$descid, 'multiple' => true, 'size' => 1,
                'data-desctitle' => s($desctitle), 'data-widget' => 'desc-fill']);

            echo '</div>';
        }
        echo '</div>'; // card

        echo '</div>'; // col-md-6
        echo '</div>'; // row
        echo '<hr>';
    }

    echo '<div class="pull-right" style="margin:1em 0 0">
        <button class="btn btn-success" type="submit">'.get_string('savechanges').'</button>
        </div>';

    echo '<div style="clear:both"></div>';

    echo '</form>';
    echo '</div>';
}

echo $output->footer();

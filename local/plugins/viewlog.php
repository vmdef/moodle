<?php

/**
 * This file allows the user to view logs
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Marina Glancy
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$context = context_system::instance();
require_login();
require_capability(local_plugins::CAP_EDITANYPLUGIN, $context); //TODO LOG change to proper one

$pluginid = optional_param("pluginid", null, PARAM_INT);
$userid = optional_param("userid", null, PARAM_INT);
$showall = optional_param("showall", 0, PARAM_INT);
$logid = optional_param("id", 0, PARAM_INT);
$logbulkid = optional_param("bulkid", 0, PARAM_INT);
$actions = optional_param("action", null, PARAM_TEXT);
$query = "1=1";
$params = array();
$plugin = null;
if ($pluginid) {
    $plugin = local_plugins_helper::get_plugin($pluginid, IGNORE_MISSING); // this may be a log for already deleted plugin
    $query .= " and pluginid = :pluginid";
    $params['pluginid'] = $pluginid;
}
if ($userid) {
    $query .= " and userid = :userid";
    $params['userid'] = $userid;
}
if ($logid) {
    $query .= " and id = :logid";
    $params['logid'] = $logid;
}
if ($logbulkid) {
    $query .= " and bulkid = :bulkid";
    $params['bulkid'] = $logbulkid;
}
if (!empty($actions)) {
    $actionslist = preg_split('/\s*,\s*/', trim($actions));
    list($actionselect, $actionparams) = $DB->get_in_or_equal($actionslist, SQL_PARAMS_NAMED, 'action');
    $query .= " and action ". $actionselect;
    $params = array_merge($params, $actionparams);
}
$logs = local_plugins_log::get_log_sql($query, $params);

$baseurl = new local_plugins_url('/local/plugins/viewlog.php');
$PAGE->set_url($baseurl);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins')); // TODO LOG
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');
if ($plugin) {
    local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation)->make_active();
    local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);
    $renderer = local_plugins_get_renderer($plugin);
} else {
    $renderer = local_plugins_get_renderer();
}
echo $renderer->header();

$records = $DB->get_records_sql("SELECT action, count(*) cnt from {local_plugins_log} group by action");
echo "<ul>";
foreach ($records as $record) {
    echo "<li>". html_writer::link(new local_plugins_url('/local/plugins/viewlog.php', array('action' => $record->action)), $record->action). ' ('.$record->cnt.')</li>';
}
echo "</ul>";

echo "<table border=1>";
$lastbulk = null;
foreach ($logs as $log) {
    if ($lastbulk != $log->bulkid) {
        $userlink = html_writer::link(new local_plugins_url("/local/plugins/viewlog.php", array('userid' => $log->userid)), $log->username);
        echo '<tr><td colspan=3 style="background:yellow">'.
            html_writer::link(new local_plugins_url("/local/plugins/viewlog.php", array('bulkid' => $log->bulkid, 'showall' => empty($showall))), 'Bulk '.$log->bulkid).
            ' : '.userdate($log->time).' by '.$userlink.
            '</td></tr>';
        $lastbulk = $log->bulkid;
    }
    echo "<tr><td colspan=3 style=\"background:#DDD;\">";
    echo '<b>'.$log->action.'</b><br>'.$log->info['identifier'];
    if (!$showall) {
        echo ' : '.html_writer::link(new local_plugins_url("/local/plugins/viewlog.php", array('id' => $log->id, 'showall' => 1)), 'More info');
    }
    if (array_key_exists('comment', $log->info) && !empty($log->info['comment'])) {
        echo '<br>'.$log->info['comment'];
    }
    echo "</td></tr>";

    $keys = $oldvalues = $newvalues = array();
    if (array_key_exists('oldvalue', $log->info)) {
        $oldvalues = $log->info['oldvalue'];
    }
    if (array_key_exists('newvalue', $log->info)) {
        $newvalues = $log->info['newvalue'];
    }
    if (preg_match('/-edit$/', $log->action)) {
        if ($showall) {
            $keys = array_keys($newvalues);
            foreach ($keys as $key) if (!array_key_exists($key, $oldvalues)) $oldvalues[$key] = $newvalues[$key];
        } else {
            $keys = array_keys($oldvalues);
        }
    }
    if (preg_match('/-delete$/', $log->action) && $showall) {
        $keys = array_keys($oldvalues);
    }
    if (preg_match('/-add$/', $log->action) && $showall) {
        $keys = array_keys($newvalues);
    }
    foreach ($keys as $key) {
        $oldval = $newval = '&nbsp;';
        if (array_key_exists($key, $oldvalues)) {
            $oldval = $oldvalues[$key];
        }
        if (array_key_exists($key, $newvalues)) {
            $newval = $newvalues[$key];
        }
        try {
            if (preg_match("/^(\w*?):(.*)$/", $key, $matches)) {
                $name = get_string($matches[1], 'local_plugins'). $matches[2];
            } else {
                $name = get_string($key, 'local_plugins');
            }
        } catch (Exception $e) {
            $name = "<font color=red>$key</font>";
        }
        echo "<tr><th>$name</th><td>".nl2br($oldval)."</td><td>".nl2br($newval)."</td></tr>";
    }
}
echo "</table>";

echo $renderer->footer();
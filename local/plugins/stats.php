<?php

/**
 * This file displays the stats for the plugin.
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

if (!is_null($plugin)) {
    if (empty($plugin)) { //since we're ignoring missing above (for overview statistics below) lets do the MUST_EXIST check here.
        local_plugins_error();
    }
    if (!$plugin->can_view()) {
        local_plugins_error(null, null, 403);
    }

    $PAGE->set_url($plugin->viewstatslink);
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. $plugin->formatted_name. ': '. get_string('stats', 'local_plugins'));
    $PAGE->set_heading($PAGE->title);
    $PAGE->set_pagelayout('standard');
    $PAGE->navbar->ignore_active(true);
    $PAGE->navbar->add(get_string('plugins', 'local_plugins'), new local_plugins_url('/local/plugins/index.php'));
    $PAGE->navbar->add($plugin->category->name, $plugin->category->get_browseurl());
    $PAGE->navbar->add($plugin->name, $plugin->baselink);
    $PAGE->navbar->add(get_string('stats', 'local_plugins'), new local_plugins_url('/local/plugins/stats.php', array('plugin' => $plugin->frankenstyle)));

    local_plugins_extend_navigation_for_plugin($plugin, $PAGE->navigation);
    local_plugins_extend_settings_for_plugin($plugin, $PAGE->settingsnav);

    $node1 = $PAGE->navigation->get('local_plugins', navigation_node::TYPE_CONTAINER);
    $node2 = $node1->find('local_plugins-overviewstats', navigation_node::TYPE_CUSTOM);
    if ($node2) {
        // The Overview stats node is implicitly active due to the URL match.
        $node2->make_inactive();
    }

    $renderer = local_plugins_get_renderer($plugin);

    echo $renderer->header();
    echo $renderer->stats($plugin);
    echo $renderer->footer();
} else {
    // render full plugins overview statistics.
    $url = new local_plugins_url('/local/plugins/stats.php');
    $PAGE->set_url($url);
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('stats', 'local_plugins'));
    $PAGE->set_heading($PAGE->title);
    $PAGE->set_pagelayout('standard');

    $renderer = local_plugins_get_renderer();

    $content = $renderer->stats_overview(); //get the navbars handled inside first
    echo $renderer->header();
    echo $content;
    echo $renderer->footer();
}
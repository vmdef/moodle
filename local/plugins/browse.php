<?php

/**
 * This file allows the user to view all of the plugins within a award or set.
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

// Type and id are required params where id is the id of the related type
$type = required_param('list', PARAM_ALPHA);
$id = required_param('id', PARAM_INT);

// The current URL that can be used to return to this page
$url = new local_plugins_url('/local/plugins/browse.php', array('list' => $type, 'id' => $id));

// Prepare the page
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());

// We need to get an array of plugins as well as the total count, and a title + description for the
// page that we are viewing.
switch ($type) {
    case 'award':
        $element = local_plugins_helper::get_award($id);
        $PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string($type, 'local_plugins'). ': '. $element->formatted_name);
        break;
    case 'set':
        $element = local_plugins_helper::get_set($id);
        $PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string($type, 'local_plugins'). ': '. $element->formatted_name);
        break;
    case 'category':
        $element = local_plugins_helper::get_category($id);
        $PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string($type, 'local_plugins'). ': '. $element->formatted_name);
        break;
    case 'contributor':
        $element = local_plugins_helper::get_contributor_by_user_id($id);
        $PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string($type, 'local_plugins'). ': '. $element->username);
        break;
    default:
        redirect(new local_plugins_url('/local/plugins/'));
}

$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

// Update the navigation node for ths browse page to reflect what we are looking at
switch ($type) {
    case 'award':
    case 'set':
        $node = $PAGE->navigation->get('local_plugins')->get('browse');
        $node->shorttext = get_string('browsenamed', 'local_plugins', $element->formatted_name);
        $node->text = $node->shorttext;
        $node->action = $url;
        $node->display = true;
        break;
    case 'contributor':
        // For the current user, the 'My contributions' node is used. For all others,
        // display the 'browse' node.
        if (!$element->is_current_user()) {
            $node = $PAGE->navigation->get('local_plugins')->get('browse');
            $a = new stdClass();
            $a->picture = '';
            $a->fullname = $element->username;
            $node->shorttext = get_string('contributionsmadeby', 'local_plugins', $a);
            $node->text = $node->shorttext;
            $node->action = $url;
            $node->display = true;
        }
        break;
}


// Get the local_plugins renderer
$renderer = local_plugins_get_renderer();

echo $renderer->header(null, true);
echo $renderer->render($element);
echo $renderer->footer();
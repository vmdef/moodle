<?php

/**
 * This file contains functions that are used just within this
 * plugin. They should be generic functions that don't releate to
 * any single area of this plugin. If they do they should be
 * moved to that area.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

// Don't allow direct access to this script
defined('MOODLE_INTERNAL') || die();

/**
 * This function is used to sort objects by their $obj->version and then by $obj->timecreated (if exist)
 */
function local_plugins_sort_by_version($one, $two) {
    if ($one->version == $two->version) {
        if (isset($one->timecreated) && isset($two->timecreated) && $one->timecreated != $two->timecreated) {
            return ($one->timecreated > $two->timecreated) ? 1 : -1;
        }
        return 0;
    }
    return strcmp(''. $one->version, ''. $two->version);
}

/**
 * This function is used to ensure at least one category has been created.
 * If not then the plugin is deemed to be installed but not set up and as such
 * the user is taken to create categories for the plugin.
 */
function local_plugins_ensure_categories_created() {
    if (!optional_param('initialsetup', false, PARAM_BOOL) && count(local_plugins_helper::get_categories()) === 0) {
        if (has_capability(local_plugins::CAP_MANAGECATEGORIES, context_system::instance())) {
            redirect(new local_plugins_url('/local/plugins/admin/categories.php', array('initialsetup' => true)));
        }
        $PAGE->set_url(new local_plugins_url('/index.php'));
        $PAGE->set_context($context);
        $PAGE->set_title(get_string('pluginname', 'local_plugins'));
        $PAGE->set_heading($PAGE->title);
        $PAGE->set_pagelayout('standard');
        $renderer = local_plugins_get_renderer();
        echo $renderer->header();
        echo $renderer->notification(get_string('setuprequired', 'local_plugins'));
        echo $renderer->footer();
        die();
    }
}

/**
 * Extends a navigation node with settings navigation for the given plugin
 *
 * @global stdClass $USER
 * @param local_plugins_plugin $plugin
 * @param settings_navigation $settings
 * @return navigation_node|false
 */
function local_plugins_extend_settings_for_plugin(local_plugins_plugin $plugin, settings_navigation $settings) {
    global $USER;

    // No point being here if the user can not change anything about this plugin
    if (!$plugin->can_edit() && !$plugin->can_approve()) {
        return true;
    }

    // Prepend a node to the root of the settings navigation
    $node = $settings->prepend($plugin->formatted_name, null, navigation_node::TYPE_CONTAINER, null, 'plugin-admin-'.$plugin->id);
    // Make sure it is open
    $node->force_open();

    // Add a link to approve the plugin is the plugin is not yet approved or has been disapproved and the user can approve it.
    if ($plugin->approved != local_plugins_plugin::PLUGIN_APPROVED && $plugin->can_approve()) {
        $node->add(get_string('approvethisplugin', 'local_plugins'), $plugin->approvelink);
    }

    // Add a link to disapprove the plugin (if user can approve)
    if ($plugin->approved != local_plugins_plugin::PLUGIN_UNAPPROVED && $plugin->can_approve()) {
        $node->add(get_string('disapprovethisplugin', 'local_plugins'), $plugin->disapprovelink);
    }

    // Add a link to reschedule plugin for approval (if user can edit plugin)
    if ($plugin->approved == local_plugins_plugin::PLUGIN_UNAPPROVED && $plugin->can_edit()) {
        $node->add(get_string('scheduleapprove', 'local_plugins'), $plugin->scheduleapprovallink);
    }

    if (has_capability(local_plugins::CAP_EDITANYPLUGIN, context_system::instance())) { //same cap as in viewlog.php for now.
        $node->add(get_string('changelog', 'local_plugins'), $plugin->pluginloglink);
    }

    if ($plugin->can_viewvalidation()) {
        $node->add(get_string('viewvalidation', 'local_plugins'), $plugin->viewvalidationlink);
    }

    if ($plugin->can_edit()) {
        // Add a link to edit the plugin
        $node->add(get_string('editplugin', 'local_plugins'), $plugin->editlink, navigation_node::TYPE_SETTING, null, 'plugin-edit-'.$plugin->id, new pix_icon('t/edit', ''));
        // Add a link to create a new version of the plugin
        $node->add(get_string('addnewversion', 'local_plugins'), $plugin->addversionlink, navigation_node::TYPE_SETTING, null, 'plugin-addversion-'.$plugin->id, new pix_icon('t/add', ''));
    }

    if (!empty($plugin->editfaqslink) && $plugin->can_edit()) {
        // Add a link to edit the FAQs of the plugin
        $node->add(get_string('editfaqs', 'local_plugins'), $plugin->editfaqslink);
    }

    // Add a link to toggle the visibility of the plugin
    if ($plugin->can_change_visibility()) {
        if ($plugin->visible) {
            $node->add(get_string('hidethisplugin', 'local_plugins'), $plugin->hidelink, navigation_node::TYPE_SETTING, null, 'plugin-hide-'.$plugin->id, new pix_icon('t/show', ''));
        } else {
            $node->add(get_string('showthisplugin', 'local_plugins'), $plugin->showlink, navigation_node::TYPE_SETTING, null, 'plugin-show-'.$plugin->id, new pix_icon('t/hide', ''));
        }
    }

    // Add a link to delete the plugin
    if ($plugin->can_delete()) {
        $node->add(get_string('deleteplugin', 'local_plugins'), $plugin->deletelink, navigation_node::TYPE_SETTING, null, 'plugin-delete-'.$plugin->id, new pix_icon('t/delete', ''));
    }
    return $node;
}

/**
 * Adds category navigation to the given node for the given category.
 *
 * This function is recursive.
 *
 * @param navigation_node $node
 * @param local_plugins_category $category
 */
function local_plugins_add_category_to_navigation_node(navigation_node $node, local_plugins_category $category) {
    global $CFG;

    // Add the category node and set its title
    $categorynode = $node->add($category->formatted_name, $category->browseurl, navigation_node::TYPE_CUSTOM, null, 'category-'.$category->id);
    $categorynode->title($category->formatted_shortdescription);

    // Add each of this categories child categories to the navigation as well
    if ($category->has_children()) {
        foreach ($category->children as $child) {
            local_plugins_add_category_to_navigation_node($categorynode, $child);
        }
    }
    $statsnode = new navigation_node(array(
        'text' => get_string('categorystats', 'local_plugins'),
        'type' => navigation_node::TYPE_CUSTOM,
    ));
    $statsnode = $categorynode->add_node($statsnode);
    $statsnode->action = $url = new local_plugins_url('/local/plugins/stats.php', array('plugin_category' => $category->id));
    $statsnode->key = 'statistics-'.$category->id;
    $statsnode->display = true;
}

/**
 * Adds category navbar to the navbar for the given category and uses $PAGE->navbar->ignore_active(true);
 *
 * obviously use this before  any output.
 * @param local_plugins_category $category
 */
function local_plugins_add_category_to_navbar(local_plugins_category $category) {
    global $PAGE;

    $PAGE->navbar->ignore_active(true);
    $PAGE->navbar->add(get_string('plugins', 'local_plugins'), new local_plugins_url('/local/plugins/index.php'));
    $pcategoryid = $category->parentid;
    $pcategory = $category;
    $cattree = array($pcategoryid => $category);
    while ($pcategory->has_parent()) {
        $pcategory = local_plugins_helper::get_category($pcategoryid);
        $pcategoryid = $pcategory->parentid;

        $cattree[$pcategoryid]=$pcategory;

    }

    while ( !( empty($cattree) || is_null($pcategory) ) ) {
        $pcategory = array_pop($cattree);
        $PAGE->navbar->add($pcategory->name, $pcategory->get_browseurl());
    }
}
/**
 * Extends the provided navigation node with navigation for the given plugin.
 *
 * @param local_plugins_plugin $plugin
 * @param global_navigation $navigation
 * @return navigation_node
 */
function local_plugins_extend_navigation_for_plugin(local_plugins_plugin $plugin, global_navigation $navigation) {
    // Get the base node in the navigation for the local_plugins plugin (added by local_plugins_extend_navigation)
    $node = $navigation->get('local_plugins', navigation_node::TYPE_CONTAINER);
    if (!$node) {
        return false;
    }
    // Locate the category the given plugin belongs in
    $node = $node->find('category-'.$plugin->categoryid, navigation_node::TYPE_CUSTOM);
    if (!$node) {
        return false;
    }

    // Add a node for the plugin
    $node = $node->add($plugin->formatted_name, $plugin->viewlink, navigation_node::TYPE_CONTAINER, null, 'plugin-'.$plugin->id);

    // Add links to the main pages for the plugin to the navigation
    $node->add(get_string('plugindescription', 'local_plugins'), $plugin->viewlink, navigation_node::TYPE_CUSTOM,
        null, 'description');
    if (!empty($plugin->viewversionslink)) {
        $node->add(get_string('downloadversions', 'local_plugins'), $plugin->viewversionslink, navigation_node::TYPE_CUSTOM,
            null, 'versions');
    }
    if (!empty($plugin->viewreviewslink) and !empty($plugin->reviews)) {
        $node->add(get_string('reviews', 'local_plugins'), $plugin->viewreviewslink, navigation_node::TYPE_CUSTOM,
            null, 'reviews');
    }
    if (!empty($plugin->viewstatslink)) {
        $node->add(get_string('stats', 'local_plugins'), $plugin->viewstatslink, navigation_node::TYPE_CUSTOM,
            null, 'stats');
    }
    if (!empty($plugin->translationslink)) {
        $node->add(get_string('translationstab', 'local_plugins'), $plugin->translationslink, navigation_node::TYPE_CUSTOM,
            null, 'translations');
    }
    if (!empty($plugin->devzonelink) and ($plugin->can_edit() or $plugin->can_approve())) {
        $node->add(get_string('devzone', 'local_plugins'), $plugin->devzonelink, navigation_node::TYPE_CUSTOM,
            null, 'devzone');
    }

    // Return the newly added node
    return $node;
}

/**
 * Allows to change the string before passing it to format_string and displaying
 * If the name of category/set/award is [[xxx]], it will be replaced with
 * the translation of xxx from local_moodleorg
 *
 * @param string $string
 * @return string
 */
function local_plugins_translate_string($string) {
    if (!empty($string) && preg_match('|^\[\[([a-zA-Z][a-zA-Z0-9\.:/_-]*)\]\]$|', $string, $matches)) {
        try {
            $string = get_string($matches[1], 'local_moodleorg');
        } catch (Exception $e) {}
    }
    return $string;
}

/**
 * If there is a param redirect, redirects there, otherwise redirects to $defaulturl
 */
function local_plugins_redirect($defaulturl, $message='', $delay=-1) {
    $redirect = optional_param('redirect', null, PARAM_RAW);
    if (!empty($redirect)) {
        redirect($redirect, $message, $delay);
    } else {
        redirect($defaulturl, $message, $delay);
    }
}

/**
 * Returns the renderer for the page. It is either local_plugins_renderer or
 * local_plugins_plugin_renderer (renderer for plugin-related pages)
 *
 * @param local_plugins_plugin $plugin
 */
function local_plugins_get_renderer($plugin = null) {
    global $PAGE;
    static $cachedrenderer = null;
    if ($cachedrenderer === null) {
        if (!empty($plugin)) {
            $cachedrenderer = $PAGE->get_renderer('local_plugins', 'plugin');
            $cachedrenderer->set_plugin($plugin);
        } else {
            $cachedrenderer = $PAGE->get_renderer('local_plugins');
        }
    }
    return $cachedrenderer;
}

/**
 * This function is iused by array_walk() to summarise the rendering of moodleversions in the download icon
 */
function local_plugins_summarise_versions(&$vers, $key, $arr) {
    static $totdiff=0;

    if ($key) {
        $pdiff = abs($arr[$key] - $arr[$key-1]);
        $ndiff=0;
        if (array_key_exists($key+1, $arr)) {
            $ndiff = abs($arr[$key] - $arr[$key+1]);
        }
        if ($pdiff > 0 && $pdiff < 0.1001) {
            if ($ndiff > 0 && $ndiff < 0.1001) { // ok, redundant version jump
                $vers = '-';
                $n2diff = 0;
                if (array_key_exists($key+2, $arr)) {
                    $n2diff = abs($arr[$key+1] - $arr[$key+2]); //look ahead to see if current is terminal.
                }
                if (!($n2diff > 0 && $n2diff < 0.1001) ) {
                    $vers = 'to';
                }
            }
        }
    }
}

/**
 * This breaks down the main process of accepting a request for direct installation.
 * This should remember the passed in site details under the logged in user's local_plugin table (local_plugin_user).
 * @param type $sitename
 * @param type $siteurl
 * @param type $siteversion
 */
function local_plugins_process_moodle_siteinfo($sitename, $siteurl, $sitemajorversion) {
    global $USER, $CFG;
    // check if user site exists, if not add it.
    $usersites = local_plugins_helper::get_usersites($USER->id);
    $exists = false;

    $moodleversions = local_plugins_helper::get_moodle_versions();
    $moodlesoftwareversions = local_plugins_helper::get_software_versions_options($moodleversions);
    $releasenames = $moodlesoftwareversions['Moodle']->releasenames;

    $version = null;
    $mversionid = null;
    foreach($moodleversions as $mversion) {
        if ($mversion->releasename == $sitemajorversion) {
            $mversionid = $mversion->id;
            $version = $mversion->version;
        }
    }

    foreach ($usersites as $usersite) {
        if ($siteurl == $usersite->siteurl && !is_null($mversionid)) {
            $exists = true;
        }
    }

    $newusersite = false;
    if(!$exists) {
        //validate data then create.
        require_once($CFG->dirroot.'/local/plugins/report/user_sites/site_form.php');
        $data = new stdClass();
        $data->id = null;
        $data->userid = $USER->id;
        $data->sitename = $sitename;
        $data->siteurl = $siteurl;
        $data->version = $mversionid;
        if (!empty($siteurl) && ($siteurl == clean_param($siteurl, PARAM_URL) && strpos($siteurl,':') != FALSE) ) {
            $newusersite = local_plugins_helper::create_usersite($data);
        }
    }
    return $mversionid;
}
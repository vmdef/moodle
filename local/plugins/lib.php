<?php

/**
 * This lib file contains function that are either used generally
 * throughout this plugin or are required by core Moodle to be here.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Number of days to wait in between cache updates.
 */
define('LOCAL_PLUGINS_CRONDAYS', 1);

/**
 * If called this checks that local/plugins/lib/setup.php has been included and
 * includes it if is hasn't.
 *
 * This is used to avoid having to include all of the local_plugins libraries and setup
 * until we actually need it - given that the functions in here are used by
 * calls outside of this.
 *
 * @global stdClass $CFG
 */
function plugins_ensure_setup_complete() {
    if (!defined('LOCAL_PLUGINSSETUP')) {
        global $CFG;
        require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
    }
}

/**
 * Extends the navigation for the local_plugins plugin
 *
 * Required to extend the navigation block for this plugin.
 * Called from lib/navigationlib.php
 *
 * @since Moodle 2.9
 * @param global_navigation $navigation
 */
function local_plugins_extend_navigation(global_navigation $navigation) {
    global $USER;

    if (!defined('LOCAL_PLUGINSSETUP')) {
        return;
    }

    $context = context_system::instance();

    // we do not use local_plugins::CAP_VIEW because it might not have been loaded
    if (!has_capability('local/plugins:view', $context)) {
        return;
    }

    // Ensure local_plugins libraries are loaded at this point.
    plugins_ensure_setup_complete();

    $baseurl = new local_plugins_url('/local/plugins/index.php');

    // Add a plugins node to the base of the navigation
    $plugins = $navigation->add(get_string('plugins', 'local_plugins'), $baseurl, navigation_node::TYPE_CONTAINER, null, 'local_plugins', new pix_icon('icon', '', 'local_plugins'));

    // Add a browse link. This is hidden by default. The page browse.php shows
    // its explicitly if needed.
    $browsenode = $plugins->add(
        get_string('browse', 'local_plugins'),
        new local_plugins_url('/local/plugins/browse.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'browse',
        new pix_icon('a/view_list_active', '')
    );
    $browsenode->display = false;

    // Add a link to the list of my contributions and a link to the page to create a new plugin
    if (has_capability(local_plugins::CAP_CREATEPLUGINS, $context)) {
        $plugins->add(
            get_string('mycontributions', 'local_plugins'),
            new local_plugins_url('/local/plugins/browse.php', array('list' => 'contributor', 'id' => $USER->id)),
            null,
            null,
            null,
            new pix_icon('i/user', '')
        );
        $plugins->add(
            get_string('createnewplugin', 'local_plugins'),
            new local_plugins_url('/local/plugins/registerplugin.php'),
            null,
            null,
            null,
            new pix_icon('t/add', get_string('add'))
        );
    }

    if (has_capability(local_plugins::CAP_EDITOWNPLUGINS, $context)) {
        $plugins->add(
            get_string('apiaccess', 'local_plugins'),
            new local_plugins_url('/local/plugins/api.php', ['sesskey' => sesskey()]),
            null,
            null,
            null,
            new pix_icon('t/apiaccess', null, 'local_plugins')
        );
    }

    // Add a link to the list of my moodle sites (report)
    if (has_capability(local_plugins::CAP_CREATEPLUGINS, $context) && isloggedin()) {
        $plugins->add(
            get_string('mymoodlesites', 'local_plugins'),
            new local_plugins_url('/local/plugins/report/index.php', array('report' => 'user_sites')),
            null,
            null,
            null,
            new pix_icon('i/mnethost', '')
        );
    }

    // Add a link to plugin reviews.
    $plugins->add(
        get_string('pluginreviews', 'local_plugins'),
        new local_plugins_url('/local/plugins/reviews/'),
        null,
        null,
        null,
        new pix_icon('t/approve', '')
    );

    // Add a statistics link
    $statistics = $plugins->add(get_string('statistics', 'local_plugins'), null, navigation_node::TYPE_CONTAINER);
    $statistics->add(get_string('overviewstats', 'local_plugins'), new local_plugins_url('/local/plugins/stats.php'),
        navigation_node::TYPE_CUSTOM, 'null', 'local_plugins-overviewstats');

    // Approval queue stats
    if (has_capability(local_plugins::CAP_VIEWQUEUESTATS, $context)) {
        $statistics->add(get_string('queuestats', 'local_plugins'), new local_plugins_url('/local/plugins/queue.php'));
    }

    // Add reports.
    $reports = local_plugins_helper::get_reports_viewable_by_user();
    foreach ($reports as $reportindex => $report) {
        if (!$report->can_view()) {
            unset($reports[$reportindex]);
        }
    }
    if (count($reports) > 0) {
        $reportnode = $plugins->add(get_string('reports', 'local_plugins'), null, navigation_node::TYPE_CONTAINER);
        $reportnode->add(get_string('reportsoverview', 'local_plugins'), new local_plugins_url('/local/plugins/report/index.php'),
            null, null, null, new pix_icon('i/report', get_string('report')));
        foreach ($reports as $report) {
            $reportnode->add(
                $report->report_title,
                new local_plugins_url('/local/plugins/report/index.php', array('report' => $report->name))
            );
        }
    }

    // Add the category structure to the navigation
    $categorytree = local_plugins_helper::get_categories_tree();
    if ($categorytree->has_children()) {
        $categories = $plugins->add(get_string('categories', 'local_plugins'), null, navigation_node::TYPE_CONTAINER, null, 'categories');
        $categories->mainnavonly = true;
        foreach ($categorytree->children as $category) {
            local_plugins_add_category_to_navigation_node($categories, $category);
        }
        $categories->trim_if_empty();
    }
}

/**
 * Extends the settings navigation (admin block) with the plugins administration nodes
 *
 * @param settings_navigation $settings
 * @return bool
 */
function local_plugins_extend_settings_navigation(settings_navigation $settings, context $context) {
    static $extended;

    // If we are not called from system context, quit early.
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return true;
    }

    // If the settings nav has already been extended we can exit here.
    if (!empty($extended)) {
        return true;
    }

    // If no right to view the plugins directory, quit early.
    if (!has_capability('local/plugins:view', $context)) {
        return true;
    }

    // Ensure local_plugins libraries are loaded at this point.
    plugins_ensure_setup_complete();

    // Add a plugin administration link to the top of the settings navigation
    $plugins = $settings->prepend(get_string('pluginname', 'local_plugins'), new local_plugins_url('/local/plugins/'),
        navigation_node::TYPE_CONTAINER, null, 'local_plugins');

    if (has_capability('local/plugins:managecategories', $context)) {
        $plugins->add(
            get_string('managecategories', 'local_plugins'),
            new local_plugins_url('/local/plugins/admin/categories.php'),
            navigation_node::TYPE_SETTING
        );
    }

    if (has_capability('local/plugins:managedescriptors', $context)) {
        $plugins->add(
            get_string('managedescriptors', 'local_plugins'),
            new local_plugins_url('/local/plugins/admin/descriptors.php'),
            navigation_node::TYPE_SETTING
        );
    }

    if (has_capability('local/plugins:managereviewcriteria', $context)) {
        $plugins->add(
            get_string('managereviewcriteria', 'local_plugins'),
            new local_plugins_url('/local/plugins/admin/criteria.php'),
            navigation_node::TYPE_SETTING
        );
    }

    if (has_capability('local/plugins:manageawards', $context)) {
        $plugins->add(
            get_string('manageawards', 'local_plugins'),
            new local_plugins_url('/local/plugins/admin/awards.php'),
            navigation_node::TYPE_SETTING
        );
    }

    if (has_capability('local/plugins:managesets', $context)) {
        $plugins->add(
            get_string('managesets', 'local_plugins'),
            new local_plugins_url('/local/plugins/admin/sets.php'),
            navigation_node::TYPE_SETTING
        );
    }

    if (has_capability('local/plugins:managesupportableversions', $context)) {
        $plugins->add(
            get_string('managesoftwareversions', 'local_plugins'),
            new local_plugins_url('/local/plugins/admin/softwareversions.php'),
            navigation_node::TYPE_SETTING
        );
    }

    $plugins->trim_if_empty();
    $extended = true;

    return true;
}

/**
 * Processes file requests for the local_plugins plugin
 *
 * Required to serve files for this plugin
 * Called from pluginfile.php
 *
 * @global moodle_database $DB
 * @param stdClass $course
 * @param null $cm
 * @param stdClass $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options additional options affecting the file serving
 * @return void|false
 */
function local_plugins_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $CFG, $DB;

    // Has to be the system context of course
    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    // First argument should ALWAYS be the itemid
    $itemid = (int)array_shift($args);

    // Construct a URL to the file and check it exists
    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/local_plugins/$filearea/$itemid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        // File doesnt exist anyway no point proceeding.
        return false;
    }

    plugins_ensure_setup_complete();

    // Switch by the fileare and check the appropriate information
    switch ($filearea) {
        case local_plugins::FILEAREA_CATEGORYDESCRIPTION :
        case local_plugins::FILEAREA_CATEGORYINSTALLINSTRUCTIONS :
        case local_plugins::FILEAREA_CATEGORYDEFAULTLOGO :
            // Make sure the itemid points to a valid category
            if ($DB->record_exists('local_plugins_category', array('id' => $itemid))) {
                send_stored_file($file, 0, 0, $forcedownload, $options);
            }
            break;
        case local_plugins::FILEAREA_REVIEWCRITERIADESC :
            // Make sure the itemid points to a valid review test
            if ($DB->record_exists('local_plugins_review_test', array('id' => $itemid))) {
                send_stored_file($file, 0, 0, $forcedownload, $options);
            }
            break;
        case local_plugins::FILEAREA_AWARDICON :
        case local_plugins::FILEAREA_AWARDDESCRIPTION :
            // Make sure the itemid points to a value award
            if ($DB->record_exists('local_plugins_awards', array('id' => $itemid))) {
                send_stored_file($file, 0, 0, $forcedownload, $options);
            }
            break;
        case local_plugins::FILEAREA_SETDESCRIPTION :
            // Make sure the itemid points to a value set
            if ($DB->record_exists('local_plugins_set', array('id' => $itemid))) {
                send_stored_file($file, 0, 0, $forcedownload, $options);
            }
            break;
        case local_plugins::FILEAREA_PLUGINDESCRIPTION :
        case local_plugins::FILEAREA_PLUGINSCREENSHOTS :
        case local_plugins::FILEAREA_PLUGINLOGO :
        case local_plugins::FILEAREA_PLUGINDOCS :
        case local_plugins::FILEAREA_PLUGINFAQS :
            // Make sure the itemid points to a valid plugin
            if ($DB->record_exists('local_plugins_plugin', array('id' => $itemid))) {
                send_stored_file($file, 0, 0, $forcedownload, $options);
            }
        case local_plugins::FILEAREA_VERSIONRELEASENOTES :
            // Make syre the itemid points to a valid version
            if ($DB->record_exists('local_plugins_vers', array('id' => $itemid))) {
                send_stored_file($file, 0, 0, $forcedownload, $options);
            }
        case local_plugins::FILEAREA_REVIEWOUTCOME :
            // Make sure the itemid points to a valid review outcome
            if ($DB->record_exists('local_plugins_review_outcome', array('id' => $itemid))) {
                send_stored_file($file, 0, 0, $forcedownload, $options);
            }
    }

    // Obviosly bogus comething or other in there
    return false;
}

/**
 * Works out the rating permissions for the local_plugins plugin
 *
 * Required to set the ratings permission
 * Called from rating/lib.php
 *
 * @param int $contextid
 * @param string $component
 * @param string $ratingarea
 * @return type
 */
function local_plugins_rating_permissions($contextid) {
    // Ensure local_plugins libraries are loaded at this point
    plugins_ensure_setup_complete();
    $context = context::instance_by_id($contextid, MUST_EXIST);
    return array(
        'view'    => has_capability(local_plugins::CAP_VIEW, $context),
        'viewany' => has_capability(local_plugins::CAP_VIEW, $context),
        'viewall' => has_capability(local_plugins::CAP_VIEW, $context),
        'rate'    => has_capability(local_plugins::CAP_RATE, $context)
    );
}

/**
 * Overrides the default template used when printing comments
 *
 * Required to override the default template for comments
 * Called from comment/lib.php
 *
 * @param stdClass $params
 *        $params->context
 *        $params->courseid
 *        $params->cm
 *        $params->commentarea
 *        $params->itemid
 * @return string
 */
function local_plugins_comment_template($params) {
    $template  = html_writer::start_tag('div', array('class' => 'plugin-comment card mb-2 font-size-normal'));
    $template .= html_writer::start_div('comment-header card-header d-flex p-2 align-items-center');
    $template .= html_writer::tag('div', '___picture___', array('class' => 'comment-userpicture'));
    $template .= html_writer::tag('div', '___name___', array('class' => 'comment-userfullname mr-2'));
    $template .= html_writer::tag('div', '___time___', array('class' => 'comment-time small'));
    $template .= html_writer::end_div(); // .comment-header
    $template .= html_writer::tag('div', '___content___', array('class' => 'comment-comment p-2'));
    $template .= html_writer::end_tag('div'); // .plugin-comment
    return $template;
}


function local_plugins_comment_display($comments, $args) {
    foreach ($comments as $c) {
        $c->strftimeformat = get_string('strftimerecentfull', 'langconfig');
        $c->time = userdate($c->timecreated, $c->strftimeformat);
    }
    return $comments;
}

/**
 * Checks the local_plugins permissions for the commenting
 *
 * Required to set the comments permission
 * Called from comment/lib.php
 *
 * @param stdClass $args
 * @return array
 */
function local_plugins_comment_permissions($args) {
    // Ensure local_plugins libraries are loaded at this point
    plugins_ensure_setup_complete();
    if (!isset($args->commentarea) || !isset($args->itemid)) {
        throw new local_plugins_exception('exc_permissiondenied');
    }
    switch ($args->commentarea) {
        case 'plugin_general':
            $plugin = local_plugins_helper::get_plugin($args->itemid);
            return array(
                'view' => $plugin->can_view(),
                'post' => has_capability(local_plugins::CAP_COMMENT, context_system::instance())
            );
    }
    return array(
        'view' => false,
        'post' => false
    );
}

/**
 * Returns true if the comments manager should allow anonymous access to view
 * comments.
 *
 * @return bool
 */
function local_plugins_comment_allow_anonymous_access() {
    return true;
}

/**
 * Validates that the parameters for a comment are valid
 *
 * Required to validate incoming comments for this plugin
 * Called from comment/lib.php
 *
 * This function avoids loading all of the local_plugins libraries as they arn't
 * needed where this function is called from
 *
 * @param stdClass $params
 *        $params->context
 *        $params->courseid
 *        $params->cm
 *        $params->commentarea
 *        $params->itemid
 * @return bool
 */
function local_plugins_comment_validate($params) {
    global $DB;

    // Check the basic params
    if ($params->context->id != SYSCONTEXTID || $params->courseid != SITEID || !empty($params->cm)) {
        throw new comment_exception('invalidcommentparam');
    }

    // Check the comment area is correct
    if ($params->commentarea != 'plugin_general') {
        throw new comment_exception('invalidcommentarea');
    }

    // Check the plugin exists;
    if (!$DB->record_exists('local_plugins_plugin', array('id' => $params->itemid))) {
        throw new comment_exception('invalidcommentitemid');
    }

    return true;
}

/**
 * Callback to notify users or plugin maintainers of comments being added.
 *
 * @param stdClass $comment
 * @param stdClass $param
 */
function local_plugins_comment_add(stdClass $comment, stdClass $param) {
    global $USER;

    // This is a nasty hack to make me happy (because I can). Ideally, there
    // should be either per-comment or per-user preference field for this.
    // Or, actually, we could just force it for everyone in this comment
    // area. For now, I need to make the comments nicely formatted during the
    // initial plugin review.
    if ($USER->id == 1601) {
        $comment->format = FORMAT_MARKDOWN;
    }

    $plugin = local_plugins_helper::get_plugin($comment->itemid);

    // Notify contributors.
    $contributors = $plugin->get_contributors();
    foreach ($contributors as $recipient) {
        $plugincommented = new core\message\message();
        $plugincommented->courseid = SITEID;
        $plugincommented->component = 'local_plugins';
        $plugincommented->name = local_plugins::NOTIFY_CONTRIBUTOR;
        $plugincommented->userfrom = $USER;
        $plugincommented->userto = $recipient->user;
        $plugincommented->subject = get_string('commentnotifcontributorsubject', 'local_plugins', $plugin->name);
        $a = array('fullname' => fullname($USER), 'message' => $comment->content, 'viewlink' => $plugin->viewlink->out());
        $plugincommented->fullmessage = get_string('commentnotifcontributormessage', 'local_plugins', (object)$a);
        $plugincommented->fullmessage .= get_string('messageprovidersetting', 'local_plugins', get_string('messageprovider:contributor', 'local_plugins'));
        $plugincommented->fullmessageformat = FORMAT_PLAIN;
        $plugincommented->fullmessagehtml = '';
        $plugincommented->smallmessage = '';
        $plugincommented->notification = 1;
        $plugincommented->contexturl = $plugin->viewlink->out();
        $plugincommented->contexturlname = $plugin->formatted_name;

        if ($USER->id != $recipient->user->id) {
            message_send($plugincommented);
        }
    }

    // Notify subscribers.
    $subscribers = local_plugins_subscription::get_subcribers($plugin->id, $plugin->sub_get_type(local_plugins::NOTIFY_COMMENT));
    foreach ($subscribers as $subscriber) {
        if ( $USER->id == $subscriber->id || array_key_exists($subscriber->id, $contributors)) {
            // Don't message contributors again or self.
            continue;
        }
        if ( $plugin->approved != local_plugins_plugin::PLUGIN_APPROVED && !has_capability(local_plugins::CAP_VIEWUNAPPROVED, context_system::instance(), $subscriber)) {
            continue;
        }
        $plugincommented->name = local_plugins::NOTIFY_COMMENT;
        $plugincommented->userto = $subscriber;
        $plugincommented->subject = get_string('commentnotifsubscriptionsubject', 'local_plugins', $plugin->name);
        $a = array('fullname' => fullname($USER), 'message' => $comment->content, 'viewlink' => "$plugin->viewlink");
        $plugincommented->fullmessage = get_string('commentnotifsubscriptionmessage', 'local_plugins', (object)$a);
        $plugincommented->fullmessage .= get_string('messageprovidersetting', 'local_plugins', get_string('messageprovider:comment', 'local_plugins'));

        message_send($plugincommented);
    }

    if ($plugin->approved != local_plugins_plugin::PLUGIN_APPROVED) {
        // Notify those who should be informed on an activity in unapproved plugins.
        $watchers = get_users_by_capability(context_system::instance(), local_plugins::CAP_NOTIFIEDUNAPPROVEDACTIVITY);
        foreach ($watchers as $watcher) {
            if ( $USER->id == $watcher->id) {
                continue;
            }
            $plugincommented = new core\message\message();
            $plugincommented->courseid = SITEID;
            $plugincommented->component = 'local_plugins';
            $plugincommented->name = local_plugins::NOTIFY_REGISTRATION;
            $plugincommented->userfrom = $USER;
            $plugincommented->userto = $watcher;
            $plugincommented->subject = get_string('commentnotifunapprovedsubject', 'local_plugins', $plugin->name);
            $a = array('fullname' => fullname($USER), 'message' => $comment->content, 'viewlink' => $plugin->viewlink->out());
            $plugincommented->fullmessage = get_string('commentnotifunapprovedmessage', 'local_plugins', (object)$a);
            $plugincommented->fullmessage .= get_string('messageprovidersetting', 'local_plugins', get_string('messageprovider:registration', 'local_plugins'));
            $plugincommented->fullmessageformat = FORMAT_PLAIN;
            $plugincommented->fullmessagehtml = '';
            $plugincommented->smallmessage = '';
            $plugincommented->notification = 1;
            $plugincommented->contexturl = $plugin->viewlink->out();
            $plugincommented->contexturlname = $plugin->formatted_name;
            message_send($plugincommented);
        }
    }
}

/**
 * Validates a submitted rating
 *
 * Required to validate incoming ratings for this plugin
 * Called from rating/lib.php
 *
 * This function avoids loading all of the local_plugins libraries as they arn't
 * needed where this function is called from
 *
 * @param array $params {
 *            context => object the context in which the rated items exists [required]
 *            component => The component the rating belongs to [required]
 *            ratingarea => The ratingarea the rating is associated with [required]
 *            itemid => int the ID of the object being rated
 *            scaleid => int the scale from which the user can select a rating. Used for bounds checking. [required]
 *            rating => int the submitted rating
 *            rateduserid => int the id of the user whose items have been rated. NOT the user who submitted the ratings. 0 to update all. [required]
 *            aggregation => int the aggregation method to apply when calculating grades ie RATING_AGGREGATE_AVERAGE [optional]
 * }
 */
function local_plugins_rating_validate(array $params) {
    global $DB;
    if (empty($params['context']->id) || $params['context']->id != SYSCONTEXTID) {
        // Has to be the system context obviously
        return false;
    }
    if ($params['component'] != 'local_plugins') {
        // Obviously has to be local_plugins
        return false;
    }
    if ($params['ratingarea'] != 'plugin_vers') {
        // Presently plugin_vers is the only rating area
        return false;
    }

    // Final check that the version actually exists.
    return $DB->record_exists('local_plugins_vers', array('id' => $params['itemid']));
}

/**
 * Custom variant of print_error(), typically for "plugin not found"
 *
 * @param string $title
 * @param string $message
 * @param int $httpstatus (400 - bad request, 403 - forbidden, 404 - not found, 500 internal error)
 */
function local_plugins_error($title=null, $message=null, $httpstatus=404) {
    global $PAGE;

    if ($title === null) {
        $title = get_string('exc_pluginnotfound', 'local_plugins');
    }

    if ($message === null) {
        $message = get_string('exc_cannotviewplugin', 'local_plugins');
    }

    $PAGE->set_url(new local_plugins_url('/local/plugins/index.php'));
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title($title);
    $PAGE->navbar->add($PAGE->title);
    $PAGE->set_pagelayout('base');

    http_response_code($httpstatus);

    $output = local_plugins_get_renderer();
    echo $output->error($message, $PAGE->url);
    die();
}

/**
 * Returns a form via fragment API to add a new descriptor
 *
 * @param array $args
 * @return string
 */
function local_plugins_output_fragment_descriptor_form(array $args) {
    global $DB;

    $form = new \local_plugins\form\descriptor(new local_plugins_url('/local/plugins/admin/descriptors.php'), $args);

    if (!empty($args['descid'])) {
        $record = $DB->get_record('local_plugins_desc', ['id' => $args['descid']], '*', MUST_EXISTS);
        $record->descid = $record->id;
        $form->set_data($record);
    }

    return html_writer::div($form->render(), 'mform-fragment mform-panel', ['style' => 'margin-bottom:2em']);
}

/**
 * Adds information about contributed plugins to the user profile pages.
 *
 * @param \core_user\output\myprofile\tree $tree Profile tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param stdClass $course
 *
 * @return bool
 */
function local_plugins_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $DB, $OUTPUT;

    // TODO: Following logic should be moved so that it is also available via
    // external function in the new UI.

    $params = ['userid' => $user->id];
    $sql = "SELECT p.id, p.name, p.frankenstyle,
                   c.maintainer,
                   l.filename AS logofilename, l.filepath AS logofilepath
              FROM {local_plugins_plugin} p
         LEFT JOIN {local_plugins_contributor} c ON (c.pluginid = p.id)
         LEFT JOIN {user} u ON c.userid = u.id
         LEFT JOIN {files} l ON (l.component='local_plugins' AND l.filearea='plugin_logo'
                   AND l.contextid = ".SYSCONTEXTID." AND l.itemid = p.id AND l.filename <> '.')
             WHERE p.approved = 1 AND u.id = :userid
          ORDER BY p.aggsites DESC, p.id DESC,
                   l.sortorder DESC, l.filepath, l.filename";

    $recordset = $DB->get_recordset_sql($sql, $params);
    $plugins = [];

    foreach ($recordset as $record) {
        if (!isset($plugins[$record->id])) {
            $plugins[$record->id] = (object)[
                'id' => $record->id,
                'name' => $record->name,
                'frankenstyle' => $record->frankenstyle,
                'maintainer' => $record->maintainer,
                'icon' => null,
                'url' => null,
            ];
        }

        $plugin = $plugins[$record->id];

        if ($record->logofilename !== null) {
            // Use the logo as the plugin icon.
            $logourl = local_plugins_url::make_pluginfile_url(SYSCONTEXTID, 'local_plugins', 'plugin_logo',
                $plugin->id, $record->logofilepath, $record->logofilename);
            $plugin->icon = (new local_plugins_url($logourl, ['preview' => 'tinyicon']))->out();

        } else {
            // Use a general placeholder.
            $iconurl = $OUTPUT->image_url('icon', 'local_plugins')->out();
            $plugin->icon = (new local_plugins_url($iconurl, ['preview' => 'tinyicon']))->out();
        }
    }

    $recordset->close();

    if (empty($plugins)) {
        return;
    }

    $maintainer = [];
    $contributor = [];

    foreach ($plugins as $plugin) {
        if ($plugin->frankenstyle) {
            $plugin->url = (new local_plugins_url('/local/plugins/'.$plugin->frankenstyle))->out();

        } else {
            $plugin->url = (new local_plugins_url('/local/plugins/view.php', ['id' => $plugin->id]))->out();
        }

        if ($plugin->maintainer == 1 or $plugin->maintainer == 2) {
            $maintainer[] = $plugin;

        } else {
            $contributor[] = $plugin;
        }
    }

    $category = new core_user\output\myprofile\category('moodleplugins',
        get_string('myprofilecattitle', 'local_plugins'), 'contact');
    $tree->add_category($category);

    $browselink = (new local_plugins_url('/local/plugins/browse.php', ['list' => 'contributor', 'id' => $user->id]))->out();
    $node = new core_user\output\myprofile\node('moodleplugins', 'browselink', get_string('myprofilebrowse', 'local_plugins'),
        null, $browselink, null, null, 'viewmore');
    $tree->add_node($node);

    if ($maintainer) {
        $list = '<div>';
        foreach ($maintainer as $plugin) {
            $list .= '<div style="display: inline-block; margin:0 0.5em;">
                <a href="'.$plugin->url.'">
                    <img height="12" width="12" src="'.$plugin->icon.'" alt="Icon">&nbsp;<span>'.s($plugin->name).'</span>
                </a>
            </div>';
        }
        $list .= '</div>';
        $node = new core_user\output\myprofile\node('moodleplugins', 'maintainedplugins',
            get_string('myprofilemaintainer', 'local_plugins'), null, null, $list);
        $tree->add_node($node);
    }

    if ($contributor) {
        $list = '<div>';
        foreach ($contributor as $plugin) {
            $list .= '<div style="display: inline-block; margin:0 0.5em;">
                <a href="'.$plugin->url.'">
                    <img height="12" width="12" src="'.$plugin->icon.'" alt="Icon">&nbsp;<span>'.s($plugin->name).'</span>
                </a>
            </div>';
        }
        $list .= '</div>';
        $node = new core_user\output\myprofile\node('moodleplugins', 'contributedplugins',
            get_string('myprofilecontributor', 'local_plugins'), null, null, $list);
        $tree->add_node($node);
    }
}

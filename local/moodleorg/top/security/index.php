<?php

/**
 * Display security announcements
 */

require(__DIR__.'/../../../../config.php');
require_once($CFG->dirroot .'/mod/forum/lib.php');

$PAGE->set_url(new moodle_url('/security/index.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('securitytitle', 'local_moodleorg'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

$pageno = optional_param('p', 0, PARAM_INT);
$PAGE->navbar->add($PAGE->heading);

$numarticles = 10;
$CFG->forum_longpost = 320000;

$vaultfactory = mod_forum\local\container::get_vault_factory();
$forumvault = $vaultfactory->get_forum_vault();
$forum = $forumvault->get_from_id(996);

$rendererfactory = mod_forum\local\container::get_renderer_factory();
$legacydatamapperfactory = mod_forum\local\container::get_legacy_data_mapper_factory();
$vaultfactory = mod_forum\local\container::get_vault_factory();
$discussionlistvault = $vaultfactory->get_discussions_in_forum_vault();

$discussionsrenderer = $rendererfactory->get_blog_discussion_list_renderer($forum);
$coursemodule = $forum->get_course_module_record();
$cm = \cm_info::create($coursemodule);
$groupid = groups_get_activity_group($cm, true) ?: null;

$datamapperfactory = mod_forum\local\container::get_legacy_data_mapper_factory();
$forumdatamapper = $datamapperfactory->get_forum_data_mapper();
$legacyforumrecord = $forumdatamapper->to_legacy_object($forum);

echo $OUTPUT->header();
echo $OUTPUT->heading($forum->get_name());
if (!empty($USER->id)) {
    forum_set_return();

    if (\mod_forum\subscriptions::is_subscribed($USER->id, $legacyforumrecord, null, $cm)) {
        $subtext = get_string('unsubscribe', 'forum');
    } else {
        $subtext = get_string('subscribe', 'forum');
    }

    $options['id'] = $forum->get_id();
    $options['sesskey'] = sesskey();
    $url = new moodle_url('/mod/forum/subscribe.php', $options);
    echo $OUTPUT->single_button($url, $subtext, 'get', array('title' => $subtext));
}

echo $discussionsrenderer->render($USER, $cm, $groupid, $discussionlistvault::SORTORDER_CREATED_DESC,
        $pageno, $numarticles);

echo $OUTPUT->footer();

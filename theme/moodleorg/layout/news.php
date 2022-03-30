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
 * The news layout for the moodleorg theme.
 *
 * @package   theme_moodleorg
 * @copyright 2019 Moodle
 * @author    Mathew May
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot .'/mod/forum/lib.php');
require($CFG->dirroot .'/theme/moodleorg/classes/output/news.php');

$bodyattributes = $OUTPUT->body_attributes();
$news = new \theme_moodleorg\output\news();
$renderer = $PAGE->get_renderer('theme_moodleorg');
if (! $mainforum = forum_get_course_forum($SITE->id, 'news')) {
    redirect('/error');
    die();
}

$numarticles = 10;
$CFG->forum_longpost = 320000;

$forum = forum_get_course_forum($SITE->id, 'news');

$coursemodule = get_coursemodule_from_instance('forum', $forum->id);
$context = context_module::instance($coursemodule->id);

$entityfactory = mod_forum\local\container::get_entity_factory();
$forumentity = $entityfactory->get_forum_from_stdclass($forum, $context, $coursemodule, $SITE);

$rendererfactory = mod_forum\local\container::get_renderer_factory();
$discussionsrenderer = $rendererfactory->get_frontpage_news_discussion_list_renderer($forumentity);
$cm = \cm_info::create($coursemodule);
$allnews = $news->export_for_template($renderer);
$newsdata = $allnews->feed[0];
$newsheading = $mainforum->name.'&nbsp;&nbsp;' . $OUTPUT->action_icon(new moodle_url($newsdata->rssurl),
                new pix_icon('i/rss', get_string('rss', 'core'), '', array('class' => 'iconmedium')));

$subscribebutton = null;
if (!empty($USER->id)) {
    forum_set_return();

    if (\mod_forum\subscriptions::is_subscribed($USER->id, $forum, null, $cm)) {
        $subtext = get_string('unsubscribe', 'forum');
    } else {
        $subtext = get_string('subscribe', 'forum');
    }

    $options['id'] = $forum->id;
    $options['sesskey'] = sesskey();
    $url = new moodle_url('/mod/forum/subscribe.php', $options);
    $subscribebutton = $OUTPUT->single_button($url, $subtext, 'get', array('title' => $subtext, 'class' => 'mb-2'));
}

$news =  $discussionsrenderer->render($USER, $cm, null, null, 0, $SITE->newsitems);
$feeditems = $allnews->feed[1]->newsitems;

$templatecontext = [
        'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
        'output' => $OUTPUT,
        'header' => $newsheading,
        'subscribe' => $subscribebutton,
        'news' => $news,
        'bodyattributes' => $bodyattributes,
        'haspostblocks' => true,
        'blocktitle' => $allnews->feed[1]->rsstitle,
        'feeditems' => $feeditems,
];

echo $OUTPUT->render_from_template('theme_moodleorg/layout_news', $templatecontext);

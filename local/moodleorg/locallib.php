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
 * Library for moodle.org.
 *
 * @package local_moodleorg
 * @copyright 2013 Dan Poltawski
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Picks the most appropriate course mapping for feeds the current user
 *
 * @todo use MUC instead of fetching mappings from the database over and over again
 * @param string $forcelang force the given language code instead of the detected one
 * @param int $forcepop try to pop off a number of dependency langs (we won't pop off the first 'en')
 * @return stdClass|null the course mapping or null of not found
 */
function local_moodleorg_get_mapping($forcelang = false, $forcepop = null) {
    global $SESSION, $DB;

    if ($forcelang) {
        // Language has been forced by params.
        $userlang = $forcelang;
    } else {
        // Get the users current lang.
        $userlang = isset($SESSION->lang) ? $SESSION->lang : 'en';
        if ($userlang === 'es_mx') { //hardcode mapping lookups for es_mx to es.
            $userlang = 'es';
        }
    }

    // We will default to English, unless a mapping is found.
    $lang = null;

    // Get the depdencies of the users lang and see if a mapping exists
    // for the current language or its parents..
    $langdeps = get_string_manager()->get_language_dependencies($userlang);

    // pop off some , we're probably searching for a higher lang (for posts/content there).
    if ($forcepop) {
        for ($x=0; $x<$forcepop; $x++) {
            array_pop($langdeps);
        }
    }

    // Prepend English to the start of the array as get_language_dependencies() goes
    // in least specific order first.
    array_unshift($langdeps, 'en');

    list($insql, $inparams) = $DB->get_in_or_equal($langdeps);
    $sql = "SELECT lang, courseid, scaleid FROM {moodleorg_useful_coursemap} WHERE lang $insql";
    $mappings = $DB->get_records_sql($sql, $inparams);

    $mapping = null;
    while (!empty($langdeps) and empty($mapping)) {
        $thislang = array_pop($langdeps);

        if (isset($mappings[$thislang])) {
            $mapping = $mappings[$thislang];
        }
    }

    return $mapping;
}

/**
 * Represents a frontpage block to display a feed of information (such as
 * site news, useful posts, events and resources).
 */
abstract class frontpage_column {

    /** The number of items to show on the front page */
    const MAXITEMS = 4;

    /** @var string the associated mapping record */
    protected $mapping = null;

    /**
     * Constructor.
     *
     * @param stdClass $mapping optional course mapping if needed
     */
    public function __construct($mapping = null) {
        $this->mapping = $mapping;
    }

    /**
     * Returns items for this column
     *
     * Optionally use cached items if they are available.
     *
     * @param boolean $usecache Whether to use Moodle cache or not
     *
     * @return array of items to be displayed
     */
    public function get($usecache = true) {

        if ($usecache) {
            if (debugging('', DEBUG_DEVELOPER)) {
                // Do not rely on cached structures in developer mode.
                $skipcache = true;
            }

            $cache = $this->get_cache();
            $key = $this->cache_key();

            // If we have a valid cache, use it.
            if (empty($skipcache) and ($content = $cache->get($key))) {
                $content->source = 'cache/' . $key;
                return $content;
            }
        }

        // Otherwise re-generate the contents.
        $content = $this->generate();
        if ($usecache) {
            $cache->set($key, $content);
            $content->source = 'fresh/'.$key;
        }

        return $content;
    }

    /**
     * Force the update of the content
     */
    public function update() {

        $content = $this->generate();
        $cache = $this->get_cache();
        $key = $this->cache_key();
        $cache->set($key, $content);
    }

    /**
     * Define the key to be used for storing this infromation in
     * the cache.
     * @return string the key
     */
    abstract protected function cache_key();

    /**
     * Generate the content to be displayed in this column.
     *
     * @return array of li items to be displayed/cached.
     */
    abstract protected function generate();

    /**
     * @return moodle_url|string|null URL to display more data or null if not available
     */
    abstract protected function more_url();

    /**
     * @return moodle_url|string|null URL to display info via RSS or null if not available
     */
    abstract protected function rss_url();

    /**
     * Returns the course object of this lang codes mapping
     *
     * @return stdClass course object from the database
     * @throws exception if mapping/course doesn't exist.
     */
    protected function get_mapped_course($mapping = null) {
        global $DB;

        if (is_null($mapping)) {
            $mapping = $DB->get_record('moodleorg_useful_coursemap', array('lang' => $this->mapping->lang), '*', MUST_EXIST);
        }

        $course = $DB->get_record('course', array('id' => $mapping->courseid), '*', MUST_EXIST);

        return $course;
    }

    /**
     * @return cache frontpagecolumn cache defined in local_moodleorg/db/caches.php
     */
    protected function get_cache() {
        return cache::make('local_moodleorg', 'frontpagecolumn');
    }

    /**
     * Get items from feed item
     * @param  string $url       The url of the feed
     * @param  int $itemcount The count of items
     * @param  string $prefix    Prefix the feed items title with this string.
     * @return array feed items
     */
    protected function get_feed_items($url, $itemcount, $prefix) {
        global $CFG;
        require_once($CFG->libdir.'/simplepie/moodle_simplepie.php');
        $feed = new moodle_simplepie();
        $feed->enable_order_by_date(true);
        $feed->set_feed_url($url);

        if (CLI_SCRIPT) {
            // Agressive timeout when in non-web environment!
            $feed->set_timeout(10);
            $feed->set_cache_duration(0);
        }
        $feed->init();

        $allitems = array();
        foreach ($feed->get_items(0, $itemcount) as $item) {
            $allitems[] = array(
                'title' => $prefix. $item->get_title(),
                'description' => $item->get_description(),
                'url' => $item->get_link(),
                'date' => (int)$item->get_date('U'),
            );
        }
        return $allitems;
    }
}

/**
 * Returns the news to be displayed from a RSS feed or the Site news
 *
 * If no RSS feed URL specified, returns the Site news (aka Announcements).
 */
class frontpage_column_news extends frontpage_column {
    /** @var string Feed title */
    private $rsstitle = null;

    /** @var string Feed URL */
    private $rssurl = null;

    /** @var string Feed origin URL */
    private $moreurl = null;

    /** @var string Feed origin anchor text */
    private $moreanchortext = null;

    /**
     * Constructor.
     *
     * If no params
     * @param string $rsstitle optional feed title
     * @param string $rssurl optional feed URL
     * @param string $moreurl optional more URL
     * @param string $moretext optional more anchor text
     */
    public function __construct($rsstitle = null, $rssurl = null, $moreurl = null, $moretext = null) {
        parent::__construct();
        $this->rsstitle = $rsstitle;
        $this->rssurl = $rssurl;
        $this->moreurl = $moreurl;
        $this->moreanchortext = $moretext;
    }

    protected function cache_key() {
        if (empty($this->rssurl)) {
            return 'news_' . current_language();
        } else {
            $host = str_replace(parse_url($this->rssurl, PHP_URL_HOST), '.', '');
            return 'news_' . $host . '_'. current_language();
        }
    }

    protected function generate() {
        global $CFG, $SITE;

        // Structure to be returned
        $data = (object)array(
            'timegenerated' => time(),
            'rsstitle' => $this->rss_title(),
            'rssurl' => (string) $this->rss_url(),
            'moreurl' => (string) $this->more_url(),
            'moreanchortext' => (string) $this->rss_anchortext(),
            'items' => array(),
        );

        /* If there is no external rss feed, get the site news */
        if (empty($this->rssurl)) {
            require_once($CFG->dirroot.'/mod/forum/lib.php');   // We'll need this

            if (!$forum = forum_get_course_forum($SITE->id, 'news')) {
                return $data;
            }

            $modinfo = get_fast_modinfo($SITE);
            if (empty($modinfo->instances['forum'][$forum->id])) {
                return $data;
            }
            $cm = $modinfo->instances['forum'][$forum->id];

            $posts = forum_get_discussions($cm, 'p.modified DESC', false, -1, self::MAXITEMS);

            $isfirstpost = true;
            foreach ($posts as $post) {
                //$url = new moodle_url('/mod/forum/discuss.php', array('d' => $post->discussion));
                $url = new moodle_url('/news/');
                if ($isfirstpost) {
                    $isfirstpost = false;
                } else {
                    $url->set_anchor('p' . $post->id);
                }
                $data->items[] = (object) array(
                    'title' => s($post->subject),
                    'date' => userdate($post->modified, get_string('strftimedaydate', 'core_langconfig')),
                    'url' => $url->out(),
                );
            }
        } else {
            // Get the RSS feed items.
            $entries = $this->get_feed_items($this->rssurl, 3, '');
            // Formatting the dates.
            foreach($entries as $key => $value) {
                $entries[$key]['date'] = userdate($value['date'], get_string('strftimedaydate', 'core_langconfig'));
            }
            $data->items = $entries;
        }

        return $data;
    }

    protected function more_url() {
        global $CFG, $SITE;
        if (empty($this->rssurl)) {
            require_once($CFG->dirroot . '/mod/forum/lib.php');

            if (!$forum = forum_get_course_forum($SITE->id, 'news')) {
                return '';
            }

            #return new moodle_url('/mod/forum/view.php', array('f' => $forum->id));
            return new moodle_url('/news/');
        } else {
            return $this->moreurl;
        }
    }

    protected function rss_url() {
        global $CFG, $SITE;
        if (empty($this->rssurl)) {
            require_once($CFG->dirroot . '/mod/forum/lib.php');
            require_once($CFG->dirroot . '/lib/rsslib.php');

            if (!$forum = forum_get_course_forum($SITE->id, 'news')) {
                return '';
            }

            $modinfo = get_fast_modinfo($SITE);
            $cm = $modinfo->instances['forum'][$forum->id];
            $context = context_module::instance($cm->id);
            $user = guest_user();

            return rss_get_url($context->id, $user->id, 'mod_forum', $forum->id);
        } else {
            return $this->rssurl;
        }
    }

    protected function rss_title() {
        if (empty($this->rssurl)) {
            $this->rsstitle = 'feed_news';
        }
        return get_string($this->rsstitle, 'local_moodleorg');
    }

    protected function rss_anchortext() {
        if (empty($this->rssurl)) {
            $this->moreanchortext = 'feed_news_more';
        }
        return get_string($this->moreanchortext, 'local_moodleorg');
    }
}


/**
 * Events
 */
class frontpage_column_events extends frontpage_column {

    protected function cache_key() {
        return 'events_'.current_language();
    }

    protected function generate() {
        global $CFG, $DB;
        require_once($CFG->dirroot.'/calendar/lib.php');

        $calendar = calendar_information::create(time(), SITEID);
        list($data, $template) = calendar_get_view($calendar, 'upcoming_mini');
        $events = $data->events;

        // Define the base url for calendar linking
        $course = $this->get_mapped_course();
        $baseurl = new moodle_url('/calendar/view.php', array('view' => 'day', 'course'=> $course->id));

        // Structure to be returned
        $data = (object)array(
            'timegenerated' => time(),
            'rssurl' => (string) $this->rss_url(),
            'moreurl' => (string) $this->more_url(),
            'items' => array(),
        );

        foreach (array_slice($events, 0, 4) as $event) {
            $ed = usergetdate($event->timestart);
            $linkurl = calendar_get_link_href($baseurl, $ed['mday'], $ed['mon'], $ed['year']);
            $data->items[] = (object) array(
                'title' => s($event->name),
                'url'=> (string) $linkurl,
                'date' => userdate($event->timestart, get_string('strftimedaydate', 'core_langconfig')),
            );
        }

        return $data;
    }

    protected function more_url() {
        return new moodle_url('calendar/view.php');
    }

    /**
     * We have no RSS feed here, provide iCal if possible
     */
    protected function rss_url() {
        global $CFG, $USER, $DB;

        if (isloggedin()) {
            $authtoken = sha1($USER->id . $DB->get_field('user', 'password', array('id'=>$USER->id)) . $CFG->calendar_exportsalt);
            $link = new moodle_url('/calendar/export_execute.php', array(
                'preset_what' => 'all',
                'preset_time' => 'recentupcoming',
                'userid' => $USER->id,
                'authtoken'=>$authtoken,
            ));
            return $link;

        } else {
            $authtoken = sha1('1' . $DB->get_field('user', 'password', array('id'=>1)) . $CFG->calendar_exportsalt);
            $link = new moodle_url('/calendar/export_execute.php', array(
                'preset_what' => 'all',
                'preset_time' => 'recentupcoming',
                'userid' => 1,
                'authtoken' => $authtoken,
            ));
            return $link;
        }
    }
}


/**
 * Useful posts
 */
class frontpage_column_useful extends frontpage_column {

    protected function cache_key() {
        return 'useful_'.current_language();
    }

    protected function generate() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/rating/lib.php');

        $course = $this->get_mapped_course();

        // Set up the ratings information that will be the same for all posts.
        $ratingoptions = new stdClass();
        $ratingoptions->component = 'mod_forum';
        $ratingoptions->ratingarea = 'post';
        $ratingoptions->userid = $CFG->siteguest;
        $rm = new rating_manager();

        $rs = $this->getposts($course);

        $rsscontent = '';
        $fullcontents = '';
        $frontcontent = array();
        $frontpagecount = 0;

        $rsscontent.= $this->rss_header();

        if (!empty($rs)) {
            foreach ($rs as $post) {
                 //function prints also which we capture via buffer
                list($frontcontentbit, $rsscontentbit, $fullcontentbit) = $this->processprintpost($post, $course, $rm, $ratingoptions);
                $rsscontent .= $rsscontentbit;
                if ($frontpagecount < self::MAXITEMS) {
                    $frontcontent[] = $frontcontentbit;
                    $frontpagecount++;
                }
                $fullcontents .= $fullcontentbit;
            }
            $rs->close();
        }

        // check number of posts, get more if not enough from other mappings.
        // no loop,just one look towards 'parent' langs for now
        if ($frontpagecount < self::MAXITEMS && $this->mapping->lang !== 'en') {
            $moremapping = local_moodleorg_get_mapping(false, 1);
            $anothercourse = $this->get_mapped_course($moremapping);
            $rs = $this->getposts($anothercourse);

            if (!empty($rs)) {
                foreach ($rs as $post) {
                     //function prints also which we capture via buffer
                    list($frontcontentbit, $rsscontentbit, $fullcontentbit) = $this->processprintpost($post, $anothercourse, $rm, $ratingoptions);
                     $rsscontent .= $rsscontentbit; //lets keep the content same for sanity.
                    if ($frontpagecount < self::MAXITEMS) {
                        $frontcontent[] = $frontcontentbit;
                        $frontpagecount++;
                    }
                    $fullcontents .= $fullcontentbit;
                }
                $rs->close();
            }
        }

        $rsscontent.= $this->rss_footer();
        $cache = $this->get_cache();
        $cache->set('useful_full_'.$this->mapping->lang, $fullcontents);
        $cache->set('rss_'.$this->mapping->lang, $rsscontent);

        return (object)array(
            'timegenerated' => time(),
            'rssurl' => (string) $this->rss_url(),
            'moreurl' => (string) $this->more_url(),
            'items' => $frontcontent,
        );
    }

    protected function getposts($course) {
        global $DB;
        $ctxselect = ', ' . context_helper::get_preload_record_columns_sql('ctx');
        $ctxjoin = "LEFT JOIN {context} ctx ON (ctx.instanceid = cm.id AND ctx.contextlevel = :contextlevel)";
        $userselect = \core_user\fields::for_userpic()->get_sql('u', false, '', 'uid', false)->selects;

        $params = array();
        $params['courseid'] = $course->id;
        $params['since'] = time() - (DAYSECS * 30);
        $params['cmtype'] = 'forum';
        $params['contextlevel'] = CONTEXT_MODULE;

        $noscalesql = "SELECT fp.*, fd.forum $ctxselect, $userselect
                FROM {forum_posts} fp
                JOIN {user} u ON u.id = fp.userid
                JOIN {forum_discussions} fd ON fd.id = fp.discussion
                JOIN {course_modules} cm ON (cm.course = fd.course AND cm.instance = fd.forum)
                JOIN {modules} m ON (cm.module = m.id)
                $ctxjoin
                WHERE fd.course = :courseid
                AND m.name = :cmtype
                AND fp.created > :since
                ORDER BY fp.created DESC";

        if (!empty($this->mapping->scaleid)) {
            // Check some forums with the scale exist..
            $negativescaleid = $this->mapping->scaleid * -1;
            $forumids = $DB->get_records('forum', array('course'=>$course->id, 'scale'=>$negativescaleid), '', 'id');
            if (empty($forumids)) {
                $sql = $noscalesql;
            } else {
                $params['scaleid'] = $negativescaleid;
                $sql = "SELECT fp.*, fd.forum $ctxselect, $userselect
                    FROM {forum_posts} fp
                    JOIN {user} u ON u.id = fp.userid
                    JOIN {forum_discussions} fd ON fd.id = fp.discussion
                    JOIN {course_modules} cm ON (cm.course = fd.course AND cm.instance = fd.forum)
                    JOIN {modules} m ON (cm.module = m.id)
                    $ctxjoin
                    JOIN {rating} r ON (r.contextid = ctx.id AND fp.id = r.itemid AND r.scaleid = :scaleid)
                    WHERE fd.course = :courseid
                    AND m.name = :cmtype
                    AND r.timecreated > :since
                    GROUP BY fp.id, fd.forum, ctx.id, u.id
                    ORDER BY MAX(r.timecreated) DESC";
            }
        } else {
            $sql = $noscalesql;
        }

        return $DB->get_recordset_sql($sql, $params, 0, 30);
    }

    protected function processprintpost($post, $course, $rm, $ratingoptions) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/mod/forum/lib.php');

        $discussions = array();
        $forums = array();
        $cms = array();
        $rsscontent = '';
        context_helper::preload_from_record($post);

        if (!array_key_exists($post->discussion, $discussions)) {
            $discussions[$post->discussion] = $DB->get_record('forum_discussions', array('id'=>$post->discussion));
            if (!array_key_exists($post->forum, $forums)) {
                $forums[$post->forum] = $DB->get_record('forum', array('id'=>$post->forum));
                $cms[$post->forum] = get_coursemodule_from_instance('forum', $post->forum, $course->id);
            }
        }

        $discussion = $discussions[$post->discussion];
        $forum = $forums[$post->forum];
        $cm = $cms[$post->forum];

        $forumlink = new moodle_url('/mod/forum/view.php', array('f'=>$post->forum));
        $discussionlink = new moodle_url('/mod/forum/discuss.php', array('d'=>$post->discussion));
        $postlink = clone $discussionlink;
        $postlink->set_anchor('p'.$post->id);

        // First do the rss file
        $rsscontent.= html_writer::start_tag('item')."\n";
        $rsscontent.= html_writer::tag('title', s($post->subject))."\n";
        $rsscontent.= html_writer::tag('link', $postlink->out())."\n";
        $rsscontent.= html_writer::tag('pubDate', gmdate('D, d M Y H:i:s',$post->modified).' GMT')."\n";
        $post->message = file_rewrite_pluginfile_urls($post->message, 'pluginfile.php', $cm->id, 'mod_forum', 'post', $post->id);
        $rsscontent.= html_writer::tag('description', 'by '.htmlspecialchars(fullname($post).' <br /><br />'.format_text($post->message, $post->messageformat)))."\n";
        $rsscontent.= html_writer::tag('guid', $postlink->out(), array('isPermaLink'=>'true'))."\n";
        $rsscontent.= html_writer::end_tag('item')."\n";

        $postuser = new stdClass();
        $postuser->id        = $post->userid;
        $postuser->firstname = $post->firstname;
        $postuser->lastname  = $post->lastname;
        $postuser->imagealt  = $post->imagealt;
        $postuser->picture   = $post->picture;
        $postuser->email     = $post->email;
        foreach (\core_user\fields::for_name()->get_required_fields() as $addname) {
            $postuser->$addname = $post->$addname;
        }

        $link = new moodle_url('/mod/forum/discuss.php', array('d'=>$post->discussion));
        $link->set_anchor('p'.$post->id);

        $frontcontentbit = new stdClass();
        $frontcontentbit->courseid = $course->id;
        $frontcontentbit->user = $postuser;
        $frontcontentbit->url = (string)$link;
        $frontcontentbit->title = s($post->subject);
        $frontcontentbit->date = userdate($post->modified, get_string('strftimedaydate', 'core_langconfig'));

        // Output normal posts
        $fullsubject = html_writer::link($forumlink, format_string($forum->name,true));
        if ($forum->type != 'single') {
            $fullsubject .= ' -> '.html_writer::link($discussionlink->out(false), format_string($post->subject,true));
            if ($post->parent != 0) {
                $fullsubject .= ' -> '.html_writer::link($postlink->out(false), format_string($post->subject,true));
            }
        }
        $post->subject = $fullsubject;
        $fulllink = html_writer::link($postlink, get_string("postincontext", "forum"));

        ob_start();
        echo "<br /><br />";
        //add the ratings information to the post
        //Unfortunately seem to have do this individually as posts may be from different forums
        if ($forum->assessed != RATING_AGGREGATE_NONE) {
            $modcontext = context_module::instance($cm->id, MUST_EXIST);
            $ratingoptions->context = $modcontext;
            $ratingoptions->items = array($post);
            $ratingoptions->aggregate = $forum->assessed;//the aggregation method
            $ratingoptions->scaleid = $forum->scale;
            $ratingoptions->assesstimestart = $forum->assesstimestart;
            $ratingoptions->assesstimefinish = $forum->assesstimefinish;
            $postswithratings = $rm->get_ratings($ratingoptions);

            if ($postswithratings && count($postswithratings)==1) {
                $post = $postswithratings[0];
            }
        }
        // the actual reason for buffer follows
        forum_print_post($post, $discussion, $forum, $cm, $course, false, false, false, $fulllink);

        $fullcontentbit = ob_get_contents();
        ob_end_clean();
        return array($frontcontentbit, $rsscontent, $fullcontentbit);
    }

    public function get_rss() {
        $cache = $this->get_cache();
        $key = 'rss_'.$this->mapping->lang;
        if ($content = $cache->get($key)) {
            return $content;
        }

        $this->generate();
        if (!$content = $cache->get($key)) {
            throw new moodle_exception('cant get content');
        }

        return $content;
    }

    public function get_full_content() {
        $cache = $this->get_cache();
        $key = 'useful_full_'.$this->mapping->lang;
        if ($content = $cache->get($key)) {
            return $content;
        }

        $this->generate();
        if (!$content = $cache->get($key)) {
            throw new moodle_exception('cant get content');
        }

        return $content;
    }

    protected function more_url() {
        return new moodle_url('/course/view.php', array('id' => $this->mapping->courseid));
    }

    protected function rss_url() {
        return new moodle_url('/useful/rss.php', array('lang' => $this->mapping->lang));
    }

    private function rss_header() {
        $title = get_string('rsstitle', 'local_moodleorg');
        $description = get_string('rssdescription', 'local_moodleorg');
        $year = date("Y");

       return <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>$title</title>
    <link>http://moodle.org/useful/</link>
    <description>$description</description>
    <generator>Moodle</generator>
    <copyright>&amp;#169; $year Moodle.org</copyright>
    <image>
      <url>http://moodle.org/pix/i/rsssitelogo.gif</url>
      <title>moodle</title>
      <link>http://moodle.org</link>
      <width>140</width>
      <height>35</height>
    </image>
EOF;
    }
    private function rss_footer() {
        return "</channel>\n</rss>";
    }
}

/**
 * Resources
 */
class frontpage_column_resources extends frontpage_column {

    const PLUGINSURL = 'http://moodle.org/rss/file.php/50/63fc7bdfa4d5de1917667df452419de5/local_plugins/recent_plugins/0/rss.xml';
    const JOBSURL = 'https://moodle.org/rss/file.php/288759/1b51bf7f3cab9689af042af1ff4a07f0/mod_data/54/rss.xml';
    const COURSESURL = 'https://moodle.net/rss/file.php/2/1bc026107958a191ddf471c5e78e1d0a/local_hub/all/all/all/all/all/all/0/newest/rss.xml';
    const BUZZURL = 'http://moodle.org/rss/file.php/109/1b51bf7f3cab9689af042af1ff4a07f0/mod_data/19/rss.xml?foo';

    protected function cache_key() {
        return 'resources_'. current_language();
    }

    protected function get_resource_content() {

        $plugins = $this->get_feed_items(self::PLUGINSURL, 2, 'Plugins: ');
        $buzz = $this->get_feed_items(self::BUZZURL, 1, 'Buzz: ');
        $jobs = $this->get_feed_items(self::JOBSURL, 3, 'Jobs: ');
        $courses = $this->get_feed_items(self::COURSESURL, 2, 'Courses: ');

        $allcontent = array_merge($plugins, $buzz, $jobs, $courses);

        usort($allcontent, function($a, $b) {
            return ($a['date'] < $b['date']);
        });

        return $allcontent;
    }

    protected function generate() {
        // Structure to be returned
        $data = (object)array(
            'timegenerated' => time(),
            'rssurl' => (string) $this->rss_url(),
            'moreurl' => (string) $this->more_url(),
            'items' => array(),
        );

        $content = $this->get_resource_content();
        $this->update_rss_feed($content);

        foreach (array_slice($content, 0, self::MAXITEMS) as $item) {
            $title = s($item['title']);
            if (preg_match('/^Plugins: /', $title)) {
                $imagename = 'icon';
                $imagecomponent = 'mod_lti';
                $imagealt = 'Plugins';

            } else if (preg_match('/^Jobs: /', $title)) {
                $imagename = 'icon';
                $imagecomponent = 'mod_feedback';
                $imagealt = 'Jobs';

            } else if (preg_match('/^Courses: /', $title)) {
                $imagename = 'icon';
                $imagecomponent = 'mod_imscp';
                $imagealt = 'Courses';

            } else {
                $imagename = 'icon';
                $imagecomponent = 'mod_label';
                $imagealt = 'Buzz';
            }

            $data->items[] = (object) array(
                'title' => $title,
                'url' => (string) new moodle_url($item['url']),
                'date' => userdate($item['date'], get_string('strftimedaydate', 'core_langconfig')),
                'image' =>  (object) array(
                    'name' => $imagename,
                    'component' => $imagecomponent,
                    'alt' => $imagealt,
                )
            );
        }

        return $data;
    }

    protected function more_url() {
        return null;
    }

    protected function rss_url() {
        return new moodle_url('/resources/rss.php', array('lang' => $this->mapping->lang));
    }

    /**
     * Update the rss feed and store in cache.
     * @param  array $content [description]
     */
    protected function update_rss_feed($content) {
        $year = date("Y");

        $rsscontent = <<<EOF
<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0">
  <channel>
    <title>Moodle.org: Recent resources</title>
    <link>https://moodle.org/</link>
    <description>Recent resources from moodle.org</description>
    <generator>Moodle</generator>
    <copyright>&amp;#169; $year Moodle.org</copyright>
    <image>
      <url>https://moodle.org/pix/i/rsssitelogo.gif</url>
      <title>moodle</title>
      <link>http://moodle.org</link>
      <width>140</width>
      <height>35</height>
    </image>
EOF;

        foreach ($content as $item) {
            $url = new moodle_url($item['url']);
            $rsscontent .= html_writer::start_tag('item')."\n";
            $rsscontent .= html_writer::tag('title', s($item['title']))."\n";
            $rsscontent .= html_writer::tag('link', $url->out(true))."\n";
            $rsscontent .= html_writer::tag('pubDate', gmdate('D, d M Y H:i:s', $item['date']).' GMT')."\n";
            $rsscontent .= html_writer::tag('description', s($item['description']))."\n";
            $rsscontent .= html_writer::tag('guid', $url->out(true), array('isPermaLink' => 'true'))."\n";
            $rsscontent .= html_writer::end_tag('item')."\n";
        }

        $rsscontent.= "</channel>\n</rss>";

        $cache = $this->get_cache();
        $key = 'rss_'.$this->cache_key();
        $cache->set($key, $rsscontent);
    }

    /**
     * Get the RSS feed content
     * @return string content of rss feed.
     */
    public function get_rss() {
        $cache = $this->get_cache();
        $key = 'rss_'.$this->cache_key();
        if ($content = $cache->get($key)) {
            return $content;
        }

        $this->generate();
        if (!$content = $cache->get($key)) {
            throw new moodle_exception('cant get content');
        }

        return $content;
    }
}


/**
 * Helper class to update the list of the PHM cohort members
 *
 * 1. Make an instance of this manager
 * 2. Call add_member() for every user to add/confirm
 * 3. Call remove_old_users() to prune existing members not added in 2.
 * 4. Call award_badge() to automatically award the PHM badge to all existing members.
 */
class local_moodleorg_phm_cohort_manager {

    /** @var object cohort object from cohort table */
    private $cohort;
    /** @var array of cohort members indexed by userid */
    private $existingusers;
    /** @var array of cohort members indexed by userid */
    private $currentusers;

    /**
     * Creates a cohort for identifier if it doesn't exist
     *
     * @param string $identifier identifier of cohort uniquely identifiying cohorts between dev plugin generated cohorts
     */
    public function __construct() {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/cohort/lib.php');

        $cohort = new stdClass;
        $cohort->idnumber = 'local_moodleorg:particularly-helpful-moodlers';
        $cohort->component = 'local_moodleorg';

        if ($existingcohort = $DB->get_record('cohort', (array) $cohort)) {
            $this->cohort = $existingcohort;
            // populate cohort members array based on existing members
            $this->existingusers = $DB->get_records('cohort_members', array('cohortid' => $this->cohort->id), 'userid', 'userid');
            $this->currentusers = array();
        } else {
            $cohort->contextid = context_system::instance()->id;
            $cohort->name = 'Particularly helpful moodlers';
            $cohort->description = 'Automatically generated cohort from particularly helpful moodler scripts.';
            $cohort->id = cohort_add_cohort($cohort);

            $this->cohort = $cohort;
            // no existing members as we've just created cohort
            $this->existingusers = array();
            $this->currentusers = array();
        }
    }

    /**
     * Add a member to the cohort keeps track of members who have been added.
     *
     * @param int $userid id from user table of user
     * @return bool true if member is a new member of cohort
     */
    public function add_member($userid) {
        if (!isset($this->existingusers[$userid]) and !isset($this->currentusers[$userid])) {
            cohort_add_member($this->cohort->id, $userid);
        }

        if (isset($this->existingusers[$userid])) {
            $isnewmember = false;
        } else {
            $isnewmember = true;
        }

        $this->currentusers[$userid] = $userid;

        return $isnewmember;
    }

    /**
     * Returns the userids who have been added to the cohort since the manager was created
     *
     * @return array array of new members indexed by userid
     */
    public function new_users() {
        return array_diff_key($this->currentusers, $this->existingusers);
    }

    /**
     * Returns the usersids who have not been added to the cohort since this manager was created
     *
     * @param array array of removed users indexed by userid
     */
    public function old_users() {
        return array_diff_key($this->existingusers, $this->currentusers);
    }

    /**
     * Returns the cohort record
     *
     * @param stdClass cohort record
     */
    public function cohort() {
        return $this->cohort;
    }

    /**
     * Returns the current users of the cohort
     *
     * @param array array of removed users indexed by userid
     */
    public function current_users() {
        return $this->currentusers;
    }

    public function remove_old_users() {
        $userids = $this->old_users();

        foreach($userids as $userid => $value) {
            cohort_remove_member($this->cohort->id, $userid);
            unset($this->existingusers[$userid]);
        }

        return $userids;
    }

    /**
     * Awards the given badge to all current members of the cohort.
     *
     * If the cohort member already has the badge awarded, nothing happens for
     * them. The list of newly awarded users is returned.
     *
     * @param int $badgeid
     * @return array of (int)userid
     */
    public function award_badge($badgeid) {
        global $CFG, $DB;
        require_once($CFG->libdir.'/badgeslib.php');
        require_once($CFG->dirroot.'/badges/lib/awardlib.php');

        $badge = new badge($badgeid);
        $awarded = $badge->get_awards();
        $newlyawarded = array();

        foreach ($this->currentusers as $currentuserid) {
            if (!isset($awarded[$currentuserid])) {
                $newlyawarded[$currentuserid] = $currentuserid;

                // This is a little hack, we do not actually check and store
                // the information about the manual awarding via
                // process_manual_award() and badges_award_handle_manual_criteria_review().
                // We just cheat here and mark that criteria as complete and
                // issue the badge.
                $badge->criteria[BADGE_CRITERIA_TYPE_MANUAL]->mark_complete($currentuserid);
                $badge->issue($currentuserid);
                mtrace(sprintf(" ... awarded the badge to the user %d", $currentuserid));
            }
        }

        return $newlyawarded;
    }
}

/**
 * Works out the particularly helpful moodlers across the whole site and returns
 * metadata about the PHMs.
 *
 * Supported options:
 *
 * bool verbose - produce debugging information via {@link mtrace()}
 * int minratings - the minimum number of ratings to be counted as a PHM
 * int minraters - the minimum number of raters
 * float minratio - the ratio of posts to 'useful' ratings to be coutned as phm.
 * int recentposttime - phm must have posted something somewhere after this timestamp
 * int recentratingtime - phm must have had something rated useful after this timestamp
 * bool ignorefirstpost - dont count first post in ratings
 *
 * The returned array is the list of PHMs candidates, indexed by userid. For
 * each PHM, array with following keys is returned:
 *
 * userid, firstname, lastname, totalratings, postcount, raters, ratio
 *
 * @param array $options parameters for criteria for granting the PHM status
 * @return array of phms indexed by userid
 */
function local_moodleorg_get_phms(array $options = array()) {
    global $DB;

    $verbose = isset($options['verbose']) ? $options['verbose'] : true;
    $minratings = isset($options['minratings']) ? $options['minratings'] : 14;
    $minraters = isset($options['minraters']) ? $options['minraters'] : 8;
    $minratio = isset($options['minratio']) ? $options['minratio'] : 0.02;
    $recentposttime = isset($options['recentposttime']) ? $options['recentposttime'] : time() - 60 * DAYSECS;
    $recentratingtime = isset($options['recentratingtime']) ? $options['recentratingtime'] : time() - (YEARSECS / 2);
    $ignorefirstpost = isset($options['ignorefirstpost']) ? $options['ignorefirstpost'] : true;

    if ($verbose) {
        mtrace('Searching for PHM candidate users ...');
    }

    $forummodid = $DB->get_field('modules', 'id', array('name' => 'forum'));

    $firstpostcondition = '';
    if ($ignorefirstpost) {
        $firstpostcondition = "AND fp.parent != 0";
    }

    $innersql = " FROM {forum_posts} fp
                  JOIN {forum_discussions} fd ON fp.discussion = fd.id
                  JOIN {course_modules} cm ON cm.instance = fd.forum
                  JOIN {context} ctx ON ctx.instanceid = cm.id
                  JOIN {rating} r ON r.contextid = ctx.id
                  JOIN {moodleorg_useful_coursemap} m ON -r.scaleid = m.scaleid
                  JOIN {user} u ON fp.userid = u.id
                  WHERE cm.module = :forummodid
                  AND ctx.contextlevel = :contextlevel AND r.component = :component
                  AND r.ratingarea = :ratingarea AND r.itemid = fp.id
                  AND u.deleted = 0 $firstpostcondition
                  ";


    $params = array('forummodid'    => $forummodid,
                     'contextlevel' => CONTEXT_MODULE,
                     'component'    => 'mod_forum',
                     'ratingarea'   => 'post',
                    );


    $raterssql = "SELECT fp.userid, u.firstname, u.lastname, COUNT(r.id) AS ratingscount, MAX(r.timecreated) AS lastratingtime
                    $innersql
                  GROUP BY fp.userid, u.firstname, u.lastname";

    $phms = array();
    $rs = $DB->get_recordset_sql($raterssql, $params);
    foreach($rs as $record) {

        $verbose and mtrace(sprintf('Processing user %d %s %s', $record->userid, $record->firstname, $record->lastname), ' ... ');

        if ($record->ratingscount < $minratings) {
            $verbose and mtrace(' not enough ratings ('.$record->ratingscount.' / '.$minratings.')');
            continue;
        }

        $countsql = "SELECT COUNT(DISTINCT(r.userid)) $innersql AND fp.userid = :userid";
        $countparms = array_merge($params, array('userid' => $record->userid));
        $raterscount = $DB->count_records_sql($countsql, $countparms);

        if ($raterscount < $minraters) {
            $verbose and mtrace(' not enough raters ('.$raterscount.' / '.$minraters.')');
            continue;
        }

        if ($ignorefirstpost) {
            $totalpostselect = "userid = :userid AND parent != 0";
        } else {
            $totalpostselect = "userid = :userid";
        }

        $totalpostcount = $DB->count_records_select('forum_posts', $totalpostselect, array('userid' => $record->userid));

        $ratio = round($record->ratingscount / $totalpostcount, 3);

        if ($ratio < $minratio) {
            $verbose and mtrace(' not enough ratio ('.$ratio.' / '.$minratio.')');
            continue;
        }

        if ($record->lastratingtime < $recentratingtime) {
            $verbose and mtrace(' no ratings in X days');
            continue;
        }

        $recentpostcount = $DB->count_records_select('forum_posts', "userid = :userid AND created > :recentposttime",
            array('userid' => $record->userid, 'recentposttime' => $recentposttime));

        if ($recentpostcount < 1) {
            $verbose and mtrace(' no post in last 60 days');
            continue;
        }

        $phms[$record->userid] = array(
            'userid' => $record->userid,
            'lastname' => $record->lastname,
            'firstname' => $record->firstname,
            'totalpostcount' => $totalpostcount,
            'recentpostcount' => $recentpostcount,
            'ratingscount' => $record->ratingscount,
            'raterscount' => $raterscount,
            'ratio' => $ratio,
        );

        $verbose and mtrace(' looking good');
    }
    $rs->close();

    return $phms;
}

/**
 * Send e-mail notification about the PHM cohort update
 *
 * At the moment, this sends the e-mail to a list of hard-coded people only.
 * In the future, this may be improved so that we take recipients from our own
 * mapping table and e-mail them info about the PHMs who are also enrolled in
 * some of their course.
 *
 * @param array $phms as returned by {@link local_moodleorg_get_phms()}
 * @param array $newmembers indexed by userid
 * @param array $removedmembers indexed by userid
 */
function local_moodleorg_notify_phm_cohort_status(array $phms, array $newmembers, array $removedmembers) {
    global $CFG, $DB;

    if (empty($phms)) {
        // This is weird and should not happen. Consider raising an alarm here.
        return;
    }

    if (empty($newmembers) and empty($removedmembers)) {
        // Nothing has changed in the cohort, no need to report anything.
        return;
    }

    $message = "The PHM cohort at moodle.org has been updated:\n";
    $message .= sprintf(" %d member(s) added\n", count($newmembers));
    $message .= sprintf(" %d member(s) removed\n", count($removedmembers));

    if (!empty($newmembers)) {
        $message .= "\nNewly added PHM cohort members:\n";
        foreach ($newmembers as $newmemberid => $unused) {
            $message .= sprintf("* %s %s (https://moodle.org/user/profile.php?id=%d)\n",
                $phms[$newmemberid]['firstname'],
                $phms[$newmemberid]['lastname'],
                $phms[$newmemberid]['userid']);
        }
    }

    if (!empty($removedmembers)) {
        list($subsql, $params) = $DB->get_in_or_equal(array_keys($removedmembers));
        $sql = "SELECT id,firstname,lastname
                  FROM {user}
                 WHERE id $subsql";
        $names = $DB->get_records_sql($sql, $params);
        $message .= "\nRemoved cohort members:\n";
        foreach ($removedmembers as $removedmemberid => $unused) {
            $message .= sprintf("* %s %s (https://moodle.org/user/profile.php?id=%d)\n",
                $names[$removedmemberid]->firstname,
                $names[$removedmemberid]->lastname,
                $names[$removedmemberid]->id);
        }
    }

    $message .= "\nSee the attached file for more details.\n";
    $vars = array_keys(reset($phms));

    // $report will hold CSV formatted per RFC 4180
    $report = implode(';', $vars);
    $report .= ";status\r\n";

    foreach ($phms as $phm) {
        $line = array();
        foreach ($vars as $var) {
            $line[] = '"'.$phm[$var].'"';
        }
        if (isset($newmembers[$phm['userid']])) {
            $line[] = '"NEW"';
        } else {
            $line[] = '""';
        }
        $line = implode(';', $line);
        $report .= $line."\r\n";
    }

    $attachment = tempnam($CFG->dataroot, 'tmp_phm_report_');
    file_put_contents($attachment, $report);

    $subject = '[moodle.org] Particularly helpful Moodlers';
    $supportuser = core_user::get_support_user();

    $helen = $DB->get_record('user', array('id' => 24152), '*', MUST_EXIST);
    email_to_user($helen, $supportuser, $subject, $message, '', basename($attachment), 'phm_report.csv', false);

    $david = $DB->get_record('user', array('id' => 1601), '*', MUST_EXIST);
    email_to_user($david, $supportuser, $subject, $message, '', basename($attachment), 'phm_report.csv', false);

    unlink($attachment);
}

/**
 * Gets statistics from moodle.net via webservice and inserts into registry table.
 * @return $sites data for insertion into registry table.
 */
function local_moodleorg_get_moodlenet_stats($token, $moodleneturl, $fromid=0, $modifiedafter=0, $numrecs=50) {
    global $CFG;

    $functionname = 'hub_get_sitesregister';
    $restformat = 'json';
    $params = array ('fromid' => $fromid, 'numrecs' => $numrecs, 'modifiedafter' => $modifiedafter);

    /// REST CALL
    $serverurl = $moodleneturl . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction='.$functionname;
    $curl = new curl;
    //if rest format == 'xml', then we do not add the param for backward compatibility with Moodle < 2.2
    $restformat = ($restformat == 'json')?'&moodlewsrestformat=' . $restformat:'';
    $resp = $curl->post($serverurl . $restformat, $params);
    $sites = json_decode($resp);
    return $sites;
}

/**
 *
 */
function local_moodleorg_send_moodlenet_stats_19_sites($token, $moodleneturl, $newdatasince) {
    global $CFG;

    $functionname = 'hub_sync_into_sitesregister';
    $restformat = 'json';
    $params = array ('newdatasince' => $newdatasince);

    /// REST CALL
    $serverurl = $moodleneturl . '/webservice/rest/server.php'. '?wstoken=' . $token . '&wsfunction='.$functionname;
    mtrace('using ws-samples curl class (not moodle core\'s). '. $CFG->dirroot);
    require_once($CFG->dirroot. '/local/moodleorg/curlws.php');
    $curl = new curlws;
    $restformat = ($restformat == 'json')?'&moodlewsrestformat=' . $restformat:'';
    $resp = $curl->post($serverurl . $restformat, $params);
    $resp = json_decode($resp);
    return $resp;
}

<?php

/**
 * This file defines the plugin comment RSS feed
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

// No direct access to this script
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

class local_plugins_rss_plugin_comments_feed extends local_plugins_rss_feed {
    /**
     *
     * @var local_plugins_plugin
     */
    protected $plugin;
    
    protected $comments;

    public function __construct($id, $args) {
        parent::__construct($id, $args);
        $this->plugin = local_plugins_helper::get_plugin($id);
    }
    protected function get_title() {
        return get_string('pluginname', 'local_plugins'). ': '. $this->plugin->formatted_name. ': '. get_string('comments');
    }
    protected function get_description() {
        return $this->plugin->formatted_shortdescription;
    }
    protected function get_link() {
        $this->plugin->viewlink;
    }
    protected function get_name() {
        return 'plugin_comments';
    }
    public function user_can_view() {
        return $this->plugin->can_view();
    }
    protected function get_items() {
        if (empty($this->comments)) {
            $this->comments = local_plugins_helper::comment_for_plugin($this->plugin->id)->get_comments();
        }
        if (empty($this->comments)) {
            $title = get_string('nocomments', 'local_plugins');
            $author = $this->plugin->maintained_by;
            $pubdate = $this->plugin->timelastmodified;
            $link = $this->plugin->viewlink;
            $description = get_string('nocomments_desc', 'local_plugins');
            return array(new local_plugins_rss_item($title, $author, $pubdate, $link, $description));
        }
        $items = array();
        $link = $this->plugin->viewlink;
        foreach ($this->comments as $comment) {
            $title = userdate($comment->timecreated).' - '.$comment->fullname;
            $author = $comment->fullname;
            $pubdate = $comment->timecreated;
            $description = $comment->content;
            $items[] = new local_plugins_rss_item($title, $author, $pubdate, $link.'#comment-'. $comment->id, $description);
        }
        return $items;
    }
    protected function get_most_recent_item_timestamp() {
        if (empty($this->comments)) {
            $this->comments = local_plugins_helper::comment_for_plugin($this->plugin->id)->get_comments();
        }
        if (!empty($this->comments)) {
            $first = reset($this->comments);
            return $first->timecreated;
        }
        return 0;
    }
}

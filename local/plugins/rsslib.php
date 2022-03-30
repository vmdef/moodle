<?php

/**
 * This file contains the renderers used by the local_plugins plugin.
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

function plugins_rss_get_feed(stdClass $context, array $args) {
    global $CFG;
    
    if ($context->id != SYSCONTEXTID) {
        // Must be the system context
        return null;
    }
    
    $contextid  = clean_param($args[0], PARAM_INT);
    $token      = clean_param($args[1], PARAM_ALPHANUM);
    $component  = clean_param($args[2], PARAM_FILE);
    $name       = clean_param($args[3], PARAM_SAFEDIR);
    $id         = clean_param($args[4], PARAM_INT);
    
    $path = $CFG->dirroot.'/local/plugins/rss/'.$name.'/lib.php';
    $class = 'local_plugins_rss_'.$name.'_feed';
    
    if (!file_exists($path)) {
        return null;
    }
    require_once($path);
    if (!class_exists($class)) {
        debugging('The requested RSS feed doesn\'t exist', DEBUG_DEVELOPER);
        return null;
    }
    if (get_parent_class($class) != 'local_plugins_rss_feed') {
        debugging('Plugins RSS feeds MUST extend the local_plugins_rss_feed class', DEBUG_DEVELOPER);
        return null;
    }
    $rssfeed = new $class($id, $args);
    if (!$rssfeed->user_can_view()) {
        return null;
    }
    return $rssfeed->get_file_path();
}

abstract class local_plugins_rss_feed {
    
    protected $id;
    protected $args;
    
    public function __construct($id, array $args) {
        $this->id = $id;
        $this->args = $args;
    }
    public final function get_file_path() {
        global $CFG;
        $filename = md5($this->get_name().$this->id);
        $component = 'local_plugins';
        $filepath = rss_get_file_full_name($component, $filename);
        if (!file_exists($filepath) || filemtime($filepath) < $this->get_most_recent_item_timestamp()) {
            $rss = $this->get_rss();
            rss_save_file($component, $filename, $rss);
            return rss_get_file_full_name($component, $filename);
        }
        
        return $filepath;
    }
    public final function get_rss() {
        $title = $this->get_title();
        $link = $this->get_link();
        $description = $this->get_description();
        $items = $this->get_items();
        
        $header = rss_standard_header($title, $link, $description);
        $body = rss_add_items($items);
        $footer = rss_standard_footer();
        
        return $header.$body.$footer;
    }
    public final function get_url() {
        global $USER;

        $context = SYSCONTEXTID;
        $token = rss_get_token($USER->id);
        $compontent = 'local_plugins';
        $name = $this->get_name();
        $id = $this->id;

        return local_plugins_url::make_file_url('/rss/file.php', "/{$context}/{$token}/{$component}/{$name}/{$id}/rss.xml");
    }
    public function user_can_view() {
        return true;
    }

    abstract protected function get_name();
    abstract protected function get_items();
    abstract protected function get_title();
    abstract protected function get_link();
    abstract protected function get_description();
    abstract protected function get_most_recent_item_timestamp();
}

class local_plugins_rss_item {
    public $title;
    public $author;
    public $pubdate;
    public $link;
    public $description;
    public function __construct($title, $author, $pubdate, $link, $description) {
        $this->title = $title;
        $this->author = $author;
        $this->pubdate = $pubdate;
        $this->link = $link;
        $this->description = $description;
    }
    public static function from_plugin(local_plugins_plugin $plugin) {
        $title = $plugin->formatted_name;
        $author = $plugin->maintained_by;
        $recentversion = $plugin->mostrecentversion;
        if (is_null($recentversion)) { //lets ignore user moodleversion setting and simply show latest.
            $latestversions = $plugin->latestversions;
            $recentversion = reset($latestversions);
        }
        $pubdate = $recentversion->timelastmodified;
        $link = $plugin->viewlink;
        $description = $plugin->formatted_shortdescription;
        return new local_plugins_rss_item($title, $author, $pubdate, $link, $description);
    }
    public static function from_version(local_plugins_version $version) {
        $title = $version->formatted_releasename;
        $author = $version->plugin->maintained_by;
        $pubdate = $version->timelastmodified;
        $link = $version->viewlink;
        $description = $version->formatted_releasenotes;
        return new local_plugins_rss_item($title, $author, $pubdate, $link, $description);
    }
}
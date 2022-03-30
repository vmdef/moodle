<?php

/**
 * This file contains the local_plugins helper class. An abstract class that
 * contains static methods for achieving the common tasks within the plugin
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');

/**
 * This is the local_plugins_helper class.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */
abstract class local_plugins_helper {

    const PLUGIN_EXTLANDING = '/admin/index.php'; // plugins landing page on any moodle site (2.5 and onwards)

    protected static $categories;
    protected static $plugins = array();
    protected static $reviewcohorts;
    protected static $reviewcriteria;
    protected static $reviews = array();
    protected static $softwareversions = null;
    protected static $awards = null;
    protected static $sets = null;
    protected static $frontpagecategories = null;
    protected static $frontpagesets = null;
    protected static $frontpageawards = null;
    protected static $reports = null;
    protected static $contributorsplugins = array();
    protected static $usersites = array();

    /**
     * Resets all the static caches back to their empty state.
     */
    public static function reset_static_caches() {

        self::$categories = null;
        self::$plugins = [];
        self::$reviewcohorts = null;
        self::$reviewcriteria = null;
        self::$reviews = [];
        self::$softwareversions = null;
        self::$awards = null;
        self::$sets = null;
        self::$frontpagecategories = null;
        self::$frontpagesets = null;
        self::$frontpageawards = null;
        self::$reports = null;
        self::$contributorsplugins = [];
        self::$usersites = [];
    }

    /**
     *
     * @param int $id
     * @return local_plugins_category
     */
    public final static function get_category($id) {
        $categories = self::get_categories();
        if (!array_key_exists($id, $categories)) {
            return null;
        }
        return $categories[$id];
    }

    /**
     *
     * @param string
     * @return local_plugins_category
     */
    public final static function get_suggested_category($frankenstyle) {
        if (empty($frankenstyle)) {
            return null;
        } else if ($frankenstyle == '-') {
            $plugintype = '-';
        } else {
            $chunks = preg_split('/_/', $frankenstyle);
            if (count($chunks) < 2) {
                return null;
            }
            $plugintype = $chunks[0];
        }
        $categories = self::get_categories();
        foreach ($categories as $category) {
            if ($category->plugintype == $plugintype) {
                return $category;
            }
        }
        return null;
    }

    /**
     *
     * @global moodle_database $DB
     * @return array
     */
    public final static function get_categories($includeroot = false) {
        global $DB;

        if (empty(self::$categories)) {
            $catcolumns = 'c.id, c.parentid, c.name, c.plugintype, c.shortdescription, c.description, c.descriptionformat,
                           c.installinstructions, c.installinstructionsformat, c.onfrontpage, c.sortorder';

            if (has_any_capability(array(local_plugins::CAP_VIEWUNAPPROVED, local_plugins::CAP_MANAGECATEGORIES), context_system::instance())) {
                // Get the list of categories, number of available plugins and number of total plugins for each category
                $sql = 'SELECT '. $catcolumns. ',
                          COUNT(DISTINCT p.id) AS plugincount,
                          COUNT(DISTINCT pt.id) AS totalplugincount
                          FROM {local_plugins_category} c
                     LEFT JOIN {local_plugins_plugin} p
                            ON p.categoryid = c.id
                            AND p.visible = 1
                            AND p.approved = '. local_plugins_plugin::PLUGIN_APPROVED. '
                     LEFT JOIN {local_plugins_plugin} pt
                            ON pt.categoryid = c.id
                      GROUP BY c.id
                      ORDER BY c.parentid ASC, c.sortorder ASC, c.id ASC';

            } else {
                // Get the list of categories and number of available plugins in each category
                $sql = 'SELECT '. $catcolumns. ',
                          COUNT(p.id) AS plugincount
                          FROM {local_plugins_category} c
                     LEFT JOIN {local_plugins_plugin} p
                            ON p.categoryid = c.id
                            AND p.visible = 1
                            AND p.approved = '. local_plugins_plugin::PLUGIN_APPROVED. '
                      GROUP BY c.id
                      ORDER BY c.parentid ASC, c.sortorder ASC, c.id ASC';
            }

            $categories = $DB->get_records_sql($sql);
            self::$frontpagecategories = array();
            foreach ($categories as $category) {
                $categories[$category->id] = new local_plugins_category($category);
            }
            foreach ($categories as $category) {
                if ($category->has_parent()) {
                    $categories[$category->parentid]->add_child($category);
                }
                if ($category->onfrontpage) {
                    self::$frontpagecategories[$category->id] = $category;
                }
            }
            self::$categories = $categories;
        }
        if ($includeroot) { // consider using local_plugins_category_tree here?
            $properties = array('id'=>0,
                'parentid'      =>  -1,
                'name'          =>  get_string('allcategories', 'local_plugins'),
                'plugintype'    =>  0,
                'description'   =>  get_string('allcategories', 'local_plugins'),
                'children'      =>  -1, // All (n/a)
                'onfrontpage'   =>  -1  //yea but no. (n/a)
            );
            $theallcategory = new local_plugins_category($properties); // a pseudo entry for all categories.
            //this is incomplete pseudo 'local_plugins_category' - a fake.
            self::$categories[0] = $theallcategory;
        }
        return self::$categories;
    }

    /**
     * Returns an array of categories to be shown on the front page
     *
     * @return array
     */
    public final static function get_frontpage_categories() {
        self::get_categories();
        return self::$frontpagecategories;
    }

    /**
     *
     * @return local_plugins_category_tree
     */
    public final static function get_categories_tree() {
        $categories = self::get_categories();
        $tree = new local_plugins_category_tree();
        foreach ($categories as $category) {
            if (!$category->has_parent()) {
                $tree->add_child($category);
            }
        }
        return $tree;
    }

    public final static function get_category_parent_options($excludecategoryid = null) {
        $categories = self::get_categories_tree();
        $options = array(get_string('none', 'local_plugins'));
        foreach ($categories->children as $category) {
            $options = $options + self::recurse_category_options($category, 0, $excludecategoryid);
        }
        return $options;
    }

    public final static function get_review_scale_options() {
        $scales = array();
        $strscale = get_string('scale');
        $scales[0] = get_string('nograde');
        for ($i = 100; $i >= 1; $i--) {
            $scales[$i] = $i;
        }
        return $scales;
    }

    public final static function get_review_cohort_options() {
        global $DB;
        if (self::$reviewcohorts === null) {
            $sql = "SELECT c.id, c.name, c.idnumber, COUNT(cm.userid) AS cnt
                      FROM {cohort} c
                      JOIN {cohort_members} cm ON cm.cohortid = c.id
                     WHERE c.contextid = :syscontextid
                  GROUP BY c.id, c.name, c.idnumber
                  ORDER BY c.name, c.idnumber";
            $params['syscontextid'] = SYSCONTEXTID;

            $rs = $DB->get_recordset_sql($sql, $params);
            self::$reviewcohorts = array(0 => get_string('none', 'local_plugins'));
            foreach ($rs as $cid => $cohort) {
                self::$reviewcohorts[$cid] = format_string($cohort->name) .' (' . $cohort->cnt . ')';
            }
            $rs->close();
        }
        return self::$reviewcohorts;
    }

    public final static function get_category_options() {
        $categories = self::get_categories_tree();
        $options = array();
        foreach ($categories->children as $category) {
            $options = $options + self::recurse_category_options($category);
        }
        return $options;
    }

    protected static final function  recurse_category_options(local_plugins_category $category, $depth = 0, $excludecategoryid = null, $path='') {
        $options = array();
        if ($category->id == $excludecategoryid) {
            return $options;
        }
        $path .= $category->formatted_name;
        $options[(string)$category->id] = $path;
        $path .= ' > '; //@todo - consider future need for $depth. could still be useful.
        if ($category->has_children()) {
            foreach ($category->children as $child) {
                $options = $options + self::recurse_category_options($child, $depth+1, $excludecategoryid, $path);
            }
        }
        return $options;
    }

    public static function create_category(array $properties) {
        global $DB;

        $categories = self::get_categories();

        unset($properties['id']);
        if (!array_key_exists('name', $properties)) {
            throw new local_plugins_exception('exc_categorynamerequired');
        }
        if (!array_key_exists('shortdescription', $properties)) {
            throw new local_plugins_exception('exc_categoryshortdescriptionrequired');
        }
        if ($DB->record_exists('local_plugins_category', array('name' => $properties['name']))) {
            throw new local_plugins_exception('exc_categorynamealreadyexists');
        }
        if (array_key_exists('parentid', $properties) && !empty($properties['parentid'])) {
            $properties['parentid'] = (int)$properties['parentid'];
            if (!array_key_exists($properties['parentid'], $categories)) {
                throw new local_plugins_exception('exc_categoryinvalidparent');
            }
        } else {
            $properties['parentid'] = 0;
        }

        $category = new stdClass;
        $fields = array('parentid' => 0, 'name' => null, 'shortdescription' => '', 'description' => '', 'descriptionformat' => FORMAT_HTML,
            'installinstructions' => '', 'installinstructionsformat' => FORMAT_HTML, 'plugintype' => '');
        foreach ($fields as $field => $defvalue) {
            if (array_key_exists($field, $properties)) {
                $category->$field = $properties[$field];
            } else {
                $category->$field = $defvalue;
            }
        }
        $category->id = $DB->insert_record('local_plugins_category', $category);
        $category = $DB->get_record('local_plugins_category', array('id'=>$category->id), '*', MUST_EXIST);
        $category = new local_plugins_category($category);
        if ($category->has_parent()) {
            $categories[$category->parentid]->add_child($category);
        }
        self::$categories[$category->id] = $category;
        return $category;
    }

    /**
     *
     * @global moodle_database $DB
     * @global stdClass $USER
     * @param array $properties
     * @return local_plugins_plugin
     */
    public static function create_plugin(array $properties) {
        global $DB, $USER;

        $context = context_system::instance();

        $categories = self::get_categories();
        unset($properties['id']);
        if (!array_key_exists('name', $properties)) {
            throw new local_plugins_exception('exc_pluginnamerequired');
        }
        if (!array_key_exists('shortdescription', $properties)) {
            throw new local_plugins_exception('exc_pluginshortdescriptionrequired');
        }
        if (!array_key_exists('categoryid', $properties)) {
            throw new local_plugins_exception('exc_plugincategoryrequired');
        } else if (!array_key_exists($properties['categoryid'], $categories)) {
            throw new local_plugins_exception('exc_plugincategoryinvalid');
        }

        $plugin = new stdClass;
        // Require attributes
        $plugin->categoryid         = (int)$properties['categoryid'];
        $plugin->name               = $properties['name'];
        $plugin->shortdescription   = $properties['shortdescription'];
        // Optional attributes
        if (array_key_exists('description', $properties)) {
            $plugin->description = $properties['description'];
        }
        if (array_key_exists('descriptionformat', $properties)) {
            $plugin->descriptionformat = $properties['descriptionformat'];
        }
        if (array_key_exists('websiteurl', $properties)) {
            $plugin->websiteurl = $properties['websiteurl'];
        }
        if (array_key_exists('sourcecontrolurl', $properties)) {
            $plugin->sourcecontrolurl = $properties['sourcecontrolurl'];
        }

        // Calculated attributes
        $now = time();
        $plugin->timecreated = $now;
        $plugin->timelastmodified = $now;
        $plugin->visible = 1;

        if (empty($properties['frankenstyle'])) {
            $plugin->type = '_other_';
        } else {
            list($ptype, $pname) = core_component::normalize_component($properties['frankenstyle']);
            $plugin->type = $ptype;
        }

        if (has_capability(local_plugins::CAP_AUTOAPPROVEPLUGINS, $context)) {
            $plugin->approved = local_plugins_plugin::PLUGIN_APPROVED;
            $plugin->timefirstapproved = $now;
        } else {
            $plugin->approved = local_plugins_plugin::PLUGIN_PENDINGAPPROVAL;
        }
        $plugin->id = $DB->insert_record('local_plugins_plugin', $plugin);
        $plugin = $DB->get_record('local_plugins_plugin', array('id'=>$plugin->id));
        $plugin = new local_plugins_plugin($plugin);
        $contributor = array('userid' => $USER->id,
            'maintainer' => local_plugins_contributor::LEAD_MAINTAINER);
        $plugin->add_contributor($contributor);
        return $plugin;
    }

    /**
     *
     * @param type $properties
     * @return local_plugins_review_criterion
     */
    public static function create_review_criterion($properties) {
        global $DB;

        $context = context_system::instance();
        $criteria = self::get_review_criteria();
        unset($properties['id']);
        if (!array_key_exists('name', $properties)) {
            throw new local_plugins_exception('exc_reviewcriterionnamerequired');
        }
        $criterion = new stdClass;
        $criterion->name = $properties['name'];
        $criterion->description = '';
        $criterion->descriptionformat = FORMAT_MOODLE;
        if (array_key_exists('scaleid', $properties) && !empty($properties['scaleid'])) {
            $criterion->scaleid = $properties['scaleid'];
        }
        if (array_key_exists('cohortid', $properties) && !empty($properties['cohortid'])) {
            $criterion->cohortid = $properties['cohortid'];
        }
        $criterion->id = $DB->insert_record('local_plugins_review_test', $criterion);
        self::$reviewcriteria[$criterion->id] = new local_plugins_review_criterion($criterion);
        return self::$reviewcriteria[$criterion->id];
    }

    public static function create_usersite($properties) {
        global $DB;
        $properties = (array)$properties;

        $usersite = new stdClass();

        foreach (array('userid','sitename','siteurl', 'version') as $key) {
            if (array_key_exists($key, $properties)) {
                $usersite->$key = $properties[$key];
            } else {
                throw new local_plugins_exception('exc_usersitedatamissing');
            }
        }

        $usersite->id = $DB->insert_record('local_plugins_usersite', $usersite, true);
        return $usersite;
    }

    public static function get_usersite($id) {
        global $DB;

        $result = $DB->get_record('local_plugins_usersite', array('id'=>$id));
        $usersite = new local_plugins_usersite($result);
        return $usersite;
    }

    public static function get_usersites($userid) {
        global $DB;

        $usersites = array();
        $result = $DB->get_records('local_plugins_usersite', array('userid'=>$userid));
        foreach ($result as $usersite) {
            $usersites[$usersite->id] = new local_plugins_usersite($usersite);
        }
        return $usersites;
    }

    /**
     * @param moodle_url $url
     * @param string $extraclasses
     * @return string
     */
    public static function get_download_button($url, $extraclasses = '') {
        return html_writer::link(
            $url,
            get_string('download', 'local_plugins'),
            array('class' => 'download '.$extraclasses)
        );
    }

    /**
     * @param moodle_url $url
     * @param string $extraclasses
     * @return string
     */
    public static function get_install_button($url, $extraclasses = '') {
        return html_writer::link(
            $url,
            get_string('installplugin', 'local_plugins'),
            array('class' => 'install '.$extraclasses)
        );
    }

    /**
     *
     * @global moodle_database $DB
     * @param int $id
     * @return local_plugins_plugin
     */
    public final static function get_plugin($id, $strictness = MUST_EXIST) {
        global $DB;
        $id = (int)$id;
        if (!array_key_exists($id, self::$plugins)) {
            $plugin = $DB->get_record('local_plugins_plugin', array('id' => (int)$id), '*', $strictness);
            if ($plugin) {
                self::$plugins[$id] = new local_plugins_plugin($plugin);
            } else {
                return null;
            }
        }
        return self::$plugins[$id];
    }

    /**
     * Analyzes the URL query, if id is submitted, searches for plugin by id
     * if plugin is submitted, searches for plugin by frankenstyle
     *
     * @return local_plugins_plugin
     */
    public final static function get_plugin_from_params($strictness = MUST_EXIST) {
        $pluginid = optional_param('id', 0, PARAM_INT);
        $plugin = null;
        if ($pluginid) {
            $plugin = self::get_plugin($pluginid, IGNORE_MISSING);
        } else {
            $pluginname = optional_param('plugin', '', PARAM_TEXT);
            if (!empty($pluginname)) {
                $plugin = self::get_plugin_by_frankenstyle($pluginname, IGNORE_MISSING);
            }
        }
        if (empty($plugin) && $strictness == MUST_EXIST) {
            throw new local_plugins_exception('exc_pluginnotfound', new local_plugins_url('/local/plugins/'));
        }
        return $plugin;
    }

    public final static function get_plugins(array $ids) {
        //MIM TODO this function does not seem to be used
        global $DB;
        $plugins = array();
        $toget = array();
        foreach ($ids as $id) {
            $id = (int)$id;
            if (array_key_exists($id, self::$plugins)) {
                $plugins[$id] = self::$plugins[$id];
            } else {
                $plugins[$id] = null;
                $toget[] = $id;
            }
        }
        list($select, $params) = $DB->get_in_or_equal($toget, SQL_PARAMS_NAMED);
        $rs = $DB->get_recordset_select('local_plugins_plugin', 'id '.$select, $params);
        foreach ($rs as $plugin) {
            self::$plugins[$plugin->id] = new local_plugins_plugin($plugin);
            $plugins[$plugin->id] = self::$plugins[$plugin->id];
        }
        $rs->close();
        return $plugins;
    }

    public final static function load_plugins_from_result(array $plugins) {
        foreach ($plugins as $id => $plugin) {
            if (array_key_exists($id, self::$plugins)) {
                $plugins[$id] = self::$plugins[$id];
            } else {
                self::$plugins[$plugin->id] = new local_plugins_plugin($plugin);
                $plugins[$id] = self::$plugins[$plugin->id];
            }
        }
        return $plugins;
    }

    /**
     *
     * @global moodle_database $DB
     * @param int $id
     * @return local_plugins_plugin
     */
    public final static function get_plugin_by_version($versionid) {
        global $DB;
        $sql = "SELECT p.* FROM {local_plugins_plugin} p JOIN {local_plugins_vers} v ON v.pluginid = p.id WHERE v.id = :versionid";
        $plugin = $DB->get_record_sql($sql, array('versionid' => $versionid));
        if (!$plugin) {
            // Make nicer response than what MUST_EXIST does.
            local_plugins_error();
        }
        if (!array_key_exists($plugin->id, self::$plugins)) {
            $plugin = new local_plugins_plugin($plugin);
            self::$plugins[$plugin->id] = $plugin;
        }
        return self::$plugins[$plugin->id];
    }

    /**
     * If $str is valid frankenstyle name and there is a plugin in db with this frankenstyle, returns it
     * (for quick search and frankenstyle uniqueness validation)
     *
     * @param string $str
     * @return local_plugins_plugin
     */
    public final static function get_plugin_by_frankenstyle($str, $strictness = IGNORE_MISSING) {
        global $DB;
        if ($strictness == MUST_EXIST || (!empty($str) && preg_match(self::validate_frankenstyle_regexp(true), $str))) {
            $plugin = $DB->get_record('local_plugins_plugin', array('frankenstyle' => $str), '*', $strictness);
            if (!empty($plugin)) {
                return new local_plugins_plugin($plugin);
            }
        }
        return false;
    }

    public final static function editor_options_category_description() {
        global $CFG;
        return array(
            'maxfiles' => -1,
            'maxbytes' => get_max_upload_file_size($CFG->maxbytes),
            'changeformat' => true,
            'context' => context_system::instance(),
            'noclean' => true,
            'trusttext' => true
        );
    }

    public final static function editor_options_category_installinstructions() {
        global $CFG;
        return array(
            'maxfiles' => -1,
            'maxbytes' => get_max_upload_file_size($CFG->maxbytes),
            'changeformat' => true,
            'context' => context_system::instance(),
            'noclean' => true,
            'trusttext' => true
        );
    }

    public final static function editor_options_plugin_description() {
        global $CFG;
        return array(
            'maxfiles' => -1,
            'maxbytes' => get_max_upload_file_size($CFG->maxbytes),
            'context' => context_system::instance()
        );
    }

    public final static function editor_options_plugin_docs() {
        global $CFG;
        return array(
            'maxfiles' => -1,
            'maxbytes' => get_max_upload_file_size($CFG->maxbytes),
            'context' => context_system::instance()
        );
    }

    public final static function editor_options_plugin_faqs() {
        global $CFG;
        return array(
            'maxfiles' => -1,
            'maxbytes' => get_max_upload_file_size($CFG->maxbytes),
            'context' => context_system::instance()
        );
    }

    public final static function editor_options_version_releasenotes() {
        return array(
            'maxfiles' => -1,
            'maxbytes' => 0,
            'context' => context_system::instance(),
            'subdirs' => false,
        );
    }

    public final static function editor_options_review_criterion_description() {
        return array(
            'maxfiles' => -1,
            'maxbytes' => 0,
            'context' => context_system::instance(),
            'subdirs' => false,
        );
    }

    public final static function editor_options_review_outcome_review() {
        return array(
            'maxfiles' => -1,
            'maxbytes' => 0,
            'context' => context_system::instance(),
            'subdirs' => false,
        );
    }

    public final static function editor_options_award_description() {
        return array(
            'maxfiles' => -1,
            'maxbytes' => 0,
            'context' => context_system::instance(),
            'subdirs' => false,
        );
    }

    public final static function editor_options_set_description() {
        return array(
            'maxfiles' => -1,
            'maxbytes' => 0,
            'context' => context_system::instance(),
            'subdirs' => false,
        );
    }

    public final static function filemanager_options_plugin_screenshots() {
        global $CFG;
        $maxscreenshots = 10;
        if (!empty($CFG->local_plugins_maxscreenshots)) {
            $maxscreenshots = $CFG->local_plugins_maxscreenshots;
        }
        return array(
            'mainfile'       => true,
            'subdirs'        => false,
            'maxbytes'       => 0,
            'maxfiles'       => $maxscreenshots,
            'accepted_types' => array('web_image'),
            'return_types'   => FILE_INTERNAL,
        );
    }

    public final static function filemanager_options_plugin_logo() {
        return array(
            'mainfile'       => '',
            'subdirs'        => false,
            'maxbytes'       => 102400,
            'maxfiles'       => 1,
            'accepted_types' => array('web_image'),
            'return_types'   => FILE_INTERNAL
        );
    }

    public final static function filemanager_options_version_upload() {
        return array(
            'mainfile'       => '',
            'subdirs'        => false,
            'maxbytes'       => 0,
            'maxfiles'       => 1,
            'accepted_types' => array('.zip'),
            'return_types'   => FILE_INTERNAL
        );
    }

    public final static function filemanager_options_award_icon() {
        return array(
            'mainfile'       => '',
            'subdirs'        => false,
            'maxbytes'       => 0,
            'maxfiles'       => 1,
            'accepted_types' => array('web_image'),
            'return_types'   => FILE_INTERNAL
        );
    }

    public static function get_version_maturity_options() {
        return array(
            '',
            MATURITY_STABLE => get_string('maturity'.MATURITY_STABLE, 'admin'),
            MATURITY_RC => get_string('maturity'.MATURITY_RC, 'admin'),
            MATURITY_BETA => get_string('maturity'.MATURITY_BETA, 'admin'),
            MATURITY_ALPHA => get_string('maturity'.MATURITY_ALPHA, 'admin')
        );
    }

    public static function get_version_control_system_options() {
        return array(
            'none' => get_string('none', 'local_plugins'),
            'git' => get_string('git', 'local_plugins'),
            'cvs' => get_string('cvs', 'local_plugins'),
            'svn' => get_string('svn', 'local_plugins'),
            'mercurial' => get_string('mercurial', 'local_plugins'),
            'other' => get_string('other', 'local_plugins')
        );
    }

    public static function get_contributor($id) {
        global $DB;
        $contributor = $DB->get_record('local_plugins_contributor', array('id' => $id), '*', MUST_EXIST);
        return new local_plugins_contributor($contributor);
    }

    /**
     * Returns a pseudo object of type local_plugins_contributor that is only needed to store the user information
     * and invoke user-related methods (username, rss link, contributed plugins list, etc.)
     *
     * @global <type> $DB
     * @param <type> $userid
     * @return local_plugins_contributor 
     */
    public static function get_contributor_by_user_id($userid) {
        global $DB;
        $user = $DB->get_record('user', array('id' => (int)$userid), '*', MUST_EXIST);
        $contributor = array('id' => 0, 'userid' => $userid);
        return new local_plugins_contributor($contributor);
    }

    /**
     * Return the total number of contributors registered with the directory
     *
     * @return int
     */
    public static function count_contributors() {
        global $DB;

        $sql = "SELECT COUNT(DISTINCT c.userid)
                  FROM {local_plugins_contributor} c
                  JOIN {local_plugins_plugin} p ON c.pluginid = p.id
                 WHERE p.approved = 1 AND p.visible = 1";

        return $DB->count_records_sql($sql);
    }

    /**
     * Return the total number of available plugins
     *
     * This is implemented in a way that 1) does not require additional DB
     * queries and 2) produces consistent result with the number of plugin per
     * category at the front page (so users are not confused eventually).
     *
     * @return int
     */
    public static function count_available_plugins() {

        $numofplugins = 0;

        foreach (local_plugins_helper::get_categories_tree()->children as $category) {
            $numofplugins += $category->plugincount_withchildren;
        }

        return $numofplugins;
    }

    /**
     * Returns an array of contributors for this plugin
     *
     * @param int $pluginid
     * @return array
     */
    public static function get_plugin_contributors($pluginid) {
        global $DB;

        $rs = $DB->get_recordset_sql("SELECT *
                                        FROM {local_plugins_contributor}
                                       WHERE pluginid = :pluginid
                                    ORDER BY CASE maintainer
                                              WHEN :lead_maintainer THEN 10
                                              WHEN :maintainer THEN 20
                                              ELSE 30
                                             END ASC, timecreated ASC", array(
                                            'pluginid' => $pluginid,
                                            'lead_maintainer' => local_plugins_contributor::LEAD_MAINTAINER,
                                            'maintainer' => local_plugins_contributor::MAINTAINER));
        $contributors = array();
        foreach ($rs as $contributor) {
            $contributors[$contributor->userid] = new local_plugins_contributor($contributor);
        }
        return $contributors;
    }

    /**
     * Returns an array of plugin ids where the user is a contributor
     *
     * @param int $id Contributor user id
     * @return array
     */
    public static function get_contributor_plugins_ids($id) {
        global $DB;
        $id = intval($id);
        if (!$id) {
            return array();
        }
        if (!array_key_exists($id, self::$contributorsplugins)) {
            self::$contributorsplugins[$id] = $DB->get_fieldset_select('local_plugins_contributor', 'pluginid', 'userid = '. $id);
        }
        return self::$contributorsplugins[$id];
    }

    /**
     *
     * @param int $pluginid
     * @return comment
     */
    public static function comment_for_plugin($pluginid) {
        $options = new stdClass;
        $options->area          = 'plugin_general';
        $options->context       = context_system::instance();
        $options->itemid        = $pluginid;
        $options->component     = 'local_plugins';
        $options->showcount     = true;
        $options->notoggle      = true;
        $options->autostart     = true;
        $options->displaycancel = true;
        $options->ignore_permission = true;

        $api = new comment($options);
        $api->set_fullwidth();

        return $api;
    }

    public static function send_version_file(local_plugins_version $version) {
        global $CFG;

        // Moodle sites also use HEAD request to check the availability of the ZIP.
        // Do not log such requests as valid downloads.
        if (strtolower($_SERVER['REQUEST_METHOD']) === 'get') {
            if (!core_useragent::is_web_crawler()) {
                $version->log_download('website');
            }
        }

        $file = $CFG->dataroot.'/local_plugins/'.$version->pluginid.'/'.$version->id.'.zip';
        $name = $version->downloadfilename;
        send_file($file, $name, 'default', 0, false, true, 'application/zip');
    }

    public static function get_review_criteria() {
        global $DB;

        if (!is_array(self::$reviewcriteria) || empty(self::$reviewcriteria)) {
            self::$reviewcriteria = array();
            $results = $DB->get_records('local_plugins_review_test');
            foreach ($results as $result) {
                self::$reviewcriteria[$result->id] = new local_plugins_review_criterion($result);
            }

            if (empty(self::$reviewcriteria)) {
                // if there are no review criteria yet, make default review criterion 'general'
                $criterion = new stdClass();
                $criterion->name = get_string('generalcriterion', 'local_plugins');
                $criterion->description = get_string('generalcriteriondesc', 'local_plugins');
                $criterion->descriptionformat = FORMAT_HTML;
                if ($DB->insert_record('local_plugins_review_test', $criterion)) {
                    $result = $DB->get_record('local_plugins_review_test', array());
                    self::$reviewcriteria[$result->id] = new local_plugins_review_criterion($result);
                }
            }
        }

        return self::$reviewcriteria;
    }

    public static function get_review_criterion($id) {
        $criteria = self::get_review_criteria();
        if (!array_key_exists($id, $criteria)) {
            throw new local_plugins_exception('exc_invalidreviewcriterion');
        }
        return $criteria[$id];
    }

    public static function get_user_review_id_if_exists(local_plugins_version $version) {
        global $DB, $USER;
        return $DB->get_field('local_plugins_review', 'id', array('userid' => $USER->id, 'versionid' => $version->id));
    }

    public static function get_software_version($id) {
        $softwareversions = self::get_software_versions();
        if (!array_key_exists($id, $softwareversions)) {
            throw new local_plugins_exception('exc_invalidsoftwareversion');
        }
        return $softwareversions[$id];
    }

    public static function get_software_versions($force = false) {
        if (self::$softwareversions === null || $force) {
            global $DB;
            $records = $DB->get_records('local_plugins_software_vers', null, 'name ASC, version DESC, timecreated DESC, id DESC');
            self::$softwareversions = array();
            foreach ($records as $record) {
                self::$softwareversions[$record->id] = new local_plugins_softwareversion($record);
            }
        }
        return self::$softwareversions;
    }

    /**
     * Returns list of available Moodle versions (sorted by version build number descending)
     *
     * @return array of local_plugins_softwareversion that are name='Moodle'
     */
    public static function get_moodle_versions() {
        $moodleversions = array();
        foreach (self::get_software_versions() as $id => $software)  {
            if ($software->name == 'Moodle') {
                $moodleversions[$id] = $software;
            }
        }
        return $moodleversions;
    }

    public static function get_software_versions_options($softwareversions=null) {
        if (is_null($softwareversions)) {
            $softwareversions = self::get_software_versions();
        }
        $return = array();
        foreach ($softwareversions as $version) {
            if (!array_key_exists($version->name, $return)) {
                $return[$version->name] = new stdClass();
                $return[$version->name]->name = $version->name;
                $return[$version->name]->versions = array();
                $return[$version->name]->releasenames = array();
            }
            $return[$version->name]->versions[$version->id] = $version->fullname_version;
            $return[$version->name]->releasenames[$version->id] = $version->releasename;
        }
        return $return;
    }

    /**
     * Return the version number like 2021051700 for the matching Moodle release name like '3.11'.
     *
     * @param string $releasename
     * @return local_plugins_softwareversion|null
     */
    public static function get_moodle_version_by_releasename(string $releasename): ?local_plugins_softwareversion {

        foreach (static::get_software_versions() as $id => $software) {
            if ($software->name === 'Moodle' && $software->releasename === $releasename) {
                return $software;
            }
        }

        return null;
    }

    /**
     * Return the version number like 2021051700 for the matching Moodle branch like 311.
     *
     * @param int $branch
     * @return local_plugins_softwareversion|null
     */
    public static function get_moodle_version_by_branch_code(int $branch): ?local_plugins_softwareversion {

        return static::get_moodle_version_by_releasename(static::moodle_branch_to_version($branch));
    }

    public static function create_software_version($properties) {
        global $DB;
        $properties = (array)$properties;
        $obj = array('name' => $properties['name'], 'version' => $properties['version']);
        if ($DB->count_records('local_plugins_software_vers', $obj) > 0) {
            throw new local_plugins_exception('exc_softwareversexists');
        }
        $obj['releasename'] = $properties['releasename'];
        $obj['timecreated'] = time();
        $id = $DB->insert_record('local_plugins_software_vers', $obj);
        self::get_software_versions(true);
        return self::$softwareversions[$id];
    }

    public static function get_supportable_software_applications() {
        global $CFG;

        if (empty($CFG->local_plugins_supportablesoftware)) {
            return array('Moodle' => 'Moodle', 'PHP' => 'PHP');
        } else {
            $bits = explode(',', $CFG->local_plugins_supportablesoftware);
            $software = array();
            foreach ($bits as $bit) {
                $bit = trim($bit);
                if (!empty($bit)) {
                    $software[$bit] = $bit;
                }
            }
            asort($software);
            return $software;
        }
    }

    public static function get_awards() {
        global $DB;

        if (is_null(self::$awards)) {
            self::$awards = array();
            self::$frontpageawards = array();

            $sql = 'SELECT a.id, a.name, a.shortname, a.description, a.descriptionformat, a.timecreated, a.onfrontpage,
                           COUNT(pa.id) AS plugincount
                      FROM {local_plugins_awards} a
                 LEFT JOIN {local_plugins_plugin_awards} pa ON pa.awardid = a.id
                  GROUP BY a.id';

            $awards = $DB->get_records_sql($sql);

            foreach ($awards as $award) {
                self::$awards[$award->id] = new local_plugins_award($award);
                if ($award->onfrontpage) {
                    self::$frontpageawards[$award->id] = self::$awards[$award->id];
                }
            }
        }

        return self::$awards;
    }

    public static function get_frontpage_awards() {
        self::get_awards();
        return self::$frontpageawards;
    }

    public static function get_awards_options() {
        $awards = self::get_awards();
        $return = array();
        foreach ($awards as $award) {
            $return[$award->id] = $award->formatted_name;
        }
        return $return;
    }

    /**
     * @return local_plugins_award
     */
    public static function get_award($awardid) {
        $awards = self::get_awards();
        if (!array_key_exists($awardid, $awards)) {
            throw new local_plugins_exception('exc_invalidaward');
        }
        return $awards[$awardid];
    }

    public static function create_award(array $properties) {
        global $DB;

        if (!array_key_exists('name', $properties) || empty($properties['name'])) {
            throw new local_plugins_exception('exc_awardnamerequired');
        }

        // To make unique index work, convert empty values to nulls.
        if (empty($properties['shortname'])) {
            $properties['shortname'] = null;
        }

        $award = new stdClass;
        $award->name = $properties['name'];
        $award->shortname = $properties['shortname'];
        $award->description = $properties['description'];
        $award->descriptionformat = $properties['descriptionformat'];
        $award->timecreated = time();
        if (array_key_exists('onfrontpage', $properties) && $properties['onfrontpage']) {
            $award->onfrontpage = 1;
        } else {
            $award->onfrontpage = 0;
        }
        $award->id = $DB->insert_record('local_plugins_awards', $award);

        if (is_null(self::$awards)) {
            self::get_awards();
        } else {
            self::$awards[$award->id] = new local_plugins_award($award);
        }
        return self::$awards[$award->id];
    }

    public static function quick_upload_version(local_plugins_plugin $plugin, $draftitemid, $validator) {
        global $USER;
        $info = $validator->versioninformation;
        $version = array(
            'userid' => $USER->id,
            'releasenotesformat' => FORMAT_MOODLE
        );
        $version = $plugin->add_version($version);
        try {
            $validator->store_archive_in_version($version);
        } catch (local_plugins_exception $exc) {
            $version->delete(false);
            throw $exc;
        }
        return $version;
    }

    public static function get_sets() {
        global $DB;

        if (self::$sets === null) {
            $sql = 'SELECT s.id, s.name, s.shortname, s.description, s.descriptionformat, s.maxplugins, s.onfrontpage,
                           COUNT(sp.id) AS plugincount
                      FROM {local_plugins_set} s
                 LEFT JOIN {local_plugins_set_plugin} sp ON sp.setid = s.id
                  GROUP BY s.id
                  ORDER BY s.name, s.id';

            $rs = $DB->get_recordset_sql($sql);
            self::$sets = array();
            self::$frontpagesets = array();
            foreach ($rs as $set) {
                self::$sets[$set->id] = new local_plugins_set($set);
                if ($set->onfrontpage) {
                    self::$frontpagesets[$set->id] = self::$sets[$set->id];
                }
            }
            $rs->close();
        }
        return self::$sets;
    }

    public static function get_frontpage_sets() {
        self::get_sets();
        return self::$frontpagesets;
    }

    public static function get_sets_options() {
        $sets = self::get_sets();
        $return = array();
        foreach ($sets as $set) {
            $return[$set->id] = $set->formatted_name;
        }
        return $return;
    }

    /**
     *
     * @param int $id
     * @return local_plugins_set
     */
    public static function get_set($id) {
        $sets = self::get_sets();
        if (!array_key_exists($id, $sets)) {
            throw new local_plugins_exception('exc_invalidset');
        }
        return $sets[$id];
    }

    public static function get_set_max_plugin_options() {
        return array(
            0 => get_string('none', 'local_plugins'),
            1 => 1,
            5 => 5,
            10 => 10,
            25 => 25,
            100 => 100
        );
    }

    public static function create_set($properties) {
        global $DB;

        $sets = self::get_sets();

        if (!array_key_exists('name', $properties)) {
            throw new local_plugins_exception('exc_setnamerequired');
        } else if (count($sets) > 0) {
            foreach ($sets as $set) {
                if ($set->name == $properties['name']) {
                    throw new local_plugins_exception('exc_setnameexists');
                }
            }
        }

        // To make unique index work, convert empty values to nulls.
        if (empty($properties['shortname'])) {
            $properties['shortname'] = null;
        }

        $set = new stdClass;
        $fields = array('name' => '', 'shortname' => null, 'description' => '', 'descriptionformat' => FORMAT_HTML,
            'maxplugins' => 10, 'onfrontpage' => 0);
        foreach ($fields as $field => $defvalue) {
            if (array_key_exists($field, $properties)) {
                $set->$field = $properties[$field];
            } else {
                $set->$field = $defvalue;
            }
        }

        $set->id = $DB->insert_record('local_plugins_set', $set);
        $set = new local_plugins_set($set);
        self::$sets[$set->id] = $set;
        return $set;
    }

    public static function sql_plugin_view_check($userid = null, $tablealias = 'p', $joinalias = 'pc', $userparam = 'contributorid') {
        global $USER;

        $sql = '';
        $join = '';
        $params = array();
        if (has_capability(local_plugins::CAP_VIEWUNAPPROVED, context_system::instance())) {
            // The user is able to view all
            $sql = '1 = 1';
        } else {
            $sql = "($tablealias.approved = ". local_plugins_plugin::PLUGIN_APPROVED. " AND $tablealias.visible = 1)";
            if (isloggedin() && !isguestuser()) {
                if ($userid === null) {
                    $userid = $USER->id;
                }
                $join = "LEFT JOIN (
                            SELECT lcc.pluginid, COUNT(lcc.id) AS plugincount
                              FROM {local_plugins_contributor} lcc
                             WHERE lcc.userid = :$userparam
                          GROUP BY lcc.pluginid
                         ) $joinalias ON $joinalias.pluginid = p.id";
                $params[$userparam] = $userid;
                $sql .= " OR $joinalias.plugincount > 0";
            }
        }
        return array("($sql)", $join, $params);
    }

    public static function get_reports() {
        global $CFG;
        if (self::$reports === null) {
            self::$reports = array();
            $reportdir = $CFG->dirroot.'/local/plugins/report/';
            $files = scandir($reportdir);
            foreach ($files as $name) {
                if (!preg_match('#^[a-zA-Z0-9_-]+$#', $name) && !is_dir($reportdir.$name) || !file_exists($reportdir.$name.'/lib.php')) {
                    continue;
                }
                require_once($reportdir.$name.'/lib.php');
                $class = 'local_plugins_'.$name.'_report';
                if (!class_exists($class) || !array_key_exists('local_plugins_report_base', class_parents($class))) {
                    continue;
                }
                self::$reports[$name] = new $class($name);
            }
        }
        return self::$reports;
    }


    /**
     * Returns list of plugin reports for the current user
     *
     * If the 'by display type' mode is on, the list is divided into two
     * groups: ['quickaccess'] => array, ['normal'] => array. Otherwise, the
     * flat list of local_plugins_report_base subclasses is returned.
     *
     * @param bool $bydisplaytype
     * @return array
     */
    public static function get_reports_viewable_by_user($bydisplaytype = false) {

        $context = context_system::instance();

        $return = $bydisplaytype ? array('quickaccess' => array(), 'normal' => array()) : array() ;

        if (!has_capability(local_plugins::CAP_VIEWREPORTS, $context)) {
            return $return;
        }

        $reports = self::get_reports();

        foreach ($reports as $report) {
            if ($report->user_can_view($context)) {
                if ($bydisplaytype) {
                    if ($report->quick_access()) {
                        $return['quickaccess'][] = $report;
                    } else {
                        $return['normal'][] = $report;
                    }
                } else {
                    $return[] = $report;
                }
            }
        }

        return $return;
    }

    /**
     *
     * @param string $reportname
     * @return local_plugins_report_base
     */
    public static function get_report($reportname) {
        $reports = self::get_reports();
        if (!array_key_exists($reportname, $reports)) {
            throw new local_plugins_exception('exc_invalidreport');
        }
        return $reports[$reportname];
    }

    public static function get_rss_url($name, $id, $userid = null) {
        global $CFG;

        require_once($CFG->dirroot.'/lib/rsslib.php');

        if (empty($userid)) {
            global $USER;
            $userid = $USER->id;
            if (empty($userid)) {
                $userid = $CFG->siteguest;
            }
        }

        $context = SYSCONTEXTID;
        $token = rss_get_token($userid);
        $component = 'local_plugins';

        return local_plugins_url::make_file_url('/rss/file.php', "/{$context}/{$token}/{$component}/{$name}/{$id}/rss.xml");
    }

    public static function search_for_user($idorusername) {
        global $DB;
        if (preg_match('/^\d+$/', $idorusername) && $DB->record_exists('user', array('id' => $idorusername, 'deleted' => '0'))) {
            return (int)$idorusername;
        } else if (clean_param($idorusername, PARAM_USERNAME) === $idorusername) {
            $userid = $DB->get_field('user', 'id', array('username' => $idorusername, 'deleted' => '0'));
            if (!empty($userid)) {
                return $userid;
            }
        }
        return 0;
    }

    /**
     * Returns regexp to validate that string can be frankenstyle name of the plugin
     */
    public static function validate_frankenstyle_regexp($withprefix = false) {
        if ($withprefix === true) {
            $prefix = '[a-z]{3,30}_';
        } else if ($withprefix === false) {
            $prefix = '';
        } else {
            $prefix = $withprefix;
        }
        return '/^'. $prefix. '[a-z][a-z|0-9|_]*[a-z|0-9]$/';
    }

    /**
     * Generates from frankenstyle the name of the directory that must be present in uploaded archive
     * This is the part of frankenstyle that follows the first underscore
     *
     * @param string $frankenstyle
     * @return string
     */
    public static function get_archive_rootdir($frankenstyle) {
        if (preg_match('#^[a-z]+_(.*)$#', $frankenstyle, $matches)) {
            return $matches[1];
        } else {
            return $frankenstyle;
        }
    }

    /**
     * Retrieves Moodle version selected by user:
     * from $_REQUEST, from cookie, from user preferences, or latest version by default
     *
     * Also stores the user moodle version in cookie and user preferences (if logged in)
     *
     * @return int
     */
    public static function get_user_moodle_version() {
        static $mversion = null;
        if ($mversion === null) {
            $moodleversions = self::get_moodle_versions();

            // Get from query string
            $mversion = optional_param('moodle_version', 0, PARAM_INT);
            if (!isset($moodleversions[$mversion]) && isset($_COOKIE['local_plugins_moodle_version'])) {
                // Get from cookie
                $mversion = clean_param($_COOKIE['local_plugins_moodle_version'], PARAM_INT);
            }
            if (!isset($moodleversions[$mversion]) && isloggedin() && !isguestuser()) {
                // Get from user preferences
                $mversion = clean_param(get_user_preferences('local_plugins_moodle_version', 0), PARAM_INT);
            }
            if (!isset($moodleversions[$mversion])) {
                if (!empty($moodleverisonsids)) {
                    // Get latest version
                    $moodleverisonsids = array_keys($moodleversions);
                    $mversion = $moodleverisonsids[0];
                } else {
                    $mversion = 0;
                }
            }

            // Save the moodle version in preferences:
            if (isloggedin() && !isguestuser()) {
                if ($mversion != get_user_preferences('local_plugins_moodle_version', 0)) {
                    set_user_preference('local_plugins_moodle_version', $mversion);
                }
            }
        }
        return $mversion;
    }

    /**
     * Retrieves Moodle version matching a usersite:
     *
     * @return local_plugins_softwareversion that is a 'Moodle' version.
     */
    public static function get_usersite_moodle_version(local_plugins_usersite $usersite) {
        $moodleversions = self::get_moodle_versions();
        if (isset($moodleversions[$usersite->version])) {
            return $moodleversions[$usersite->version];
        }
        return false;
    }

    /**
     * Retrieves plugin category selected by user:
     * from $_REQUEST, from cookie, from user preferences, or root category by default
     *
     * Also stores the user plugin category in cookie and user preferences (if logged in)
     *
     * @return int
     */
    public static function get_user_plugin_category() {
        static $plugincategory = null;
        if ($plugincategory === null) {
            $plugincategories = self::get_categories(true);
            // Get from query string
            $plugincategory = optional_param('plugin_category', 0, PARAM_INT);
            if (!isset($plugincategories[$plugincategory]) && isset($_COOKIE['local_plugins_plugin_category'])) {
                // Get from cookie
                $plugincategory = clean_param($_COOKIE['local_plugins_plugin_category'], PARAM_INT);
            }
            if (!isset($plugincategories[$plugincategory]) && isloggedin() && !isguestuser()) {
                // Get from user preferences
                $plugincategory = clean_param(get_user_preferences('local_plugins_plugin_category', 0), PARAM_INT);
            }
            if (!isset($plugincategories[$plugincategory])) {
                $plugincategory = 0;
            }

            // Save the moodle version in preferences:
            if (isloggedin() && !isguestuser()) {
                if ($plugincategory != get_user_preferences('local_plugins_plugin_category', 0)) {
                    set_user_preference('local_plugins_plugin_category', $plugincategory);
                }
            }
        }
        return $plugincategory;
    }

    /**
     * Returns the descriptor title
     *
     * @param int $descid
     * @return string
     */
    public static function descriptor_title($descid) {
        global $DB;
        static $data = null;

        if ($data === null) {
            $data = $DB->get_records('local_plugins_desc', null, 'sortorder', 'id, title');
        }

        return $data[$descid]->title;
    }

    /**
     * Convert the given branch code such as 38, 39, 310, 311, 400 to major version number like 3.8, 3.9, 3.10, 3.11, 4.0.
     *
     * @param int $branchcode
     * @return string
     */
    public static function moodle_branch_to_version(int $branchcode): string {

        if (preg_match('/^(\d)(\d)$/', $branchcode, $m) && $branchcode >= 10 && $branchcode <= 39) {
            return (string) ($m[1] . '.' . $m[2]);

        } else if (preg_match('/^(\d){3,}$/', $branchcode) && $branchcode >= 310) {
            $x = floor($branchcode / 100);
            $y = $branchcode - $x * 100;
            return (string) ($x . '.' . $y);

        } else {
            throw new local_plugins_exception('exc_invalidbranchcode', null, '', $branchcode);
        }
    }

    /**
     * Convert the major version number like 3.8, 3.9, 3.10, 3.11, 4.0 to branch code such as 38, 39, 310, 311, 400.
     *
     * @param string $version
     * @return int
     */
    public static function moodle_version_to_branch(string $version): int {

        if (preg_match('/^(\d+)\.(\d+)$/', $version, $m)) {
            if (version_compare($version, '3.9', '<=')) {
                return (int) ($m[1] * 10 + $m[2]);

            } else {
                return (int) ($m[1] * 100 + $m[2]);
            }

        } else {
            throw new local_plugins_exception('exc_invalidversionnumber', null, '', $version);
        }
    }
}

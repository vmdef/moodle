<?php

/**
 * This file contains the core abstract classes within this plugin.
 *
 * local_plugins_class_base:  This class should be extended by all other
 * 					    classes within the plugin to ensure consistent
 * 					    code accessability.
 * local_plugins:			   	This class is used to house constants used
 * 						by this plugin such as capabilities and fileareas
 * local_plugins_exception:   This class is used when throwing exceptions
 * 						within this plugin
 * local_plugins_coding_exception: This class is used when throwing coding
 * 						exceptions within this plugin.
 *
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
 * This class should be extended by all other classes within the
 * plugin to ensure consistent code accessability.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */
abstract class local_plugins_class_base {

    public function __construct($properties) {
        $properties  = (array)$properties;
        if (!array_key_exists('id', $properties)) {
            throw new local_plugins_coding_exception('The object you are loading is invalid as it has no id.');
        }
        foreach ($properties as $property => $value) {
            $method = 'set_'.$property;
            if (method_exists($this, $method)) {
                $this->$method($value);
            } else if (property_exists($this, $property)) {
                $this->$property = $value;
            }
        }
    }

    public function __get($property) {
        $method = 'get_'.$property;
        if (method_exists($this, $method)) {
            return $this->$method();
        } else if (property_exists($this, $property)) {
            return $this->$property;
        }
        throw new local_plugins_coding_exception('Attempting to get a property that doesn\'t exist: '.clean_param($property, PARAM_TEXT));
    }

    public function __set($property, $value) {
        $method = 'set_'.$property;
        if (method_exists(get_class($this), $method)) {
            $this->$method($value);
            return true;
        }
        throw new local_plugins_coding_exception('Attempting to set a property that cannot be set or does not exist '.$property);
    }

    public function __isset($property) {
        try {
            $value = $this->__get($property);
        } catch (local_plugins_coding_exception $ex) {
            return false;
        }
        return !empty($value);
    }
}

/**
 * Base class for collection of plugins
 *
 * @property-read moodle_url $rssurl
 */
abstract class local_plugins_collection_class extends local_plugins_class_base {
    public function add_plugin(local_plugins_plugin $plugin) { return false; }
    abstract protected function get_browseurl();

    protected function get_defaults($param) {
        $defaults = array(
            'p' => 0, // current page
            'l' => 100, // number of plugins per page, setting this to 100 now, lets see if theres any reactions :-)
        );
        return $defaults[$param];
    }

    protected function get_param($param, $type = PARAM_INT) {
        return optional_param($param, $this->get_defaults($param), $type);
    }

    public function get_currentpage_plugins() {
        $page = $this->get_param('p');
        $perpage = $this->get_param('l');
        return $this->get_plugins($perpage, $perpage * $page, true, true, true);
    }

    protected function get_currentpage_plugins_count() {
        static $count = false;
        if ($count === false) {
            $count = $this->get_plugins_count(true, true, true);
        }
        return $count;
    }

    public function get_currentpage_pagingbar() {
        static $pagingbar = false;
        if ($pagingbar === false) {
            $count = $this->get_currentpage_plugins_count();
            $page = $this->get_param('p');
            $perpage = $this->get_param('l');
            if ($count>$perpage) {
                $pagingbar = new paging_bar($count, $page, $perpage, $this->get_browseurl(), 'p');
            } else {
                $pagingbar = null;
            }
        }
        return $pagingbar;
    }

    public function get_plugins($limit = 20, $offset = 0, $unapproved = false, $invisible = false, $alwaysshowown = false) {
        global $DB;
        $sql = array(
            "SELECT" => "p.*",
            "FROM" => "{local_plugins_plugin} p",
            "WHERE" => "1=1",
            "ORDER BY" => "COALESCE(p.aggfavs, 0) DESC, p.timelastreleased DESC, p.id DESC"
        );
        $params = array();
        $this->plugins_alter_query($sql, $params, $unapproved, $invisible, $alwaysshowown);
        $sqlstr = '';
        foreach ($sql as $key => $val) {
            $sqlstr .= ' '. $key. ' '. $val;
        }
        $plugins = $DB->get_records_sql($sqlstr, $params, $offset, $limit);
        $plugins = local_plugins_helper::load_plugins_from_result($plugins);
        return $plugins;
    }

    public function get_plugins_count($unapproved = false, $invisible = false, $alwaysshowown = false) {
        global $DB;
        $sql = array(
            "SELECT" => "count(p.id)",
            "FROM" => "{local_plugins_plugin} p",
            "WHERE" => "1=1"
        );
        $params = array();
        $this->plugins_alter_query($sql, $params, $unapproved, $invisible, $alwaysshowown);
        $sqlstr = '';
        foreach ($sql as $key => $val) {
            $sqlstr .= ' '. $key. ' '. $val;
        }
        return $DB->get_field_sql($sqlstr, $params);
    }

    protected function plugins_alter_query(&$sql, &$params, $unapproved = false, $invisible = false, $alwaysshowown = false) {
        global $DB, $USER;
        if (!has_capability(local_plugins::CAP_VIEWUNAPPROVED, context_system::instance())) {
            // check permission to view unapproved / invisible plugins
            $unapproved = false;
            $invisible = false;
        }
        if ($unapproved && $invisible) {
            // all plugins should be returned
            return;
        }
        $where = array();
        if (!$unapproved) {
            $where[] = 'p.approved = '. local_plugins_plugin::PLUGIN_APPROVED;
        }
        if (!$invisible) {
            $where[] = 'p.visible = 1';
        }
        $wheresql = join(' AND ', $where);
        if ($alwaysshowown && isloggedin() && !isguestuser()) {
            // retrieve list of this user's plugins and add them to the query
            $plugins = local_plugins_helper::get_contributor_plugins_ids($USER->id);
            if (sizeof($plugins)) {
                list($idselect, $idparams) = $DB->get_in_or_equal($plugins, SQL_PARAMS_NAMED, 'pluginid');
                $wheresql = "(({$wheresql}) OR p.id ". $idselect. ")";
                $params = array_merge($params, $idparams);
            }
        }
        $sql["WHERE"] .= " AND ". $wheresql;
    }

    abstract public function get_rssurl();
}

/**
 * This class is used when throwing general exceptions within this plugin.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */
class local_plugins_exception extends moodle_exception {
    public function __construct($errorcode, $link = '', $a = NULL, $debuginfo = null) {
        parent::__construct($errorcode, 'local_plugins', $link, $a, $debuginfo);
    }
}

/**
 * This class is used when throwing coding exceptions within this plugin.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */
class local_plugins_coding_exception extends coding_exception {
    public function __construct($hint, $debuginfo = null) {
        parent::__construct('local_plugins: '.$hint, $debuginfo);
    }
}

/**
 * This class is used to house constants used by this plugin such as
 * capabilities and fileareas and notification message providers.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */
abstract class local_plugins {
    const CAP_VIEW                        = 'local/plugins:view';
    const CAP_VIEWUNAPPROVED              = 'local/plugins:viewunapproved';
    const CAP_VIEWREPORTS                 = 'local/plugins:viewreports';
    const CAP_CREATEPLUGINS               = 'local/plugins:createplugins';
    const CAP_EDITOWNPLUGINS              = 'local/plugins:editownplugins';
    const CAP_EDITANYPLUGIN               = 'local/plugins:editanyplugin';
    const CAP_DELETEOWNPLUGIN             = 'local/plugins:deleteownplugin';
    const CAP_DELETEANYPLUGIN             = 'local/plugins:deleteanyplugin';
    const CAP_DELETEOWNPLUGINVERSION      = 'local/plugins:deleteownpluginversion';
    const CAP_DELETEANYPLUGINVERSION      = 'local/plugins:deleteanypluginversion';
    const CAP_APPROVEPLUGIN               = 'local/plugins:approveplugin';
    const CAP_APPROVEPLUGINVERSION        = 'local/plugins:approvepluginversion';
    const CAP_AUTOAPPROVEPLUGINS          = 'local/plugins:autoapproveplugins';
    const CAP_AUTOAPPROVEPLUGINVERSIONS   = 'local/plugins:autoapprovepluginversions';
    const CAP_PUBLISHREVIEWS              = 'local/plugins:publishreviews';
    const CAP_EDITOWNREVIEW               = 'local/plugins:editownreview';
    const CAP_EDITANYREVIEW               = 'local/plugins:editanyreview';
    const CAP_COMMENT                     = 'local/plugins:comment';
    const CAP_RATE                        = 'local/plugins:rate';
    const CAP_MARKFAVOURITE               = 'local/plugins:markfavourite';
    const CAP_EDITOWNTAGS                 = 'local/plugins:editowntags';
    const CAP_EDITANYTAGS                 = 'local/plugins:editanytags';
    const CAP_MANAGESUPPORTABLEVERSIONS   = 'local/plugins:managesupportableversions';
    const CAP_MANAGESETS                  = 'local/plugins:managesets';
    const CAP_ADDTOSETS                   = 'local/plugins:addtosets';
    const CAP_MANAGEAWARDS                = 'local/plugins:manageawards';
    const CAP_HANDOUTAWARDS               = 'local/plugins:handoutawards';
    const CAP_MANAGECATEGORIES            = 'local/plugins:managecategories';
    const CAP_MANAGEREVIEWCRITERIA        = 'local/plugins:managereviewcriteria';
    const CAP_NOTIFIEDUNAPPROVEDACTIVITY  = 'local/plugins:notifiedunapprovedactivity';
    const CAP_VIEWQUEUESTATS              = 'local/plugins:viewqueuestats';
    // Please note that I've stopped using constants for capability names -- mudrd8mz

    const FILEAREA_AWARDDESCRIPTION       = 'award_description';
    const FILEAREA_AWARDICON              = 'award_icon';
    const FILEAREA_CATEGORYDESCRIPTION    = 'category_description';
    const FILEAREA_CATEGORYDEFAULTLOGO    = 'category_defaultlogo';
    const FILEAREA_CATEGORYINSTALLINSTRUCTIONS  = 'category_installinstructions';
    const FILEAREA_PLUGINDESCRIPTION      = 'plugin_description';
    const FILEAREA_PLUGINSCREENSHOTS      = 'plugin_screenshots';
    const FILEAREA_PLUGINDOCS             = 'plugin_docs';
    const FILEAREA_PLUGINFAQS             = 'plugin_faqs';
    const FILEAREA_PLUGINLOGO             = 'plugin_logo';
    const FILEAREA_SETDESCRIPTION         = 'set_description';
    const FILEAREA_VERSIONRELEASENOTES    = 'version_releasenotes';
    const FILEAREA_REVIEWCRITERIADESC     = 'review_criterion_desc';
    const FILEAREA_REVIEWOUTCOME          = 'review_outcome_review';

    const NOTIFY_AVAILABILITY             = 'availability';
    const NOTIFY_AWARD                    = 'award';
    const NOTIFY_COMMENT                  = 'comment';
    const NOTIFY_CONTRIBUTOR              = 'contributor';
    const NOTIFY_REGISTRATION             = 'registration';
    const NOTIFY_REVIEW                   = 'review';
    const NOTIFY_VERSION                  = 'version';

}

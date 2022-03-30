<?php

/**
 * This file contains the plugin pagination class.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

/**
 * The plugin pagination class does exactly what it says display
 * paginated plugins.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */
class local_plugins_plugin_pagination extends local_plugins_class_base implements renderable {

    protected $page;
    protected $perpage;
    protected $limit;
    protected $totalpages;
    protected $sort;
    protected $direction = 'ASC';
    protected $plugins = null;
    protected $totalplugins = null;

    protected $conditions = array();
    protected $params = array();

    const VAR_PAGE = '_page';
    const VAR_LIMIT = '_limit';
    const VAR_SORT = '_sort';
    const VAR_DIRECTION = '_direction';

    public function __construct() {
        $this->page = (int)optional_param(self::VAR_PAGE, 1, PARAM_INT);
        $this->limit = (int)optional_param(self::VAR_LIMIT, 20, PARAM_INT);
        $this->sort = optional_param(self::VAR_SORT, null, PARAM_ALPHANUMEXT);
        if (!array_key_exists($this->sort, $this->get_sortable_fields())) {
            $this->sort = $this->get_default_sort();
        }
        $this->direction = optional_param(self::VAR_DIRECTION, 'ASC', PARAM_ALPHA);
        if ($this->direction !== 'ASC' && $this->direction !== 'DESC') {
            $this->direction = 'ASC';
        }
    }

    public function get_sortable_fields() {
        return array(
            'name' => get_string('name', 'local_plugins'),
            'lastmodified' => get_string('lastmodified', 'local_plugins'),
        );
    }

    public function get_plugins_sql() {

        if (count($this->conditions) > 0) {
            $conditions = join(' AND ', $this->conditions);
        } else {
            $conditions = '1 == 1';
        }

        $sql = 'SELECT p.*
                  FROM {local_plugins_plugin} p
                  WHERE '.$conditions.'
              ORDER BY '.$this->sort.' '.$this->direction;

        return array($sql, $this->params);
    }


    public function get_default_sort() {
        return 'timelastmodified';
    }

    /**
     *
     * @global moodle_database $DB
     * @return array
     */
    final protected function get_plugins() {
        global $DB;
        if ($this->plugins !== null) {
            return $this->plugins;
        }

        list($sql, $params) = $this->get_plugins_sql();

        $limitfrom = $this->limit * ($this->page-1);
        $rs = $DB->get_recordset_sql($sql, $params, $limitfrom, $this->limit);
        $plugins = array();
        $categories = local_plugins_helper::get_categories();
        foreach ($rs as $plugin) {
            $plugin->category = $categories[$plugin->categoryid];
            $plugins[$plugin->id] = new local_plugins_plugin($plugin);
        }
        $this->plugins = $plugins;
        return $this->plugins;
    }

    public function add_condition($where, $params) {
        $this->conditions[] = $where;
        $this->params = array_merge($this->params, $params);
    }

    public function set_totalplugins($count) {
        $this->totalplugins = (int)$count;
    }

    public function get_page_link($url, $page) {
        $url->param(self::VAR_PAGE, $page);
        if ($this->sort != $this->get_default_sort()) {
            $url->param(self::VAR_SORT, $this->sort);
        }
        if ($this->direction != 'ASC') {
            $url->param(self::VAR_DIRECTION, $this->sort);
        }
        if ($this->limit != 20) {
            $url->param(self::VAR_LIMIT, $this->limit);
        }
        return $url;
    }
}
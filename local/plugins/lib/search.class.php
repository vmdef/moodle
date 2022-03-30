<?php

define('LOCAL_PLUGINSSEARCH_PLUGINNAME',             1);
define('LOCAL_PLUGINSSEARCH_PLUGINSHORTDESCRIPTION', 2);
define('LOCAL_PLUGINSSEARCH_PLUGINDESCRIPTION',      4);
define('LOCAL_PLUGINSSEARCH_PLUGINDOCUMENTATION',    8);
define('LOCAL_PLUGINSSEARCH_PLUGINFAQS',            16);
define('LOCAL_PLUGINSSEARCH_PLUGINCOMMENTS',        32);
define('LOCAL_PLUGINSSEARCH_VERSIONRELEASENAME',    64);
define('LOCAL_PLUGINSSEARCH_VERSIONRELEASENOTES',  128);
define('LOCAL_PLUGINSSEARCH_PLUGINFRANKENSTYLE',   256);

class local_plugins_search implements renderable {

    protected $search = '';

    protected $page;
    protected $perpage;
    protected $options;

    protected $results = array();
    protected $resultcount = 0;
    protected $pages = 0;
    protected $pagingbar = null;

    public function __construct($search) {
        $this->search = $search;
        $this->page = (int)optional_param('p', 0, PARAM_INT);
        $this->perpage = (int)optional_param('l', 20, PARAM_INT);

        $default  = 0;
        $default += LOCAL_PLUGINSSEARCH_PLUGINNAME;
        $default += LOCAL_PLUGINSSEARCH_PLUGINSHORTDESCRIPTION;
        $default += LOCAL_PLUGINSSEARCH_PLUGINDESCRIPTION;
        $default += LOCAL_PLUGINSSEARCH_PLUGINFRANKENSTYLE;

        $this->options = (int)optional_param('o', $default, PARAM_INT);
    }

    public function get_searchstring() {
        return $this->search;
    }

    public function get_plugins() {
        return $this->results;
    }

    public function is_paginated() {
        return $this->pages > 0;
    }

    public function get_pagingbar(moodle_url $url) {
        if ($this->pagingbar === null && ($this->pages > 1)) {
            $url = clone $url;
            $url->param('s', $this->search);
            $url->param('p', $this->page);
            $url->param('l', $this->perpage);
            $url->param('o', $this->options);
            $this->pagingbar = new paging_bar($this->resultcount, $this->page, $this->perpage, $url, 'p');
        }
        return $this->pagingbar;
    }

    public function get_resultcount() {
        return $this->resultcount;
    }

    /**
     *
     * @global moodle_database $DB
     */
    public function search() {
        global $DB, $USER;

        if ($this->search === null) {
            return array();
        }

        $searchstring = '%'.$this->search.'%';

        list($viewsql, $viewjoin, $params) = local_plugins_helper::sql_plugin_view_check();
        $fields = array('p.*');
        $ands = array($viewsql);
        $ors = array();
        $joins = array($viewjoin);
        $havings = array();

        if ($this->options & LOCAL_PLUGINSSEARCH_PLUGINNAME) {
            $ors[] = $DB->sql_like('p.name', ':name', false);
            $params['name'] = $searchstring;
        }

        if ($this->options & LOCAL_PLUGINSSEARCH_PLUGINSHORTDESCRIPTION) {
            $ors[] = $DB->sql_like('p.shortdescription', ':shortdescription', false);
            $params['shortdescription'] = $searchstring;
        }

        if ($this->options & LOCAL_PLUGINSSEARCH_PLUGINDESCRIPTION) {
            $ors[] = $DB->sql_like('p.description', ':description', false);
            $params['description'] = $searchstring;
        }

        if ($this->options & LOCAL_PLUGINSSEARCH_PLUGINDOCUMENTATION) {
            $ors[] = $DB->sql_like('p.documentation', ':documentation', false);
            $params['documentation'] = $searchstring;
        }
        if ($this->options & LOCAL_PLUGINSSEARCH_PLUGINFAQS) {
            $ors[] = $DB->sql_like('p.faqs', ':faqs', false);
            $params['faqs'] = $searchstring;
        }
        if ($this->options & LOCAL_PLUGINSSEARCH_PLUGINFRANKENSTYLE) {
            $ors[] = $DB->sql_like('p.frankenstyle', ':frankenstyle', false);
            $params['frankenstyle'] = $searchstring;
        }
        if ($this->options & LOCAL_PLUGINSSEARCH_PLUGINCOMMENTS) {
            $subwheres = array();
            $subwheres[] = 'contextid = :commentcontextid';
            $subwheres[] = 'commentarea = :commentarea';
            $subwheres[] = $DB->sql_like('c.content', ':content', false);
            $join[] = "LEFT JOIN (
                           SELECT c.itemid, COUNT(c.id) AS commentcount
                             FROM {comments} c
                            WHERE $subwheres
                         GROUP BY c.itemid
                       ) c ON c.itemid = p.id";
            $params['commentcontextid'] = SYSCONTEXTID;
            $params['commentarea'] = 'plugin_general';
            $params['content'] = $searchstring;
            $fields[] = 'c.commentcount';
            if ($this->options == LOCAL_PLUGINSSEARCH_PLUGINCOMMENTS) {
                $havings[] = 'HAVING c.commentcount > 0';
            }
        }
        if ($this->options & LOCAL_PLUGINSSEARCH_VERSIONRELEASENAME || $this->options & LOCAL_PLUGINSSEARCH_VERSIONRELEASENOTES) {
            $subwheres = array();
            if ($this->options & LOCAL_PLUGINSSEARCH_VERSIONRELEASENAME) {
                $subwheres[] = $DB->sql_like('v.releasename', ':releasename', false);
                $params['releasename'] = $searchstring;
            }
            if ($this->options & LOCAL_PLUGINSSEARCH_VERSIONRELEASENOTES) {
                $subwheres[] = $DB->sql_like('v.releasenotes', ':releasenotes', false);
                $params['releasenotes'] = $searchstring;
            }
            if ($this->options == LOCAL_PLUGINSSEARCH_VERSIONRELEASENAME || $this->options == LOCAL_PLUGINSSEARCH_VERSIONRELEASENOTES || $this->options == LOCAL_PLUGINSSEARCH_VERSIONRELEASENAME + LOCAL_PLUGINSSEARCH_VERSIONRELEASENOTES) {
                $havings[] = 'HAVING v.versioncount > 0';
            }
            $subwheres = join(' OR ', $subwheres);
            $join[] = "LEFT JOIN (
                           SELECT v.pluginid, COUNT(v.id) AS versioncount
                             FROM {local_plugins_vers} v
                            WHERE $subwheres
                         GROUP BY v.pluginid
                       ) v ON v.pluginid = p.id";
        }

        $fields = join(', ', $fields);
        $joins  = join("\n", $joins);
        $ands  = join(" AND\n", $ands);
        $ors  = join(" OR\n", $ors);
        $havings  = join(" ", $havings);
        $sql = "SELECT $fields
                  FROM {local_plugins_plugin} p
                       $joins
                 WHERE $ands AND
                       ($ors)
                       $havings
              ORDER BY COALESCE(p.aggfavs, 0) DESC, p.timelastreleased DESC, p.id DESC";
        $limit = $this->perpage;
        $offset = $this->page * $limit;

        $plugins = $DB->get_records_sql($sql, $params, $offset, $limit);
        $this->results = local_plugins_helper::load_plugins_from_result($plugins);

        if (count($this->results) >= $limit) {
            $sql = "SELECT COUNT(p.id) AS resultcount
                      FROM {local_plugins_plugin} p
                           $joins
                     WHERE $ands AND
                           ($ors)
                           $havings";
            $this->resultcount = $DB->count_records_sql($sql, $params);
        } else {
            $this->resultcount = $offset + count($this->results);
        }
        $this->pages = ceil($this->resultcount / $limit);

        return (count($this->results) > 0);
    }
}
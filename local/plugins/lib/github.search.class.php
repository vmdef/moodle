<?php

define('LOCAL_PLUGINSSEARCH_CODE',             1);

class local_plugins_search_github extends local_plugins_search implements renderable {
    protected $ghapireposmatched = 0;
    public function __construct($search) {
        $this->search = $search;
        $this->page = (int)optional_param('p', 0, PARAM_INT);
        $this->perpage = (int)optional_param('l', 30, PARAM_INT); //seems github returns message: server error above when 40 repos sent
        if ($this->perpage > 30) {
            $this->perpage = 30;
        }
        $default  = 0;
        $default += LOCAL_PLUGINSSEARCH_CODE;

        $this->options = (int)optional_param('o', $default, PARAM_INT);
    }

    public function get_resultcount() {
        return 'Total of '. $this->ghapireposmatched. ' github repositories in this page (perpage='.$this->perpage.') have matching ';
    }

    public function get_pagingbar(moodle_url $url) {
        if ($this->pagingbar === null && ($this->pages > 1)) {
            $url = clone $url;
            $url->param('s', $this->search);
            $url->param('p', $this->page);
            $url->param('l', $this->perpage);
            $url->param('search', 'Search code');
            $url->param('o', $this->options);
            $this->pagingbar = new paging_bar($this->resultcount, $this->page, $this->perpage, $url, 'p');
        }
        return $this->pagingbar;
    }
    public function get_plugins() {
        return $this->results;
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

        $ands[] = $DB->sql_like('p.sourcecontrolurl', ':srcrepourl', false);
        $params['srcrepourl'] = '%github.com%';

//        if ($this->options & LOCAL_PLUGINSSEARCH_PLUGINSHORTDESCRIPTION) {
//            $ors[] = $DB->sql_like('p.shortdescription', ':shortdescription', false);
//            $params['shortdescription'] = $searchstring;
//        }

        $fields = join(', ', $fields);
        $joins  = join("\n", $joins);
        $ands  = join(" AND\n", $ands);
        $ors  = join(" OR\n", $ors);
        $havings  = join(" ", $havings);
        $sql = "SELECT $fields
                  FROM {local_plugins_plugin} p
                       $joins
                 WHERE $ands"; // AND
//                       ($ors)
//                       $havings";
        $limit = $this->perpage;
        $offset = $this->page * $limit;

        $plugins = $DB->get_records_sql($sql, $params, $offset, $limit);
        $this->results = local_plugins_helper::load_plugins_from_result($plugins);

        if (count($this->results) >= $limit) {
            $sql = "SELECT COUNT(p.id) AS resultcount
                      FROM {local_plugins_plugin} p
                           $joins
                     WHERE $ands"; // AND
//                           ($ors)
//                           $havings";
            $this->resultcount = $DB->count_records_sql($sql, $params);
        } else {
            $this->resultcount = $offset + count($this->results);
        }
        $this->pages = ceil($this->resultcount / $limit);

        // generate shortrepo array
        $shortrepo = array();
        foreach ($this->results as $plugin) {
                $repoexplode = explode('github.com/', $plugin->sourcecontrolurl);
                $shortrepo[$plugin->id] = end($repoexplode); //the user/repo/ part
        }
        $found = $this->githubsearch($shortrepo, $this->search);
        $matchedplugins = array();
        // map the plugin ids with shortrepo.
        foreach ($found['items'] as $repo => $info) {
            foreach ($shortrepo as $id => $idrepo) {
                if ( $repo === $idrepo ) {
                    $this->results[$id]->searchinfo = $info; //set info on plugin
                    $matchedplugins[$id] = $plugins[$id];
                }
            }
        }

        //filter results quickly.
        $this->results = local_plugins_helper::load_plugins_from_result($matchedplugins);

        return true; // always return true for pagination. (count($this->results) > 0);
    }
    /**
     * An search of multiple repositories with a single search string.
     *
     * Based on the following resources:
     * https://help.github.com/articles/searching-repositories#users-organizations-and-repositories
     * https://api.github.com/search/code?q=octokit+in:file+extension:gemspec+-repo:octokit/octokit.rb&sort=indexed
     * http://developer.github.com/v3/search/#search-repositories
     *
     * @param array $shortrepo Array of shortened github repositories
     * @param type $search Search string.
     * @return array Search 'total' and 'items'. items contain an array of reposhort names which contain search results and direct github irl to search as an object within.
     */
    public function githubsearch($shortrepo, $search) {

        $searchapi = 'https://api.github.com/search/code';
        $searchweburl = 'https://github.com/search';
        $reposearch = '+repo:';
        $reposearch .= join('+repo:', array_values($shortrepo));
        $param['q'] = $search. '+in:file+extension:php'. $reposearch; //search terms 
        $param['sort'] = 'indexed';
        $param['fork'] = 0;
        $searchquery = '?q='. $param['q']. '&sort='. $param['sort']. '&fork='. $param['fork'];
        // Initializing curl
        $ch = curl_init();
        // Configuring curl options
        $options = array(
        CURLOPT_RETURNTRANSFER => true,
//        CURLOPT_USERPWD => $username . ":" . $password,   // authentication
        CURLOPT_HTTPHEADER => array('Content-type: application/json', 'Accept: application/vnd.github.preview') ,
        CURLOPT_URL => $searchapi. $searchquery,
        );
        // Setting curl options
        curl_setopt_array( $ch, $options );

        // Getting results
        $result =  json_decode(curl_exec($ch)); // Getting jSON result string

        // return array of total count and link to github's search page with same query , eg:
        // https://github.com/search?q=repo%3Amoodle%2Fmoodle+repo%3Atinymce%2Ftinymce+is_archetypal&type=Code&ref=searchresults
        $found = array('total' => array('num' => $result->total_count, 'url' => $searchweburl. $searchquery. '&type=Code&ref=searchresults'));

        //group items into repositories.
        $reporesults = array();
        foreach ($result->items as $itemno => $item) {
            foreach ($shortrepo as $pluginid => $repo) {
                if($repo == $item->repository->full_name || $repo == $item->repository->full_name.'/') {
                    $reporesults[$repo][$itemno] = $item;
                }
            }
        }
        $this->ghapireposmatched = count($reporesults);

        //genereate individual search links.

        $param['q'] = $search. '+in:file+extension:php'. $reposearch;
        $param['sort'] = 'indexed';
        $param['fork'] = 0;
        foreach ($reporesults as $repo => $items) {
            $reposearch = '+repo:'. $repo;
            $param['q'] = $search. '+in:file+extension:php'. $reposearch;
            $searchquery = '?q='. $param['q']. '&sort='. $param['sort']. '&fork='. $param['fork'];
            $rs = new stdClass();
            $rs->items = $items;
            $rs->searchurl = $searchweburl. $searchquery. '&type=Code&ref=searchresults';
            $reporesults[$repo] = $rs;
        }
        $found['items'] = $reporesults;
                // @todo return individual plugins listing with a link to html_url of file in github. (figure out how to highlight search word here).

        return $found;
    }
}
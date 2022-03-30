<?php

class local_plugins_user_sites_report extends local_plugins_report_base {

    protected $defaultsort = 'timecreated';

    protected function get_userid_value() {
        global $USER;

        // For now, display just the USER's sites. In the future, we may want
        // to extend this to be able to allow admins to see other users' sites.
        return $USER->id;
    }

    protected function get_report_title() {
        return get_string('report_usersites', 'local_plugins');
    }

    protected function get_report_description() {
        return get_string('report_usersitesdesc', 'local_plugins');
    }

    public function can_view() {
        if (!isloggedin() or isguestuser()) {
            return false;
        }

        return true;
    }

    public function quick_access() {
        return true;
    }

    protected function define_columns() {
        $pluginid = optional_param('pluginid', 0, PARAM_ALPHANUM);
        $installcolstr = '';
        if($pluginid) {
            $plugin = local_plugins_helper::get_plugin($pluginid);
            $installcolstr = html_writer::link($plugin->viewlink, $plugin->formatted_name);
        }
        $columns = array(
             'install'     => new local_plugins_report_column($this, 'install', $installcolstr),
             'sitename'      => new local_plugins_report_column($this, 'sitename', get_string('sitename', 'local_plugins'), null, true),
             'version'     => new local_plugins_report_column($this, 'version', get_string('version', 'local_plugins'), null, true),
             'siteurl' => new local_plugins_report_column($this, 'siteurl', get_string('siteurl', 'local_plugins'), null, true),
             'actions'     => new local_plugins_report_column($this, 'actions', $this->actions_title()),
         );
        return $columns;
    }

    protected function fetch_count() {
        global $DB;
        $sitessql = "SELECT count(s.id) ".
            " FROM {local_plugins_usersite} s".
            " WHERE s.userid = :userid";
        return $DB->count_records_sql($sitessql, array('userid' => $this->get_userid_value()));
    }

    /**
     *
     * @global moodle_database $DB
     */
    protected function fetch_data() {
        global $DB, $USER;
        $pluginid = optional_param('pluginid', 0, PARAM_ALPHANUM);

        $orderby = array('s.sitename '.$this->sortdir);
        if (!empty($this->sort) && array_key_exists($this->sort, $this->get_columns())) {
            switch ($this->sort) {
//                case 'sitename':
//                    array_unshift($orderby, 's.sitename '.$this->sortdir);
//                    break;
                case 'siteurl':
                    array_unshift($orderby, 's.siteurl '.$this->sortdir);
                    array_unshift($orderby, 's.version '.$this->sortdir);
                    break;
                case 'version': //grr this was later made to be asoftwareversionid
                    array_unshift($orderby, 's.version '.$this->sortdir);
                    break;
            }
        }

        $select = "SELECT s.* ";
        $sql    = 'FROM {local_plugins_usersite} s '.
                  'WHERE s.userid = '. $this->get_userid_value(). '
                   ORDER BY '.join(', ', $orderby);
        $data = $DB->get_records_sql($select.$sql, null, $this->page * $this->perpage, $this->perpage);
        $mversions = local_plugins_helper::get_moodle_versions();
        $moodlesoftwareversions = local_plugins_helper::get_software_versions_options($mversions);
        foreach ($data as $id => $row) {
            $usersite = local_plugins_helper::get_usersite($id);
            $data[$id]->sitename = $row->sitename;
            $data[$id]->siteurl = $row->siteurl;
            $releasenames = $moodlesoftwareversions['Moodle']->releasenames;
            if (!isset($mversions[$row->version])) {
                $data[$id]->version = '*';
            } else if ($mversions[$row->version]->version >= '2013040500') {
                $data[$id]->version = $releasenames[$row->version];
            } else {
                $data[$id]->version = $releasenames[$row->version]. ' *';
            }
            $data[$id]->actions = $this->action_buttons($id, $row->siteurl);
            $data[$id]->install = $this->install_button($usersite, $pluginid);
        }

        $rowcount = count($data);
        if ($rowcount < $this->perpage && $this->totalrows === null) {
            // to prevent from running extra query
            $this->totalrows = $rowcount;
        }

        return $data;
    }

    protected function action_button($url, $name) {
        $form  = html_writer::start_tag('form', array('method' => 'post', 'action' => $url));
        foreach ($this->get_url()->params() as $key => $value) {
            $form .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $key,  'value' => $value));
        }
        $form .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => $name));
        $form .= html_writer::end_tag('form');
        return $form;
    }

    protected function install_button($usersite, $pluginid) {
        if ($pluginid) {
            //$renderer = local_plugins_get_renderer();
            $plugin = local_plugins_helper::get_plugin($pluginid);
            $url = $plugin->get_install_link($usersite);
            if (!empty($url)) {
                return local_plugins_helper::get_install_button($url);
            }
        }
        return '';
    }
    protected function action_buttons($id) {
        global $OUTPUT;
        $output = '';
        $url = new local_plugins_url('/local/plugins/report/user_sites/site.php', array('id' => $id, 'action' => 'edit'));
        $output .= $OUTPUT->action_icon($url, new pix_icon('t/edit', get_string('edit')));
        $url = new local_plugins_url('/local/plugins/report/user_sites/site.php', array('id' => $id, 'action' => 'delete'));
        $output .= $OUTPUT->action_icon($url, new pix_icon('t/delete', get_string('delete')));
        return $output;
    }

    protected function actions_title() {
        global $OUTPUT;
        $url = new local_plugins_url('/local/plugins/report/user_sites/site.php', array('action' => 'add'));
        return $OUTPUT->action_icon($url, new pix_icon('t/add', get_string('addsite', 'local_plugins')));
    }
}

<?php

class local_plugins_unapproved_versions_report extends local_plugins_report_base {

    protected $defaultsort = 'timecreated';

    protected function get_approved_value() {
        return local_plugins_plugin::PLUGIN_UNAPPROVED;
    }

    protected function get_report_title() {
        return get_string('report_unapprovedversions', 'local_plugins');
    }
    protected function get_report_description() {
        return get_string('report_unapprovedversionsdesc', 'local_plugins');
    }
    public function can_view() {
        return has_all_capabilities(array(local_plugins::CAP_VIEWUNAPPROVED, local_plugins::CAP_APPROVEPLUGINVERSION), context_system::instance());
    }
    protected function define_columns() {
       $columns = array(
            'plugin'      => new local_plugins_report_column($this, 'plugin', get_string('plugin', 'local_plugins'), null, true),
            'version'     => new local_plugins_report_column($this, 'version', get_string('version', 'local_plugins'), null, true),
            'timecreated' => new local_plugins_report_column($this, 'timecreated', get_string('timecreated', 'local_plugins'), null, true),
            'creator'     => new local_plugins_report_column($this, 'creator', get_string('creator', 'local_plugins'), null, true),
            'actions'     => new local_plugins_report_column($this, 'actions', ''),
        );
       return $columns;
    }
    protected function fetch_count() {
        global $DB;
        $versionssql = "SELECT count(v.id) ".
            " FROM {local_plugins_plugin} p, {local_plugins_vers} v".
            " WHERE v.pluginid = p.id".
            " AND p.approved = ". local_plugins_plugin::PLUGIN_APPROVED.
            " AND v.approved = :approved";
        return $DB->count_records_sql($versionssql, array('approved' => $this->get_approved_value()));
    }
    /**
     *
     * @global moodle_database $DB
     */
    protected function fetch_data() {
        global $DB;

        $orderby = array('timecreated '.$this->sortdir);
        if (!empty($this->sort) && array_key_exists($this->sort, $this->get_columns())) {
            switch ($this->sort) {
                case 'plugin':
                    array_unshift($orderby, 'p.name '.$this->sortdir);
                    break;
                case 'version':
                    array_unshift($orderby, 'v.releasename '.$this->sortdir);
                    array_unshift($orderby, 'v.version '.$this->sortdir);
                    break;
                case 'creator':
                    array_unshift($orderby, 'u.lastname '.$this->sortdir);
                    array_unshift($orderby, 'u.firstname '.$this->sortdir);
                    break;
            }
        }

        $userfields = \core_user\fields::for_name()->get_sql('u')->selects;
        $select = "SELECT v.id, v.pluginid, v.releasename, v.version, v.timecreated, p.name $userfields ";
        $sql    = 'FROM {local_plugins_vers} v
                   LEFT JOIN {local_plugins_plugin} p ON p.id = v.pluginid
                   LEFT JOIN {user} u ON u.id = v.userid
                   WHERE v.approved = '. $this->approved_value. '
                       AND p.approved = '. local_plugins_plugin::PLUGIN_APPROVED. '
                   ORDER BY '.join(', ', $orderby);
        $data = $DB->get_records_sql($select.$sql, null, $this->page * $this->perpage, $this->perpage);
        foreach ($data as $id => $row) {
            $data[$id]->plugin = html_writer::link(new local_plugins_url('/local/plugins/view.php', array('id' => $row->pluginid)), s($row->name));
            $data[$id]->creator = fullname($row);
            $data[$id]->timecreated = userdate($row->timecreated);
            $data[$id]->version = html_writer::link(new local_plugins_url('/local/plugins/pluginversion.php', array('id' => $id)), s($row->releasename.' ('.$row->version.')'));
            $data[$id]->actions = $this->action_buttons($id);
            unset($data[$id]->id);
            unset($data[$id]->firstname);
            unset($data[$id]->lastname);
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

    protected function action_buttons($id) {
        $output = '';
        $canapprove = has_capability(local_plugins::CAP_APPROVEPLUGIN, context_system::instance());
        if ($canapprove) {
            $url = new local_plugins_url('/local/plugins/report/unapproved_versions/approve.php', array('id' => $id, 'approve' => local_plugins_plugin::PLUGIN_APPROVED));
            $output .= $this->action_button($url, get_string('approve', 'local_plugins'));
        }
        return $output;
    }
}

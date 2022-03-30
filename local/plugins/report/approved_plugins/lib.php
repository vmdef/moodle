<?php

class local_plugins_approved_plugins_report extends local_plugins_report_base {

    protected function get_approved_value() {
        return local_plugins_plugin::PLUGIN_APPROVED;
    }

    protected function get_approvals_start_time() {
        $strtime = optional_param('t', '', PARAM_TEXT);
        $timestamp = strtotime($strtime);
        if ($timestamp > time()) {
            $timestamp = strtotime('-'. $strtime);
        }
        if ($timestamp) {
            return $timestamp;
        }
        return strtotime('-1 week');
    }

    protected function get_report_title() {
        return get_string('report_approvedplugins', 'local_plugins');
    }

    protected function get_report_description() {
        $since = $this->get_approvals_start_time();
        return '<p>'.get_string('report_approvedpluginsdesc', 'local_plugins').'</p>
                <p>'.get_string('report_approvedpluginssince', 'local_plugins', userdate($since, '%d/%m/%Y %H:%M')).' (
                <a href="index.php?report=approved_plugins&t=-1week">1 week</a> |
                <a href="index.php?report=approved_plugins&t=-3weeks">3 weeks</a> |
                <a href="index.php?report=approved_plugins&t=-1month">1 month</a> |
                <a href="index.php?report=approved_plugins&t=-3months">3 months</a>
                )</p>';
    }

    public function can_view() {
        return true;
    }

    public function quick_access() {
        return false;
    }

    protected function define_columns() {
       $columns = array(
            'name'             => new local_plugins_report_column($this, 'name', get_string('name', 'local_plugins'), null, true),
            'category'         => new local_plugins_report_column($this, 'category', get_string('category', 'local_plugins'), null, true),
            'shortdescription' => new local_plugins_report_column($this, 'shortdescription', get_string('shortdescription', 'local_plugins')),
            'leadmaintainer'   => new local_plugins_report_column($this, 'leadmaintainer', get_string('leadmaintainer', 'local_plugins'), null, true),
            'timeapproved'     => new local_plugins_report_column($this, 'timefirstapproved', get_string('timefirstapproved', 'local_plugins'), null, true),
        );
       return $columns;
    }

    protected function fetch_count() {
        global $DB;
        $windowstart = $this->get_approvals_start_time();
        $pluginssql = "SELECT count(id) FROM {local_plugins_plugin} WHERE approved = :approved and timefirstapproved IS NOT NULL AND timefirstapproved > :time";
        return $DB->count_records_sql($pluginssql, array('approved' => $this->get_approved_value(), 'time' => $windowstart));
    }

    public function registration_queue_count() {
        return $this->fetch_count();
    }

    /**
     *
     * @global moodle_database $DB
     */
    protected function fetch_data() {
        global $DB;

        $orderby = array('p.timefirstapproved '.$this->sortdir);
        if (!empty($this->sort) && array_key_exists($this->sort, $this->get_columns())) {
            switch ($this->sort) {
                case 'category':
                    array_unshift($orderby, 'c.name '.$this->sortdir);
                    break;
                case 'name':
                    array_unshift($orderby, 'p.name '.$this->sortdir);
                    break;
                case 'leadmaintainer':
                    array_unshift($orderby, 'u.firstname '.$this->sortdir);
                    array_unshift($orderby, 'u.lastname '.$this->sortdir);
                    break;
            }
        }

        $userfields = \core_user\fields::for_name()->get_sql('u')->selects;
        $select = "SELECT p.id, p.name, p.shortdescription, p.timefirstapproved, c.name AS category $userfields ";
        $sql    = "FROM {local_plugins_plugin} p
                   LEFT JOIN (
                       SELECT lpc.pluginid $userfields
                       FROM {local_plugins_contributor} lpc
                       LEFT JOIN {user} u ON u.id = lpc.userid
                       WHERE lpc.maintainer = ". local_plugins_contributor::LEAD_MAINTAINER. "
                   ) u ON u.pluginid = p.id
                   LEFT JOIN {local_plugins_category} c ON c.id = p.categoryid
                   WHERE p.approved = ".$this->get_approved_value()." and p.timefirstapproved is not null and p.timefirstapproved > ". $this->get_approvals_start_time(). "
                   ORDER BY ".join(', ', $orderby);
        $data = $DB->get_records_sql($select.$sql, null, $this->page * $this->perpage, $this->perpage);
        foreach ($data as $id => $row) {
            $data[$id]->leadmaintainer = fullname($row);
            $data[$id]->timefirstapproved = userdate($row->timefirstapproved, '%d/%m/%Y %H:%M');
            $data[$id]->name = html_writer::link(new local_plugins_url('/local/plugins/view.php', array('id' => $id)), s($row->name));
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
}
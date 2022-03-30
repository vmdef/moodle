<?php

class local_plugins_unapproved_plugins_report extends local_plugins_report_base {

    protected $defaultsort = 'reviewtype ASC, timequeuing DESC';

    protected function get_approved_value() {
        return local_plugins_plugin::PLUGIN_UNAPPROVED;
    }

    protected function get_report_title() {
        return get_string('report_unapprovedplugins', 'local_plugins');
    }

    protected function get_report_description() {
        return get_string('report_unapprovedpluginsdesc', 'local_plugins');
    }

    public function can_view() {
        return has_capability(local_plugins::CAP_VIEWUNAPPROVED, context_system::instance());
    }

    protected function define_columns() {
       $columns = array(
            'name'             => new local_plugins_report_column($this, 'name', get_string('name', 'local_plugins'), null, true),
            'category'         => new local_plugins_report_column($this, 'category', get_string('category', 'local_plugins'), null, true),
            'leadmaintainer'   => new local_plugins_report_column($this, 'leadmaintainer', get_string('leadmaintainer', 'local_plugins'), null, true),
            'reviewtype'       => new local_plugins_report_column($this, 'reviewtype', get_string('reviewtype', 'local_plugins'), null, true),
            'timequeuing'      => new local_plugins_report_column($this, 'timequeuing', get_string('timequeuing', 'local_plugins'), null, true),
            'timecreated'      => new local_plugins_report_column($this, 'timecreated', get_string('timecreated', 'local_plugins'), null, true),
            'timelastmodified' => new local_plugins_report_column($this, 'timelastmodified', get_string('lastmodified', 'local_plugins'), null, true),
            'comments'         => new local_plugins_report_column($this, 'comments', get_string('comments')),
        );
       return $columns;
    }

    protected function fetch_count() {
        global $DB;
        $pluginssql = "SELECT count(id) FROM {local_plugins_plugin} WHERE approved = :approved";
        return $DB->count_records_sql($pluginssql, array('approved' => $this->get_approved_value()));
    }

    public function registration_queue_count() {
        return $this->fetch_count();
    }

    protected function fetch_data() {
        global $DB, $OUTPUT;

        $orderby = array($this->defaultsort);
        if (!empty($this->sort) && array_key_exists($this->sort, $this->get_columns())) {
            switch ($this->sort) {
                case 'name':
                    array_unshift($orderby, 'p.name '.$this->sortdir);
                    break;
                case 'category':
                    array_unshift($orderby, 'c.name '.$this->sortdir);
                    break;
                case 'leadmaintainer':
                    array_unshift($orderby, 'u.firstname '.$this->sortdir);
                    array_unshift($orderby, 'u.lastname '.$this->sortdir);
                    break;
                case 'reviewtype':
                    array_unshift($orderby, 'reviewtype '.$this->sortdir);
                    break;
                case 'timequeuing':
                    array_unshift($orderby, 'timequeuing '.$this->sortdir);
                    break;
                case 'timecreated':
                    array_unshift($orderby, 'p.timecreated '.$this->sortdir);
                    break;
                case 'timelastmodified':
                    array_unshift($orderby, 'p.timelastmodified '.$this->sortdir);
                    break;
            }
        }

        $userfields1 = join(',', array_map(function ($field) {
            return 'user' . $field;
        }, \core_user\fields::for_name()->get_required_fields()));

        $userfields2 = \core_user\fields::for_name()->get_sql('u', false, 'user')->selects;

        $now = time();

        $sql = "SELECT p.id, p.name, p.timecreated, p.timelastmodified, p.timefirstapproved, c.name AS category, $userfields1,
                       CASE WHEN p.timelastapprovedchange IS NULL THEN 0 ELSE 1 END AS reviewtype,
                       COALESCE($now - p.timelastapprovedchange, $now - p.timecreated) AS timequeuing
                  FROM {local_plugins_plugin} p
                       LEFT JOIN (
                           SELECT lpc.pluginid $userfields2
                           FROM {local_plugins_contributor} lpc
                           LEFT JOIN {user} u ON u.id = lpc.userid
                           WHERE lpc.maintainer = ". local_plugins_contributor::LEAD_MAINTAINER. "
                       ) u ON u.pluginid = p.id
                       LEFT JOIN {local_plugins_category} c ON c.id = p.categoryid
                 WHERE p.approved = ".$this->get_approved_value()."
              ORDER BY ".join(', ', $orderby);
        $data = $DB->get_records_sql($sql, null, $this->page * $this->perpage, $this->perpage);
        foreach ($data as $id => $row) {
            $data[$id]->name = html_writer::link(
                new local_plugins_url('/local/plugins/pluginversions.php', ['id' => $id, 'validation' => 1]),
                s($row->name)
            );
            $leadmaintainer = user_picture::unalias($row, null, 'userid', 'user');
            $data[$id]->leadmaintainer = s(fullname($leadmaintainer));
            $data[$id]->reviewtype = get_string('reviewtype'.$row->reviewtype, 'local_plugins');
            $data[$id]->timequeuing = format_time($row->timequeuing);
            $data[$id]->timecreated = userdate($row->timecreated, '%d/%m/%Y %H:%M');
            $data[$id]->timelastmodified = userdate($row->timelastmodified, '%d/%m/%Y %H:%M');
            unset($data[$id]->id);
            unset($data[$id]->firstname);
            unset($data[$id]->lastname);

            $comments = local_plugins_helper::comment_for_plugin($id);
            $data[$id]->comments = $comments->count();
        }

        $rowcount = count($data);
        if ($rowcount < $this->perpage && $this->totalrows === null) {
            // to prevent from running extra query
            $this->totalrows = $rowcount;
        }

        return $data;
    }
}
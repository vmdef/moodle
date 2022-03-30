<?php

class local_plugins_approval_reviews_report extends local_plugins_report_base {

    protected function get_start_time() {
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
        return get_string('report_approval_reviews', 'local_plugins');
    }

    protected function get_report_description() {
        $since = $this->get_start_time();
        return '<p>'.get_string('report_approval_reviews_desc', 'local_plugins').'</p>
                <p>'.get_string('report_approval_reviews_since', 'local_plugins', userdate($since, '%d/%m/%Y %H:%M')).'</p>';
    }

    public function can_view() {
        return has_capability(local_plugins::CAP_APPROVEPLUGIN, context_system::instance());
    }

    public function quick_access() {
        return false;
    }

    protected function define_columns() {
        return array(
            'name'             => new local_plugins_report_column($this, 'name', get_string('name', 'local_plugins')),
            'category'         => new local_plugins_report_column($this, 'category', get_string('category', 'local_plugins')),
            'shortdescription' => new local_plugins_report_column($this, 'shortdescription', get_string('shortdescription', 'local_plugins')),
            'leadmaintainer'   => new local_plugins_report_column($this, 'leadmaintainer', get_string('leadmaintainer', 'local_plugins')),
            'time'             => new local_plugins_report_column($this, 'time', get_string('time')),
            'reviewer'         => new local_plugins_report_column($this, 'reviewer', get_string('reviewer', 'local_plugins')),
            'status'           => new local_plugins_report_column($this, 'status', get_string('status', 'local_plugins')),
        );
    }

    protected function fetch_count() {
        return count($this->get_affected_plugins());
    }

    /**
     *
     * @global moodle_database $DB
     */
    protected function fetch_data() {
        global $DB;

        $plugins = $this->get_affected_plugins();

        if (empty($plugins)) {
            return array();
        }

        $userfields = \core_user\fields::for_name()->get_sql('u')->selects;
        list($insql, $params) = $DB->get_in_or_equal(array_keys($plugins));

        $sql = "SELECT p.id AS pluginid, p.name, p.shortdescription, c.name AS category $userfields
                  FROM {local_plugins_plugin} p
                  JOIN (
                       SELECT lpc.pluginid $userfields
                         FROM {local_plugins_contributor} lpc
                         JOIN {user} u ON u.id = lpc.userid
                        WHERE lpc.maintainer = ".local_plugins_contributor::LEAD_MAINTAINER."
                       ) u ON u.pluginid = p.id
                  JOIN {local_plugins_category} c ON c.id = p.categoryid
                 WHERE p.id $insql
              ORDER BY p.id DESC";
        $data = $DB->get_records_sql($sql, $params, $this->page * $this->perpage, $this->perpage);

        foreach ($data as $id => $row) {
            $data[$id]->leadmaintainer = fullname($row);
            $data[$id]->reviewer = fullname($plugins[$id]);
            $data[$id]->time = userdate($plugins[$id]->time, '%d/%m/%Y %H:%M');
            $data[$id]->name = html_writer::link(new local_plugins_url('/local/plugins/view.php', array('id' => $id)), s($row->name));
            $data[$id]->status = $plugins[$id]->statusnew;
            unset($data[$id]->id);
        }

        $rowcount = count($data);
        if ($rowcount < $this->perpage && $this->totalrows === null) {
            // to prevent from running extra query
            $this->totalrows = $rowcount;
        }

        return $data;
    }

    protected function get_affected_plugins() {
        global $DB;

        $userfields = \core_user\fields::for_name()->get_sql('u')->selects;

        $sql = "SELECT l.pluginid, l.time, l.userid, l.info $userfields
                  FROM {local_plugins_log} l
                  JOIN {user} u ON l.userid = u.id
                 WHERE time >= :timestart AND action = 'plugin-plugin-edit'";

        $rs = $DB->get_recordset_sql($sql, array('timestart' => $this->get_start_time()));

        $plugins = array();

        foreach ($rs as $record) {
            $info = unserialize($record->info);
            if (isset($info['oldvalue']['status']) and isset($info['newvalue']['status'])
                    and $info['oldvalue']['status'] !== $info['newvalue']['status']
                    and in_array($info['oldvalue']['status'], array('Available', 'Needs more work', 'Waiting for approval'))
                    and in_array($info['newvalue']['status'], array('Available', 'Needs more work'))) {
                if (!isset($plugins[$record->pluginid]) or $plugins[$record->pluginid]->time < $record->time) {
                    $plugins[$record->pluginid] = (object)array(
                        'time' => $record->time,
                        'statusold' => $info['oldvalue']['status'],
                        'statusnew' => $info['newvalue']['status'],
                        'userid' => $record->userid
                    );
                    username_load_fields_from_object($plugins[$record->pluginid], $record);
                }
            }
        }
        $rs->close();

        return $plugins;
    }
}
<?php

require_once($CFG->dirroot.'/local/plugins/report/unapproved_plugins/lib.php');

class local_plugins_pendingapproval_plugins_report extends local_plugins_unapproved_plugins_report {

    protected function get_approved_value() {
        return local_plugins_plugin::PLUGIN_PENDINGAPPROVAL;
    }

    protected function get_report_title() {
        return get_string('report_pendingapprovalplugins', 'local_plugins');
    }

    protected function get_report_description() {
        return get_string('report_pendingapprovalpluginsdesc', 'local_plugins');
    }

    public function quick_access() {
        return true;
    }
}

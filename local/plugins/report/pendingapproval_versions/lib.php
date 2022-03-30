<?php

require_once($CFG->dirroot.'/local/plugins/report/unapproved_versions/lib.php');

class local_plugins_pendingapproval_versions_report extends local_plugins_unapproved_versions_report {
    protected function get_approved_value() {
        return local_plugins_plugin::PLUGIN_PENDINGAPPROVAL;
    }

    protected function get_report_title() {
        return get_string('report_pendingapprovalversions', 'local_plugins');
    }

    protected function get_report_description() {
        return get_string('report_pendingapprovalversionsdesc', 'local_plugins');
    }

    protected function action_buttons($id) {
        $output = '';
        $canapprove = has_capability(local_plugins::CAP_APPROVEPLUGIN, context_system::instance());
        if ($canapprove) {
            $url = new local_plugins_url('/local/plugins/report/unapproved_versions/approve.php', array('id' => $id, 'approve' => local_plugins_plugin::PLUGIN_APPROVED));
            $output .= $this->action_button($url, get_string('approve', 'local_plugins'));
            $url = new local_plugins_url('/local/plugins/report/unapproved_versions/approve.php', array('id' => $id, 'approve' => local_plugins_plugin::PLUGIN_UNAPPROVED));
            $output .= $this->action_button($url, get_string('disapprove', 'local_plugins'));
        }
        return $output;
    }
}


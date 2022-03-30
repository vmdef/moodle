<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * API access management page.
 *
 * @package     local_plugins
 * @copyright   2021 David Mudrák <david@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/plugins/lib/setup.php');
require_once($CFG->dirroot . '/webservice/lib.php');

$action = optional_param('action', null, PARAM_ALPHA);

$systemcontext = context_system::instance();
$usercontext = context_user::instance($USER->id);

$PAGE->set_url(new local_plugins_url('/local/plugins/api.php'));
$PAGE->set_context($systemcontext);
$PAGE->set_title(get_string('apiaccess', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');

require_login();
require_capability(local_plugins::CAP_EDITOWNPLUGINS, $systemcontext);
require_sesskey();

$output = local_plugins_get_renderer();
$webservice = new webservice();

$service = $DB->get_record('external_services', ['shortname' => 'plugins_maintenance', 'enabled' => 1]);

if (empty($service)) {
    throw new moodle_exception('servicenotavailable', 'webservice');
}

if ($action) {
    if (is_siteadmin()) {
        throw new moodle_exception('not_for_admins', 'local_plugins');
    }

    require_capability('moodle/webservice:createtoken', $systemcontext);

    if ($action === 'revoke' || $action === 'reset') {
        $DB->delete_records('external_tokens', [
            'userid' => $USER->id,
            'tokentype' => EXTERNAL_TOKEN_PERMANENT,
            'externalserviceid' => $service->id,
        ]);
    }

    if ($action === 'create' || $action === 'reset') {
        $token = external_generate_token_for_current_user($service);

        // Unset the expiration.
        $DB->set_field('external_tokens', 'validuntil', null, ['id' => $token->id]);
        $token->validuntil = null;

        external_log_token_request($token);
    }

    redirect(new local_plugins_url($PAGE->url, ['sesskey' => sesskey()]));
}

$token = $DB->get_record('external_tokens', [
    'userid' => $USER->id,
    'tokentype' => EXTERNAL_TOKEN_PERMANENT,
    'externalserviceid' => $service->id,
]);

echo $output->header($PAGE->title);
echo html_writer::div(get_string('apiaccessabout', 'local_plugins'));

if (empty($token)) {
    echo html_writer::div(get_string('apinotoken', 'local_plugins'), 'alert alert-info');

    if (is_siteadmin()) {
        echo html_writer::div('Site administrators cannot use this interface to manage access tokens. Please use the ' .
            'standard <a href="' . (new moodle_url('/admin/webservice/tokens.php'))->out() . '">Manage tokens</a> page.');

    } else if (has_capability('moodle/webservice:createtoken', $systemcontext)) {
        echo $output->single_button(
            new local_plugins_url($PAGE->url, ['action' => 'create']),
            get_string('apiaccesscreatetoken', 'local_plugins'),
            'post',
            ['primary' => 'true']
        );
    }

    echo $output->footer();
    die();
}

echo $output->heading(get_string('apiaccessdetails', 'local_plugins'), 3);

$endpoint = (new moodle_url('/webservice/rest/server.php'))->out();

$table = new html_table();
$table->data = [
    ['Web service endpoint', $endpoint],
    ['Web service name', $service->shortname],
    ['Web service token', $token->token],
];

echo html_writer::table($table);

if (is_siteadmin()) {
    echo html_writer::div('Site administrators cannot use this interface to manage access tokens. Please use the ' .
        'standard <a href="' . (new moodle_url('/admin/webservice/tokens.php'))->out() . '">Manage tokens</a> page.');

} else if (has_capability('moodle/webservice:createtoken', $systemcontext)) {
    echo $output->single_button(
        new local_plugins_url($PAGE->url, ['action' => 'reset']),
        get_string('apiaccesscreatetoken', 'local_plugins'),
        'post',
        ['primary' => 'true']
    );

    echo $output->single_button(
        new local_plugins_url($PAGE->url, ['action' => 'revoke']),
        get_string('apiaccessrevoketoken', 'local_plugins')
    );
}

echo $output->heading(get_string('apiaccessexample', 'local_plugins'), 3, 'mt-5');
echo html_writer::div("<pre>
ENDPOINT={$endpoint}
TOKEN={$token->token}
FUNCTION=local_plugins_get_maintained_plugins
FORMAT=json

curl -s \${ENDPOINT} --data \"wstoken=\${TOKEN}&wsfunction=\${FUNCTION}&moodlewsrestformat=\${FORMAT}\" | jq
</pre>", 'card p-3');

echo $output->footer();

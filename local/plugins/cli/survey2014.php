<?php

define('CLI_SCRIPT', 1);

require(__DIR__.'/../../../config.php');

$sql = "SELECT l.pluginid, l.userid, l.time, u.firstname, u.lastname, u.email, p.timefirstapproved, p.name
          FROM {local_plugins_log} l
          JOIN {user} u ON u.id = l.userid
          JOIN {local_plugins_plugin} p ON p.id = l.pluginid
         WHERE action = :action AND l.time >= :timestart AND l.time < :timeend
           AND l.pluginid NOT IN (1093,1092,1091,1090,1089,1088,1087,1086,1085,1084,1083,1082,1081,1080,1079,1078,1077,1076)
      ORDER BY l.time DESC";

$params = array(
    'action' => 'plugin-plugin-add',
    'timestart' => strtotime('2014-01-01T00:00:00UTC'),
    'timeend' => strtotime('2015-01-01T00:00:00UTC'),
);

$records = $DB->get_records_sql($sql, $params);

$users = array();

foreach ($records as $record) {
    if (!isset($users[$record->userid])) {
        $users[$record->userid] = (object)array(
            'userid' => $record->userid,
            'firstname' => $record->firstname,
            'lastname' => $record->lastname,
            'email' => $record->email,
            'plugins' => array(),
        );
    }
    $users[$record->userid]->plugins[$record->pluginid] = (object)array(
        'pluginid' => $record->pluginid,
        'name' => $record->name,
        'submitted' => $record->time,
        'timefirstapproved' => $record->timefirstapproved,
    );
}

$from = $DB->get_record('user', array('id' => 1601));

foreach ($users as $user) {

    $msg = 'Hi '.$user->firstname.',

we are curently running a survey among Moodle contributors who submitted their
plugin into the Moodle Plugins directory in the last year. The survey aims at
the plugin review and approval process. I would like to hear your feedback on
this topic and will appreciate if you find couple of minutes to answer the
survey.
';

    if (count($user->plugins) > 1) {
        $msg .= '
Our logs show that you submitted following plugins last year:

';
    } else {
        $msg .= '
Our logs show that you submitted following plugin last year:

';
    }

    foreach ($user->plugins as $plugin) {
        $msg .= "* ".$plugin->name." (https://moodle.org/plugins/view.php?id=".$plugin->pluginid.")\n";
    }

    $msg .= '
To answer the survey:

* Log in at http://dev.moodle.org/
* Enrol the \'Plugin approvals survey 2014\' course
  (http://dev.moodle.org/course/view.php?id=16)
* The course enrolment key is \'modularity\'
* Please answer the survey in the course

Thank you very much in advance for giving us the feedback. It will allow us to
reflect on and improve the plugin approval reviews.

Answering the survey should not take more than 5 minutes of your time. It will
be great if you manage to have the survey answered by Friday.

Thanks a lot again.

David Mudrák <david@moodle.com>
Community development manager
Moodle HQ
';


    $to = $DB->get_record('user', array('id' => $user->userid));
    $subject = 'Plugin approvals survey 2014';

    email_to_user($to, $from, $subject, $msg);

    echo 'e-mail sent to '.$to->email.PHP_EOL;
}

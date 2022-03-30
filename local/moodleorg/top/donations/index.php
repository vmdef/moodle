<?php

require('../../../../config.php');

$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('donations');
$PAGE->set_url(new moodle_url('/donations/'));
$PAGE->set_title('Moodle.org: donations');
$PAGE->set_heading(get_string('donationstitle', 'local_moodleorg'));
$PAGE->navbar->add($PAGE->heading, $PAGE->url);

echo $OUTPUT->header();
echo html_writer::start_tag('div');

echo html_writer::tag('h2', get_string('donationsmoodle', 'local_moodleorg'));
echo html_writer::tag('p', get_string('donationsopensource', 'local_moodleorg'), array('class'=>'lead'));
echo html_writer::tag('p', get_string('donationsdevelopment', 'local_moodleorg'));
echo html_writer::tag('p', get_string('donationsensure', 'local_moodleorg'));

echo '<table><tr>';
echo '<td><form style="display:inline;" method="post" action="https://www.paypal.com/cgi-bin/webscr">';
echo '<p><br /><input type="hidden" value="_xclick" name="cmd" />';
echo '<input type="hidden" value="donations@moodle.org" name="business" />';
echo '<input type="hidden" value="DONATION towards Moodle Development" name="item_name" />';
echo '<input type="hidden" value="https://moodle.org/donations/thankyou.php" name="return" />';
echo '<input type="hidden" value="https://moodle.org/" name="cancel_return" />';
echo '<input type="hidden" value="https://moodle.com/wp-content/uploads/Moodle-Logo-PayPal.png" name="image_url" />';
echo '<input type="hidden" name="cbt" value="Click here to add your name to the Donations Page" />';
echo '<input type="hidden" name="page_style" value="donations" />';
echo '<input type="hidden" name="rm" value="2" />';
echo '<input type="hidden" value="Moodle" name="item_number" />';
echo '<input type="hidden" value="Optional Information or Notes" name="cn" />';
echo html_writer::tag('button', get_string('donationsdonatenow', 'local_moodleorg'), array('type' => 'submit', 'value' => 'Donate', 'class' => 'btn btn-primary btn-large'));
echo '</p></form></td>';
echo '</tr></table>';

echo html_writer::tag('h3', get_string('donationsways', 'local_moodleorg'));
echo html_writer::start_tag('ul');
echo html_writer::tag('li', get_string('donationspartner', 'local_moodleorg'));
echo html_writer::tag('li', get_string('donationsassociation', 'local_moodleorg'));
echo html_writer::tag('li', get_string('donationsshop', 'local_moodleorg'));
echo html_writer::end_tag('ul');

echo html_writer::tag('h3', get_string('donationsthankyou', 'local_moodleorg'));
echo html_writer::tag('p', get_string('donationsthankeveryone', 'local_moodleorg'));

echo html_writer::end_tag('div');

echo "<br />";

$fromdate = time() - 31536000;

echo html_writer::start_tag('div');

$donations = array();

$bigdonations = $DB->get_records_select("register_donations", "timedonated > ? AND ".$DB->sql_cast_char2real('amount')." >= 1000", array($fromdate), "timedonated DESC");

foreach ($bigdonations as $key => $donation) {
    $donations[] = $donation;
}

$otherdonations = $DB->get_records_select("register_donations", "timedonated > ? AND ".$DB->sql_cast_char2real('amount')." >= 500 AND ".$DB->sql_cast_char2real('amount')." < 1000", array($fromdate), "timedonated DESC");

foreach ($otherdonations as $key => $donation) {
    $donations[] = $donation;
}

$otherdonations = $DB->get_records_select("register_donations", "timedonated > ? AND ".$DB->sql_cast_char2real('amount')." >= 200 AND ".$DB->sql_cast_char2real('amount')." < 500", array($fromdate), "timedonated DESC");

foreach ($otherdonations as $key => $donation) {
    $donations[] = $donation;
}

$otherdonations = $DB->get_records_select("register_donations", "timedonated > ? AND ".$DB->sql_cast_char2real('amount')." < 200", array($fromdate), "timedonated DESC");

foreach ($otherdonations as $key => $donation) {
    $donations[] = $donation;
}

if (!empty($donations)) {
    echo html_writer::start_tag('table', array('class' => 'table table-hover'));
    echo html_writer::start_tag('tr');
    echo html_writer::tag('th', get_string('donationsover1000', 'local_moodleorg'), array('colspan' => '2'));
    echo html_writer::end_tag('tr');
    foreach ($donations as $donation) {
        $string = '';
        // Make proper xhtml
        $donation->name = trim(htmlspecialchars($donation->name, ENT_COMPAT, 'UTF-8'));
        $donation->org = trim(htmlspecialchars($donation->org, ENT_COMPAT, 'UTF-8'));
        $donation->url = trim($donation->url);
        if ($donation->name) {
            $string = $donation->name;
        }

        $donation->url = '';   // 4 September 2008  -  New policy from MD: no links at all

        if ($donation->org and $donation->url) {
            if ($string) { $string .= ", "; }
            $string .= "<a rel=\"nofollow\" href=\"$donation->url\">$donation->org</a>";
        } else if ($donation->org) {
            if ($string) { $string .= ", "; }
            $string .= "$donation->org";
        } else if ($donation->url) {
            if (!$string) { $string = $donation->url;}
            $string = "<a rel=\"nofollow\" href=\"$donation->url\">$string</a>";
        }
        if ($donation->amount >= 1000) {
            $star = "**";
            $amount = round($donation->amount);
            $string = "<b>$string</b> (\$$amount)";
            $section = 1000;
        } else if ($donation->amount >= 500) {
            if ($section > 500) {
                $section = 500;
                echo html_writer::start_tag('tr');
                echo html_writer::tag('th', get_string('donationsover500', 'local_moodleorg'), array('colspan' => '2'));
                echo html_writer::end_tag('tr');
            }
            $star = "**";
        } else if ($donation->amount >= 200) {
            if ($section > 200) {
                $section = 200;
                echo html_writer::start_tag('tr');
                echo html_writer::tag('th', get_string('donationsover200', 'local_moodleorg'), array('colspan' => '2'));
                echo html_writer::end_tag('tr');
            }
            $star = "*";
        } else {
            if ($section > 10) {
                $section = 10;
                echo html_writer::start_tag('tr');
                echo html_writer::tag('th', get_string('donations10over', 'local_moodleorg'), array('colspan' => '2'));
                echo html_writer::end_tag('tr');
            }
            $star = "";
        }
        $time = userdate($donation->timedonated, '%d %B %Y');
        echo html_writer::start_tag('tr');
        echo html_writer::tag('td', $string);
        echo html_writer::tag('td', $time, array('class' => 'time'));
        echo html_writer::end_tag('tr');
    }
    echo html_writer::end_tag('table');
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();

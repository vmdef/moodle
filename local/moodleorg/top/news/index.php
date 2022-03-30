<?php

/**
 * Display site news
 */
require(__DIR__.'/../../../../config.php');

// Force the theme for this page
$CFG->theme = 'moodleorg';

$PAGE->set_context(context_system::instance());
$PAGE->set_title('Moodle News');
$PAGE->set_heading($PAGE->title);
$PAGE->set_url(new moodle_url('/news/'));
$PAGE->set_pagelayout('news');

echo $OUTPUT->header();
echo $OUTPUT->footer();



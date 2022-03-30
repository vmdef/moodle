This is the Moodle theme for Moodle.org, it is based on the Boost and Classic themes using Bootstrap 4 Stable (Moodle 3.5 and newer). 


1. Theme Structure

1.1 Widgets

This theme contains a number of widgets to render information on the frontpage.

Widgets are defined in /theme/moodleorg/classes/output/moodleorg and rendered using mustache templates in /theme/moodleorg/templates

Logic and Database queries for theses widgets are loaded from a local plugin installed in moodleorg/locallib.php

TODO: rewrite more of the old renderers in this widget structure.

1.2 Layout

This is a 3 column theme supporting the classic navigation and settings menus.

1.3 Styling

The main sass file is loaded from function

theme_moodleorg_get_main_scss_content() in /theme/moodleorg/lib.php

This returns a Moodle Preset file to include all required SASS files from

/theme/moodleorg/scss/preset/moodleorg.scss

The less files from the moodleorgcleaned theme have been translated to sass files. To speed up development a Gruntfile.js has been added to do instant linting on sass files and prevent endles page reloads to test results.

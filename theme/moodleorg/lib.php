<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Theme Moodle org functions
 *
 * @package    theme_moodleorg
 * @copyright  2019 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// This line protects the file from being accessed by a URL directly.
defined('MOODLE_INTERNAL') || die();


/**
 * Returns the main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_moodleorg_get_main_scss_content($theme) {
    global $CFG;

    if (!empty($theme->settings->domain)) {
        // Use the domain specified in the css.php request.
        $domain = $theme->settings->domain;

    } else {
        // Fall back to using domain specified in the config.php.
        $domain = theme_moodleorg_get_domain();
    }

    $scss = '';
    $scss .= file_get_contents($CFG->dirroot . '/theme/moodleorg/scss/pre.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/moodleorg/scss/preset/'.$domain.'.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/classic/scss/classic/post.scss');
    $scss .= file_get_contents($CFG->dirroot . '/theme/moodleorg/scss/post.scss');

    return $scss;
}

/**
 * Get the domain for this theme.
 *
 *
 * @return String domain
 */
function theme_moodleorg_get_domain() {
    global $CFG, $SCRIPT;

    $theme = theme_config::load('moodleorg');

    if (empty($CFG->theme_moodleorg_domain)) {
        debugging('The theme_moodleorg_domain should be set in config.php', DEBUG_DEVELOPER);
        $CFG->theme_moodleorg_domain = 'org';
    }

    if ($CFG->theme_moodleorg_domain === 'org') {
        if (strpos($SCRIPT, '/local/plugins/') === 0 || strpos($SCRIPT, '/plugins/') === 0) {
            $CFG->theme_moodleorg_domain = 'plugins';
        }

        if (strpos($SCRIPT, '/local/moodleorg/top/demo/') === 0 || strpos($SCRIPT, '/demo/') === 0) {
            $CFG->theme_moodleorg_domain = 'demo';
        }
    }

    if (!in_array($CFG->theme_moodleorg_domain, $theme->settings->validdomains)) {
        debugging('Unsupported theme_moodleorg_domain defined', DEBUG_DEVELOPER);
        $CFG->theme_moodleorg_domain = 'org';
    }

    return $CFG->theme_moodleorg_domain;
}

/**
 * Allows to modify URL and cache file for the theme CSS.
 * For this to work a core hack is required in lib/outputlib.php
 *
 * @param moodle_url[] $urls
 */
function theme_moodleorg_alter_css_urls(&$urls) {
    global $CFG, $PAGE;

    if (during_initial_install() || defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
        // No CSS switch during behat runs, or it will take ages to run a scenario.
        return;
    }

    $domain = theme_moodleorg_get_domain();

    $rev = theme_get_revision();

    if ($rev == -1) {
        foreach (array_keys($urls) as $i) {
            if ($urls[$i]->get_param('type') == 'scss') {
                unset($urls[$i]);
            }
        }
    } else {
        $urls = [];
    }

    $rev = $CFG->themerev;

    $subrev = get_config('theme_moodleorg', 'themerev');

    $themecss = new moodle_url('/theme/moodleorg/css.php');
    $cssfile = right_to_left() ? 'all-' . $domain . '-rtl' : 'all-' . $domain;
    if (!empty($CFG->slasharguments)) {
        $themecss->set_slashargument('/moodleorg/' . $rev .  '_' . $subrev . '/' . $cssfile);
    } else {
        $params = array('theme' => 'moodleorg', 'rev' => $rev, 'type' => $cssfile);
        $themecss->params($params);
    }
    $urls[] = $themecss;
}
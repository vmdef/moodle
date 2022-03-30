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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Build the store the theme CSS for the given custom domains.
 *
 * This custom script is based on the standard {@link theme_build_css_for_themes()} with the added support for our
 * custom domains (such as org, plugins, download etc).
 *
 * @package     theme_moodleorg
 * @subpackage  cli
 * @category    output
 * @copyright   2020 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
require_once($CFG->libdir . '/csslib.php');
require_once($CFG->libdir . '/filelib.php');

$help = "
Compile the CSS files for the given domains of the moodle.org theme.

Options:
    --domains=<domain[,domain,...]>     Comma separated list of domain. Defaults to the current site's ones.
                                        Specify ALL to build CSS for all supported domains.

Example:
    \$ sudo -u www-data php build_theme_css.php --domains=org

";

list($options, $unrecognized) = cli_get_params([
    'domains' => '',
    'help' => false,
], [
    'h' => 'help',
]);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if ($options['help']) {
    echo $help;
    die;
}

$theme = theme_config::load('moodleorg');
$validdomains = $theme->settings->validdomains ?? [];

$domains = array_filter(array_map('trim', explode(',', $options['domains'])));

if (empty($domains)) {
    $domains = [$CFG->theme_moodleorg_domain ?? 'org'];
}

if (in_array('ALL', $domains)) {
    $domains = $theme->settings->validdomains;
}

if (in_array('org', $domains) and !in_array('plugins', $domains)) {
    $domains[] = 'plugins';
}

foreach ($domains as $domain) {
    if (!in_array($domain, $validdomains)) {
        cli_error('Not a valid domain: ' . $domain);
    }
}

$trace = new text_progress_trace();
$trace->output('Building CSS for domains: ' . implode(', ', $domains));

$themename = $theme->name;

$localcachethemeroot = make_localcache_directory('theme');
$fallbackroot = make_temp_directory('theme/moodleorg');

$themerev = theme_get_revision();
$trace->output('global theme rev: ' . $themerev);

$oldrevision = theme_get_sub_revision_for_theme($themename);
$trace->output('cur sub-revision: ' . $oldrevision);

$newrevision = theme_get_next_sub_revision_for_theme($themename);
$trace->output('new sub-revision: ' . $newrevision);

$localcachedir = "{$localcachethemeroot}/{$themerev}/{$themename}/css";
$trace->output('target cache dir: ' . $localcachedir);

// Do not bother with non-SVG these days.
$theme->force_svg_use(true);

foreach (['ltr', 'rtl'] as $direction) {
    $theme->set_rtl_mode(($direction === 'rtl'));

    // Generate the editor CSS file.
    $css = $theme->get_css_content_editor();

    // Store it in the localcachedir.
    $filename = 'editor' . ($direction === 'rtl' ? '-rtl' : '') . '_' . $newrevision . '.css';
    css_store_css($theme, $localcachedir . '/' . $filename, $css);

    // Store the fallback in the temp directory.
    $filename = 'editor' . ($direction === 'rtl' ? '-rtl' : '') . '.css';
    css_store_css($theme, $fallbackroot . '/' . $filename, $css);

    $trace->output('CSS ' . basename($filename, '.css') . ' ' . strlen($css) . ' bytes');

    // Generate the all-<domain> CSS files for all the domains.
    foreach ($domains as $domain) {
        $theme->settings->domain = $domain;
        $css = $theme->get_css_content();

        // Store it in the localcachedir.
        $filename = 'all-' . $domain . ($direction === 'rtl' ? '-rtl' : '') . '_' . $newrevision . '.css';
        css_store_css($theme, $localcachedir . '/' . $filename, $css);

        // Store the fallback in the temp directory.
        $filename = 'all-' . $domain . ($direction === 'rtl' ? '-rtl' : '') . '.css';
        css_store_css($theme, $fallbackroot . '/' . $filename, $css);

        $trace->output('CSS ' . basename($filename, '.css') . ' ' . strlen($css) . ' bytes');
    }
}

// Now that we have new CSS files generated, switch the theme to use them.
theme_set_sub_revision_for_theme($themename, $newrevision);
$trace->output('new theme sub-revision set');

// Delete old global revisions from the localcache.
$themecachedirs = glob("{$CFG->localcachedir}/theme/*", GLOB_ONLYDIR);
foreach ($themecachedirs as $dir) {
    $cachedrev = [];
    preg_match("/\/theme\/([0-9]+)$/", $dir, $cachedrev);
    $cachedrev = isset($cachedrev[1]) ? intval($cachedrev[1]) : 0;
    if ($cachedrev > 0 && $cachedrev < $themerev) {
        $trace->output('Deleting outdated dir: ' . $dir);
        fulldelete($dir);
    }
}

// Delete old sub-revision from the localcache.
$subrevfiles = glob("{$CFG->localcachedir}/theme/{$themerev}/{$themename}/css/*.css");
foreach ($subrevfiles as $subrevfile) {
    $cachedsubrev = [];
    preg_match("/_([0-9]+)\.([0-9]+\.)?css$/", $subrevfile, $cachedsubrev);
    $cachedsubrev = isset($cachedsubrev[1]) ? intval($cachedsubrev[1]) : 0;
    if ($cachedsubrev > 0 && $cachedsubrev < $newrevision) {
        $trace->output('Deleting outdated file: ' . $subrevfile);
        fulldelete($subrevfile);
    }
}

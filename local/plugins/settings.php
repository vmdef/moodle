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
 * Defines the plugin directory settings
 *
 * @package     local_plugins
 * @copyright   2011 Sam Hemelryk
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if (!$hassiteconfig) {
    return;
}

$temp = new admin_settingpage(
    'local_plugins_settings',
    new lang_string('pluginname', 'local_plugins'),
    'moodle/site:config'
);

if ($ADMIN->fulltree) {
    //// We do not use this now so that we can count downloads ourselves.
    //$temp->add(new admin_setting_heading(
    //    'local_plugins_heading_urls',
    //    'URL redirecting',
    //    ''
    //));

    //$temp->add(new admin_setting_configtext(
    //    'local_plugins_downloadredirectorurl',
    //    new lang_string('downloadredirectorurl', 'local_plugins'),
    //    new lang_string('downloadredirectorurl_desc', 'local_plugins'),
    //    'https://download.moodle.org/download.php/direct/addons/'
    //));

    $temp->add(new admin_setting_heading(
        'local_plugins_heading_contents',
        'Plugins records contents',
        ''
    ));

    $temp->add(new admin_setting_configtextarea('local_plugins_supportablesoftware',
        new lang_string('supportablesoftware', 'local_plugins'),
        new lang_string('supportablesoftwaredesc', 'local_plugins'),
        'Moodle, PHP'
    ));

    $temp->add(new admin_setting_configselect('local_plugins_maxscreenshots',
        new lang_string('maxscreenshots', 'local_plugins'),
        new lang_string('maxscreenshotsdesc', 'local_plugins'),
        10,
        [1 => 1, 2 => 2, 5 => 5, 10 => 10, 15 => 15, 20 => 20, 50 => 50, 100 => 100]
    ));

    $temp->add(new admin_setting_configselect('local_plugins_maxcontributors',
        new lang_string('maxcontributors', 'local_plugins'),
        new lang_string('maxcontributorsdesc', 'local_plugins'),
        5,
        [1 => 1, 5 => 5, 10 => 10, 25 => 25, 50 => 50]
    ));

    $temp->add(new admin_setting_configtextarea('local_plugins_mainpagetext',
        new lang_string('mainpagetext', 'local_plugins'),
        new lang_string('mainpagetextdesc', 'local_plugins'),
        '',
        PARAM_RAW
    ));

    $temp->add(new admin_setting_configtextarea('local_plugins_registerplugintext',
        new lang_string('registerplugintext', 'local_plugins'),
        new lang_string('registerplugintextdesc', 'local_plugins'),
        '',
        PARAM_RAW
    ));

    $temp->add(new admin_setting_configtextarea('local_plugins_addversiontext',
        new lang_string('addversiontext', 'local_plugins'),
        new lang_string('addversiontextdesc', 'local_plugins'),
        '',
        PARAM_RAW
    ));

    $temp->add(new admin_setting_heading(
        'local_plugins_heading_amos',
        'AMOS integration',
        ''
    ));

    $temp->add(new admin_setting_configtext(
        'local_plugins/amosurl',
        new lang_string('amosurl', 'local_plugins'),
        new lang_string('amosurl_desc', 'local_plugins'),
        'https://lang.moodle.org'
    ));

    $temp->add(new admin_setting_configtext(
        'local_plugins/amosapikey',
        new lang_string('amosapikey', 'local_plugins'),
        new lang_string('amosapikey_desc', 'local_plugins'),
        ''
    ));

    $temp->add(new admin_setting_heading(
        'local_plugins_heading_usagestats',
        'Usage statistics',
        ''
    ));

    $temp->add(new admin_setting_configtext(
        'local_plugins/usagestatsfilesroot',
        new lang_string('usagestatsfilesroot', 'local_plugins'),
        new lang_string('usagestatsfilesroot_desc', 'local_plugins'),
        '',
        PARAM_PATH
    ));

    $temp->add(new admin_setting_configtextarea(
        'local_plugins/extratypes',
        new lang_string('extratypes', 'local_plugins'),
        new lang_string('extratypes_desc', 'local_plugins'),
        ''
    ));

    $temp->add(new admin_setting_heading(
        'local_plugins_heading_precheck',
        'Precheck integration',
        ''
    ));

    $temp->add(new admin_setting_configtext(
        'local_plugins/prechecksnapshotsrepo',
        'Plugins snapshot repository',
        'Public moodle.git clone where we will push plugins snapshot for code style precheck.',
        'git@git.in.moodle.com:pluginsbot/moodle-plugins-snapshots.git'
    ));

    $temp->add(new admin_setting_configtextarea(
        'local_plugins/prechecksshkey',
        'SSH key',
        'Private key to be used to write to the snapshots repository (the contents of the id_rsa file).',
        ''
    ));

    $temp->add(new admin_setting_configtext(
        'local_plugins/prechecksnapshotsreporead',
        'Read-only plugins snapshot repository',
        'URL of the public moodle.git repository that CI Jenkins should use to pull plugins snapshot for code style precheck.',
        'https://git.in.moodle.com/pluginsbot/moodle-plugins-snapshots.git',
        PARAM_URL
    ));

    $temp->add(new admin_setting_configtext(
        'local_plugins/precheckcihost',
        'CI host',
        'The URL where the CI Jenkins is running. For testing purposes you may want to use `http://ci.stronk7.com`. The default value `https://integration.moodle.org` is suitable for production environment.',
        'https://integration.moodle.org',
        PARAM_URL
    ));

    $temp->add(new admin_setting_configtext(
        'local_plugins/precheckcijob',
        'CI job name',
        'The name of the job to execute.',
        'Precheck remote branch',
        PARAM_RAW
    ));

    $temp->add(new admin_setting_configtext(
        'local_plugins/precheckcitoken',
        'CI token',
        'The secret token allowing us to trigger the job.',
        'we01allow02tobuild04this05from06remote07scripts08didnt09you10know',
        PARAM_RAW
    ));
}

$ADMIN->add('localplugins', $temp);
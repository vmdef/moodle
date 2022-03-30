<?php

/**
 * This file defines the capabilities used by the local_plugins plugin.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'local/plugins:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'guest' => CAP_ALLOW,
            'user' => CAP_ALLOW
        )
    ),
    'local/plugins:viewunapproved' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:viewreports' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    ),
    'local/plugins:createplugins' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
    ),
    'local/plugins:editownplugins' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    ),
    'local/plugins:editanyplugin' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_SPAM | RISK_XSS | RISK_MANAGETRUST | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:deleteownplugin' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:deleteanyplugin' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS | RISK_MANAGETRUST,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:deleteownpluginversion' => array(
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:deleteanypluginversion' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS | RISK_MANAGETRUST,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:approveplugin' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS | RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:approvepluginversion' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS | RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:autoapproveplugins' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS | RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:autoapprovepluginversions' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS | RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:approvereviews' => array(
        'riskbitmask' => RISK_PERSONAL | RISK_DATALOSS | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:publishreviews' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:editownreview' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    ),
    'local/plugins:editanyreview' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS | RISK_MANAGETRUST | RISK_PERSONAL,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:comment' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
    ),
    'local/plugins:rate' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
    ),
    'local/plugins:markfavourite' => array(
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'user' => CAP_ALLOW
        )
    ),
    'local/plugins:editowntags' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
    ),
    'local/plugins:editanytags' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:managesupportableversions' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS | RISK_DATALOSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:managesets' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS | RISK_DATALOSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:addtosets' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:manageawards' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS | RISK_DATALOSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:handoutawards' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:managecategories' => array(
        'riskbitmask' => RISK_SPAM | RISK_XSS | RISK_DATALOSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    'local/plugins:managereviewcriteria' => array(
        'riskbitmask' => RISK_XSS | RISK_DATALOSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM
    ),
    // Be notified on an activity in unapproved plugins.
    'local/plugins:notifiedunapprovedactivity' => array(
        'riskbitmask' => RISK_SPAM,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'local/plugins:approveplugin',
    ),
    // View approval queue stats.
    'local/plugins:viewqueuestats' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => array(
            'guest' => CAP_ALLOW,
            'user' => CAP_ALLOW
        ),
    ),
    // Manage descriptors.
    'local/plugins:managedescriptors' => [
        'riskbitmask' => RISK_DATALOSS | RISK_CONFIG,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'local/plugins:managecategories',
    ],
    // View prechecks report.
    'local/plugins:viewprecheckreport' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'clonepermissionsfrom' => 'local/plugins:managecategories',
    ],
);

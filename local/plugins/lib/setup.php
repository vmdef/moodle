<?php

/**
 * This file prepares everything for the local_plugins plugin.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

define('LOCAL_PLUGINSSETUP', true);

require_once($CFG->dirroot.'/comment/lib.php');
require_once($CFG->dirroot.'/rating/lib.php');
require_once($CFG->dirroot.'/lib/filelib.php');

$localpluginspath = $CFG->dirroot.'/local/plugins';
require_once($localpluginspath.'/lib.php');
require_once($localpluginspath.'/locallib.php');

$localpluginslib = $localpluginspath.'/lib';
require_once($localpluginslib.'/base.class.php');
require_once($localpluginslib.'/helper.php');
require_once($localpluginslib.'/log.class.php');
require_once($localpluginslib.'/subscription.class.php');

require_once($localpluginslib.'/category.class.php');
require_once($localpluginslib.'/plugin.class.php');
require_once($localpluginslib.'/version.class.php');
require_once($localpluginslib.'/award.class.php');
require_once($localpluginslib.'/set.class.php');
require_once($localpluginslib.'/contributor.class.php');
require_once($localpluginslib.'/review.class.php');
require_once($localpluginslib.'/recentplugins.class.php');
require_once($localpluginslib.'/recentpluginsnew.class.php');
require_once($localpluginslib.'/recentpluginsupdated.class.php');
require_once($localpluginslib.'/softwareversion.class.php');

require_once($localpluginslib.'/user.class.php');

require_once($localpluginslib.'/report.class.php');
require_once($localpluginslib.'/search.class.php');
require_once($localpluginslib.'/github.search.class.php');

require_once($localpluginslib.'/plugin.pagination.class.php');
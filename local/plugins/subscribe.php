<?php

/**
 * This file processes/toggles subscription upon request.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2012 Aparup Banerjee
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');

$notificationtype = required_param('type', PARAM_ALPHA);
$pluginid = required_param('pluginid', PARAM_INT);

$plugin = local_plugins_helper::get_plugin($pluginid);

if (!$plugin->can_view()) {
    local_plugins_error(null, null, 403);
}

$context = context_system::instance();

$PAGE->set_url($plugin->get_subscriptionlink($notificationtype));
$PAGE->set_context($context);
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_plugins'));
$PAGE->set_heading($PAGE->title);

require_login();
if (isguestuser()) {
    throw new local_plugins_exception('exc_noguestsallowed');
}

$subscription = local_plugins_subscription::subscription_for_plugin($plugin, $notificationtype);
$subscription = new local_plugins_subscription($subscription);

// toggle the subscription.

if($plugin->sub_toggle($subscription)) {
    redirect($plugin->viewlink, get_string('subscriptionupdated', 'local_plugins'));
} else {
    notice('Sorry, there was a problem with your subscription.');
}

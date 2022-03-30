<?php

/**
 * This file contains moodle forms used to create and edit plugins as
 * well as specific plugin fields.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class local_plugins_edit_plugin_form extends moodleform {

    protected function definition() {

        $form = $this->_form;

        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);

        // name
        $form->addElement('text', 'name', get_string('name', 'local_plugins'), array('size'=>52));
        $form->addRule('name', get_string('required'), 'required');
        $form->setType('name', PARAM_TEXT);

        if (has_capability(local_plugins::CAP_EDITANYPLUGIN, context_system::instance())) {
            // Category ID
            $form->addElement('select', 'categoryid', get_string('category', 'local_plugins'), local_plugins_helper::get_category_options());
            $form->setType('categoryid', PARAM_INT);

            // frankenstyle (editable only by admin)
            $form->addElement('text', 'frankenstyle', get_string('pluginfrankenstyle', 'local_plugins'), array('size'=>52));
            $form->setType('frankenstyle', PARAM_ALPHANUMEXT);
        }

        // shortdescription
        $form->addElement('textarea', 'shortdescription', get_string('shortdescription', 'local_plugins'), array('rows'=>3,'cols'=>75));
        $form->setType('shortdescription', PARAM_TEXT);
        $form->addRule('shortdescription', get_string('required'), 'required');
        $form->addHelpButton('shortdescription', 'pluginshortdescription', 'local_plugins');

        // description
        $form->addElement('editor', 'description_editor', get_string('description', 'local_plugins'), null, local_plugins_helper::editor_options_plugin_description());
        $form->setType('description_editor', PARAM_RAW);
        $form->addRule('description_editor', get_string('required'), 'required');

        // sourcecontrolurl
        $form->addElement('text', 'sourcecontrolurl', get_string('sourcecontrolurl', 'local_plugins'), array('size'=>52));
        $form->setType('sourcecontrolurl', PARAM_URL);
        $form->addRule('sourcecontrolurl', get_string('required'), 'required');
        $form->addHelpButton('sourcecontrolurl', 'sourcecontrolurl', 'local_plugins');

        // bugtrackerurl
        $form->addElement('text', 'bugtrackerurl', get_string('bugtrackerurl', 'local_plugins'), array('size'=>52));
        $form->setType('bugtrackerurl', PARAM_URL);
        $form->addRule('bugtrackerurl', get_string('required'), 'required');
        $form->addHelpButton('bugtrackerurl', 'bugtrackerurl', 'local_plugins');

        // documentationurl
        $form->addElement('text', 'documentationurl', get_string('documentationurl', 'local_plugins'), array('size'=>52));
        $form->setType('documentationurl', PARAM_URL);

        // websiteurl
        $form->addElement('text', 'websiteurl', get_string('websiteurl', 'local_plugins'), array('size'=>52));
        $form->setType('websiteurl', PARAM_URL);
        $form->addHelpButton('websiteurl', 'websiteurl', 'local_plugins');

        // discussionurl
        $form->addElement('text', 'discussionurl', get_string('discussionurl', 'local_plugins'), array('size'=>52));
        $form->setType('discussionurl', PARAM_URL);
        $form->addHelpButton('discussionurl', 'discussionurl', 'local_plugins');

        // trackingwidgets
        $form->addElement('textarea', 'trackingwidgets', get_string('trackingwidgets', 'local_plugins'), array('rows' => 3, 'cols' => 52));
        $form->setType('trackingwidgets', PARAM_TEXT);
        $form->addHelpButton('trackingwidgets', 'trackingwidgets', 'local_plugins');

        // screenshots
        $form->addElement('filemanager', 'screenshots_filemanager', get_string('screenshots', 'local_plugins'), null, local_plugins_helper::filemanager_options_plugin_screenshots());
        $form->addRule('screenshots_filemanager', get_string('required'), 'required');
        $form->addHelpButton('screenshots_filemanager', 'screenshots', 'local_plugins');

        // logo
        $form->addElement('filemanager', 'logo_filemanager', get_string('logo', 'local_plugins'), null, local_plugins_helper::filemanager_options_plugin_logo());
        $form->addHelpButton('logo_filemanager', 'logo', 'local_plugins');

        $this->add_action_buttons(true);
    }

    function validation($data, $files) {
        $err = parent::validation($data, $files);

        foreach (array('websiteurl', 'sourcecontrolurl', 'documentationurl', 'bugtrackerurl', 'discussionurl') as $key) {
            if (!empty($data[$key]) && $data[$key] != clean_param($data[$key], PARAM_URL)) {
                $err[$key] = get_string('invalidurl', 'local_plugins');
            }

            if (!empty($data[$key]) && !preg_match('~^https?://.+~', $data[$key])) {
                $err[$key] = get_string('invalidurl', 'local_plugins');
            }
        }

        //Frankenstyle validation
        if (array_key_exists('frankenstyle', $data) and $data['frankenstyle'] !== '') {
            $plugin = local_plugins_helper::get_plugin($data['id']);
            if ($plugin->frankenstyle != $data['frankenstyle']) {
                // admin attempts to change the frankenstyle
                $frankenstyleregexp = local_plugins_helper::validate_frankenstyle_regexp(true);
                if (!preg_match($frankenstyleregexp, $data['frankenstyle'])) {
                    $err['frankenstyle'] = get_string('invalidfrankenstyle', 'local_plugins', $frankenstyleregexp);
                } else {
                    if (local_plugins_helper::get_plugin_by_frankenstyle($data['frankenstyle'])) {
                        $err['frankenstyle'] = get_string('frankenstyleexists', 'local_plugins', $data['frankenstyle']);
                    }
                }
            }
        }
        return $err;
    }
}
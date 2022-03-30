<?php

/**
 * This file contains the contributor class used to manage contributors
 * within the plugin.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2013 Aparup Banerjee
 */

defined('MOODLE_INTERNAL') || die();

/**
 * @property-read int $id
 * @property-read int $userid
 * @property-read string $sitename
 * @property-read string $siteurl
 * @property-read string $version
 */
class local_plugins_usersite extends local_plugins_class_base implements renderable {

    protected $id;
    protected $userid;
    protected $sitename;
    protected $siteurl;
    protected $version;

    protected $user;

    protected function get_user() {
        global $DB;
        if (empty($this->user)) {
            $this->user = $DB->get_record('user', array('id' => $this->userid), '*', MUST_EXIST);
        }
        return $this->user;
    }

    protected function get_username() {
        $user = $this->get_user();
        return fullname($user);
    }

    public function is_current_user() {
        global $USER;
        return ($USER->id == $this->userid);
    }

    protected function get_editurl() {
        return new local_plugins_url('/local/plugins/user.php');
    }

    public function update($properties) {
        global $DB;
        $properties = (array)$properties;
        $changes = false;
        $fields = array('sitename', 'siteurl', 'version');

        $usersite = new stdClass;
        $usersite->id = $this->id;

        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] != $this->$field) {
                $usersite->$field = $properties[$field];
                $changes = true;
            }
        }
        if ($changes) {
            $DB->update_record('local_plugins_usersite', $usersite);
            foreach ($usersite as $key => $value) {
                $this->$key = $value;
            }
        }
        return $this;
    }

    public function delete() {
        global $DB;
        $DB->delete_records('local_plugins_usersite', array('id' => $this->id));
    }
}
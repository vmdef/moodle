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
 * Provides the {@link \local_plugins\task\award_privacy} class.
 *
 * @package     local_plugins
 * @category    task
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Grant plugins the "Privacy friendly" award.
 *
 * @copyright 2018 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class award_privacy extends award_base {

    /** @var int The id of the award to grant. */
    protected $awardid = 11;

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return 'Grant the Privacy friendly award';
    }

    /**
     * Does the given plugin version contain a privacy provider?
     *
     * @param local_plugins_plugin $plugin
     * @param local_plugins_version $version
     * @param string $zip path to the plugin version package
     * @return bool
     */
    protected function should_be_awarded(\local_plugins_plugin $plugin, \local_plugins_version $version, string $zip) {

        $found = false;

        foreach ($this->packer->list_files($zip) as $fileinfo) {
            if (!$fileinfo->is_directory && preg_match('|^.+/classes/privacy/provider\.php$|', $fileinfo->pathname, $matches)) {
                $found = $matches[0];
                break;
            }
        }

        if ($found) {
            mtrace('... found privacy provider file '.$found);
            return true;
        }

        return false;
    }
}

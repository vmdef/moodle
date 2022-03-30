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
 * The donation_submitted event.
 *
 * @package    local_moodleorg
 * @copyright  2017 Moodle
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
namespace local_moodleorg\event;
defined('MOODLE_INTERNAL') || die();
/**
 * The donation_submitted event class.
 *
 * @property-read array $other {
 * }
 *
 * @since     Moodle 2.9
 * @copyright 2017 Karen Holland
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/
 
class donation_submitted extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'r'; // c(reate), r(ead), u(pdate), d(elete)
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }
 
    public static function get_name() {
        return get_string('eventdonation_submitted', 'local_moodleorg');
    }
 
    public function get_description() {
        return "Donation submitted: '{$this->other['requeststr']}'.";
    }
 
    public function get_url() {
        return new \moodle_url('/local/moodleorg/top/donations/index.php');

    }
 
    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();
    }
}

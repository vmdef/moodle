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
 * Provides testable subclasses of real classes used in tests
 *
 * @package     local_plugins
 * @category    phpunit
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Testable subclass of local_plugins_usage_manager
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_local_plugins_usage_manager extends local_plugins_usage_manager {

    /** @var int|null */
    public $fakerecentlyprocessedyear = -1;
    /** @var int|null */
    public $fakerecentlyprocessedmonth = -1;
    /** @var int|null */
    public $fakecurrentyear = -1;
    /** @var int|null */
    public $fakecurrentmonth = -1;

    /**
     * Provide access to the parent's protected method.
     */
    public function get_all_monthly_stats_files() {
        return parent::get_all_monthly_stats_files();
    }

    /**
     * Provide access to the parent's protected method.
     */
    public function filter_stats_files(array $files, $yearfrom = null, $monthfrom = null, $yearto = null, $monthto = null) {
        return parent::filter_stats_files($files, $yearfrom, $monthfrom, $yearto, $monthto);
    }

    /**
     * Provide access to the parent's protected method.
     */
    public function parse_stats_file(array $lines) {
        return parent::parse_stats_file($lines);
    }

    /**
     * @return string Four digit integer number such as '2015'
     */
    protected function get_current_year() {
        return ($this->fakecurrentyear == -1 ? parent::get_current_year() : $this->fakecurrentyear);
    }

    /**
     * @return string Two digits integer number such as '09'
     */
    protected function get_current_month() {
        return ($this->fakecurrentmonth == -1 ? parent::get_current_month() : $this->fakecurrentmonth);
    }

    /**
     * @return string|null Four digit integer number such as '2015' or null
     */
    protected function get_recently_processed_year() {
        return ($this->fakerecentlyprocessedyear == -1 ? parent::get_recently_processed_year() : $this->fakerecentlyprocessedyear);
    }

    /**
     * @param string|null $year
     * @return string|null
     */
    protected function get_recently_processed_month($year) {
        return ($this->fakerecentlyprocessedmonth == -1 ? parent::get_recently_processed_month() : $this->fakerecentlyprocessedmonth);
    }
}

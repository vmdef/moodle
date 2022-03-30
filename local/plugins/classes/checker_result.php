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
 * Provides the local_plugins_checker_result class
 *
 * @package     local_plugins
 * @subpackage  checkers
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the checker result structure
 */
class local_plugins_checker_result implements renderable {

    const IMPORTANCE_REQUIRED       = 90;   // This is considered as an error/mistake (must have).
    const IMPORTANCE_RECOMMENDED    = 60;   // It is really good to try and achieve this (should have).
    const IMPORTANCE_SUGGESTED      = 30;   // Sort of detail, but still nice to have (nice to have).

    /** @var string The internal identifier of the result. */
    public $name;
    /** @var int The importance level of the result. */
    public $importance;

    /**
     * @param string $name
     * @param int $importance
     * @return local_plugins_checker_result
     */
    public function __construct($name, $importance) {
        $this->name = $name;
        $this->importance = $importance;
    }
}

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
 * Strings for filter_h5p
 *
 * @package    filter_h5p
 * @copyright  2019 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['alloweddomainslist'] = 'Allowed domains';
$string['alloweddomainslistdesc'] = 'List of domains from which users can embed H5P content. If empty, any domain will be allowed.

Wildcards are supported. For example, *.example.com will embed H5P content from any subdomain of example.com, but not from the example.com domain.';
$string['filtername'] = 'H5P';
$string['frameheight'] = 'Iframe height';
$string['frameheightdesc'] = 'Default H5P iframe height';
$string['framewidth'] = 'Iframe width';
$string['framewidthdesc'] = 'Default H5P iframe width';
$string['privacy:metadata'] = 'This H5P filter does not store any personal data.';

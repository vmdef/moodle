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
 * A javascript module to handle user ajax actions.
 *
 * @module     core_user/repository
 * @copyright  2018 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery', 'core/ajax'], function($, Ajax) {

    /**
     * Get the list of activities that the user has most recently accessed.
     *
     * @method getRecentActivities
     * @param {int} userid User from which the activities will be obtained
     * @param {int} limit Only return this many results
     * @param {int} offset Skip this many results from the start of the result set
     * @param {string} sort Column to sort by and direction, e.g. 'shortname asc'
     * @param {boolean} available Filter not available activities
     * @return {promise} Resolved with an array of activities
     */
    var getRecentActivities = function(userid, limit, offset, sort, available) {
        var args = {
            userid: userid
        };
        if (typeof limit !== 'undefined') {
            args.limit = limit;
        }
        if (typeof offset !== 'undefined') {
            args.offset = offset;
        }
        if (typeof sort !== 'undefined') {
            args.sort = sort;
        }
        if (typeof available !== 'undefined') {
            args.sort = available;
        }
        var request = {
            methodname: 'core_user_get_recent_activities',
            args: args
        };
        return Ajax.call([request])[0];
    };
    return {
        getRecentActivities: getRecentActivities
    };
});

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
 * Javascript to initialise the Recent activities block.
 *
 * @copyright  2018 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
    [
        'jquery',
        'core_user/repository',
        'core/templates',
        'core/notification'
    ],
    function(
        $,
        UsersRepository,
        Templates,
        Notification
    ) {

        var NUM_ACTIVITIES = 9;

        var SELECTORS = {
            CARDDECK_CONTAINER: '[data-region="recent-activities-view"]',
            CARDDECK: '[data-region="recent-activities-view-content"]',
        };

        /**
         * Get recent activities from backend.
         *
         * @method getRecentActivities
         * @param {int} userid User from which the courses will be obtained
         * @param {int} limit Only return this many results
         * @return {array} Activities user most recently has accessed
         */
        var getRecentActivities = function(userid, limit) {
            return UsersRepository.getRecentActivities(userid, limit);
        };

        /**
         * Render the block content.
         *
         * @method renderActivities
         * @param {object} root The root element for the activities view.
         * @param {array} activities containing array of returned activities.
         * @return {promise} Resolved with HTML and JS strings
         */
        var renderActivities = function(root, activities) {
            if (activities.length > 0) {
                return Templates.render('block_recentactivities/view-cards', {
                    activities: activities
                });
            } else {
                var noactivitiesimg = root.attr('data-noactivitiesimg');
                return Templates.render('block_recentactivities/no-activities', {
                    noactivitiesimg: noactivitiesimg
                });
            }
        };

        /**
         * Get and show the recent activities into the block.
         *
         * @param {int} userid User from which the activities will be obtained
         * @param {object} root The root element for the activities block.
         */
        var init = function(userid, root) {
            root = $(root);

            var activitiesContainer = root.find(SELECTORS.CARDDECK_CONTAINER);
            var activitiesContent = root.find(SELECTORS.CARDDECK);

            var activitiesPromise = getRecentActivities(userid, NUM_ACTIVITIES);

            activitiesPromise.then(function(activities) {
                var pagedContentPromise = renderActivities(activitiesContainer, activities);

                pagedContentPromise.then(function(html, js) {
                    return Templates.replaceNodeContents(activitiesContent, html, js);
                }).catch(Notification.exception);
                return activitiesPromise;
            }).catch(Notification.exception);
        };

        return {
            init: init
        };
    });
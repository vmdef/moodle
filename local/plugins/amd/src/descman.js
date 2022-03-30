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
 * Controller for the descriptors management page
 *
 * @module      local_plugins/descman
 * @package     local_plugins
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery',
        'core/fragment',
        'core/notification',
        'core/templates',
        'core/yui',
        'core/form-autocomplete'
    ], function($, fragment, notification, templates, Y, autocomplete) {

    'use strict';

    function init() {
        $('[data-widget="desc-manage-trigger"]').on('change', showDescriptorForm);
        $('[data-widget="desc-fill"]').each(function() {
            var select = $(this);
            autocomplete.enhance('#'+select.attr('id'), true, '', select.attr('data-desctitle'), false, true);
        });
    }

    function showDescriptorForm(e) {
        var select = $(e.target);
        var contextid = select.attr('data-contextid');
        var descid = select.val();
        var target = $('[data-widget="desc-form-target"]');

        if (!target.length) {
            return;
        }

        fragment.loadFragment('local_plugins', 'descriptor_form', contextid, {'descid': descid})
            .done(function(html, js) {
                niceReplaceNode(target, html, js);
            })
            .fail(notification.exception);
    }

    /**
     * Fade the node out, update it, and fade it back.
     *
     * @private
     * @method niceReplaceNodeContents
     * @param {JQuery} node
     * @param {String} html
     * @param {String} js
     * @return {Deferred} promise resolved when the animations are complete.
     */
    function niceReplaceNode(node, html, js) {
        var promise = $.Deferred();

        node.fadeOut("fast", function() {
            templates.replaceNode(node, html, js);
            node.fadeIn("fast", function() {
                promise.resolve();
            });
        });

        return promise.promise();
    }

    return {
        init: init
    };
});

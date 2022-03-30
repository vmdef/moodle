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
 * Implements the behaviour of the filter form on the front page.
 *
 * @module      local_plugins/browser-control
 * @package     local_plugins
 * @subpackage  amd
 * @category    output
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
        'jquery',
        'core/log',
],
function ($, Log) {
    'use strict';

    /**
     * Represents the browser control widget on the screen.
     *
     * @constructor
     * @param {jQuery} region
     * @param {Object} browserGrid
     */
    function BrowserControl(region, browserGrid) {

        var self = this;

        self.region = region;
        self.browserGrid = browserGrid;

        self.initFilterForm();
        self.initSortForm();
    }

    /**
     * Initialize the filter form.
     *
     * @method
     */
    BrowserControl.prototype.initFilterForm = function () {

        var self = this;

        self.filterForm = self.region.find('[data-widget="browser-filter-form"]').first();

        if (!self.filterForm.length) {
            Log.error('Filter form widget not found');
            return;
        }

        self.filterForm.on('submit', function(e) {
            e.preventDefault();
            self.submitControlForm();
        });

        self.filterForm.find('select,input').on('change', function() {
            self.highlightCurrentlyUsedFields();
        });

        self.filterForm.find('select').on('change', function() {
            self.filterForm.submit();
        });

        self.filterForm.find('[data-widget="advanced-filter-hide"]').on('click', function(e) {
            e.preventDefault();
            self.filterForm.attr('data-show-advanced', '');
        });

        self.filterForm.find('[data-widget="advanced-filter-show"]').on('click', function(e) {
            e.preventDefault();
            self.filterForm.attr('data-show-advanced', '1');
        });

        self.highlightCurrentlyUsedFields();
    };

    /**
     * Initialize the sorting panel.
     *
     * @method
     */
    BrowserControl.prototype.initSortForm = function () {

        var self = this;

        self.sortForm = self.region.find('[data-widget="browser-sort-form"]').first();

        if (!self.sortForm.length) {
            Log.error('Sort form widget not found');
            return;
        }

        self.sortForm.find('[data-sortby]').on('click', function (e) {
            e.preventDefault();
            var sortlink = $(e.currentTarget);
            self.filterForm.find('input[name="sort-by"]').val(sortlink.attr('data-sortby'));
            self.highlightCurrentSortLink();
            self.submitControlForm();
        });

        self.highlightCurrentSortLink();
    };

    /**
     * Highlights the currently active sort link.
     *
     * @method
     */
    BrowserControl.prototype.highlightCurrentSortLink = function () {

        var self = this;
        var currentSortBy = self.filterForm.find('input[name="sort-by"]').val();

        self.sortForm.find('[data-sortby]').removeClass('current');
        self.sortForm.find('[data-sortby="' + currentSortBy + '"]').addClass('current');
    };

    /**
     * Highlights the currently activated filter form fields.
     *
     * @method
     */
    BrowserControl.prototype.highlightCurrentlyUsedFields = function () {

        var self = this;
        var fields = self.filterForm.find('[data-widget="descriptor-dropdown"],input[name="search"]');

        fields.removeClass('current');
        fields.each(function (ix, field) {
            field = $(field);
            if (field.val() !== '') {
                field.addClass('current');
                field.removeAttr('data-is-advanced');
            }
        });
    };

    /**
     * Handle the filter form submission.
     *
     * @method
     */
    BrowserControl.prototype.submitControlForm = function () {

        var self = this;
        var query = buildBrowserQuery(self.filterForm.serializeArray());

        self.browserGrid.search(query);
    };

    /**
     * Prepares the browser query string from the form values.
     *
     * @private
     * @param {Array} values as returned by jQuery.serializeArray()
     */
    function buildBrowserQuery(values) {

        var query = '';

        $.each(values, function(i, field) {
            if (field.name === "search") {
                // Keyword search box.
                query += ' ' + field.value;

            } else if (field.value !== "") {
                // Descriptor dropdown selector.
                query += ' ' + field.name + ':' + field.value;
            }
        });

        return query.trim();
    }

    return {
        /**
         * Factory method for browser control instance.
         *
         * @param {jQuery} region
         * @param {Object} browserGrid
         */
        init: function (region, browserGrid) {
            return new BrowserControl(region, browserGrid);
        }
    };
});

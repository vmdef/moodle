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
 * Controls the display of browser plugins in a grid.
 *
 * @module      local_plugins/browser-grid
 * @package     local_plugins
 * @subpackage  amd
 * @category    output
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
        'jquery',
        'core/log',
        'core/ajax',
        'core/templates',
],
function($, Log, Ajax, Templates) {
    'use strict';

    /**
     * Represents the grid of plugins displayed on the screen.
     *
     * @constructor
     * @param {jQuery} region
     */
    function BrowserGrid(region) {
        var self = this;

        self.grid = region.find('[data-widget="browser-grid"]').first();

        if (!self.grid.length) {
            Log.error('Grid wrapper widget not found');
            return;
        }

        imgSrcLazyLoad(self.grid, 'data-lazyload');

        self.displayShowMoreWidget();

        self.grid.on('click', 'article', /** @this HTMLElement */  function (e) {
            var article = $(this);

            if (! $(e.target).is('[data-widget="directlink"]')) {
                e.preventDefault();
                self.previewPlugin(article);
            }
        });

        $(window).on('popstate', function (e) {
            if ((e.originalEvent.state !== null) && typeof e.originalEvent.state.query !== 'undefined') {
                location.reload();
            }
        });
    }

    /**
     * Display a modal preview of the plugin in the given article card.
     *
     * @method
     * @param {jQuery} article
     * @return {Deferred}
     */
    BrowserGrid.prototype.previewPlugin = function (article) {

        var rawinfo = JSON.parse(article.attr('data-rawinfo'));

        if (!rawinfo) {
            Log.error('Unable to access plugin raw info');
            return $.Deferred().reject('Unable to access plugin raw info');
        }

        return Templates.render('local_plugins/browser_grid_preview', rawinfo).fail(function(reason) {
            Log.error('Unable to render preview:' + reason);

        }).then(function(html) {
            var preview = $(html);
            article.after(preview);
            // Templates.runTemplateJS(js);
            return preview;

        }).then(function(preview) {
            preview.modal('show');
            preview.find('[data-widget="close"]').on('click', function(e) {
                e.preventDefault();
                preview.modal('hide');
            });

            if (rawinfo.screenshots.length > 0) {
                // Preload all remaining screenshots and make it so that the user can cycle through them
                var screenshoturls = [rawinfo.mainscreenshot.bigthumb];
                var screenshotindex = 0;
                rawinfo.screenshots.forEach(function (s) {
                    screenshoturls.push(s.bigthumb);
                    $('<img/>')[0].src = s.bigthumb;
                });
                preview.find('[data-region="preview-screenshot"]').on('click', function (e) {
                    e.preventDefault();
                    var img = $(e.currentTarget);
                    screenshotindex++;
                    if (screenshotindex >= screenshoturls.length) {
                        screenshotindex = 0;
                    }
                    img.attr('src', screenshoturls[screenshotindex]);
                });
            }

            $(window).on('keyup', function(e) {
                if (e.keyCode === 27) {
                    preview.modal('hide');
                }
            });

            $(window).on('click', function(e) {
                if (e.target == preview[0]) {
                    e.preventDefault();
                    preview.modal('hide');
                }
            });
        });
    };

    /**
     * Perform a search for plugins and display results.
     *
     * @method
     * @param {String} query
     * @return {Deferred}
     */
    BrowserGrid.prototype.search = function (query) {
        var self = this;

        query = query.trim();

        if (self.grid.attr('data-query') === query) {
            return $.Deferred().resolve();
        }

        return Ajax.call([{
            methodname: 'local_plugins_get_plugins_batch',
            args: {
                query: query,
                batch: 0
            }

        }])[0].fail(function(reason) {
            Log.error('Unable to get plugins batch: ' + reason);

        }).then(function(response) {
            self.grid.attr('data-query', response.grid.query);
            history.pushState({query: response.grid.query}, '', '/plugins/?q=' + response.grid.query);
            return self.replace(response);
        });
    };

    /**
     * Display the "Show more" button if it seems like there might be more plugins.
     *
     * @method
     */
    BrowserGrid.prototype.displayShowMoreWidget = function () {
        var self = this;

        var batchsize = parseInt(self.grid.attr('data-batchsize'));

        if (self.grid.find('[data-widget="plugincard"]').length < batchsize) {
            return;
        }

        self.showMoreWidget = $('<a href="" class="btn btn-primary btn-lg">' +
            '<i class="fa fa-forward" aria-hidden="true"></i> Show more </a>');
        self.showMoreWidget.on('click', function (e) {
            e.preventDefault();
            self.showMore();
        });

        self.grid.find('[data-region="loadmore"]').html(self.showMoreWidget);
    };

    /**
     * Repeat the same search, load and display next batch of results.
     *
     * @method
     * @return {Deferred}
     */
    BrowserGrid.prototype.showMore = function () {
        var self = this;

        var query = self.grid.attr('data-query');
        var nextbatch = parseInt(self.grid.attr('data-batch')) + 1;

        Log.debug(query);
        Log.debug(nextbatch);

        return Ajax.call([{
            methodname: 'local_plugins_get_plugins_batch',
            args: {
                query: query,
                batch: nextbatch
            }

        }])[0].fail(function(reason) {
            Log.error('Unable to get plugins batch: ' + reason);

        }).then(function(response) {
            return self.append(response);
        });
    };

    /**
     * Replace the contents with a new batch
     *
     * @method
     * @param {Object} response returned by the local_plugins_get_plugins_batch function
     * @return {Deferred}
     */
    BrowserGrid.prototype.replace = function (response) {
        var self = this;

        self.processResponse(response);

        return Templates.render('local_plugins/browser_grid', response.grid).fail(function(reason) {
            Log.error('Unable to render returned response:' + reason);

        }).then(function(html) {
            self.grid.html(html);
            self.displayShowMoreWidget();
            return response;

        }).then(function() {
            return imgSrcLazyLoad(self.grid, 'data-lazyload');

        });
    };

    /**
     * Append plugins into the grid
     *
     * @method
     * @param {Object} response returned by the local_plugins_get_plugins_batch function
     * @return {Deferred}
     */
    BrowserGrid.prototype.append = function (response) {
        var self = this;

        self.processResponse(response);

        if (response.grid.plugins.length === 0) {
            self.showMoreWidget.remove();
            return $.Deferred().resolve();
        }

        return Templates.render('local_plugins/browser_grid_batch', response.grid).fail(function(reason) {
            Log.error('Unable to render returned response:' + reason);

        }).then(function(html) {
            self.grid.find('[data-region="loadmore"]').before(html);
            return response;

        }).then(function() {
            return imgSrcLazyLoad(self.grid, 'data-lazyload');

        });
    };

    /**
     * Common operations with the received AJAX response.
     *
     * @method
     */
    BrowserGrid.prototype.processResponse = function (response) {
        var self = this;

        self.grid.attr('data-batch', response.grid.batch);
        self.grid.attr('data-batchsize', response.grid.batchsize);

        response.grid.plugins.forEach(function (data) {
            data.rawinfo = JSON.stringify(data);
        });
    };

    /**
     * Lazy load images into the given placeholders.
     *
     * @private
     * @param {jQuery} root Root element to search for placeholders in
     * @param {String} sourceHolder The name of the attribute that holds the image source to be lazy loaded.
     * @returns {Deferred}
     */
    function imgSrcLazyLoad(root, sourceHolder) {

        var promise = $.Deferred();
        var imgs = root.find('[' + sourceHolder + ']');

        if (imgs.length) {
            // Load all images asynchronously and then call back (resolve the promise).
            imgs.each(/** @this HTMLElement */ function () {
                var img = $(this);
                var src = img.attr(sourceHolder);
                if (src) {
                    img.attr('src', src);
                }
                img.removeAttr(sourceHolder);
                if (root.find('[' + sourceHolder + ']').length === 0) {
                    img.on('load', promise.resolve);
                }
            });

        } else {
            promise.resolve();
        }

        return promise;
    }

    return {
        /**
         * Factory method returning a new grid instance.
         *
         * @param {jQuery} region
         */
        init: function (region) {
            return new BrowserGrid(region);
        }
    };
});

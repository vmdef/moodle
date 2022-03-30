YUI.add('moodle-local_plugins-favourites', function (Y, NAME) {

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
 * Provides ability to mark plugin as favourite via AJAX
 *
 * @module  moodle-local_plugins-favourite
 * @author  David Mudrak <david@moodle.com>
 */

M.local_plugins = M.local_plugins || {};
M.local_plugins.favourites = {};
var NS = M.local_plugins.favourites;

/**
 * @property initparams
 */
NS.initparams = {};

/**
 * @method init
 */
NS.init = function(params) {
    var addlink;

    this.initparams = params;

    addlink = Y.one(this.initparams.selectors.addlink);

    if (addlink) {
        addlink.set('href', '#');
        addlink.on('click', this.addToFavourites, this);
    }
};

/**
 * @method addToFavourites
 */
NS.addToFavourites = function(e) {
    e.preventDefault();

    Y.use(['io-base', 'handlebars', 'json-parse'], function (Y) {
        Y.io(NS.initparams.urls.add, {
            on: {
                success: function(transid, outcome) {
                    var container, result;

                    try {
                        result = Y.JSON.parse(outcome.responseText);

                    } catch(x) {
                        window.alert('Unable to parse server response');
                        return;
                    }

                    if (result.error) {
                        window.alert(result.error);
                        return;

                    } else if (result.count) {
                        container = Y.one(NS.initparams.selectors.container);
                        if (container) {
                            container.setHTML(Y.Handlebars.render(NS.initparams.templates.added, {'count': result.count}));
                        }
                    }
                }
            }
        });
    });
};


}, '@VERSION@', {"requires": ["base", "node", "event", "io-base", "json-parse", "handlebars"]});

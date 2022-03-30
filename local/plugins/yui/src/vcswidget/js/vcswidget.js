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
 * JS for the VCS integration widget
 *
 * @module  moodle-local_plugins-vcswidget
 * @author  David Mudrak <david@moodle.com>
 */

M.local_plugins = M.local_plugins || {};
var NS = M.local_plugins.vcswidget = {};
var SELECTORS = {
    PLACEHOLDER: '.addvcsversion.placeholder'
};

NS.debug = function(message) {
    Y.log(message, 'debug', 'M.local_plugins.vcswidget');
};

NS.init = function(options) {
    var placeholder = Y.one(SELECTORS.PLACEHOLDER),
        ajaxurl = options.ajaxurl,
        ajaxcfg = {
            method: 'GET',
            context: this,
            on: {
                success : this.handle_success,
                failure : this.handle_failure
            },
            'arguments': {placeholder: placeholder}
        };

    this.debug('Initialising');

    if (placeholder) {
        Y.io(ajaxurl, ajaxcfg);
    }
};

NS.handle_success = function(transid, outcome, args) {
    var result;

    try {
        result = Y.JSON.parse(outcome.responseText);
    } catch(e) {
        result = { 'success': false, 'error': 'Can not parse response' };
    }

    if (result.success) {
        this.debug('Displaing the VCS widget');
        args.placeholder.replace(result.widget);
    } else {
        this.debug('Error: ' + result.error);
    }
};

NS.handle_failure = function() {
    this.debug('Error: AJAX call failure');
};

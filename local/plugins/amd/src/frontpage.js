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
 * Implements the behaviour of the directory front page.
 *
 * @module      local_plugins/frontpage
 * @package     local_plugins
 * @subpackage  amd
 * @category    output
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
        'jquery',
        'core/log',
        'local_plugins/browser-control',
        'local_plugins/browser-grid',
],
function ($, Log, BrowserControl, BrowserGrid) {
    'use strict';

    /**
     * Initialize the front page.
     *
     * This is basically a factory creating instances of all the widgets on the screen.
     *
     * @method
     */
    function init() {

        var controlRegion = $('[data-region="local_plugins-browser-control"]').first();
        var gridRegion = $('[data-region="local_plugins-browser-grid"]').first();

        if (!controlRegion.length) {
            Log.error('Browser control region not found');
            return;
        }

        if (!gridRegion.length) {
            Log.error('Browser grid region not found');
            return;
        }

        var gridWidget = BrowserGrid.init(gridRegion);

        BrowserControl.init(controlRegion, gridWidget);
    }

    return {
        init: init,
    };
});

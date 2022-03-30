<?php
// This file is part of Moodle - https://moodle.org/
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
 * Provides the {@link local_plugins_translation_stats_manager} class.
 *
 * @package     local_plugins
 * @subpackage  amos
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides access to the translation stats for the given plugin.
 *
 * Stats are obtained from the lang.moodle.org via a webservice call. There is a local caching layer implemented in this
 * class.
 *
 * @copyright 2019 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_translation_stats_manager {

    /** @var local_plugins_plugin */
    protected $plugin;

    /** @var cache_application */
    protected $cache;

    /** @var stdClass|null */
    protected $stats = null;

    /**
     * Instantiate the manager.
     *
     * @param local_plugins_plugin $plugin
     */
    public function __construct(local_plugins_plugin $plugin) {

        $this->plugin = $plugin;
        $this->cache = cache::make('local_plugins', 'translationstats');
        $this->init_stats();
    }

    /**
     * Do we have translation stats available?
     *
     * @return bool
     */
    public function has_stats() {
        return ($this->stats !== false);
    }

    /**
     * How many strings are there to translate.
     *
     * We use the English language pack (first in the list) on the latest (first in the list) version.
     *
     * @return int
     */
    public function total_strings() {

        if ($this->stats === false) {
            throw new coding_exception('Make sure the stats are available prior calling this method.');
        }

        foreach ($this->stats->branches as $branch) {
            foreach ($branch->languages as $language) {
                if ($language->lang === 'en') {
                    return $language->numofstrings;
                }
            }
        }

        throw new coding_exception('Unexpected stats data structure.');
    }

    /**
     * Get stats data suitable for presenting them in a chart.
     *
     * @return array [string labels[], int data[]]
     */
    public function get_chart_data() {

        if ($this->stats === false) {
            throw new coding_exception('Make sure the stats are available prior calling this method.');
        }

        $langnames = [];
        foreach ($this->stats->langnames as $langname) {
            $langnames[$langname->lang] = $langname->name;
        }

        $labels = [];
        $data = [];
        $latestbranch = reset($this->stats->branches);

        foreach ($latestbranch->languages as $language) {
            $labels[] = $langnames[$language->lang];
            $data[] = $language->ratio;
        }

        return [$labels, $data];
    }

    /**
     * Initialize the plugin's stats, eventually re-using the locally cached data
     */
    protected function init_stats() {

        // Only approved plugins with a valid frankenstyle name are supported.
        if ($this->plugin->approved != local_plugins_plugin::PLUGIN_APPROVED) {
            $this->stats = false;
            return;
        }

        $component = clean_param($this->plugin->frankenstyle, PARAM_COMPONENT);

        if (empty($component) || $this->plugin->frankenstyle !== $component) {
            $this->stats = false;
            return;
        }

        $cachedstats = $this->cache->get($component);

        if (($cachedstats === false) || ($cachedstats->lastfetched < time() - 300)) {
            $this->stats = $this->fetch_stats_from_amos();
            if ($this->stats) {
                $this->cache->set($component, $this->stats);
            }

        } else {
            $this->stats = $cachedstats;
        }
    }

    /**
     * Fetch plugin translation stats from AMOS via a web service call.
     *
     * @return bool|stdClass false if unable to fetch or data do not exist
     */
    protected function fetch_stats_from_amos() {

        $client = new \local_plugins\local\amos\client();

        $component = $this->plugin->frankenstyle;

        if (strpos($component, 'mod_') === 0) {
            // Activity modules are stored without the mod_ prefix in AMOS.
            $component = substr($component, 4);
        }

        $response = $client->call('local_amos_plugin_translation_stats', ['component' => $component]);

        if (is_object($response)) {
            if (isset($response->exception)) {
                debugging('AMOS remote exception: '.$response->exception.': '.$response->message, DEBUG_DEVELOPER);
                return false;

            } else {
                $response->lastfetched = time();
                return $response;
            }

        } else {
            debugging('Unexpected AMOS response', DEBUG_DEVELOPER);
            return false;
        }
    }
}

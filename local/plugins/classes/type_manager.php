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
 * Provides the {@link local_plugins_type_manager} class.
 *
 * @package     local_plugins
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Provides an interface to all known plugin types.
 *
 * The is a wrapper around standard list of plugin types as returned by the
 * Moodle core. This allows to manually inject types not (yet) known to the
 * site where the plugins directory runs on, or add subtypes derived from
 * plugins not installed on the site.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_type_manager {

    /** @var local_plugins_type_manager */
    protected static $singleton = null;

    /** @var array */
    protected $types = null;

    /**
     * Factory method returning an instance of the class.
     *
     * @return local_plugins_type_manager
     */
    public static function instance($allowsingleton = true) {

        if (!$allowsingleton) {
            return new self();
        }

        if (!self::$singleton) {
            self::$singleton = new self();
        }

        return self::$singleton;
    }

    /**
     * Declares the constructor as protected to force usage of the factory method.
     */
    protected function __construct() {
    }

    /**
     * Returns a list of plugin types.
     *
     * @return object
     */
    public function list_types() {

        $this->load();

        return $this->types;
    }

    /**
     * Returns a human readable name of the given plugin type.
     *
     * @param string $type
     * @return string|null
     */
    public function name($type) {

        if (empty($type)) {
            return null;
        }

        $this->load();

        if (!isset($this->types[$type])) {
            return null;

        } else {
            return $this->types[$type]['name'];
        }
    }

    /**
     * Populates the raw list of plugin types if it does not exist yet.
     */
    protected function load() {
        global $DB;

        if ($this->types !== null) {
            return;
        }

        $this->types = [];

        // Load standard plugin types known to this Moodle core.
        $pluginman = core_plugin_manager::instance();
        force_current_language('en');
        foreach ($pluginman->get_plugin_types() as $type => $ignored) {
            $this->types[$type] = [
                'type' => $type,
                'name' => $pluginman->plugintype_name($type),
                'count' => null,
            ];
        }
        force_current_language(null);

        // Sort by visible names.
        uasort($this->types, function($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        // Load explicitly defined extra types (or renamed ones).
        $extratypes = get_config('local_plugins', 'extratypes');
        $separator = "\r\n";
        $line = strtok($extratypes, $separator);
        while ($line !== false) {
            // Note that PARAM_COMPONENT is more strict, we allow underscores here.
            if (preg_match('/^\s*([a-z_]+)\s*:\s*(.+?)\s*$/', $line, $matches)) {
                $this->types[$matches[1]] = [
                    'type' => $matches[1],
                    'name' => $matches[2],
                    'count' => null,
                ];
            }
            $line = strtok($separator);
        }

        // Fake type for non-plugins.
        $this->types['_other_'] = [
            'type' => '_other_',
            'name' => 'Other',
            'count' => null,
        ];

        // Type representing an empty (null) type column in the database.
        $this->types[''] = [
            'type' => '',
            'name' => 'Unknown',
            'count' => null,
        ];

        // Load the actual number of each type from the database.
        $sql = "SELECT type, COUNT(*) AS num
                  FROM {local_plugins_plugin}
                 WHERE approved = 1
              GROUP BY type";

        foreach ($DB->get_records_sql_menu($sql) as $type => $count) {
            if (!isset($this->types[$type])) {
                debugging('Unknown plugin type: '.$type);
                continue;
            }
            $this->types[$type]['count'] = $count;
        }
    }
}

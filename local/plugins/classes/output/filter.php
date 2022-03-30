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
 * Provides {@link local_plugins\output\filter} class.
 *
 * @package local_plugins
 * @subpackage output
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\output;

defined('MOODLE_INTERNAL') || die();

use local_plugins_type_manager;
use renderable;
use renderer_base;
use templatable;

/**
 * Represents the filter controlling the browser contents.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter implements renderable, templatable {

    /** @var array of keyword strings (provided via the search field) */
    public $keywords = [];

    /** @var array of (string)descriptor => (string)value to search for */
    public $descriptors = [];

    /** @var string the plugin type the plugin must have */
    public $type = null;

    /** @var string the moodle version the plugin must support */
    public $moodleversion = null;

    /** @var string the shortname of the award the plugin has received */
    public $award = null;

    /** @var string the shortname of the set the plugin is part of */
    public $set = null;

    /** @var string the plugin field to sort by */
    public $sortby = null;

    /** @var array (string) url => (string) sql - must be from local_plugins_plugin table only! */
    public $sortmap = [
        'release' => 'p.timelastreleased DESC',
        'sites' => 'p.aggsites DESC',
        'fans' => 'p.aggfavs DESC',
        'downloads' => 'p.aggdownloads DESC',
        'publish' => 'COALESCE(p.timefirstapproved, p.timecreated) DESC',
    ];

    /**
     * Constructor
     *
     * @param string $query
     */
    public function __construct($query) {

        $this->parse_query($query);
    }

    /**
     * Exports the filter data for the template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        // Pick the descriptors to display.
        $displaydescriptors = [];
        foreach ($this->export_descriptors_values() as $descriptor) {
            if (in_array($descriptor['name'], ['purpose'])) {
                $displaydescriptors[] = $descriptor;
            } else if (in_array($descriptor['name'], array_keys($this->descriptors))) {
                $displaydescriptors[] = $descriptor;
            }
        }

        $data = [
            'keywords' => join(' ', $this->keywords),
            'descriptors' => array_merge(
                $displaydescriptors,
                $this->export_plugintype_descriptor(),
                $this->export_supported_moodle_versions(),
                $this->export_award_descriptor(),
                $this->export_set_descriptor()
            ),
            'sortby' => $this->sortby,
        ];

        return $data;
    }

    /**
     * Exports the data for rendering the descriptors selectors.
     *
     * @todo mucify
     * @return array
     */
    protected function export_descriptors_values() {
        global $DB;

        $sql = "SELECT DISTINCT d.id, d.title, d.sortorder, v.value
                  FROM {local_plugins_desc} d
             LEFT JOIN {local_plugins_desc_values} v ON v.descid = d.id
              ORDER BY d.sortorder, v.value";

        $recordset = $DB->get_recordset_sql($sql);
        $desctitles = [];
        $descvalues = [];

        foreach ($recordset as $record) {
            if (!isset($desctitles[$record->id])) {
                $desctitles[$record->id] = $record->title;
            }

            if ($record->value !== null) {
                $descvalues[$record->id][$record->value] = $record->value;
            }
        }

        $recordset->close();

        $data = [];

        foreach ($desctitles as $descid => $desctitle) {
            if (empty($descvalues[$descid])) {
                continue;
            }
            $descname = strtolower(str_replace(' ', '-', $desctitle));
            $desc = [
                'title' => $desctitle,
                'name' => $descname,
                'values' => [
                    [
                        'title' => $desctitle . ' (any)',
                        'value' => '',
                        'selected' => false,
                    ]
                ]
            ];
            foreach ($descvalues[$descid] as $descvalue) {
                if (isset($this->descriptors[$descname]) and strtolower($this->descriptors[$descname]) === strtolower($descvalue)) {
                    $selected = true;
                } else {
                    $selected = false;
                }
                $desc['values'][] = [
                    'title' => $descvalue,
                    'value' => strtolower($descvalue),
                    'selected' => $selected,
                ];
            }
            $data[] = $desc;
        }

        return $data;
    }

    /**
     * Returns a descriptor representing list of all known plugin types for the filter form.
     *
     * @return array
     */
    protected function export_plugintype_descriptor() {

        // Max length of plugin type name to fit the filter on mobiles.
        $maxlength = 34;

        $types = [[
            'title' => 'Plugin type (any)',
            'value' => '',
            'selected' => false,
        ]];

        $typeman = local_plugins_type_manager::instance();

        foreach ($typeman->list_types() as $info) {
            if (empty($info['count'])) {
                // Do not offer types with no plugin approved.
                continue;
            }

            if (strtolower($this->type) === strtolower($info['type'])) {
                $selected = true;
            } else {
                $selected = false;
            }

            $title = $info['name'];
            if (strlen($title) > $maxlength) {
                $title = substr($title, 0, $maxlength - 1).'…';
            }
            $types[] = [
                'title' => $title,
                'value' => $info['type'],
                'selected' => $selected,
            ];
        }

        $descriptor = [
            'title' => 'Plugin type',
            'name' => 'type',
            'values' => $types,
        ];

        return [$descriptor];
    }

    /**
     * Returns a descriptor representing list of supported Moodle versions for the filter form.
     *
     * @todo mucify
     * @return array
     */
    protected function export_supported_moodle_versions() {
        global $DB;

        $records = $DB->get_records('local_plugins_software_vers', ['name' => 'Moodle'], 'version DESC', 'id, releasename');
        $versions = [
            [
                'title' => 'Moodle version (any)',
                'value' => '',
                'selected' => false,
            ]
        ];

        foreach ($records as $record) {
            if ($this->moodleversion === $record->releasename) {
                $selected = true;
            } else {
                $selected = false;
            }

            $versions[] = [
                'title' => 'Moodle '.$record->releasename,
                'value' => $record->releasename,
                'selected' => $selected,
            ];
        }

        $descriptor = [
            'title' => 'Moodle version',
            'name' => 'moodle-version',
            'values' => $versions,
            'advanced' => true,
        ];

        return [$descriptor];
    }

    /**
     * Returns a descriptor representing list of awards for the filter form.
     *
     * @todo mucify
     * @return array
     */
    protected function export_award_descriptor() {
        global $DB;

        $sql = "SELECT id, name, shortname
                  FROM {local_plugins_awards}
                 WHERE shortname IS NOT NULL
                   AND shortname <> ''
                   AND onfrontpage = 1
              ORDER BY name";

        $records = $DB->get_records_sql($sql);
        $awards = [
            [
                'title' => 'Received award (any)',
                'value' => '',
                'selected' => false,
            ]
        ];

        foreach ($records as $record) {
            if ($this->award === $record->shortname) {
                $selected = true;
            } else {
                $selected = false;
            }

            $awards[] = [
                'title' => format_string($record->name),
                'value' => $record->shortname,
                'selected' => $selected,
            ];
        }

        $descriptor = [
            'title' => 'Received award',
            'name' => 'award',
            'values' => $awards,
            'advanced' => true,
        ];

        return [$descriptor];
    }

    /**
     * Returns a descriptor representing list of sets for the filter form.
     *
     * @todo mucify
     * @return array
     */
    protected function export_set_descriptor() {
        global $DB;

        $sql = "SELECT id, name, shortname
                  FROM {local_plugins_set}
                 WHERE shortname IS NOT NULL
                   AND shortname <> ''
                   AND onfrontpage = 1
              ORDER BY name";

        $records = $DB->get_records_sql($sql);
        $sets = [
            [
                'title' => 'Part of set (any)',
                'value' => '',
                'selected' => false,
            ]
        ];

        foreach ($records as $record) {
            if ($this->set === $record->shortname) {
                $selected = true;
            } else {
                $selected = false;
            }

            $sets[] = [
                'title' => format_string($record->name),
                'value' => $record->shortname,
                'selected' => $selected,
            ];
        }

        $descriptor = [
            'title' => 'Part of set',
            'name' => 'set',
            'values' => $sets,
            'advanced' => true,
        ];

        return [$descriptor];
    }

    /**
     * Parses the raw filter query string as coming from the form.
     *
     * @param string $query as coming from the client
     */
    public function parse_query($query) {

        $query = trim($query);

        if ($query === '') {
            return;
        }

        $words = preg_split("/[\s,]*\\\"([^\\\"]+)\\\"[\s,]*|" . "[\s,]*'([^']+)'[\s,]*|" . "[\s,]+/", $query, 0,
            PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);

        $words = array_unique($words);

        foreach ($words as $word) {
            if (preg_match('/^sort-by:('.join('|', array_keys($this->sortmap)).')$/', $word, $matches)) {
                $this->sortby = $matches[1];

            } else if (preg_match('/^moodle-version:([1-9]+\.[0-9]+)$/', $word, $matches)) {
                $this->moodleversion = $matches[1];

            } else if (preg_match('/^award:([a-z0-9_-]+)$/i', $word, $matches)) {
                $this->award = $matches[1];

            } else if (preg_match('/^set:([a-z0-9_-]+)$/i', $word, $matches)) {
                $this->set = $matches[1];

            } else if (preg_match('/^type:([a-z_]+)$/', $word, $matches)) {
                $this->type = $matches[1];

            } else if (preg_match('/^([a-z0-9_-]+):([^:]+)$/i', $word, $matches)) {
                if (in_array($matches[1], ['sort-by', 'moodle-version', 'type'])) {
                    // These are not real descriptors even if they use same syntax. If we got here, then it was
                    // used in an unexpected way such as "moodle-version:stable" and we ignore such query items.
                    continue;
                }
                $this->descriptors[strtolower($matches[1])] = $matches[2];

            } else {
                $this->keywords[] = $word;
            }
        }
    }

    /**
     * Encode the parsed query back to a string.
     *
     * This is effectively reverse operation to {@link self::parse_query()}.
     *
     * @return string
     */
    public function encode_query() {

        $qitems = [];

        if (!empty($this->keywords)) {
            foreach ($this->keywords as $keyword) {
                if (strpos($keyword, ' ') !== false) {
                    $qitems[] = '"'.$keyword.'"';
                } else {
                    $qitems[] = $keyword;
                }
            }
        }

        if (!empty($this->descriptors)) {
            foreach ($this->descriptors as $desctitle => $descvalue) {
                if (strpos($desctitle, ' ') !== false or strpos($descvalue, ' ') !== false) {
                    $qitems[] = '"'.$desctitle.':'.$descvalue.'"';
                } else {
                    $qitems[] = $desctitle.':'.$descvalue;
                }
            }
        }

        if (!empty($this->type)) {
            $qitems[] = 'type:'.$this->type;
        }

        if (!empty($this->moodleversion)) {
            $qitems[] = 'moodle-version:'.$this->moodleversion;
        }

        if (!empty($this->award)) {
            $qitems[] = 'award:'.$this->award;
        }

        if (!empty($this->set)) {
            $qitems[] = 'set:'.$this->set;
        }

        if (!empty($this->sortby)) {
            $qitems[] = 'sort-by:'.$this->sortby;
        }

        $q = implode(' ', $qitems);
        $q = trim($q);

        return $q;
    }

    /**
     * Return the list of keywords subsets used for results ordering.
     *
     * @param int $maxitems Max number of items to return
     * @return array
     */
    public function order_by_keywords(int $maxitems = 11) {

        $result = [];

        // Individual keywords.
        foreach ($this->keywords as $keyword) {
            $result[] = [
                'text' => $keyword,
                'weight' => 1,
            ];
        }

        // Selected subsets.
        for ($i = 2; $i <= count($this->keywords); $i++) {
            $slice = array_slice($this->keywords, 0, $i);
            array_unshift($result, [
                'text' =>  implode(' ', $slice),
                'weight' => count($slice),
            ]);
        }

        return array_slice($result, 0, $maxitems);
    }
}

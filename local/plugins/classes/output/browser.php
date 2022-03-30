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
 * Provides {@link local_plugins\output\browser} class.
 *
 * @package local_plugins
 * @subpackage output
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\output;

defined('MOODLE_INTERNAL') || die();

use coding_exception;
use core_date;
use local_plugins_type_manager;
use local_plugins_url;
use moodle_page;
use moodle_url;
use renderable;
use renderer_base;
use templatable;

/**
 * Represents a sequence of plugins that can be browsed.
 *
 * Typical usage of the class is to (1) create a new instance, (2) populate the
 * data with a method like search() and (3) export the data so they can be
 * rendered.
 *
 * @copyright 2016 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class browser implements renderable, templatable {

    /** @var array */
    protected $plugins = null;

    /** @var string how the data were populated (e.g. "search") */
    protected $source = null;

    /** @var local_plugins\output\filter instance used to populate the plugins data via the search() call */
    protected $filter = null;

    /** @var int the batch index of the loaded data */
    protected $batch = null;

    /** @var int the number of plugins in one batch */
    protected $batchsize = null;

    /**
     * Constructor
     *
     * @param int $batchsize Size of a batch for this browser (useful mostly for unit tests)
     */
    public function __construct($batchsize = 30) {

        $this->batchsize = $batchsize;
    }

    /**
     * Exports the browser data for the template.
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {

        $this->assert_populated();

        foreach ($this->plugins as &$plugin) {
            $plugin->rawinfo = json_encode($plugin);
        }

        $data = (object)[
            'control' => $this->filter->export_for_template($output),
            'grid' => (object)[
                'plugins' => $this->plugins,
                'screenshotloading' => $output->image_url('screenshotloading', 'local_plugins')->out(),
                'source' => $this->source,
                'query' => $this->filter->encode_query(),
                'batch' => $this->batch,
                'batchsize' => $this->batchsize,
            ],
        ];

        return $data;
    }

    /**
     * Populate the browser contents with search results.
     *
     * @param local_plugins\output\filter $filter Filter instance to use for searching
     * @param int $batch batch index
     * @param bool $includeunapproved include not yet approved plugins (works only with keywords)
     */
    public function search(filter $filter, $batch=0, $includeunapproved=false) {
        global $DB, $OUTPUT;

        $this->assert_empty();

        $this->plugins = [];
        $this->source = 'search';
        $this->filter = $filter;
        $this->batch = $batch;

        $params = [];
        $sql = "SELECT p.id, p.name, p.frankenstyle, p.shortdescription, p.type, p.approved,
                       COALESCE(p.timelastreleased, p.timecreated) AS timelastreleased,
                       p.aggdownloads, p.aggfavs, p.aggsites,
                       u.id AS userid, u.firstname AS userfirstname, u.lastname AS userlastname,
                       v.id AS descvalueid, v.value AS descvalue, d.id AS descid, d.title AS desctitle,
                       l.filename AS logofilename, l.filepath AS logofilepath,
                       s.id AS screenshotid, s.filename AS screenshotfilename, s.filepath AS screenshotfilepath
                  FROM {local_plugins_plugin} p ";

        if (!empty($this->filter->moodleversion)) {
            $sql .= "
                  JOIN {local_plugins_vers} pv ON pv.pluginid = p.id
                  JOIN {local_plugins_supported_vers} sv ON sv.versionid = pv.id
                  JOIN {local_plugins_software_vers} sw ON sv.softwareversionid = sw.id ";
        }

        if (!empty($this->filter->award)) {
            $sql .= "
                  JOIN {local_plugins_plugin_awards} paw ON paw.pluginid = p.id
                  JOIN {local_plugins_awards} aw ON paw.awardid = aw.id ";
        }

        if (!empty($this->filter->set)) {
            $sql .= "
                  JOIN {local_plugins_set_plugin} pst ON pst.pluginid = p.id
                  JOIN {local_plugins_set} st ON pst.setid = st.id ";
        }

        $sql .= "
             LEFT JOIN {local_plugins_contributor} c ON (c.pluginid = p.id AND (c.maintainer = 1 OR c.maintainer = 2))
             LEFT JOIN {user} u ON c.userid = u.id
             LEFT JOIN {local_plugins_desc_values} v ON v.pluginid = p.id
             LEFT JOIN {local_plugins_desc} d ON v.descid = d.id
             LEFT JOIN {files} l ON (l.component='local_plugins' AND l.filearea='plugin_logo'
                       AND l.contextid = ".SYSCONTEXTID." AND l.itemid = p.id AND l.filename <> '.')
             LEFT JOIN {files} s ON (s.component='local_plugins' AND s.filearea='plugin_screenshots'
                       AND s.contextid = ".SYSCONTEXTID." AND s.itemid = p.id AND s.filename <> '.')
                 WHERE 1 = 1 ";

        if (empty($this->filter->keywords)) {
            // Even if the user has permission to view unapproved plugins, we
            // do not want them to occupy the results. Only when the user is
            // searching for something and can see unapproved plugins, we will
            // actually include them.
            $includeunapproved = false;
        }

        if (!$includeunapproved) {
            $sql .= " AND p.approved = 1 ";
        }

        if (!empty($this->filter->moodleversion)) {
            $sql .= " AND sw.name = ? AND sw.releasename = ? ";
            $params[] = 'Moodle';
            $params[] = $this->filter->moodleversion;
        }

        if (!empty($this->filter->award)) {
            $sql .= " AND aw.shortname = ? ";
            $params[] = $this->filter->award;
        }

        if (!empty($this->filter->set)) {
            $sql .= " AND st.shortname = ? ";
            $params[] = $this->filter->set;
        }

        if (!empty($this->filter->type)) {
            $sql .= " AND p.type = ? ";
            $params[] = $this->filter->type;
        }

        if (!empty($this->filter->keywords)) {
            $subsql = [];
            foreach ($this->filter->keywords as $keyword) {
                foreach (["p.name", "p.frankenstyle", "p.shortdescription", "p.description", "v.value", "u.firstname",
                        "u.lastname"] as $field) {
                    $subsql[] = $DB->sql_like($field, "?", false, false);
                    $params[] = "%".$DB->sql_like_escape($keyword)."%";
                }
            }

            $sql .= " AND ( ".implode(" OR ", $subsql)." ) ";
        }

        if (!empty($this->filter->descriptors)) {
            $subsql = [];
            foreach ($this->filter->descriptors as $desctitle => $descvalue) {
                $subsql[] = " (".
                    $DB->sql_like("d.title", "?", false, false).
                    " AND ".
                    $DB->sql_like("v.value", "?", false, false).
                    ") ";
                $params[] = $DB->sql_like_escape($desctitle);
                $params[] = $DB->sql_like_escape($descvalue);
            }

            $sql .= " AND ( ".implode(" AND ", $subsql)." ) ";
        }

        // Note: Please see how the recordset rows are grouped and processed. It
        // is important that after ordering, all the rows related to a single
        // plugin are always kept together.

        if (!empty($this->filter->sortby) and isset($this->filter->sortmap[$this->filter->sortby])) {
            // If explicit sorting is defined, use it.
            $sql .= " ORDER BY ".$this->filter->sortmap[$this->filter->sortby].", p.aggsites DESC, ";

        } else if (!empty($this->filter->keywords)) {
            // If search keywords are provided, order by relevance.
            $orderbykeywords = $this->filter->order_by_keywords();

            $sql .= " ORDER BY CASE ";
            foreach ([
                "p.name" => 100,
                "p.frankenstyle" => 90,
                "p.shortdescription" => 80,
                "p.description" => 70,
                "v.value" => 60,
                "u.lastname" => 50,
                "u.firstname" => 40,
            ] as $field => $relevance) {
                foreach ($orderbykeywords as $orderbykeyword) {
                    $sql .= " WHEN ".$DB->sql_like($field, "?", false, false)
                        . " THEN " . $relevance * $orderbykeyword['weight'];
                    $params[] = "%" . $DB->sql_like_escape($orderbykeyword['text']) . "%";
                }
            }
            $sql .= " ELSE 0 END DESC, p.aggsites DESC, ";

        } else {
            // Otherwise order by recent plugins first.
            $sql .= " ORDER BY COALESCE(p.timelastreleased, p.timecreated) DESC, ";
        }

        $sql .= "p.id DESC, c.maintainer, c.timecreated, d.sortorder, v.value, l.sortorder DESC,
            l.filepath, l.filename, s.sortorder DESC, s.filepath, s.filename ";

        $recordset = $DB->get_recordset_sql($sql, $params);
        $plugins = [];
        $index = -1;

        foreach ($recordset as $record) {
            if (!isset($plugins[$record->id])) {
                $index++;
                if ($index < $batch * $this->batchsize) {
                    // This plugin is not to be returned as a part of the requested batch.
                    $plugins[$record->id] = false;
                    continue;

                } else if ($index >= ($batch + 1) * $this->batchsize) {
                    // No more plugins are to be returned as a part of the requested batch.
                    break;

                } else {
                    // Display the plugin as a part of the batch.
                    if ($record->frankenstyle) {
                        $pluginurl = new local_plugins_url('/local/plugins/view.php', ['plugin' => $record->frankenstyle]);
                    } else {
                        $pluginurl = new local_plugins_url('/local/plugins/view.php', ['id' => $record->id]);
                    }
                    $plugins[$record->id] = (object)[
                        'id' => $record->id,
                        'index' => $index,
                        'name' => $record->name,
                        'frankenstyle' => $record->frankenstyle,
                        'plugintype' => [
                            'type' => $record->type,
                            'name' => local_plugins_type_manager::instance()->name($record->type),
                        ],
                        'approved' => $record->approved,
                        'url' => $pluginurl->out(),
                        'shortdescription' => $record->shortdescription,
                        'timelastreleased' => [
                            'absdate' => userdate($record->timelastreleased, '', core_date::get_user_timezone()),
                            'reldate' => \local_plugins\human_time_diff::for($record->timelastreleased, null, false),
                            'iso8601date' => date('c', $record->timelastreleased),
                        ],
                        'aggsites' => $record->aggsites ? $record->aggsites : null,
                        'aggdownloads' => $record->aggdownloads ? $this->kilofy($record->aggdownloads) : null,
                        'aggfavs' => $record->aggfavs ? $record->aggfavs : null,
                        'has_logo' => null,
                        'logo' => (object)[
                            'tinyicon' => null,
                            'thumb' => null,
                        ],
                        'has_screenshots' => null,
                        'mainscreenshot' => (object)[
                            'bigthumb' => $OUTPUT->image_url('screenshotmissing', 'local_plugins')->out(),
                        ],
                        'screenshots' => [],
                        'maintainers' => [],
                        'descriptors' => [],
                    ];
                }
            }

            $plugin = $plugins[$record->id];

            if ($plugin !== false) {
                if ($record->userid !== null) {
                    $plugin->maintainers[$record->userid] = (object) [
                        'id' => $record->userid,
                        'firstname' => $record->userfirstname,
                        'lastname' => $record->userlastname,
                        'url' => (new moodle_url('/user/profile.php', ['id' => $record->userid]))->out(),
                    ];
                }

                if ($record->descid !== null and $record->descvalue !== null) {
                    $plugin->descriptors[$record->descvalueid] = (object) [
                        'descriptorid' => $record->descid,
                        'value' => $record->descvalue,
                    ];
                }

                if ($plugin->has_logo === null) {
                    if ($record->logofilename === null) {
                        $plugin->has_logo = false;
                    } else {
                        $logourl = local_plugins_url::make_pluginfile_url(SYSCONTEXTID, 'local_plugins', 'plugin_logo',
                            $plugin->id, $record->logofilepath, $record->logofilename);
                        $plugin->logo = [
                            'tinyicon' => (new local_plugins_url($logourl, ['preview' => 'tinyicon']))->out(),
                            'thumb' => (new local_plugins_url($logourl, ['preview' => 'thumb']))->out(),
                        ];
                        $plugin->has_logo = true;
                    }
                }

                if ($record->screenshotid !== null) {
                    $screenshoturl = local_plugins_url::make_pluginfile_url(SYSCONTEXTID, 'local_plugins', 'plugin_screenshots',
                        $plugin->id, $record->screenshotfilepath, $record->screenshotfilename);
                    $plugin->screenshots[$record->screenshotid] = [
                        'bigthumb' => (new local_plugins_url($screenshoturl, ['preview' => 'bigthumb']))->out(),
                    ];
                }
            }
        }

        $recordset->close();

        $plugins = array_filter($plugins);

        foreach ($plugins as $plugin) {
            // Drop the internal item indexes so that data can be JSONised.
            $plugin->maintainers = array_values($plugin->maintainers);
            $plugin->descriptors = array_values($plugin->descriptors);

            if ($plugin->screenshots) {
                $plugin->screenshots = array_values($plugin->screenshots);
                $plugin->mainscreenshot = array_shift($plugin->screenshots);
                $plugin->has_screenshots = true;
            }
        }

        $this->plugins = array_values($plugins);
    }

    /**
     * Assert that the browser has not been yet populated with data.
     */
    protected function assert_empty() {

        if ($this->plugins !== null or $this->source !== null) {
            throw new coding_exception('Illegal browser instance usage - data already populated');
        }
    }

    /**
     * Assert that the browser has been populated with data.
     *
     * Note this does not mean there are some plugins. The browser can still be empty.
     */
    protected function assert_populated() {

        if ($this->plugins === null or $this->source === null) {
            throw new coding_exception('Illegal browser instance usage - data not populated');
        }
    }

    /**
     * Rounds a number to thousands if needed and displays it like '10k'
     *
     * Numbers less than 1000 are left as-are.
     *
     * @param int $number
     * @return string
     */
    protected function kilofy($number) {

        if (empty($number)) {
            $number = 0;
        }

        if ($number > 999) {
            $number = floor($number / 1000) . 'k';
        }

        return $number;
    }
}

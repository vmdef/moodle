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
 * @package     local_plugins
 * @subpackage  usage
 * @copyright   2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Controls the plugins usage stats processing.
 *
 * The only data about what plugins are installed out there come from the
 * requests for available updates. These requests are anonymously tracked at
 * the download.moodle.org server by scripts written by Eloy Lafuente.
 *
 * We have a cron job set up that rsync'es the generated *.stats files from the
 * download.moodle.org to moodle.org (into the /local_plugins/update_stats folder
 * in the data directory) - see MDLSITE-4016.
 *
 * This manager class basically loads the monthly stats into the database and
 * provides access to them.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_usage_manager {

    /** @var null|string */
    protected $logger = null;

    /** @var null|array */
    protected $cachepluginids = null;

    /**
     * Creates an instance of the manager.
     *
     * @param null|string $logger where to put the output (mtrace supported for now)
     */
    public function __construct($logger = null) {
        $this->logger = $logger;
        $this->log('Plugins usage stats processing controller initialized');
    }

    /**
     * Truncates the local_plugins_plugin_usage table.
     */
    public function clear_plugin_usage_data() {
        global $DB;

        $this->log('Truncating the plugin usage table');
        $DB->delete_records("local_plugins_plugin_usage");
    }

    /**
     * Read the contents of the monthly.stats files into the database.
     *
     * This is supposed to be executed regularly via a scheduled task. The
     * monthly stats are available at the beginning of every month. But it
     * seems reasonable and safe to run this daily.
     */
    public function update_plugin_usage_data() {

        $files = $this->get_stats_files();

        foreach ($files as $year => $months) {
            foreach($months as $month => $statsfile) {
                $stats = $this->parse_stats_file(file($statsfile));
                $this->store_monthly_stats($year, $month, $stats);
            }
        }

        $this->update_plugin_aggsites();
    }

    /**
     * Get the monthly stats on number of sites using this plugin
     *
     * @param int $pluginid
     * @return array (int)year => (int)month => (int)sites
     */
    public function get_stats_monthly($pluginid) {
        global $DB;

        $sql = "SELECT year, month, moodlever, sites
                  FROM {local_plugins_plugin_usage}
                 WHERE pluginid = :pluginid
              ORDER BY year, month, moodlever DESC";

        $rs = $DB->get_recordset_sql($sql, array('pluginid' => $pluginid));

        $data = array();

        foreach ($rs as $record) {
            if ($record->moodlever === null) {
                $ver = 'total';
            } else {
                $ver = $record->moodlever;
            }
            $data[$record->year][$record->month][$ver] = $record->sites;
        }

        $rs->close();

        return $data;
    }

    /**
     * Saves the monthly stats data in the database
     *
     * @param string $year
     * @param string $month
     * @param array $stats
     */
    protected function store_monthly_stats($year, $month, array $stats) {
        global $DB;

        $now = time();

        if ($month < 1 or $month > 12) {
            throw new coding_exception('Month out of range 1-12');
        }

        foreach ($stats as $pluginname => $usage) {
            $pluginid = $this->get_plugin_id($pluginname);

            if (empty($pluginid)) {
                continue;
            }

            foreach ($usage as $ver => $sites) {
                if ($ver === 'total') {
                    $moodlever = null;
                } else {
                    $moodlever = $ver;
                }

                $current = $DB->get_record("local_plugins_plugin_usage", array(
                    "pluginid" => $pluginid,
                    "month" => $month,
                    "year" => $year,
                    "moodlever" => $moodlever,
                ));

                if (empty($current)) {
                    $DB->insert_record("local_plugins_plugin_usage", array(
                        "pluginid" => $pluginid,
                        "month" => $month,
                        "year" => $year,
                        "moodlever" => $moodlever,
                        "sites" => $sites,
                        "timeupdated" => $now,
                    ));

                } else if ($current->sites != $usage) {
                    $DB->update_record("local_plugins_plugin_usage", array(
                        "id" => $current->id,
                        "sites" => $sites,
                        "timeupdated" => $now,
                    ));
                }
            }
        }
    }

    /**
     * Returns the id of the given plugin of null if not known
     *
     * @param string $pluginname frankenstyle name of the plugin
     * @return int|null
     */
    protected function get_plugin_id($pluginname) {
        global $DB;

        if ($this->cachepluginids === null) {
            $sql = "SELECT frankenstyle, id
                      FROM {local_plugins_plugin}
                     WHERE frankenstyle IS NOT NULL";
            $this->cachepluginids = $DB->get_records_sql($sql);
        }

        if (!isset($this->cachepluginids[$pluginname])) {
            return null;
        }

        return $this->cachepluginids[$pluginname]->id;
    }

    /**
     * Return the list of available data files to be processed.
     *
     * @return array of (int)year => (int)month => (string)filepath
     */
    protected function get_stats_files() {

        $files = $this->get_all_monthly_stats_files();

        $recentyear = $this->get_recently_processed_year();
        $recentmonth = $this->get_recently_processed_month($recentyear);
        $currentyear = $this->get_current_year();
        $currentmonth = $this->get_current_month();

        return $this->filter_stats_files($files, $recentyear, $recentmonth, $currentyear, $currentmonth);
    }

    /**
     * Sets the aggsites property of the plugin records
     */
    protected function update_plugin_aggsites() {
        global $DB;

        // Load the most recent available stats.
        $sql = "SELECT a.pluginid, a.sites
                  FROM {local_plugins_plugin_usage} a
                  JOIN (SELECT pluginid, MAX(year * 12 + month) AS stamp
                          FROM {local_plugins_plugin_usage}
                         WHERE moodlever IS NULL
                      GROUP BY pluginid) b ON a.pluginid = b.pluginid AND b.stamp = a.year * 12 + a.month
                 WHERE a.moodlever IS NULL";

        $recent = $DB->get_records_sql_menu($sql);

        $sql = "SELECT id, aggsites
                  FROM {local_plugins_plugin}
                 WHERE frankenstyle IS NOT NULL";

        $rs = $DB->get_recordset_sql($sql);

        foreach ($rs as $plugin) {
            if (!isset($recent[$plugin->id])) {
                continue;
            }

            if ($recent[$plugin->id] == $plugin->aggsites) {
                continue;
            }

            $DB->set_field("local_plugins_plugin", "aggsites", $recent[$plugin->id], array("id" => $plugin->id));
        }

        $rs->close();
    }

    /**
     * Return the list of all available monthly.stats files
     *
     * @return array of (int)year => (int)month => (string)filepath
     */
    protected function get_all_monthly_stats_files() {

        $root = $this->get_stats_files_root();

        if (empty($root)) {
            $this->log('Source data files root directory not defined');
            return array();
        }

        if (!is_dir($root)) {
            $this->log('Source data files root directory does not exist', 'ERR');
            return array();
        }

        $files = array();

        foreach (new DirectoryIterator($root) as $yeardir) {
            if ($yeardir->isDot()) {
                continue;
            }

            if (!$yeardir->isDir()) {
                $this->log('Unexpected file detected: '.$yeardir->getPathname(), 'DBG');
                continue;
            }

            $year = $yeardir->getFilename();

            if (!is_numeric($year)) {
                $this->log('Unexpected folder detected: '.$yeardir->getPathname(), 'DBG');
                continue;
            }

            foreach (new DirectoryIterator($root.'/'.$year) as $monthdir) {
                if ($monthdir->isDot()) {
                    continue;
                }

                if (!$monthdir->isDir()) {
                    $this->log('Unexpected file detected: '.$monthdir->getPathname(), 'DBG');
                    continue;
                }

                $month = $monthdir->getFilename();

                if (!is_numeric($month)) {
                    $this->log('Unexpected folder detected: '.$monthdir->getPathname(), 'DBG');
                    continue;
                }

                foreach (new DirectoryIterator($root.'/'.$year.'/'.$month) as $file) {
                    if ($file->isDot()) {
                        continue;
                    }

                    if ($file->isDir()) {
                        $this->log('Unexpected folder detected: '.$file->getPathname(), 'DBG');
                        continue;
                    }

                    if ($file->getFilename() === 'monthly.stats') {
                        $files[$year][$month] = $file->getPathname();
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Returns the full path to the root directory of the monthly stats files.
     *
     * This is where Eloy's stats files are rsync'ed to.
     *
     * @return string
     */
    protected function get_stats_files_root() {
        global $CFG;

        return get_config('local_plugins', 'usagestatsfilesroot');
    }

    /**
     * Return the current year.
     *
     * @return string Four digit integer number such as '2015'
     */
    protected function get_current_year() {
        return date('Y');
    }

    /**
     * Return the current month.
     *
     * @return string Two digits integer number such as '09'
     */
    protected function get_current_month() {
        return date('m');
    }

    /**
     * Return the most recent year for which we have data already processed.
     *
     * @return string|null Four digit integer number such as '2015' or null
     */
    protected function get_recently_processed_year() {
        global $DB;

        return $DB->get_field_select("local_plugins_plugin_usage", "MAX(year)", null);
    }

    /**
     * Get the last month in the given year for which we hava data already processed.
     *
     * @param string|null $year
     * @return string|null
     */
    protected function get_recently_processed_month($year) {
        global $DB;

        if ($year === null) {
            return null;
        }

        return $DB->get_field_select("local_plugins_plugin_usage", "MAX(month)", "year = ?", array($year));
    }

    /**
     * Filter the list of monthly.stats files and return only those to be processed.
     *
     * Given the starting/ending year and month, this function returns the
     * list of files that hold the monthly stats for all the months in the
     * given period.
     *
     * If the given year or month is set to null, it does not limit the time
     * range.
     *
     * @param array $files (int)year => (int)month => (string)filepath
     * @param string|null $yearfrom
     * @param string|null $monthfrom
     * @param string|null $yearto
     * @param string|null $monthto
     * @return array of (int)year => (int)month => (string)filepath
     */
    protected function filter_stats_files(array $files, $yearfrom = null, $monthfrom = null, $yearto = null, $monthto = null) {

        if (($monthfrom !== null and $yearfrom === null) or ($monthto !== null and $yearto === null)) {
            throw new coding_exception('Missing year in the period limit specification.');
        }

        $filtered = array();

        foreach ($files as $y => $months) {
            foreach ($months as $m => $filepath) {
                if ($yearfrom !== null and $y < $yearfrom) {
                    continue;

                } else if ($yearto !== null and $y > $yearto) {
                    continue;

                } else if ($y == $yearfrom and $monthfrom !== null and $m < $monthfrom) {
                    continue;

                } else if ($y == $yearto and $monthto !== null and $m > $monthto) {
                    continue;

                } else {
                    $filtered[$y][$m] = $filepath;
                }
            }
        }

        return $filtered;
    }

    /**
     * Parse the contents of the stats file and return relevant info.
     *
     * @param array $lines
     * @return array of (string)pluginname => (string)moodlever => (int)number of installations
     */
    protected function parse_stats_file(array $lines) {

        $data = array();
        $total = '|^plugin ([a-z]+(_[a-z][a-z0-9_]*)?[a-z0-9]+): ([0-9]+) \(.+%\)$|';
        $byver = '|^- plugin ([a-z]+(_[a-z][a-z0-9_]*)?[a-z0-9]+) on moodle ([1-9]+\.[0-9]+): ([0-9]+)$|';

        foreach ($lines as $line) {
            if (preg_match($total, $line, $matches)) {
                $data[$matches[1]]['total'] = (int)$matches[3];
            }
            if (preg_match($byver, $line, $matches)) {
                $data[$matches[1]][$matches[3]] = (int)$matches[4];
            }
        }

        return $data;
    }

    /**
     * Records an output log message
     *
     * @param string $message to be logged
     * @param string $type of the message DBG|INF|WRN|ERR
     * @param int $indent indentation level
     */
    protected function log($message, $type = 'INF', $indent = 0) {

        if ($this->logger === null or $this->logger === 'null') {
            return;
        }

        $output = sprintf('[%s] %s%s', $type, str_repeat("  ", $indent), $message);

        switch ($this->logger) {
        case 'mtrace':
            mtrace($output);
            break;
        default:
            throw new coding_exception('Unsupported logger type '.$this->logger);
        }
    }
}

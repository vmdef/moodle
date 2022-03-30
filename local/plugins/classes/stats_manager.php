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
 * Stats processing controller class is defined here
 *
 * @package     local_plugins
 * @subpackage  stats
 * @copyright   2014 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Controls the stats processing
 *
 * @copyright 2014 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_plugins_stats_manager {

    /** @var mixed */
    protected $logger = null;

    /**
     * Creates an instance of the manager
     *
     * @param null|string $logger where to put the output (mtrace supported for now)
     */
    public function __construct($logger = null) {
        $this->logger = $logger;
        $this->log('Stats processing controller initialized');
    }

    /**
     * Record an attempt to download the give plugin version
     *
     * @param int $versionid
     * @param int $timedownloaded timestamp
     * @param int $userid
     * @param string $downloadmethod
     * @param int $ip
     * @param bool $exclude force exclude flag, defaults to automatic anti-spam protection
     * @return bool false if the log should be excluded from stats, true otherwise
     */
    public function log_version_download($versionid, $timedownloaded, $userid, $downloadmethod, $ip, $exclude = null) {
        global $DB;

        if (is_null($exclude)) {
            $exclude = $this->should_exclude_download($versionid, $timedownloaded, $ip);
        }

        $info = array();
        foreach (array('SERVER_PROTOCOL', 'REQUEST_METHOD', 'REQUEST_TIME_FLOAT', 'HTTP_REFERER',
                'HTTP_USER_AGENT', 'REMOTE_ADDR', 'REQUEST_URI') as $property) {
            if (isset($_SERVER[$property])) {
                $info[$property] = $_SERVER[$property];
            }
        }

        $DB->insert_record('local_plugins_stats_raw', array(
            'versionid' => $versionid,
            'timedownloaded' => $timedownloaded,
            'userid' => $userid,
            'downloadmethod' => $downloadmethod,
            'ip' => $ip,
            'exclude' => $exclude,
            'info' => json_encode($info),
        ));

        return empty($exclude);
    }

    /**
     * Updates the download statistics for the plugins
     *
     * @param int $from timestamp indicating the first month to start with, defaults to the start of epoch
     * @param int $to timestamp indicating the last month to end with, defaults to the current month
     * @throws local_plugins_exception
     */
    public function update_download_stats($from = null, $to = null) {

        $this->update_download_stats_recent();
        $this->update_download_stats_monthly($from, $to);
    }

    /**
     * Truncates the local_plugins_stats_cache table
     */
    public function reset_stats_cache_table() {
        global $DB;

        $this->log('Truncating the stats cache table');
        $DB->delete_records('local_plugins_stats_cache');
    }

    /**
     * How many recent valid downloads are there tracked in our logs (all plugins).
     *
     * @return int
     */
    public function get_stats_downloads_recent() {
        global $DB;

        $sql = "SELECT SUM(downloads)
                  FROM {local_plugins_stats_cache}
                 WHERE month = 0 AND year = 0";

        return (int)$DB->get_field_sql($sql);
    }

    /**
     * How many times the given plugin has been downloaded recently.
     *
     * @param int $pluginid
     * @return int
     */
    public function get_stats_plugin_recent($pluginid) {
        global $DB;

        $sql = "SELECT SUM(downloads)
                  FROM {local_plugins_stats_cache}
                 WHERE pluginid = :pluginid AND month = 0 AND year = 0";

        return (int)$DB->get_field_sql($sql, array('pluginid' => $pluginid));
    }

    /**
     * How many times the given plugin has been downloaded each month
     *
     * @param int $pluginid
     * @param int|null $from timestamp, defaults to the start of the epoch
     * @param int|null $to timestamps, defaults to now
     * @return array of (int)year => (int)month => (int)downloads
     */
    public function get_stats_plugin_monthly($pluginid, $from = null, $to = null) {
        global $DB;

        if (is_null($from)) {
            $from = (int)$DB->get_field_sql("SELECT MIN(timedownloaded) FROM {local_plugins_stats_raw}");
        }

        if (is_null($to)) {
            $to = time();
        }

        if ($from > $to) {
            throw new coding_exception('From date bigger than to date.');
        }

        $yearfrom = (int)date('Y', $from);
        $monthfrom = (int)date('n', $from);
        $yearto = (int)date('Y', $to);
        $monthto = (int)date('n', $to);

        // Initialize the structure being returned.
        $return = array();
        for ($year = $yearfrom; $year <= $yearto; $year++) {
            for ($month = 1; $month <= 12; $month++) {

                if ($year == $yearfrom and $month < $monthfrom) {
                    continue;
                }

                if ($year == $yearto and $month > $monthto) {
                    continue;
                }

                $return[$year][$month] = 0;
            }
        }

        $sql = "SELECT year, month, SUM(downloads) AS downloads
                  FROM {local_plugins_stats_cache}
                 WHERE pluginid = :pluginid AND year <> 0 AND month <> 0 AND (
                              (year = $yearfrom AND year < $yearto AND month >= $monthfrom)
                           OR (year > $yearfrom AND year < $yearto)
                           OR (year = $yearto AND year > $yearfrom AND month <= $monthto)
                           OR (year = $yearto AND year = $yearfrom AND month >= $monthfrom AND month <= $monthto)
                       )
              GROUP BY year, month
              ORDER BY year, month";

        $params = array('pluginid' => $pluginid);

        $recordset = $DB->get_recordset_sql($sql, $params);

        foreach ($recordset as $record) {
            $return[$record->year][$record->month] = $record->downloads;
        }

        return $return;
    }

    /**
     * How many times each plugin's version has been downloaded each month
     *
     * @param int $pluginid
     * @param int|null $from timestamp, defaults to the start of the epoch
     * @param int|null $to timestamps, defaults to now
     * @return array of (int)year => (int)month => (int)versionid => (object)info
     */
    public function get_stats_plugin_by_version_monthly($pluginid, $from = null, $to = null) {
        global $DB;

        if (is_null($from)) {
            $from = (int)$DB->get_field_sql("SELECT MIN(timedownloaded) FROM {local_plugins_stats_raw}");
        }

        if (is_null($to)) {
            $to = time();
        }

        if ($from > $to) {
            throw new coding_exception('From date bigger than to date.');
        }

        $yearfrom = (int)date('Y', $from);
        $monthfrom = (int)date('n', $from);
        $yearto = (int)date('Y', $to);
        $monthto = (int)date('n', $to);

        // Initialize the structure being returned.
        $return = array();
        for ($year = $yearfrom; $year <= $yearto; $year++) {
            for ($month = 1; $month <= 12; $month++) {

                if ($year == $yearfrom and $month < $monthfrom) {
                    continue;
                }

                if ($year == $yearto and $month > $monthto) {
                    continue;
                }

                $return[$year][$month] = array();
            }
        }

        $sql = "SELECT d.year, d.month, v.id, COALESCE(v.releasename, v.version) AS name, d.downloads, v.approved, v.visible,
                       v.timecreated AS timereleased
                  FROM {local_plugins_vers} v
             LEFT JOIN {local_plugins_stats_cache} d ON d.versionid = v.id
                 WHERE v.pluginid = :pluginid AND v.approved = :approved AND v.visible = 1 AND d.year <> 0 AND d.month <> 0 AND (
                              (d.year = $yearfrom AND d.year < $yearto AND d.month >= $monthfrom)
                           OR (d.year > $yearfrom AND d.year < $yearto)
                           OR (d.year = $yearto AND d.year > $yearfrom AND d.month <= $monthto)
                           OR (d.year = $yearto AND d.year = $yearfrom AND d.month >= $monthfrom AND d.month <= $monthto)
                       )
              ORDER BY d.year, d.month, v.id";

        $params = array('pluginid' => $pluginid, 'approved' => local_plugins_plugin::PLUGIN_APPROVED);

        $recordset = $DB->get_recordset_sql($sql, $params);

        // Make sure that for all months, we return the same amount of versions
        // so that our charts API can display them nicely.
        $default = [];

        foreach ($recordset as $record) {
            $info = (object)array(
                'id' => $record->id,
                'name' => $record->name,
                'downloads' => $record->downloads,
                'timereleased' => $record->timereleased,
            );
            $return[$record->year][$record->month][$record->id] = $info;
            $default[$record->id] = (object)array(
                'id' => $record->id,
                'name' => $record->name,
                'downloads' => 0,
                'timereleased' => $record->timereleased,
            );
        }

        $recordset->close();

        foreach ($return as $year => $months) {
            foreach ($months as $month => $versions) {
                $return[$year][$month] = $versions + $default;
                ksort($return[$year][$month]);
            }
        }

        return $return;
    }

    /**
     * Get top downloaded plugins
     *
     * @param int $categoryid empty for all categories, non-empty for the given category only
     * @param int $limit how many plugins to be reported
     * @param mixed $from timestamp, defaults to the start of downloads tracking
     * @param mixed $to timestamp, defaults to now
     * @return array of (int)pluginid => (object)(->(string)name ->(int)downloads
     */
    public function get_stats_top_plugins($categoryid = 0, $limit = 20, $from = null, $to = null) {
        global $DB;

        if (is_null($from)) {
            $from = (int)$DB->get_field_sql("SELECT MIN(timedownloaded) FROM {local_plugins_stats_raw}");
        }

        if (is_null($to)) {
            $to = time();
        }

        if ($from > $to) {
            throw new coding_exception('From date bigger than to date.');
        }

        $yearfrom = (int)date('Y', $from);
        $monthfrom = (int)date('n', $from);
        $yearto = (int)date('Y', $to);
        $monthto = (int)date('n', $to);

        $params = array();

        if ($categoryid) {
            $categoryjoin = " JOIN {local_plugins_category} c ON c.id = p.categoryid ";
            $categorywhere = " AND c.id = :categoryid ";
            $params['categoryid'] = $categoryid;
        } else {
            $categoryjoin = " ";
            $categorywhere = " ";
        }

        $sql = "SELECT p.id, p.name, SUM(d.downloads) AS downloads
                  FROM {local_plugins_plugin} p
                       {$categoryjoin}
                  JOIN {local_plugins_stats_cache} d ON d.pluginid = p.id
                 WHERE p.approved = :approved AND p.visible = 1 AND d.year <> 0 AND d.month <> 0 AND (
                              (d.year = $yearfrom AND d.year < $yearto AND d.month >= $monthfrom)
                           OR (d.year > $yearfrom AND d.year < $yearto)
                           OR (d.year = $yearto AND d.year > $yearfrom AND d.month <= $monthto)
                           OR (d.year = $yearto AND d.year = $yearfrom AND d.month >= $monthfrom AND d.month <= $monthto)
                       ) {$categorywhere}
              GROUP BY p.id, p.name
                HAVING SUM(d.downloads) > 0
              ORDER BY SUM(d.downloads) DESC, p.name, p.id";

        $params['approved'] = local_plugins_plugin::PLUGIN_APPROVED;

        return $DB->get_records_sql($sql, $params, 0, $limit);
    }

    /**
     * How many total downloads were there each month
     *
     * @param int $categoryid empty for all categories, non-empty for the given category only
     * @param int|null $from timestamp, defaults to the start of the epoch
     * @param int|null $to timestamps, defaults to now
     * @return array of (int)year => (int)month => (int)downloads
     */
    public function get_stats_total_monthly($categoryid = 0, $from = null, $to = null) {
        global $DB;

        if (is_null($from)) {
            $from = (int)$DB->get_field_sql("SELECT MIN(timedownloaded) FROM {local_plugins_stats_raw}");
        }

        if (is_null($to)) {
            $to = time();
        }

        if ($from > $to) {
            throw new coding_exception('From date bigger than to date.');
        }

        $yearfrom = (int)date('Y', $from);
        $monthfrom = (int)date('n', $from);
        $yearto = (int)date('Y', $to);
        $monthto = (int)date('n', $to);

        // Initialize the structure being returned.
        $return = array();
        for ($year = $yearfrom; $year <= $yearto; $year++) {
            for ($month = 1; $month <= 12; $month++) {

                if ($year == $yearfrom and $month < $monthfrom) {
                    continue;
                }

                if ($year == $yearto and $month > $monthto) {
                    continue;
                }

                $return[$year][$month] = 0;
            }
        }

        if (!empty($categoryid)) {
            $catfrom = " JOIN {local_plugins_plugin} p ON p.id = d.pluginid ";
            $catwhere = " p.categoryid = :categoryid AND ";
            $params = array('categoryid' => $categoryid);

        } else {
            $catfrom = '';
            $catwhere = '';
            $params = array();
        }

        $sql = "SELECT d.year, d.month, SUM(d.downloads) AS downloads
                  FROM {local_plugins_stats_cache} d $catfrom
                 WHERE $catwhere d.year <> 0 AND d.month <> 0 AND (
                              (d.year = $yearfrom AND d.year < $yearto AND d.month >= $monthfrom)
                           OR (d.year > $yearfrom AND d.year < $yearto)
                           OR (d.year = $yearto AND d.year > $yearfrom AND d.month <= $monthto)
                           OR (d.year = $yearto AND d.year = $yearfrom AND d.month >= $monthfrom AND d.month <= $monthto)
                       )
              GROUP BY d.year, d.month
              ORDER BY d.year, d.month";

        $recordset = $DB->get_recordset_sql($sql, $params);

        foreach ($recordset as $record) {
            $return[$record->year][$record->month] = $record->downloads;
        }

        $recordset->close();

        return $return;
    }

    /**
     * Updates the recent downloads by version stats
     *
     * Data for this stats are stored in the table local_plugins_stats_cache
     * with columns month and year set to 0. The downloads then contains the
     * total number of recent downloads of the given plugin version.
     *
     * By "recent" we understand "in last 90 days".
     */
    protected function update_download_stats_recent() {
        global $DB;

        $this->log('Updating recent download stats');

        $sql = "SELECT v.pluginid, v.id AS versionid, COALESCE(j.num, 0) AS num
                  FROM {local_plugins_vers} v
             LEFT JOIN (SELECT c.pluginid, c.id AS versionid, COUNT(d.id) AS num
                          FROM {local_plugins_stats_raw} d
                          JOIN {local_plugins_vers} c ON d.versionid = c.id
                         WHERE d.exclude <> 1 AND d.timedownloaded >= ?
                      GROUP BY c.pluginid, c.id) j ON v.id = j.versionid";

        $this->log('Aggregating recent downloads by plugin and version', 'DBG', 1);
        $recordset = $DB->get_recordset_sql($sql, array(time() - 90 * DAYSECS));
        $pluginids = array();
        if ($recordset->valid()) {
            $this->log('Updating recent figures in the stats cache table', 'DBG', 1);
            foreach ($recordset as $record) {
                $this->update_stats_cache($record->pluginid, $record->versionid, 0, 0, $record->num);
                $pluginids[$record->pluginid] = true;
            }
        }
        $recordset->close();

        $this->log('Updating the plugin->aggdownloads properties', 'DBG', 1);
        foreach ($pluginids as $pluginid => $notused) {
            $recent = $this->get_stats_plugin_recent($pluginid);
            $DB->set_field('local_plugins_plugin', 'aggdownloads', $recent, array('id' => $pluginid));
        }
    }

    /**
     * Updates the total downloads by version stats
     *
     * @param int $from timestamp indicating the first month to start with, defaults to the start of epoch
     * @param int $to timestamp indicating the last month to end with, defaults to the current month
     */
    protected function update_download_stats_monthly($from = null, $to = null) {
        global $DB;

        if (is_null($from)) {
            $from = (int)$DB->get_field_sql("SELECT MIN(timedownloaded) FROM {local_plugins_stats_raw}");
        }

        if (is_null($to)) {
            $to = time();
        }

        if ($from > $to) {
            throw new coding_exception('From date bigger than to date.');
        }

        $yearfrom = date('Y', $from);
        $monthfrom = date('n', $from);
        $yearto = date('Y', $to);
        $monthto = date('n', $to);

        $this->log(sprintf('Updating monthly download stats from %d/%d to %d/%d', $yearfrom, $monthfrom, $yearto, $monthto));

        for ($year = $yearfrom; $year <= $yearto; $year++) {
            for ($month = 1; $month <= 12; $month++) {

                if ($year == $yearfrom and $month < $monthfrom) {
                    continue;
                }

                if ($year == $yearto and $month > $monthto) {
                    continue;
                }

                $this->log('Aggregating monthly downloads by plugin and version for '.$year.'/'.$month, 'DBG', 1);

                $timestart = mktime(0, 0, 0, $month, 1, $year);
                $timeend = mktime(0, 0, 0, $month + 1, 1, $year);

                $sql = "SELECT v.pluginid, v.id AS versionid, COALESCE(j.num, 0) AS num
                          FROM {local_plugins_vers} v
                     LEFT JOIN (SELECT c.pluginid, c.id AS versionid, COUNT(d.id) AS num
                                  FROM {local_plugins_vers} c
                                  JOIN {local_plugins_stats_raw} d ON c.id = d.versionid
                                 WHERE d.timedownloaded >= :timestart AND d.timedownloaded < :timeend AND d.exclude <> 1
                              GROUP BY c.pluginid, c.id) j ON v.id = j.versionid";

                $params = array('timestart' => $timestart, 'timeend' => $timeend);
                $recordset = $DB->get_recordset_sql($sql, $params);

                if ($recordset->valid()) {
                    foreach ($recordset as $record) {
                        $this->update_stats_cache($record->pluginid, $record->versionid, $year, $month, $record->num);
                    }
                }
            }
        }
    }

    /**
     * Should an attempt to download the given versionid be excluded from the stats?
     *
     * Checks for the number of recent downloads from that IP address. If the
     * plugin version has been downloaded from the given IP ten times during
     * the last 24 hours, this new attempt is excluded from stats. In other
     * words, for stats processing, we count only the first ten download
     * attempts a day. This may be tweaked in the future as needed.
     *
     * @param int $versionid
     * @param int $timedownloaded
     * @param string $ip
     * @return bool
     */
    protected function should_exclude_download($versionid, $timedownloaded, $ip) {
        global $DB;

        $sql = "SELECT COUNT(*)
                  FROM {local_plugins_stats_raw}
                 WHERE versionid = :versionid AND ip = :ip AND ABS(timedownloaded - :timedownloaded) < :interval";

        $params = array('versionid' => $versionid, 'timedownloaded' => $timedownloaded, 'ip' => $ip, 'interval' => DAYSECS);

        // How many times the version was downloaded from the given IP in the last 24 hours?
        $recent = $DB->count_records_sql($sql, $params);

        // If more than ten times, ignore further downloads in the stats.
        if ($recent >= 10) {
            return true;
        }

        // This seems valid, count that.
        return false;
    }

    /**
     * Inserts or updates to the local_plugins_stats_cache table
     *
     * For now, we ignore the race conditions risk here. This might get some
     * locking support / unique index protection in the future.
     *
     * @param int $pluginid
     * @param int $versionid
     * @param int $year
     * @param int $month
     * @param int $downloads how many times it was downloaded
     */
    protected function update_stats_cache($pluginid, $versionid, $year, $month, $downloads) {
        global $DB;

        if (($year == 0 and $month <> 0) or ($year <> 0 and $month == 0)) {
            throw new coding_exception('Unsupported combination of year/month');
        }

        $now = time();

        $current = $DB->get_record('local_plugins_stats_cache', array(
            'pluginid' => $pluginid, 'versionid' => $versionid, 'year' => $year, 'month' => $month), '*', IGNORE_MISSING);

        if ($current === false) {
            $DB->insert_record('local_plugins_stats_cache', array(
                'pluginid' => $pluginid, 'versionid' => $versionid, 'year' => $year, 'month' => $month,
                'downloads' => $downloads, 'timeupdated' => $now));

        } else if ($current->downloads <> $downloads) {
            $DB->update_record('local_plugins_stats_cache', array(
                'id' => $current->id, 'downloads' => $downloads, 'timeupdated' => $now));
        }
    }

    /**
     * Records an output log message
     *
     * @param string $message to be logged
     * @param string $type of the message debug|info|warning|error
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

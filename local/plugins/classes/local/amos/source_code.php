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
 * Provides {@link \local_plugins\local\amos\source_code} class.
 *
 * @package     local_plugins
 * @subpackage  amos
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_plugins\local\amos;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/filelib.php');

/**
 * Provides access to the contents of the uploaded ZIP package of a plugin
 *
 * @copyright 2012 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class source_code {

    /** @var local_plugins_version */
    protected $version;

    /** @var string full path to the directory with the contents of the package */
    protected $basepath;

    /**
     * Factory method.
     *
     * @param local_plugins_version $version
     * @return local_plugins_package
     */
    public static function instance(\local_plugins_version $version) {
        global $CFG;

        $source = $CFG->dataroot.'/local_plugins/'.$version->plugin->id.'/'.$version->id.'.zip';
        $target = make_temp_directory('local_plugins/amos_exporter/'.$version->plugin->id.'/'.$version->id);
        $packer = get_file_packer('application/zip');
        $packer->extract_to_pathname($source, $target);
        $content = get_directory_list($target, '', false, true, false);
        if (is_array($content) && count($content) == 1) {
            $rootname = reset($content);
            return new static($version, $target.'/'.$rootname);
        } else {
            throw new \moodle_exception('invalid_archive_contents', 'local_plugins');
        }
    }

    /**
     * Clean-up the temp directory with the unpacked contents of the plugin source code
     */
    public function __destruct() {
        fulldelete($this->basepath);
    }

    /**
     * Locates all language files included in the package that should be sent to AMOS
     *
     * For all plugins, it looks up for the English strings file in the lang/en/ folder.
     * String files of eventual subplugins are returned, too.
     *
     * @return array of (string)pluginname => (string)relative path of the file => (string)the file contents
     */
    public function get_included_string_files() {

        $files = [];

        if ($this->version->plugin->category->plugintype === 'mod') {
            // Activity modules have language files without the mod_ prefix.
            $file = 'lang/en/' . substr($this->version->plugin->frankenstyle, 4) . '.php';
        } else {
            $file = 'lang/en/' . $this->version->plugin->frankenstyle . '.php';
        }

        if (is_readable($this->basepath . '/' . $file)) {
            $files[$this->version->plugin->frankenstyle][$file] = file_get_contents($this->basepath . '/' . $file);
        }

        $subplugins = [];
        $subpluginsfilejson = $this->basepath . '/db/subplugins.json';
        $subpluginsfilephp = $this->basepath . '/db/subplugins.php';

        if (is_readable($subpluginsfilejson)) {
            $subplugins = (array) json_decode(file_get_contents($subpluginsfilejson))->plugintypes;

        } else if (is_readable($subpluginsfilephp)) {
            $subplugins = static::get_subplugins_from_file($subpluginsfilephp);
        }

        if ($subplugins) {
            foreach ($subplugins as $subplugintype => $subpluginpath) {
                if ($subpluginpath !== clean_param($subpluginpath, PARAM_SAFEPATH)) {
                    continue;
                }
                $subpluginpath = explode('/', $subpluginpath);
                array_shift($subpluginpath);
                array_shift($subpluginpath);
                $subpluginpath = implode('/', $subpluginpath);
                if (is_readable($this->basepath . '/' . $subpluginpath)) {
                    $list = get_list_of_plugins($subpluginpath, '', $this->basepath);
                    foreach ($list as $subpluginname) {
                        $subplugincomponent = $subplugintype . '_' . $subpluginname;
                        if ($subplugincomponent !== clean_param($subplugincomponent, PARAM_COMPONENT)) {
                            continue;
                        }
                        $subfile = $subpluginpath . '/' . $subpluginname . '/lang/en/' . $subplugincomponent . '.php';
                        if (is_readable($this->basepath . '/'.$subfile)) {
                            $files[$subplugincomponent][$subfile] = file_get_contents($this->basepath . '/' . $subfile);
                        }
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Use the factory method {@link self::instance()} to get an instance of this class
     *
     * @param string $path full path to the directory comtaining an unpacked source code of a plugin
     */
    private function __construct(\local_plugins_version $version, $path) {
        $this->version = $version;
        $this->basepath = $path;
    }

    /**
     * Extracts the declaration of subplugins without actually including the file
     *
     * @param string $path the full path to the subplugin.php file
     * @return array of (string)subplugintype => (string)subpluginpath
     */
    protected static function get_subplugins_from_file($path) {

        if (!is_readable($path)) {
            throw new \coding_exception('No such file', $path);
        }

        $subplugins = array();
        $text = file_get_contents($path);

        preg_match_all("/('|\")([a-z][a-z0-9_]*[a-z0-9])\\1\s*=>\s*('|\")([a-z][a-zA-Z0-9\/_-]*)\\3/", $text, $matches);

        if (!empty($matches[2]) && !empty($matches[4])) {
            foreach ($matches[2] as $ix => $subplugintype) {
                $subplugins[$subplugintype] = $matches[4][$ix];
            }
        }

        if (empty($subplugins)) {
            preg_match_all("/\\\$subplugins\s*\[\s*('|\")([a-z][a-z0-9_]*[a-z0-9])\\1\s*\]\s*=\s*('|\")([a-z][a-zA-Z0-9\/_-]*)\\3/",
                $text, $matches);

            if (!empty($matches[2]) && !empty($matches[4])) {
                foreach ($matches[2] as $ix => $subplugintype) {
                    $subplugins[$subplugintype] = $matches[4][$ix];
                }
            }
        }

        return $subplugins;
    }
}

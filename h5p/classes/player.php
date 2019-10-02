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
 * H5P player class.
 *
 * @package    core
 * @subpackage h5p
 * @copyright  2019 Moodle
 * @author     Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p;

defined('MOODLE_INTERNAL') || die();

/**
 * H5P player class, for displaying any local H5P content.
 *
 * @package    core_h5p
 * @copyright  2019 Sara Arjona <sara@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class player {

    /**
     * @var string The local H5P URL containing the .h5p file to display.
     */
    private $url;

    /**
     * @var \H5PCore The H5PCore object.
     */
    private $core;

    /**
     * @var int H5P DB id.
     */
    private $h5pid;

    /**
     * @var array JavaScript requirements for this H5P.
     */
    private $jsrequires;

    /**
     * @var array CSS requirements for this H5P.
     */
    private $cssrequires;

    /**
     * @var array H5P content to display.
     */
    private $content;

    /**
     * @var string Type of embed object, div or iframe.
     */
    private $embedtype;

    /**
     * @var array Main H5P configuration.
     */
    private $settings;

    /**
     * Inits the H5P player for rendering the content.
     *
     * @param string $url Local URL of the H5P file to display.
     * @param stdClass $config Configuration for H5P buttons.
     */
    public function __construct(string $url, \stdClass $config) {
        $this->url = $url;
        $this->jsrequires  = [];
        $this->cssrequires = [];
        $context = \context_system::instance();
        $this->core = \core_h5p\framework::instance();
        // Get the H5P identifier linked to this URL.
        $this->h5pid = $this->get_h5p_id($url, $config);

        $this->content = $this->core->loadContent($this->h5pid);
        $this->settings = $this->get_core_assets($context);

        $disable = array_key_exists('disable', $this->content) ? $this->content['disable'] : \H5PCore::DISABLE_NONE;
        $displayoptions = $this->core->getDisplayOptionsForView($disable, $this->h5pid);

        $embedurl = new \moodle_url('/h5p/embed.php', ['id' => $this->h5pid]);
        $contenturl = new \moodle_url("/pluginfile.php/{$context->id}/core_h5p/content/{$this->h5pid}");

        $this->settings['contents'][ 'cid-' . $this->h5pid ] = [
            'library'         => \H5PCore::libraryToString($this->content['library']),
            'jsonContent'     => $this->get_filtered_parameters(),
            'fullScreen'      => $this->content['library']['fullscreen'],
            'exportUrl'       => $this->get_export_settings($displayoptions[ \H5PCore::DISPLAY_OPTION_DOWNLOAD ]),
            'embedCode'       => $this->get_embed_code($displayoptions[ \H5PCore::DISPLAY_OPTION_EMBED ]),
            'resizeCode'      => $this->get_resize_code(),
            'title'           => $this->content['slug'],
            'displayOptions'  => $displayoptions,
            'url'             => $embedurl->out(),
            'contentUrl'      => $contenturl->out(),
            'metadata'        => $this->content['metadata'],
            'contentUserData' => [],
        ];

        $this->embedtype = \H5PCore::determineEmbedType($this->content['embedType'], $this->content['library']['embedTypes']);

        $this->files = $this->get_dependency_files();
        $this->generate_assets();
    }

    /**
     * Get the title of the H5P content to display.
     *
     * @return string the title
     */
    public function get_title() {
        return $this->content['title'];
    }

    /**
     * Get the H5P DB instance id for a H5P pluginfile URL.
     *
     * @param string $url H5P pluginfile URL.
     * @param stdClass $config Configuration for H5P buttons.
     *
     * @return int H5P DB identifier.
     */
    private function get_h5p_id(string $url, \stdClass $config) : int {
        global $DB;

        $fs = get_file_storage();

        $pathnamehash = $this->get_pluginfile_hash($url);
        $file = $fs->get_file_by_hash($pathnamehash);
        $contenthash = $file->get_contenthash();

        if (!$file) {
            throw new \moodle_exception('h5pfilenotfound', 'core_h5p');
        }

        $h5p = $DB->get_record('h5p', ['pathnamehash' => $pathnamehash]);
        if ($h5p && $h5p->contenthash != $contenthash) {
            // The content exists and it is different from the one deployed previously. The existing one should be removed before
            // deploying the new version.
            $this->delete_h5p($h5p);
            $h5p = false;
        }

        if (!$h5p) {
            // The H5P content hasn't been deployed previously. It has to be validated and stored before displaying it.
            return $this->save_h5p($file, $pathnamehash, $contenthash, $config);
        } else {
            // The H5P content has been deployed previously.
            return $h5p->id;
        }
    }

    /**
     * Get the pathnamehash from an H5P internal URL.
     *
     * @param  string $url H5P pluginfile URL poiting to an H5P file.
     *
     * @return string pathnamehash for the file in the internal URL.
     */
    private function get_pluginfile_hash(string $url) : string {
        global $CFG;

        $url = new \moodle_url($url);
        // Remove params from the URL (such as the 'forcedownload=1'), to avoid errors.
        $url->remove_params(array_keys($url->params()));
        $path = $url->out_as_local_url();

        $parts = explode('/', $path);
        $filename = array_pop($parts);
        // First is an empty row and then the pluginfile.php part. Both can be ignored.
        array_shift($parts);
        array_shift($parts);
        $contextid = array_shift($parts);
        $component = array_shift($parts);
        $filearea = array_shift($parts);
        if (!empty($parts) && is_numeric($parts[0])) {
            $itemid = array_shift($parts);
        } else {
            $itemid = 0;
        }
        if (empty($parts)) {
            $filepath = DIRECTORY_SEPARATOR;
        } else {
            $filepath = DIRECTORY_SEPARATOR . array_shift($parts) . DIRECTORY_SEPARATOR;
        }

        // Ignore draft files, because they are considered temporary files, so shouldn't be displayed.
        if ($filearea == 'draft') {
            return false;
        }

        // Some components, such as mod_page or mod_resource, add the revision to the URL to prevent caching problems.
        // So the URL contains this revision number as itemid but a 0 is always stored in the files table.
        // In order to get the proper hash, the itemid should be set to 0 in these cases.
        if (!component_callback($component, 'supports', [FEATURE_ITEMID], true)) {
            $itemid = 0;
        }

        $fs = get_file_storage();
        return $fs->get_pathname_hash($contextid, $component, $filearea, $itemid, $filepath, $filename);
    }

    /**
     * Store an H5P file
     *
     * @param Object $file Moodle file instance
     * @param string $pathnamehash The pathnamehash.
     * @param string $contenthash The contenthash.
     * @param stdClass $config Button options config.
     *
     * @return int|false The H5P identifier or false if it's not a valid H5P package.
     */
    private function save_h5p($file, string $pathnamehash, string $contenthash, \stdClass $config) : int {

        $path = $this->core->fs->getTmpPath();
        $this->core->h5pF->getUploadedH5pFolderPath($path);
        // Add manually the extension to the file to avoid the validation fails.
        $path .= '.h5p';
        $this->core->h5pF->getUploadedH5pPath($path);

        // Copy the .h5p file to the temporary folder.
        $file->copy_content_to($path);

        // Check if the h5p file is valid before saving it.
        $h5pvalidator = \core_h5p\framework::instance('validator');
        if ($h5pvalidator->isValidPackage(false, false)) {
            $h5pstorage = \core_h5p\framework::instance('storage');

            $disableoptions = [
                \H5PCore::DISPLAY_OPTION_FRAME     => isset($config->frame) ? $config->frame : 0,
                \H5PCore::DISPLAY_OPTION_DOWNLOAD  => isset($config->export) ? $config->export : 0,
                \H5PCore::DISPLAY_OPTION_EMBED     => isset($config->embed) ? $config->embed : 0,
                \H5PCore::DISPLAY_OPTION_COPYRIGHT => isset($config->copyright) ? $config->copyright : 0,
            ];

            $options = ['disable' => $this->core->getStorableDisplayOptions($disableoptions, 0)];

            $content = [
                'pathnamehash' => $pathnamehash,
                'contenthash' => $contenthash
            ];

            $h5pstorage->savePackage($content, null, false, $options);
            return $h5pstorage->contentId;
        } else {
            $messages = $this->core->h5pF->getMessages('error');
            $errors = array_map(function($error) {
                return $error->message;
            }, $messages);
            throw new \Exception(implode(',', $errors));
        }

        return false;
    }

    /**
     * Delete an H5P package.
     *
     * @param stdClass $content The H5P package to delete.
     */
    private function delete_h5p(\stdClass $content) {
        $h5pstorage = \core_h5p\framework::instance('storage');
        // Add an empty slug to the content if it's not defined, because the H5P library requires this field exists.
        // It's not used when deleting a package, so the real slug value is not required at this point.
        $content->slug = $content->slug ?? '';
        $h5pstorage->deletePackage( (array) $content);
    }

    /**
     * Export path for settings
     *
     * @param bool $downloadenabled Wheter the option to export the H5P content is enabled.
     *
     * @return string The URL of the exported file.
     */
    private function get_export_settings(bool $downloadenabled) : string {

        if ( ! $downloadenabled) {
            return '';
        }

        $context = \context_system::instance();
        $slug = $this->content['slug'] ? $this->content['slug'] . '-' : '';
        $url  = \moodle_url::make_pluginfile_url(
            $context->id,
            \core_h5p\file_storage::COMPONENT,
            \core_h5p\file_storage::EXPORT_FILEAREA,
            '',
            '',
            "{$slug}{$this->content['id']}.h5p"
        );

        return $url->out();
    }

    /**
     * Get a query string with the theme revision number to include at the end
     * of URLs. This is used to force the browser to reload the asset when the
     * theme caches are cleared.
     *
     * @return string
     */
    private function get_cache_buster() : string {
        global $CFG;
        return '?ver=' . $CFG->themerev;
    }

    /**
     * Get the core H5p assets, including all core H5P JavaScript and CSS.
     *
     * @return Array core H5P assets.
     */
    private function get_core_assets() : array {
        global $CFG, $PAGE;
        // Get core settings.
        $settings = $this->get_core_settings();
        $settings['core'] = [
          'styles' => [],
          'scripts' => []
        ];
        $settings['loadedJs'] = [];
        $settings['loadedCss'] = [];

        // Make sure files are reloaded for each plugin update.
        $cachebuster = $this->get_cache_buster();

        // Use relative URL to support both http and https.
        $liburl = $CFG->wwwroot . '/lib/h5p/';
        $relpath = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $liburl);

        // Add core stylesheets.
        foreach (\H5PCore::$styles as $style) {
            $settings['core']['styles'][] = $relpath . $style . $cachebuster;
            $this->cssrequires[] = new \moodle_url($liburl . $style . $cachebuster);
        }
        // Add core JavaScript.
        foreach (\H5PCore::$scripts as $script) {
            $settings['core']['scripts'][] = $relpath . $script . $cachebuster;
            $this->jsrequires[] = new \moodle_url($liburl . $script . $cachebuster);
        }

        return $settings;
    }

    /**
     * Get the settings needed by the H5P library.
     *
     * @return array The settings.
     */
    private function get_core_settings() : array {
        global $USER, $CFG;

        $basepath = $CFG->wwwroot . '/';
        $systemcontext = \context_system::instance();

        // Generate AJAX paths.
        $ajaxpaths = [];
        $ajaxpaths['xAPIResult'] = '';
        $ajaxpaths['contentUserData'] = '';

        $settings = array(
            'baseUrl' => $basepath,
            'url' => "{$basepath}pluginfile.php/{$systemcontext->instanceid}/core_h5p",
            'urlLibraries' => "{$basepath}pluginfile.php/{$systemcontext->id}/core_h5p/libraries",
            'postUserStatistics' => false,
            'ajax' => $ajaxpaths,
            'saveFreq' => false,
            'siteUrl' => $CFG->wwwroot,
            'l10n' => array('H5P' => $this->core->getLocalization()),
            'user' => [],
            'hubIsEnabled' => false,
            'reportingIsEnabled' => false,
            'crossorigin' => null,
            'libraryConfig' => $this->core->h5pF->getLibraryConfig(),
            'pluginCacheBuster' => $this->get_cache_buster(),
            'libraryUrl' => $basepath . 'lib/h5p/js',
        );

        return $settings;
    }

    /**
     * Generate the assets arrays for the H5P settings object and the JavaScript and CSS requirements.
     *
     */
    private function generate_assets() {
        global $CFG;

        if ($this->embedtype === 'div') {
            $context = \context_system::instance();
            $h5ppath = "/pluginfile.php/{$context->id}/core_h5p";

            // Schedule JavaScripts for loading through Moodle.
            foreach ($this->files['scripts'] as $script) {
                $url = $script->path . $script->version;

                // Add URL prefix if not external.
                $isexternal = strpos($script->path, '://');
                if ($isexternal === false) {
                    $url = $h5ppath . $url;
                }
                $this->settings['loadedJs'][] = $url;
                $this->jsrequires[] = new \moodle_url($isexternal ? $url : $CFG->wwwroot . $url);
            }

            // Schedule stylesheets for loading through Moodle.
            foreach ($this->files['styles'] as $style) {
                $url = $style->path . $style->version;

                // Add URL prefix if not external.
                $isexternal = strpos($style->path, '://');
                if ($isexternal === false) {
                    $url = $h5ppath . $url;
                }
                $this->settings['loadedCss'][] = $url;
                $this->cssrequires[] = new \moodle_url($isexternal ? $url : $CFG->wwwroot . $url);
            }

        } else {
            // JavaScripts and stylesheets will be loaded through h5p.js.
            $cid = 'cid-' . $this->h5pid;
            $this->settings['contents'][ $cid ]['scripts'] = $this->core->getAssetsUrls($this->files['scripts']);
            $this->settings['contents'][ $cid ]['styles']  = $this->core->getAssetsUrls($this->files['styles']);
        }
    }

    /**
     * Finds library dependencies of view
     *
     * @return array Files that the view has dependencies to
     */
    private function get_dependency_files() : array {
        global $PAGE;

        $preloadeddeps = $this->core->loadContentDependencies($this->h5pid, 'preloaded');
        $files         = $this->core->getDependenciesFiles($preloadeddeps);

        return $files;
    }

    /**
     * Filtered and potentially altered parameters
     *
     * @return string Returns a JSON encoded string on success or FALSE on failure.
     */
    private function get_filtered_parameters() : string {
        global $PAGE;

        $safeparameters = $this->core->filterParameters($this->content);

        $decodedparams  = json_decode($safeparameters);
        $safeparameters = json_encode($decodedparams);

        return $safeparameters;
    }

    /**
     * Resizing script for settings
     *
     * @return string The HTML code with the resize script.
     */
    private function get_resize_code() : string {
        global $CFG, $OUTPUT;

        $template = new \stdClass();
        $template->resizeurl = new \moodle_url('/lib/h5p/js/h5p-resizer.js');
        return $OUTPUT->render_from_template('core_h5p/h5presize', $template);
    }

    /**
     * Embed code for settings
     *
     * @param bool $embedenabled Wheter the option to embed the H5P content is enabled.
     *
     * @return string The HTML code to reuse this H5P content in a different place.
     */
    private function get_embed_code(bool $embedenabled) : string {
        global $CFG, $OUTPUT;

        if ( ! $embedenabled) {
            return '';
        }

        $template = new \stdClass();
        $template->embedurl = new \moodle_url("/h5p/embed.php", ["url" => $this->url]);
        return $OUTPUT->render_from_template('core_h5p/h5pembed', $template);
    }

    /**
     * Create the H5PIntegration variable that will be included in the page. This variable is used as the
     * main H5P config variable.
     */
    public function add_assets_to_page() {
        global $PAGE, $CFG;

        foreach ($this->jsrequires as $script) {
            $PAGE->requires->js($script, true);
        }

        foreach ($this->cssrequires as $css) {
            $PAGE->requires->css($css);
        }

        // Print JavaScript settings to page.
        $PAGE->requires->data_for_js('H5PIntegration', $this->settings, true);
    }

    /**
     * Outputs H5P wrapper HTML.
     *
     * @return string The HTML code to display this H5P content.
     */
    public function output() : string {
        global $OUTPUT;

        $template = new \stdClass();
        $template->h5pid = $this->h5pid;
        if ($this->embedtype === 'div') {
            return $OUTPUT->render_from_template('core_h5p/h5pdiv', $template);
        } else {
            return $OUTPUT->render_from_template('core_h5p/h5piframe', $template);
        }
    }
}

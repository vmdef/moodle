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
 * H5P editor class.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_h5p;

use H5PCore;
use H5peditor;
use stdClass;

defined('MOODLE_INTERNAL') || die();

/**
 * H5P editor class, for editing local H5P content.
 *
 * @package    core_h5p
 * @copyright  2020 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editor {

    /**
     * @var core The H5PCore object.
     */
    private $core;

    /**
     * @var $h5peditor The H5P Editor object.
     */
    private $h5peditor;

    /**
     * Inits the H5P editor.
     *
     */
    public function __construct() {
        autoloader::register();

        $factory = new factory();
        $this->h5peditor = $factory->get_editor();
        $this->core = $factory->get_core();
    }

    /**
     * Creates or updates an H5P content.
     *
     * @param stdClass $content Object containing all the necessary data.
     *
     * @return bool Success/Fail
     */
    public function save_content(stdClass $content): bool {
        global $USER;

        if (!empty($content->id)) {
            // Load existing content to get old parameters for comparison.
            $oldcontent = $this->core->loadContent($content->id);
            $oldlib = $oldcontent['library'];
            $oldparams = json_decode($oldcontent['params']);
            // Keep the existing display options.
            $content->disable = $oldcontent['disable'];
        }

        // Make params and library available for core to save.
        $content->library = H5PCore::libraryFromString($content->h5plibrary);
        $content->library['libraryId'] = $this->core->h5pF->getLibraryId($content->library['machineName'],
            $content->library['majorVersion'],
            $content->library['minorVersion']);

        $content->id = $this->core->saveContent((array)$content);

        // Prepare current parameters.
        $params = json_decode($content->params);

        // Move any uploaded images or files. Determine content dependencies.
        $this->h5peditor->processParameters($content, $content->library, $params->params,
            isset($oldlib) ? $oldlib : null,
            isset($oldparams) ? $oldparams : null);

        $content = $this->core->loadContent($content->id);
        $this->core->filterParameters($content);

        // Update hash fields in the h5p table.
        $file = $this->core->fs->get_export_file((isset($content['slug']) ? $content['slug'] . '-' : '') . $content['id'] . '.h5p');
        $fs = new \file_storage();
        if ($file) {
            $fields['contenthash'] = $file->get_contenthash();
            // Rewrite the old H5P file.
            if (isset($oldcontent)) {
                $oldfile = $fs->get_file_by_hash($oldcontent['pathnamehash']);
                if ($oldfile) {
                    $record = [
                        'contextid' => $oldfile->get_contextid(),
                        'component' => $oldfile->get_component(),
                        'filearea' => $oldfile->get_filearea(),
                        'itemid' => $oldfile->get_itemid(),
                        'filepath' => $oldfile->get_filepath(),
                        'filename' => $oldfile->get_filename(),
                    ];
                    $oldfile->delete();
                    $fs->create_file_from_storedfile($record, $file);
                }
                // Keep the pathname when updating an existing H5P content.
                $pathnamehash = $oldcontent['pathnamehash'];
            } else {
                $record = [
                    'contextid' => \context_user::instance($USER->id)->id,
                    'component' => 'user',
                    'filearea' => 'private',
                    'itemid' => 0,
                    'filepath' => '/',
                    'filename' => $content['slug'] . '.h5p',
                ];
                $newfile = $fs->create_file_from_storedfile($record, $file);
                $pathnamehash =  $newfile->get_pathnamehash();
            }
            $fields['pathnamehash'] = $pathnamehash;

            $this->core->h5pF->updateContentFields($content['id'], $fields);
        }

        return $content['id'];
    }

    /**
     * Add required assets for displaying the editor.
     *
     * @param int $id Id of the content being edited. null for creating new content.
     * @param string $mformid Id of the Moodle form where the editor is loaded.
     *
     * @return void
     */
    public function add_editor_assets_to_page(?int $id = null, string $mformid = null): void {
        global $PAGE, $CFG;

        $libeditorpath = 'lib/h5peditor';

        $context = \context_system::instance();

        $settings = helper::get_core_assets();

        // Use jQuery and styles from core.
        $assets = array(
            'css' => $settings['core']['styles'],
            'js' => $settings['core']['scripts']
        );

        // Use relative URL to support both http and https.
        $url = $CFG->wwwroot . '/'. $libeditorpath . '/';
        $url = '/' . preg_replace('/^[^:]+:\/\/[^\/]+\//', '', $url);

        // Make sure files are reloaded for each plugin update.
        $cachebuster = helper::get_cache_buster();

        // Add editor styles.
        foreach (H5peditor::$styles as $style) {
            $assets['css'][] = $url . $style . $cachebuster;
        }

        // Add editor JavaScript.
        foreach (H5peditor::$scripts as $script) {
            // We do not want the creator of the iframe inside the iframe.
            if ($script !== 'scripts/h5peditor-editor.js') {
                $assets['js'][] = $url . $script . $cachebuster;
            }
        }

        // Add JavaScript with library framework integration (editor part).
        $PAGE->requires->js(new \moodle_url('/'. $libeditorpath .'/scripts/h5peditor-editor.js' . $cachebuster), true);
        $PAGE->requires->js(new \moodle_url('/'. $libeditorpath .'/scripts/h5peditor-init.js' . $cachebuster), true);

        // Add translations.
        $language = framework::get_language();
        $languagescript = "language/{$language}.js";

        if (!file_exists("{$CFG->dirroot}/" . $libeditorpath . "/{$languagescript}")) {
            $languagescript = 'language/en.js';
        }
        $PAGE->requires->js(new \moodle_url('/' . $libeditorpath .'/' . $languagescript . $cachebuster), true);

        // Add JavaScript settings.
        $root = $CFG->wwwroot;
        $filespathbase = "{$root}/pluginfile.php/{$context->id}/core_h5p/";

        $factory = new factory();
        $contentvalidator = $factory->get_content_validator();

        $editorajaxtoken = H5PCore::createToken(editor_ajax::EDITOR_AJAX_TOKEN);
        $settings['editor'] = array(
            'filesPath' => $filespathbase . 'editor',
            'fileIcon' => array(
                'path' => $url . 'images/binary-file.png',
                'width' => 50,
                'height' => 50,
            ),
            'ajaxPath' => $CFG->wwwroot . '/h5p/' . "ajax.php?contextId={$context->id}&token={$editorajaxtoken}&action=",
            'libraryUrl' => $url,
            'copyrightSemantics' => $contentvalidator->getCopyrightSemantics(),
            'metadataSemantics' => $contentvalidator->getMetadataSemantics(),
            'assets' => $assets,
            'apiVersion' => H5PCore::$coreApi,
            'language' => $language,
            'formId' => $mformid,
        );

        if ($id !== null) {
            $settings['editor']['nodeVersionId'] = $id;

            // Override content URL.
            $contenturl = "{$root}/pluginfile.php/{$context->id}/core_h5p/content/{$id}";
            $settings['contents']['cid-' . $id]['contentUrl'] = $contenturl;
        }

        $PAGE->requires->data_for_js('H5PIntegration', $settings, true);
    }
}

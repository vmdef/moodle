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
 * H5P content type manager class
 *
 * @package    contenttype_h5p
 * @copyright  2020 Amaia Anabitarte <amaia@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace contenttype_h5p;

use core_h5p\file_storage;
use core_h5p\local\library\autoloader;
use core_h5p\editor_ajax;
use H5PCore;
use stdClass;
use html_writer;

/**
 * H5P content bank manager class
 *
 * @package    contenttype_h5p
 * @copyright  2020 Amaia Anabitarte <amaia@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class contenttype extends \core_contentbank\contenttype {

    /** The component for H5P. */
    public const COMPONENT   = 'contenttype_h5p';

    /**
     * Fill content type.
     *
     * @param stdClass $content Content object to fill and validate
     */
    protected static function validate_content(stdClass &$content) {
        $content->contenttype = self::COMPONENT;
    }

    /**
     * Returns the HTML content to add to view.php visualizer.
     *
     * @param stdClass $record  Th content to be displayed.
     * @return string            HTML code to include in view.php.
     * @throws \coding_exception if content is not loaded previously.
     */
    public function get_view_content(\stdClass $record): string {
        $content = new content($record);
        $fileurl = $content->get_file_url();
        $html = \core_h5p\player::display($fileurl, new \stdClass(), true);
        return $html;
    }

    /**
     * Returns the HTML code to render the icon for H5P content types.
     *
     * @param string $contentname   The contentname to add as alt value to the icon.
     * @return string            HTML code to render the icon
     * @throws \coding_exception if not loaded.
     */
    public function get_icon(string $contentname): string {
        global $OUTPUT;
        return $OUTPUT->pix_icon('f/h5p-64', $contentname, 'moodle', ['class' => 'iconsize-big']);
    }

    /**
     * Return an array of implemented features by this plugin.
     *
     * @return array
     */
    protected function get_implemented_features(): array {
        return [self::CAN_UPLOAD, self::CAN_EDIT];
    }

    /**
     * Return an array of extensions this contenttype could manage.
     *
     * @return array
     */
    public function get_manageable_extensions(): array {
        return ['.h5p'];
    }

    /**
     * Returns user has access capability for the content itself.
     *
     * @return bool     True if content could be accessed. False otherwise.
     */
    protected function is_access_allowed(): bool {
        return true;
    }

    /**
     * Returns the list of different H5P content types the user can create.
     *
     * @return array
     */
    public function get_contenttype_items(): array {
        autoloader::register();
        $editor_ajax = new editor_ajax();
        $h5pcontenttypes = $editor_ajax->getLatestLibraryVersions();

        $libraries = [];
        foreach ($h5pcontenttypes as $h5pcontenttype) {
            $key = H5PCore::libraryToString(['name'=>$h5pcontenttype->machine_name, 'majorVersion' => $h5pcontenttype->major_version,
                'minorVersion' => $h5pcontenttype->minor_version]);
            $library = new stdClass();
            $library->key = $key;
            $library->itemname = $h5pcontenttype->title;
            $library->itemlinkparams = 'library=' . $key;
            $h5p_file_storage = new file_storage();
            $library->itemicon = $h5p_file_storage->get_icon_url(
                $h5pcontenttype->id,
                $h5pcontenttype->machine_name,
                $h5pcontenttype->major_version,
                $h5pcontenttype->minor_version);
            $libraries[] = $library;
        }

        return $libraries;
    }
}

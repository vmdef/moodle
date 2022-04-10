<?php
// This file is part of the moodleorg theme for Moodle
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

namespace theme_moodleorg\output;

use core_renderer as vanilla_core_renderer;
use html_writer;

defined('MOODLE_INTERNAL') || die;

/**
 * Renderers to align Moodle's HTML with that expected by Bootstrap
 *
 * @package    theme_moodleorg
 * @copyright  2019 Bas Brands
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class core_renderer extends vanilla_core_renderer {

    /**
     * Return the favicon links.
     *
     * @param string $domain the subfolder of pix/favicons/ to pick the icon from
     * @return string
     */
    public function favicons() {
        $favicons = (object) [];

        $domain = theme_moodleorg_get_domain();

        if ($domain === 'stats') {
            $domain = 'org';
        }

        // Please note, to support favicon.ico files stored in parent theme and
        // non-standard location, a small patch of outputlib.php is required.

        // Basic favicons in ICO format.
        $favicons->favicon = $this->image_url("favicons/{$domain}/favicon.ico", 'theme_moodleorg');

        // All other files are normal PNG files obtained via standard image_url()
        // For Opera Speed Dial.
        $favicons->favicon_195 = $this->image_url("favicons/{$domain}/favicon-195", 'theme_moodleorg');

        // For iPad with high-resolution Retina Display running iOS ≥ 7.
        $favicons->favicon_152 = $this->image_url("favicons/{$domain}/favicon-152", 'theme_moodleorg');

        // For iPad with high-resolution Retina Display running iOS ≤ 6.
        $favicons->favicon_144 = $this->image_url("favicons/{$domain}/favicon-144", 'theme_moodleorg');

        // For iPhone with high-resolution Retina Display running iOS ≥ 7.
        $favicons->favicon_120 = $this->image_url("favicons/{$domain}/favicon-120", 'theme_moodleorg');

        // For iPhone with high-resolution Retina Display running iOS ≤ 6.
        $favicons->favicon_114 = $this->image_url("favicons/{$domain}/favicon-114", 'theme_moodleorg');

        // For Google TV devices.
        $favicons->favicon_96 = $this->image_url("favicons/{$domain}/favicon-96", 'theme_moodleorg');

        // For iPad Mini.
        $favicons->favicon_76 = $this->image_url("favicons/{$domain}/favicon-76", 'theme_moodleorg');

        // For first- and second-generation iPad.
        $favicons->favicon_72 = $this->image_url("favicons/{$domain}/favicon-72", 'theme_moodleorg');

        // For non-Retina iPhone, iPod Touch and Android 2.1+ devices.
        $favicons->favicon_57 = $this->image_url("favicons/{$domain}/favicon-57", 'theme_moodleorg');

        // Windows 8 Tiles.
        $favicons->favicon_144 = $this->image_url("favicons/{$domain}/favicon-144", 'theme_moodleorg');

        return $this->render_from_template('theme_moodleorg/widget_favicons', $favicons);
    }

    /**
     * Generates html from custommenuitems that contains a sitemap - consisting of 1st and 2nd level
     * items in custommenuitems from site config.
     *
     * @return String rendered template html.
     */
    public function footer_navigation() {
        global $CFG;

        if (empty($this->page->theme->settings->footersitesmap)) {
            $sitesmenu = get_string('footersitesmap_default', 'theme_moodleorg');
        } else {
            $sitesmenu = $this->page->theme->settings->footersitesmap;
        }
        $menus = explode('~span~', $sitesmenu);

        // Limit array to 5 items.
        array_splice($menus, 5);

        $template = (object) [];
        $template->menugroups = [];

        foreach ($menus as $menu) {
            $custommenu = new \custom_menu($menu);
            // We only want the first six items in the menu.
            $children = $custommenu->get_children();

            $menugroup = (object) [];
            $menugroup->menus = [];

            foreach ($children as $item) {
                $text = $item->get_text();
                if (get_string_manager()->string_exists($text, 'local_moodleorg')) {
                    $text = get_string($text, 'local_moodleorg');
                }
                $list = (object) [];
                $list->primarytext = $text;
                $list->primaryurl = $item->get_url();
                $list->items = [];

                if ($item->has_children()) {
                    foreach ($item->get_children() as $child) {
                        $text = $child->get_text();
                        if (get_string_manager()->string_exists($text, 'local_moodleorg')) {
                            $text = get_string($text, 'local_moodleorg');
                        }

                        $listitem = (object) [];
                        $listitem->text = $text;
                        $listitem->url = $child->get_url();
                        $list->items[] = $listitem;
                    }
                }
                $menugroup->menus[] = $list;
            }
            $template->menugroups[] = $menugroup;
        }
        $template->logo = $this->image_url('moodle_logo_grayhat_small', 'theme');
        return $this->render_from_template('theme_moodleorg/widget_footer_navigation', $template);

    }

    /**
     * Renders the footer standards html
     *
     * @return {String} html from template.
     */
    public function footer_standards() {
        if ($this->page->pagelayout == 'frontpage') {
            return $this->render_from_template('theme_moodleorg/widget_standards', (object) [] );
        }
    }

    /**
     * Outputs footer of grey partner icons - gracefully fail and return nothing
     * if block_partners is not installed.
     *
     * @return {String} html from template.
     */
    public function partners() {
        global $CFG, $DB;
        if (!class_exists('\block_partners\util')) {
            return '';
        }
        $domain = theme_moodleorg_get_domain();
        if ($domain !== 'org') {
            return '';
        }
        try {
            $util = new \block_partners\util();
            if (!method_exists($util, 'get_grey_ads')) {
                // Block not upgraded.
                return '';
            }
            $ads = $util->get_grey_ads();
        } catch (dml_read_exception $e) {
            // Don't blow up on block_partners misconfig.
            return '';
        }

        $template = (object) [];
        $template->ads = $ads;

        $template->image = $this->image_url('moodle-partners_logo_small', 'theme_moodleorg');

        return $this->render_from_template('theme_moodleorg/widget_partners', $template );
    }

    /**
     * Check if we are on the frontpage
     *
     * @return {Bool} True if on layout frontpage.
     */
    public function sitehome() {
        if ($this->page->pagelayout == 'frontpage' || $this->page->subpage == 'plugins-index') {
            return true;
        }
        return false;
    }

    public function subpage() {
        return $this->page->subpage;
    }

    /**
     * Get the Moodle logo.
     *
     * @return {String} logo url.
     */
    public function moodlelogo() {
        if ($this->page->pagelayout == 'frontpage') {
            $domain = theme_moodleorg_get_domain();
            if ($domain == 'org') {
                return $this->image_url('moodle_logo_small', 'theme_moodleorg');
            } else {
                return false;
            }
        }
    }

    /**
     * Get the Domain name.
     *
     * @return {String} logo url.
     */
    public function domainname() {
        $domain = theme_moodleorg_get_domain();
        return get_string('domain-' . $domain, 'theme_moodleorg');
    }

    /**
     * Get the Sitebar logo.
     *
     * @return {String} logo url.
     */
    public function sitebarlogo() {
        return $this->image_url('sitebar/moodle_sitebar_logo_grayhat', 'theme_moodleorg');
    }

    /**
     * Get the footnote from settings
     *
     * @return {String} footnote;
     */
    public function footnote() {
        return $this->page->theme->settings->footnote;
    }
}

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
 * moodleorg specific renderers.
 *
 * @package   theme_moodleorg
 * @copyright 2018 Moodle
 * @author    Bas Brands
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_moodleorg\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use stdClass;

require_once($CFG->dirroot. "/local/moodleorg/locallib.php");

class news implements renderable, templatable {

    /**
     * @var array of news items.
     */
    private $news;


    /**
     * Constructor.
     *
     */
    public function __construct() {
        $news = new \frontpage_column_news('Moodle Community news', null, null, "See all Moodle Community news");
        $comnews = new \frontpage_column_news('Moodle HQ news',
            "https://moodle.com/feed/", "https://moodle.com/news", "See all Moodle HQ news");
        $this->rssfeed['news'] = $news->get();
        $this->rssfeed['comnews'] = $comnews->get();
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        $data = new stdClass();
        $key = 0;
        foreach ($this->rssfeed as $feed) {
            $data->hasnews = true;
            $data->feed[$key] = new stdClass();
            $data->feed[$key]->rsstitle = $feed->rsstitle;
            $data->feed[$key]->rssurl = $feed->rssurl;
            $data->feed[$key]->moreurl = $feed->moreurl;
            $data->feed[$key]->moreanchortext = $feed->moreanchortext;
            if (isset($_GET['debugcaches']) and $_GET['debugcaches'] == 1) {
                $data->feed[$key]->showdebug = true;
                $data->feed[$key]->source = $feed->source ?? 'undefined';
                $data->feed[$key]->timegenerated = $feed->timegenerated;
            }
            $data->feed[$key]->newsitems = array_slice($feed->items, 0, 3);
            $key++;
        }
        return $data;
    }
}

<?php

/**
 * This file contains the renderers used by the local_plugins plugin.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

// No direct access to this script
defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for pages in local_plugins plugin
 *
 * @property-read core_renderer $output
 */
class local_plugins_renderer extends plugin_renderer_base {

    /** Trim the plugin name if longer than this in most stats pages. */
    const PLUGIN_NAME_TRIM_LENGTH = 30;

    /**
     * Displays the header
     *
     * This includes the fake block and the top bar optionally.
     *
     * @param string|null heading page heading to display
     * @param bool $showtoolsblock show moodle version selector and the search bar
     * @param array $options additional options
     * @return string
     */
    public function header($heading = null, $showtoolsblock = false, array $options = array()) {

        // If we use URL redirection, moodle will form the path- class from the URL, not from the
        // actual location of the component. In that case we add the path-local-plugins manually
        $this->page->add_body_class('path-local-plugins');

        if (!empty($options['showinfostats'])) {
            $bc = new block_contents();
            $bc->content = $this->frontpage_info_stats();
            $bc->attributes['class'] = 'frontpage-info-stats';
            $this->page->blocks->add_fake_block($bc, $this->page->blocks->get_default_region());
        }

        if ($showtoolsblock) {
            $bc = new block_contents();
            $bc->content = html_writer::start_div('toolsblock bg-light p-3 border');
            $bc->content .= html_writer::div($this->search_form(), 'search_form');
            $bc->content .= html_writer::div($this->moodle_version_select(), 'version_select text-center');
            $bc->content .= html_writer::end_div();
            $bc->attributes['class'] = '';
            $this->page->blocks->add_fake_block($bc, $this->page->blocks->get_default_region());
        }

        $reports = local_plugins_helper::get_reports_viewable_by_user(true);
        $outputreports = $this->report_summary($reports['quickaccess'], true);
        if (!empty($outputreports)) {
            $bc = new block_contents();
            $bc->title = get_string('reports', 'local_plugins');
            $bc->attributes['class'] = 'block block_local_plugins_reports';
            $bc->content = $outputreports;
            $this->page->blocks->add_fake_block($bc, $this->page->blocks->get_default_region());
        }

        $output = $this->output->header();

        if (!empty($heading)) {
            $output .= $this->output->heading($heading);
        }

        return $output;
    }

    public function footer() {
        return $this->output->footer();
    }

    /**
     * Inform about the fatal error.
     *
     * @return string
     */
    public function error($message, $link) {

        // If we use URL redirection, moodle will form the path- class from the URL, not from the
        // actual location of the component. In that case we add the path-local-plugins manually
        $this->page->add_body_class('path-local-plugins');

        $out = $this->output->header();
        $out .= html_writer::div(':-(', 'plugins-error-icon');
        $out .= $this->output->box(html_writer::div($message, 'errormessage'), 'errorbox', null, array('data-rel' => 'fatalerror'));
        $out .= $this->continue_button($link);
        $out .= $this->output->footer();

        return $out;
    }

   /**
    * Print a message along with button choices for Continue/Cancel
    *
    * If a string or moodle_url is given instead of a single_button, method defaults to post.
    *
    * @param string $message The question to ask the user
    * @param single_button|moodle_url|string $continue The single_button component representing the Continue answer. Can also be a moodle_url or string URL
    * @param single_button|moodle_url|string $cancel The single_button component representing the Cancel answer. Can also be a moodle_url or string URL
    * @return string HTML fragment
    */
    public function confirm($message, $continue, $cancel) {
        return $this->output->confirm($message, $continue, $cancel);
    }

    /**
     * Output a notification (that is, a status message about something that has
     * just happened).
     *
     * @param string $message the message to print out
     * @param string $classes normally 'notifyproblem' or 'notifysuccess'.
     * @return string the HTML to output.
     */
    public function notification($message, $classes = 'notifyproblem') {
        return $this->output->notification($message, $classes);
    }

    /**
     * Generates HTML to display an editable category tree
     *
     * @param local_plugins_category_tree $categories
     * @param bool $canmanagecategories
     * @return string
     */
    public function editable_category_table(local_plugins_category_tree $categories, $canmanagecategories = false) {
        $table = new html_table;
        $table->attributes['class'] = 'editable-category-table generaltable';
        $table->colclasses = array(
            'category-sortorder',
            'category-name',
            'category-plugintype',
            'category-shortdescription',
            'category-subcategories',
            'category-plugins'
        );
        $table->head = array(
            get_string('sortorder', 'local_plugins'),
            get_string('category', 'local_plugins'),
            get_string('categoryplugintype', 'local_plugins'),
            get_string('shortdescription', 'local_plugins'),
            get_string('subcategories', 'local_plugins'),
            get_string('plugins', 'local_plugins'),
        );
        if ($canmanagecategories) {
            $table->head[] = $this->output->help_icon('editablecategoryaction', 'local_plugins');
            $table->colclasses[] = 'category-actions';
        }
        $table->data = array();
        foreach ($categories->children as $category) {
            $table->data = array_merge($table->data, $this->editable_category_table_row($category, $canmanagecategories));
        }

        return html_writer::table($table);
    }

    /**
     * Generates HTML to display a category as a row including rows for all of its children categories.
     *
     * @param local_plugins_category $category
     * @param bool $canmanagecategories
     * @param int $depth
     * @return array
     */
    protected function editable_category_table_row(local_plugins_category $category, $canmanagecategories = false, $depth = 0) {
        $rows = array();
        $editurl = new local_plugins_url('/local/plugins/admin/categories.php', array('id' => $category->id));
        $deleteurl = new local_plugins_url('/local/plugins/admin/categories.php', array('id' => $category->id, 'action' => 'delete', 'sesskey' => sesskey()));

        $row = new html_table_row(array(
            str_repeat('&nbsp;&nbsp;&nbsp;', $depth). $category->sortorder,
            str_repeat('&nbsp;&nbsp;&nbsp;', $depth). html_writer::link($editurl, $category->formatted_name),
            $category->plugintype,
            $category->formatted_shortdescription,
            count($category->children),
            html_writer::link($category->browseurl, $category->formatted_plugincount)
        ));
        $row->id = 'category-'.$category->id;
        $row->attributes['class'] .= 'depth-'.$depth;
        if ($category->can_edit()) {
            $editicons  = $this->output->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
            if ($category->can_delete()) {
                $editicons .= $this->output->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')));
            }
            $row->cells[] = new html_table_cell($editicons);
        }
        $rows[] = $row;

        if ($category->has_children()) {
            foreach ($category->children as $child) {
                $rows = array_merge($rows, $this->editable_category_table_row($child, $canmanagecategories, $depth+1));
            }
        }
        return $rows;
    }

    /**
     * Generates HTML to display an editable review criteria table
     *
     * @param array $criteria
     * @param bool $canmanagereviewcriteria
     * @return string
     */
    public function editable_review_criteria_table(array $criteria, $canmanagereviewcriteria = false) {
        $table = new html_table();
        $table->attributes['class'] = 'editable-review-criteria-table generaltable';
        $table->colclasses = array(
            'criteria-name',
            'criteria-description',
            'criteria-scale',
            'criteria-cohort',
        );
        $table->head = array(
            get_string('name', 'local_plugins'),
            get_string('description', 'local_plugins'),
            get_string('scale', 'local_plugins'),
            get_string('cohort', 'local_plugins'),
        );
        if ($canmanagereviewcriteria) {
            $table->head[] = '-';
            $table->colclasses[] = 'criteria-actions';
        }
        $table->data = array();

        $scales = local_plugins_helper::get_review_scale_options();
        $cohorts = local_plugins_helper::get_review_cohort_options();
        foreach ($criteria as $criterion) {
            $scale = get_string('none', 'local_plugins');
            $cohort = $scale;
            if (array_key_exists($criterion->scaleid, $scales)) {
                $scale = $scales[$criterion->scaleid];
            }
            if (array_key_exists($criterion->cohortid, $cohorts)) {
                $cohort = $cohorts[$criterion->cohortid];
            }
            $row = new html_table_row(array(
                $criterion->formatted_name,
                $criterion->formatted_description,
                $scale,
                $cohort
            ));
            if ($canmanagereviewcriteria) {
                $editurl = new local_plugins_url('/local/plugins/admin/criteria.php', array('id' => $criterion->id));
                $editicons  = $this->output->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
                $deleteurl = new local_plugins_url('/local/plugins/admin/criteria.php', array('id' => $criterion->id, 'action' => 'delete', 'sesskey' => sesskey()));
                $editicons .= $this->output->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')));
                $row->cells[] = new html_table_cell($editicons);
            }
            $table->data[] = $row;
        }
        return html_writer::table($table);
    }

    /**
     * Generates HTML to display an editable software versions table
     *
     * @param array $softwareversions
     * @param bool $canmanage
     * @return string
     */
    public function editable_software_version_table(array $softwareversions, $canmanage = false) {
        $table = new html_table();
        $table->attributes['class'] = 'editable-software-versions-table generaltable';
        $table->colclasses = array(
            'softwareversion-name',
            'softwareversion-version',
            'softwareversion-release'
        );
        $table->head = array(
            get_string('software', 'local_plugins'),
            get_string('softwareversionnumber', 'local_plugins'),
            get_string('softwareversionname', 'local_plugins')
        );
        $table->data = array();
        if ($canmanage) {
            $table->head[] = '-';
            $table->colclasses[] = 'softwareversion-actions';
        }
        foreach ($softwareversions as $softwareversion) {
            $row = new html_table_row(array(
                format_string($softwareversion->name),
                format_string($softwareversion->version),
                format_string($softwareversion->releasename)
            ));
            if ($canmanage) {
                $editurl = new local_plugins_url('/local/plugins/admin/softwareversions.php', array('id' => $softwareversion->id));
                $editicons  = $this->output->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
                $deleteurl = new local_plugins_url('/local/plugins/admin/softwareversions.php', array('id' => $softwareversion->id, 'action' => 'delete', 'sesskey' => sesskey()));
                $editicons .= $this->output->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')));
                $row->cells[] = new html_table_cell($editicons);
            }
            $table->data[] = $row;
        }
        return html_writer::table($table);
    }

    /**
     * Generates HTML to display an editable award table
     *
     * @param array $awards
     * @param bool $canmanage
     * @return string
     */
    public function editable_award_version_table(array $awards, $canmanage = false) {
        $table = new html_table();
        $table->attributes['class'] = 'editable-software-versions-table generaltable';
        $table->colclasses = array(
            'award-name',
            'award-description',
            'award-plugincount',
            'award-created',
            'award-onfrontpage'
        );
        $table->head = array(
            get_string('awardicon', 'local_plugins'),
            get_string('name', 'local_plugins'),
            get_string('description', 'local_plugins'),
            get_string('plugincount', 'local_plugins'),
            get_string('created', 'local_plugins'),
            get_string('onfrontpage', 'local_plugins'),
        );
        $table->data = array();
        if ($canmanage) {
            $table->head[] = '-';
            $table->colclasses[] = 'award-actions';
        }
        foreach ($awards as $award) {
            $icon = ($award->icon)?html_writer::empty_tag('img', array('src' => $award->icon, 'alt' => get_string('awardicon', 'local_plugins'))):'';
            $row = new html_table_row(array(
                $icon,
                $award->formatted_name.'<div><small class="text-muted">'.$award->shortname.'</small></div>',
                $award->formatted_description,
                html_writer::link($award->browseurl, $award->plugincount),
                $award->formatted_timecreated,
                $award->onfrontpage ? get_string('yes') : '',
            ));
            if ($canmanage) {
                $editurl = new local_plugins_url('/local/plugins/admin/awards.php', array('id' => $award->id));
                $editicons  = $this->output->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
                $deleteurl = new local_plugins_url('/local/plugins/admin/awards.php', array('id' => $award->id, 'action' => 'delete', 'sesskey' => sesskey()));
                $editicons .= $this->output->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')));
                $row->cells[] = new html_table_cell($editicons);
            }
            $table->data[] = $row;
        }
        return html_writer::table($table);
    }

    /**
     * Generates HTML to display an editable table of sets
     *
     * @param array $sets
     * @param bool $canmanage
     * @return string
     */
    public function editable_sets_table(array $sets, $canmanage = false) {
        $table = new html_table();
        $table->attributes['class'] = 'editable-sets-table generaltable';
        $table->colclasses = array(
            'set-name',
            'set-shortdescription',
            'set-maxplugins',
            'set-plugincount',
            'set-onfrontpage'
        );
        $table->head = array(
            get_string('name', 'local_plugins'),
            get_string('description', 'local_plugins'),
            get_string('maxplugins', 'local_plugins'),
            get_string('plugincount', 'local_plugins'),
            get_string('onfrontpage', 'local_plugins'),
        );
        $table->data = array();
        if ($canmanage) {
            $table->head[] = '-';
            $table->colclasses[] = 'set-actions';
        }
        foreach ($sets as $set) {
            $row = new html_table_row(array(
                $set->formatted_name.'<div><small class="text-muted">'.$set->shortname.'</small></div>',
                $set->formatted_description,
                $set->maxplugins,
                html_writer::link($set->browseurl, $set->plugincount),
                $set->onfrontpage ? get_string('yes') : '',
            ));
            if ($canmanage) {
                $editurl = new local_plugins_url('/local/plugins/admin/sets.php', array('id' => $set->id));
                $editicons  = $this->output->action_icon($editurl, new pix_icon('t/edit', get_string('edit')));
                $deleteurl = new local_plugins_url('/local/plugins/admin/sets.php', array('id' => $set->id, 'action' => 'delete', 'sesskey' => sesskey()));
                $editicons .= $this->output->action_icon($deleteurl, new pix_icon('t/delete', get_string('delete')));
                $row->cells[] = new html_table_cell($editicons);
            }
            $table->data[] = $row;
        }
        return html_writer::table($table);
    }

    /**
     * Generates HTML to display a heading with rss icon
     *
     * @param string $title
     * @param string $class
     * @param moodle_url $rssurl
     * @return string
     */
    protected function heading($title, $class, $rssurl = null) {
        global $CFG;
        if (!empty($rssurl) && !empty($CFG->enablerssfeeds)) {
            $title .= ' '. $this->output->action_icon($rssurl, new pix_icon('rss', 'RSS', 'local_plugins'));
        }
        return $this->output->heading($title, 2, $class);
    }

    /**
     * Generates HTML to display a local_plugins category
     *
     * @param local_plugins_category $category
     * @return string
     */
    protected function render_local_plugins_category(local_plugins_category $category) {
        if (!empty($category->plugintype) && $category->plugintype != '-') {
            $id = $category->plugintype;
        } else {
            $id = $category->formatted_name;
        }
        $id = preg_replace("/[^a-z]/", "", strtolower($id));
        if (empty($id)) {
            $id = $category->id;
        }
        $output = html_writer::start_tag('div', array('class'=>'clearfix browse-category', 'id'=>'category-'.$id));
        $output .= $this->heading($category->formatted_name, 'main category-name', $category->rssurl);
        if (!empty($category->description)) {
            $output .= html_writer::tag('div', $category->formatted_description, array('class'=>'category-description'));
        }
        if ($category->has_children()) {
            $subcategoriesheading = $this->output->heading(get_string('subcategories', 'local_plugins'), 3);
            $output .= $this->categories_listing($category->children, 'subcategories', $subcategoriesheading);
        }
        $output .= $this->plugin_collection($category);
        $output .= html_writer::end_tag('div'); // .browse-category
        return $output;
    }

    /**
     * Renders a contributor
     *
     * @param local_plugins_contributor $contributor
     * @return string
     */
    protected function render_local_plugins_contributor(local_plugins_contributor $contributor) {
        $a = new stdClass;
        $canview = $contributor->can_view_profile();
        if ($canview) {
            $a->fullname = html_writer::link(new local_plugins_url('/user/profile.php', array('id' => $contributor->userid)), $contributor->username, array('class' => 'name'));
        } else {
            $a->fullname = html_writer::tag('span', $contributor->username, array('class' => 'name'));
        }
        $a->picture = $this->output->user_picture($contributor->user, array('size' => 24, 'link' => $canview));
        $heading = get_string('contributionsmadeby', 'local_plugins', $a);
        $output = html_writer::start_tag('div', array('class'=>'clearfix browse-contributor', 'id'=>'contributor-'.$contributor->userid));
        $output .= $this->heading($heading, 'main contributor-name', $contributor->rssurl);
        $output .= $this->plugin_collection($contributor);
        $output .= html_writer::end_tag('div'); // .browse-contributor
        return $output;
    }

    /**
     * Generates HTML to display an array of categories
     *
     * @param array $categories
     * @return string
     */
    public function categories_listing(array $categories, $class, $heading) {

        $this->page->requires->yui_module('moodle-theme_moodleorgcleaned-finesses', 'M.theme_moodleorgcleaned.finesses.gridRowsEqualHeight',
            array('.categories-list .category-depth-0'));

        $output = html_writer::start_div('categories-list-container '.$class);
        $output .= $heading;
        $output .= html_writer::start_div('categories-list');

        $chunks = array_chunk($categories, 3);
        foreach ($chunks as $chunk) {
            $output .= html_writer::start_div('row');
            foreach ($chunk as $category) {
                $output .= html_writer::div($this->frontpage_category($category, true), 'col-md-4');
            }
            $output .= html_writer::end_div();
        }
        $output .= html_writer::end_div(); // categories-list
        $output .= html_writer::end_div(); // categories-list-container

        return $output;
    }

    /**
     * Generates HTML to display a plugin pagination object
     *
     * @param local_plugins_plugin_pagination $paginator
     * @return string
     */
    public function render_local_plugins_plugin_pagination(local_plugins_plugin_pagination $paginator) {
        //TODO: function render_local_plugins_plugin_pagination() is not used
        $pagination  = html_writer::start_tag('div', array('class'=>'pagination-links'));
        $page = $paginator->page;
        $totalpages = $paginator->totalpages;
        $url = $this->page->url;
        for ($i = 1; $i <= $totalpages; $i++) {
            $pagination .= html_writer::link($paginator->get_page_link($url, $i), $i);
        }
        $pagination .= html_writer::end_tag('div');

        $output  = html_writer::start_tag('div', array('class'=>'plugin-pagination'));
        $output .= $pagination;
        $output .= html_writer::start_tag('ul');
        $count = 0;
        $limit = count($paginator->plugins) - 1;
        foreach ($paginator->plugins as $plugin) {
            $classes = array('plugin');
            if ($count == 0) {
                $classes[] = 'first-plugin';
            } else if ($count == $limit) {
                $classes[] = 'last-plugin';
            }
            $count++;
            $output .= html_writer::start_tag('li', array('class'=>join(' ', $classes), 'id'=>'plugin-'.$plugin->id));
            $output .= html_writer::tag('p', html_writer::link($plugin->viewlink, $plugin->formatted_name), array('class'=>'plugin-name'));
            $output .= html_writer::tag('p', $plugin->formatted_shortdescription, array('class'=>'plugin-shortdescription'));
            $output .= html_writer::tag('p', 'Plugin details to go here', array('class'=>'plugin-details'));
            $output .= html_writer::end_tag('li');
        }
        $output .= html_writer::end_tag('ul');
        $output .= $pagination;
        $output .= html_writer::end_tag('div');

        return $output;
    }

    /**
     * Generates HTML to display a local_plugins award
     *
     * @param local_plugins_award $award
     * @return string
     */
    protected function render_local_plugins_award(local_plugins_award $award) {

        $output = html_writer::start_tag('div', array('class'=>'clearfix browse-award', 'id'=>'award-'.$award->id));

        if (!empty($award->icon)) {
            $output .= html_writer::empty_tag('img', [
                'src' => $award->icon,
                'alt' => get_string('awardicon', 'local_plugins'),
                'class' => 'award-icon',
            ]);
        }

        $output .= $this->heading($award->formatted_name, 'main award-name', $award->rssurl);

        if (!empty($award->description)) {
            $output .= html_writer::tag('div', $award->formatted_description, array('class'=>'award-description'));
        }

        $output .= $this->plugin_collection($award);
        $output .= html_writer::end_tag('div'); // .browse-award

        return $output;
    }

    /**
     * Generates HTML to display a local_plugins set
     *
     * @param local_plugins_set $set
     * @return string
     */
    protected function render_local_plugins_set(local_plugins_set $set) {
        $output = html_writer::start_tag('div', array('class'=>'clearfix browse-set', 'id'=>'set-'.$set->id));
        $output .= $this->heading($set->formatted_name, 'main set-name', $set->rssurl);
        if (!empty($set->description)) {
            $output .= html_writer::tag('div', $set->formatted_description, array('class'=>'set-description'));
        }
        $output .= $this->plugin_collection($set);
        $output .= html_writer::end_tag('div'); // .browse-set
        return $output;
    }

    /**
     * Generates HTML to display recent plugins heading
     *
     * @param local_plugins_recentplugins $recentplugins
     * @return string
     */
    protected function render_local_plugins_recentplugins(local_plugins_recentplugins $recentplugins) {
        $output = html_writer::start_tag('div', array('class'=>'clearfix recent-plugins'));
        $output .= $this->heading($recentplugins->formatted_name, 'main', $recentplugins->rssurl);
        $output .= $this->plugin_collection($recentplugins);
        $output .= html_writer::end_tag('div'); // .recent-plugins
        return $output;
    }
    protected function render_local_plugins_recentplugins_new(local_plugins_recentplugins_new $recentplugins) {
        $output = html_writer::start_tag('div', array('class'=>'clearfix recent-plugins'));
        $output .= $this->heading($recentplugins->formatted_name, 'main', $recentplugins->rssurl);
        $output .= $this->plugin_collection($recentplugins);
        $output .= html_writer::end_tag('div'); // .recent-plugins
        return $output;
    }
    protected function render_local_plugins_recentplugins_updated(local_plugins_recentplugins_updated $recentplugins) {
        $output = html_writer::start_tag('div', array('class'=>'clearfix recent-plugins'));
        $output .= $this->heading($recentplugins->formatted_name, 'main', $recentplugins->rssurl);
        $output .= $this->plugin_collection($recentplugins);
        $output .= html_writer::end_tag('div'); // .recent-plugins
        return $output;
    }

    /**
     * Produces the html that represents this rating in the UI
     *
     * @param $page the page object on which this rating will appear
     * @return string
     */
    public function render_rating(rating $rating) {
        global $CFG, $USER;

        $canrate = ($rating->user_can_rate());
        $canviewaggregate = ($rating->user_can_view_aggregate());
        if (!$canrate && !$canviewaggregate) {
            return '';
        }

        $stars = $rating->settings->scale->max;
        $userrating = $rating->rating;
        $aggregate = round($rating->get_aggregate_string(), 1);

        $width = 0;
        if ($canviewaggregate) {
            $width = round(($aggregate/$stars)*100, 1);
            if ($rating->count == 0) {
                $title = get_string('rateaverage_none', 'local_plugins');
            } else if ($rating->count == 1) {
                $title = get_string('rateaverage_one', 'local_plugins', array('aggregate' => $aggregate));
            } else {
                $title = get_string('rateaverage_many', 'local_plugins', array('ratings' => $rating->count, 'aggregate' => $aggregate));
            }
        } else if (!empty($userrating)) {
            $width = round(($userrating/$stars)*100, 1);
            $title = get_string('userrating', 'local_plugins', $userrating);
        }

        $html  = html_writer::start_tag('div', array('class' => 'clearfix starrating', 'rel' => $rating->itemid));
        $html .= html_writer::start_tag('div', array('class' => 'starrating-wrap', 'title' => $title));
        $html .= html_writer::start_tag('div', array('class' => 'starrating-empty'));
        $html .= html_writer::tag('div', '', array('class' => 'starrating-full', 'style' => 'width:'.$width.'%'));
        $html .= html_writer::end_tag('div'); // starrating-empty
        $html .= html_writer::end_tag('div'); // starrating-wrap

        if ($canrate) {

            $rateurl = $rating->get_rate_url();
            $action = $rateurl->out_omit_querystring();
            $params = $rateurl->params();

            $html .= html_writer::start_tag('div', array('class' => 'starrating-rate'));
            $html .= html_writer::start_tag('form', array('action' => $action, 'method' => 'post'));
            $html .= html_writer::tag('div', get_string('ratethisversion', 'local_plugins'), array('class' => 'ratethisversion'));
            foreach ($params as $name => $value) {
                $html .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $name, 'value' => $value));
            }
            $html .= html_writer::start_tag('select', array('name' => 'rating'));
            $html .= html_writer::tag('option', '- / -', array('value' => ''));
            foreach ($rating->settings->scale->scaleitems as $key => $item) {
                if ($key == $userrating) {
                    $html .= html_writer::tag('option', "$item / $stars", array('value' => $key, 'selected' => 'selected'));
                } else {
                    $html .= html_writer::tag('option', "$item / $stars", array('value' => $key));
                }
            }
            $html .= html_writer::end_tag('select');
            $html .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('rate', 'local_plugins')));
            $html .= html_writer::end_tag('form');
            $html .= html_writer::end_tag('div'); // starrating-rate
        }
        $html .= html_writer::end_tag('div'); // starrating
        return $html;
    }


    /**
     * Generates HTML to display a plugin version status
     *
     * @param local_plugins_version $version
     * @return string
     */
    protected function plugin_version_status(local_plugins_version $version) {
        $output = '';
        if ($version->can_view_status()) {
            $output .= html_writer::start_tag('div', array('class' => 'version-status'));
            if ($version->approved == local_plugins_plugin::PLUGIN_APPROVED && $version->visible) {
                $output .= html_writer::tag('span', get_string('available', 'local_plugins'), array('class' => 'available badge badge-success'));
            } else if ($version->approved == local_plugins_plugin::PLUGIN_UNAPPROVED) {
                $output .= html_writer::tag('span', get_string('notapproved', 'local_plugins'), array('class' => 'not-approved badge badge-danger'));
            } else if ($version->approved == local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
                $output .= html_writer::tag('span', get_string('pendingapproval', 'local_plugins'), array('class' => 'pending-approval badge badge-warning'));
            } else if (!$version->visible) {
                $output .= html_writer::tag('span', get_string('invisible', 'local_plugins'), array('class' => 'nonvisible badge badge-info'));
            }
            $output .= html_writer::end_tag('div');
        }
        return $output;
    }

    /**
     * Generates HTML for version download button
     *
     * @param local_plugins_version|null $version
     * @return string
     */
    public function plugin_version_download_button($version) {

        if (!$version || !$version->can_download()) {
            return '';
        }

        $class = 'btn btn-success my-2 mr-1';

        if ($version->is_latest_version()) {
            $class .= ' latest';
        } else {
            $class .= ' previous';
        }

        if (!$version->is_available()) {
            $class .= ' unavailable';
        }

        $html = html_writer::start_div('plugin-get-buttons');

        $url = $version->plugin->get_install_link();
        if (!empty($url)) {
            $html .= local_plugins_helper::get_install_button($url, $class);
        }

        $html .= local_plugins_helper::get_download_button($version->downloadlinkredirector, $class);

        $html .= html_writer::end_div();

        return $html;
    }

    /**
     * Render the list of Moodle versions the given plugin version supports
     */
    public function plugin_version_for_moodle(local_plugins_version $version, $tag = 'div') {
        $moodleversions = $version->get_moodle_versions();
        if (empty($moodleversions)) {
            return '';
        }
        $vers = array();
        foreach ($moodleversions as $mversion) {
            $vers[] = $mversion->releasename;
        }
        return html_writer::tag($tag, 'Moodle '.join(', ',$vers), array('class' => 'moodleversions'));
    }
    /**
     * Generates HTML to display a plugin description page
     *
     * @param local_plugins_plugin $plugin
     * @return string
     */
    protected function render_local_plugins_plugin(local_plugins_plugin $plugin) {
        $context = context_system::instance();
        $html = '';
        // Average review grades
        //$gradeshtml = $this->average_review_grades($plugin->average_review_grades, $plugin->viewreviewslink);
        //if (!empty($gradeshtml)) {
        //    $html .= html_writer::tag('div', $gradeshtml, array('class' => 'plugin-average-grades'));
        //}
        // description
        if (!empty(strip_tags($plugin->description))) {
            $html .= html_writer::tag('div', $plugin->formatted_description, array('class' => 'description border p-3 rounded mb-3'));
        }
        //sets
        $html .= $this->plugin_sets($plugin);
        // trackingwidgets
        if ($plugin->trackingwidgets) {
            $html .= html_writer::div(
                $this->output->heading(get_string('trackingwidgets', 'local_plugins'), 3).
                html_writer::div($plugin->formatted_trackingwidgets, 'content'),
                'infoblock border p-3 rounded mb-3 trackingwidgets'
            );
        }
        // links: documentationurl, websiteurl, sourcecontrolurl, bugtrackerurl, discussionurl
        $links = array();
        foreach (array('documentationurl', 'websiteurl', 'sourcecontrolurl', 'bugtrackerurl', 'discussionurl') as $linkname) {
            // We used to have PARAM_TEXT in the past so better to check PARAM_URL now.
            $linkurl = clean_param($plugin->$linkname, PARAM_URL);
            if ($linkurl) {
                if ((strpos($linkurl, 'http://') !== 0) and (strpos($linkurl, 'https://') !== 0)) {
                    $linkurl = 'http://'.$linkurl;
                }
                $link = html_writer::link($linkurl, '<i class="text-body-color fa fa-angle-double-right"></i> ' . get_string($linkname. 'link', 'local_plugins'), array('onclick'=>'this.target="_blank"', 'class' => 'external'));
                $links[] = html_writer::tag('div', $link, array('class' => 'li '.$linkname));
            }
        }

        if (sizeof($links)) {
            $html .= html_writer::start_tag('div', array('class' => 'infoblock links border p-3 rounded mb-3'));
            $html .= $this->output->heading(get_string('pluginlinks', 'local_plugins'), 3);
            $html .= html_writer::tag('div', join('', $links), array('class' => 'ul'));
            $html .= html_writer::end_tag('div'); // .infoblock.links
        }
        //screenshots
        $html .= $this->plugin_screenshots($plugin->screenshots);
        //contributors
        $html .= $this->plugin_contributors($plugin->contributors, $plugin);
        //awards
        $html .= $this->plugin_awards($plugin);
        //comments
        $html .= $this->plugin_comments($plugin);
        return $html;
    }

    /**
     * Generates HTML to display a plugin version
     *
     * @param local_plugins_version $version
     * @return string
     */
    protected function render_local_plugins_version(local_plugins_version $version) {
        $html = html_writer::start_tag('div', array('class' => 'view-version'));

        $html .= '<div class="row mt-4 mb-2">';
        $html .= '<div class="col-md-7">';

        $html .= html_writer::tag('h2', $version->pluginversionname);
        $html .= $this->plugin_version_for_moodle($version, 'div');
        $html .= html_writer::tag('div', get_string('timecreateddate', 'local_plugins', $version->formatted_timecreated),
            array('class' => 'small text-muted mb-3'));

        $html .= '</div>';
        $html .= '<div class="col-md-5">';

        // Download buttons.
        if ($version->can_download()) {
            $html .= html_writer::start_div('plugin-download-version');
            $html .= $this->plugin_version_download_button($version);
            $html .= html_writer::end_div();
        }

        // Status and action buttons
        $buttons = $version->actions_list(array('view', 'download'));
        if (empty(!$buttons)) {
            $html .= html_writer::start_tag('div', array('class' => 'version-actions'));
            foreach ($buttons as $class => $action) {
                $html .= html_writer::link($action[0], $action[1], array('class' => 'btn btn-default btn-sm mb-1 mr-1 action-'.$class));
            }
            $html .= html_writer::end_tag('div');
        }

        $html .= '</div>';
        $html .= '</div>';

        // CI precheck badges.
        $smurfresult = $version->smurfresult;
        if ($smurfresult) {
            $html .= html_writer::start_div('infoblock border p-3 rounded mb-3 ciprecheck');
            $html .= html_writer::tag('h3', get_string('codeprechecks', 'local_plugins'));
            $html .= html_writer::start_tag('div', array('class' => 'plugin-precheck-badges'));
            $html .= $this->smurf_results($version, $smurfresult);
            $html .= html_writer::end_tag('div');
            $html .= html_writer::link(new local_plugins_url($version->viewlink, ['smurf' => 'html']),
                'HTML', ['target' => '_blank']);
            $html .= ' | ';
            $html .= html_writer::link(new local_plugins_url($version->viewlink, ['smurf' => 'xml']),
                'XML', ['target' => '_blank']);
            $html .= html_writer::end_tag('div');
        }

        // Release notes
        if (!empty($version->releasenotes)) {
            $html .= html_writer::start_div('infoblock border p-3 rounded mb-3 releasenotes');
            $html .= html_writer::start_div();
            $html .= $version->formatted_releasenotes;
            $html .= html_writer::end_div();
            $html .= html_writer::end_div();
        }

        $html .= html_writer::start_tag('div', array('class' => 'infoblock border p-3 rounded mb-3 versioninformation'));
        $html .= html_writer::tag('h3', get_string('versioninformation', 'local_plugins'));
        $html .= html_writer::start_tag('dl', array('class' => 'dl-horizontal'));

        // Version
        $html .= html_writer::tag('dt', get_string('versionnumber', 'local_plugins'), array('class' => 'version'));
        if ($version->version === null) {
            $html .= html_writer::tag('dd', get_string('notspecified', 'local_plugins'), array('class' => 'notspecified'));
        } else {
            $html .= html_writer::tag('dd', $version->version);
        }

        // Name
        if (!empty($version->releasename)) {
            $html .= html_writer::tag('dt', get_string('versionname', 'local_plugins'), array('class' => 'releasename'));
            $html .= html_writer::tag('dd', $version->formatted_releasename);
        }

        // Status
        $status = $this->plugin_version_status($version);
        if (!empty($status)) {
            $html .= html_writer::tag('dt', get_string('status', 'local_plugins'), array('class' => 'status'));
            $html .= html_writer::tag('dd', $status);
        }

        // Updateable version and versions this version can be updated to
        foreach (array('updateableversions' => $version->updateable_versions, 'updatetoversions' => $version->update_to_versions) as $section => $versionslist) {
            if (!empty($versionslist)) {
                $html .= html_writer::tag('dt', get_string($section, 'local_plugins'), array('class' => $section));
                $prevversions = array();
                foreach ($versionslist as $id) {
                    $prevversion = $version->plugin->get_version($id);
                    if ($prevversion->can_view()) {
                        $prevversions[] = html_writer::link($prevversion->viewlink, $prevversion->formatted_fullname);
                    } else {
                        $prevversions[] = $prevversion->formatted_releasename;
                    }
                }
                $html .= html_writer::tag('dd', join(', ', $prevversions));
            }
        }

        // Maturity
        $html .= html_writer::tag('dt', get_string('maturity', 'local_plugins'), array('class' => 'maturity'));
        $html .= html_writer::tag('dd', $version->formatted_maturity);

        // md5sum
        $html .= html_writer::tag('dt',  get_string('md5sum', 'local_plugins'),  array('class' => 'md5sum'));
        $html .= html_writer::tag('dd', $version->md5sum);

        // Supported versions
        if (count($version->supportedsoftware)) {
            $html .= html_writer::tag('dt', get_string('supportedsoftware', 'local_plugins'), array('class' => 'supportedsoftware', 'style' => 'white-space:normal;'));
            $softversions = array();
            foreach ($version->supportedsoftware as $software) {
                $softversions[] = html_writer::tag('a', $software->fullname, array('title' => $software->fullname_version));
            }
            $html .= html_writer::tag('dd', join(', ', $softversions));
        }

        // Warning about existence of more recent version or notification about this version being latest.
        $html .= html_writer::start_tag('dd');
        $html .= html_writer::start_tag('ul');
        foreach ($version->plugin->get_moodle_versions() as $mversion) {
            if (isset($version->supportedsoftware[$mversion->id])) {
                $lastversion = $version->plugin->get_mostrecentversion($mversion->id);
                if ($lastversion) {
                    $a = array('version' => html_writer::link($lastversion->viewlink, $lastversion->formatted_fullname),
                        'requirements' => html_writer::tag('strong', $mversion->fullname));
                    if (''.$lastversion->version > ''.$version->version) {
                        $html .= html_writer::tag('li', get_string('notthelatest', 'local_plugins', (object)$a),
                            array('class' => 'notthelatest'));
                    } else if ($lastversion->id == $version->id) {
                        $html .= html_writer::tag('li', get_string('isthelatest', 'local_plugins', (object)$a),
                            array('class' => 'isthelatest'));
                    }
                }
            }
        }
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_tag('dd');

        $html .= html_writer::end_tag('dl');

        // Changelogurl URL
        if (!empty($version->changelogurl)) {
            $link = html_writer::link($version->changelogurl, get_string('changelogurl', 'local_plugins'), array('onclick'=>'this.target="_blank"', 'class' => 'external'));
            $html .= html_writer::tag('div', $link, array('class' => 'changelogurl'));
        }

        // Alternative URL
        if (!empty($version->altdownloadurl)) {
            $link = html_writer::link($version->altdownloadurl, get_string('altdownloadurl', 'local_plugins'), array('onclick'=>'this.target="_blank"', 'class' => 'external'));
            $html .= html_writer::tag('div', $link, array('class' => 'alternativeurl'));
        }

        $html .= html_writer::end_tag('div'); // .infoblock.versioninformation

        if (!empty($version->vcssystem) && $version->vcssystem != 'none') {

            $html .= html_writer::start_tag('div', array('class' => 'infoblock border p-3 rounded mb-3 versioncontrolinfo'));
            $html .= html_writer::tag('h3', get_string('versioncontrolinfo', 'local_plugins'));
            $html .= html_writer::start_tag('dl', array('class' => 'dl-horizontal'));

            // Version control system
            $html .= html_writer::tag('dt', get_string('vcssystem', 'local_plugins'), array('class' => 'vcssystem', 'style' => 'white-space:normal;'));
            $html .= html_writer::tag('dd', $version->formatted_vcssystem);

            if (!empty($version->vcsrepositoryurl)) {
                // VCS Repository
                $link = html_writer::link($version->vcsrepositoryurl, get_string('vcsrepositoryurl', 'local_plugins'), array('onclick'=>'this.target="_blank"', 'class' => 'external'));
                $html .= html_writer::tag('dd', $link, array('class' => 'vcsrepositoryurl'));

                if (!empty($version->vcsbranch)) {
                    // VCS Branch
                    $html .= html_writer::tag('dt', get_string('vcsbranch', 'local_plugins'), array('class' => 'vcsbranch'));
                    $html .= html_writer::tag('dd', $version->vcsbranch);
                }

                if (!empty($version->vcstag)) {
                    // VCS Tag
                    $html .= html_writer::tag('dt', get_string('vcstag', 'local_plugins'), array('class' => 'vcstag'));
                    $html .= html_writer::tag('dd', $version->vcstag);
                }
            }

            $html .= html_writer::end_tag('dl');
            $html .= html_writer::end_tag('div'); // .infoblock.versioncontrolinfo
        }

        // Installation instructions
        $category = $version->plugin->category;
        $installinstructions = $category->formatted_installinstructions;
        if (!empty($installinstructions)) {
            $html .= html_writer::start_div('infoblock border p-3 rounded mb-3 installinstructions');
            $html .= html_writer::tag('h3', get_string('installinstructionsforcategory', 'local_plugins', $category->formatted_name));
            $html .= html_writer::start_div();
            $html .= $installinstructions;
            $html .= html_writer::end_div();
            $html .= html_writer::end_div();
        }

        $html .= html_writer::end_tag('div'); // view-version

        return $html;
    }

    /**
     * Generates HTML to display a listing of plugins within a set
     *
     * @param local_plugins_set $set
     * @param int $pluginstoshow
     * @return string
     */
    public function set_listing(local_plugins_set $set, $pluginstoshow = 20) {
        $details = $this->output->heading($set->formatted_name, 3); //MIM TODO get_string like in awards and categories
        $plugins = $set->get_plugins($pluginstoshow);
        if (sizeof($plugins)) {
            $footer  = html_writer::link($set->browseurl, get_string('viewallinset', 'local_plugins'));
            return $this->collection_listing($details, $plugins, $footer);
        } else {
            return '';
        }
    }

    /**
     * Generates HTML to display a listing of plugins with a specific award
     *
     * @param local_plugins_award $award
     * @param int $pluginstoshow
     * @return string
     */
    public function award_listing(local_plugins_award $award, $pluginstoshow = 20) {
        $details  = $this->output->heading(get_string('awardrecentlyawarded', 'local_plugins', $award->formatted_name), 3);
        //MIM TODO display logo
        $plugins  = $award->get_plugins($pluginstoshow);
        if (sizeof($plugins)) {
            $footer  = html_writer::link($award->browseurl, get_string('viewallinaward', 'local_plugins'));
            return $this->collection_listing($details, $plugins, $footer);
        } else {
            return '';
        }
    }

    /**
     * Generates HTML to display a listing of plugins within a specific category
     *
     * @param local_plugins_category $category
     * @param int $pluginstoshow
     * @return string
     */
    public function category_listing(local_plugins_category $category, $pluginstoshow = 20) {
        $details = $this->output->heading(get_string('categorynamed', 'local_plugins', $category->formatted_name), 3);
        $plugins = $category->get_plugins($pluginstoshow);
        if (sizeof($plugins)) {
            $footer  = html_writer::link($category->browseurl, get_string('viewallincategory', 'local_plugins'));
            return $this->collection_listing($details, $plugins, $footer);
        } else {
            return '';
        }
    }

    /**
     * Generates HTML to display a listing of plugins provided in an array
     *
     * @param string $details A description for this listing
     * @param array $plugins
     * @param string $footer HTML to use in the footer of the listing
     * @return type
     */
    protected function collection_listing($details, array $plugins, $footer = null) {
        $html  = html_writer::start_tag('div', array('class' => 'collection-container'));
        $html .= html_writer::tag('div', $details, array('class' => 'collection-details'));
        $html .= html_writer::start_tag('div', array('class' => 'ul collection-plugins'));
        $count = 0;
        foreach ($plugins as $plugin) {
            $count++;
            $class = ($count % 2)?'odd':'even';
            if (false) $plugin = new local_plugins_plugin;
            $html .= html_writer::start_tag('div', array('class' => 'li collection-plugin '.$class));
            $html .= html_writer::start_tag('div', array('class' => 'plugin-details'));
            $html .= html_writer::start_tag('div', array('class' => 'plugin-name'));
            $html .= html_writer::tag('span', $plugin->category->formatted_name.' > ');
            $html .= html_writer::link($plugin->viewlink, $plugin->formatted_name);
            $html .= html_writer::end_tag('div'); // plugin-name
            $html .= html_writer::tag('div', $plugin->truncated_shortdescription, array('class' => 'plugin-shortdescription'));
            $html .= html_writer::end_tag('div'); // plugin-details
            $html .= html_writer::end_tag('div'); // .li.collection-plugin
        }
        $html .= html_writer::end_tag('div'); // .ul.collection-plugins
        if (!empty($footer)) {
            $html .= html_writer::start_tag('div', array('class' => 'collection-footer'));
            $html .= $footer;
            $html .= html_writer::end_tag('div'); // collection-footer
        }
        $html .= html_writer::end_tag('div'); // collection-container
        return $html;
    }

    /**
     * Generates the overview statistics page with graphs.
     *
     * The overview is generated either for all categories or for the given
     * category.
     *
     * @global moodle_database $DB
     * @global moodle_page $PAGE
     * @return string HTML
     */
    public function stats_overview() {
        global $CFG;

        $categoryid = local_plugins_helper::get_user_plugin_category();
        $output = '';

        if ($categoryid) {
            $output .= $this->output->heading(get_string('categorystats', 'local_plugins'));
            $category = local_plugins_helper::get_category($categoryid);
            local_plugins_add_category_to_navbar($category);

            $node1 = $this->page->navigation->get('local_plugins', navigation_node::TYPE_CONTAINER);
            $node2 = $node1->find('category-'.$category->id, navigation_node::TYPE_CUSTOM);
            if ($node2) {
                $node3 = $node2->get('statistics-'.$category->id);
                $node3->make_active();
            }
            $node4 = $node1->find('local_plugins-overviewstats', navigation_node::TYPE_CUSTOM);
            if ($node4) {
                $node4->make_inactive();
            }
        } else {
            $output .= $this->output->heading(get_string('overviewstats', 'local_plugins'));
        }

        $output .= $this->plugin_category_select();
        $output .= html_writer::start_tag('div', array('class' => 'stats'));

        $statsman = new local_plugins_stats_manager();
        $timefrom = mktime(0, 0, 0, date('n') - 12, 1);
        $timeto = mktime(0, 0, 0, date('n') - 1, 1);

        // Top twenty plugins downloaded in last x months.
        $labels = [];
        $data = [];

        $stats = $statsman->get_stats_top_plugins($categoryid, 20, $timefrom, $timeto);

        foreach ($stats as $plugin) {
            $labels[] = shorten_text($plugin->name, self::PLUGIN_NAME_TRIM_LENGTH);
            $data[] = $plugin->downloads;
        }

        $serie = new core\chart_series(get_string('downloads', 'local_plugins'), $data);

        $chart = new core\chart_bar();
        $chart->add_series($serie);
        $chart->set_labels($labels);
        $chart->set_horizontal(true);

        $output .= html_writer::tag('h3', get_string('downloadstotalrecentplugins', 'local_plugins', 12));

        $CFG->chart_colorset = ['#f5892b'];
        $output .= $this->output->render($chart);
        $CFG->chart_colorset = null;

        // Total downloads by month in last 12 months.
        $labels = [];
        $data = [];

        $stats = $statsman->get_stats_total_monthly($categoryid, $timefrom, $timeto);

        foreach ($stats as $year => $months) {
            foreach ($months as $month => $downloads) {
                $labels[] = date('M', mktime(0, 0, 0, $month, 1, $year)).' '.$year;
                $data[] = $downloads;
            }
        }

        $serie = new core\chart_series(get_string('downloads', 'local_plugins'), $data);

        $chart = new core\chart_bar();
        $chart->add_series($serie);
        $chart->set_labels($labels);

        $output .= html_writer::tag('h3', get_string('downloadmonth', 'local_plugins'));

        $CFG->chart_colorset = ['#f5892b'];
        $output .= $this->output->render($chart);
        $CFG->chart_colorset = null;

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Generates HTML to display plugin status
     *
     * @param array $plugins
     * @return string
     */
    protected function plugin_status(local_plugins_plugin $plugin) {
        $output = '';
        if ($plugin->can_view_status()) {
            $output .= html_writer::start_tag('div', array('class' => 'plugin-status'));
            if ($plugin->approved == local_plugins_plugin::PLUGIN_APPROVED && $plugin->visible) {
                // No need to clutter the UI with a label in this case.
                //$output .= html_writer::tag('span', get_string('available', 'local_plugins'), array('class' => 'available badge-success'));
            } else if ($plugin->approved == local_plugins_plugin::PLUGIN_UNAPPROVED) {
                $output .= html_writer::tag('span', get_string('notapproved', 'local_plugins'), array('class' => 'not-approved badge badge-danger'));
            } else if ($plugin->approved == local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
                $output .= html_writer::tag('span', get_string('pendingapproval', 'local_plugins'), array('class' => 'pending-approval badge badge-warning'));
            } else if (!$plugin->visible) {
                $output .= html_writer::tag('span', get_string('invisible', 'local_plugins'), array('class' => 'invisible badge badge-info'));
            }
            $output .= html_writer::end_tag('div');
        }
        return $output;
    }

    /**
     * Generates HTML to display an array of plugins
     *
     * @param array $plugins
     * @return string
     */
    public function plugin_listing(array $plugins, $pagingbar, $details = null) {
        $html = '';
        $html .= html_writer::start_tag('div', array('class' => 'plugin-list-container'));
        if (!empty($details)) {
            $html .= html_writer::tag('div', $details, array('class' => 'plugin-list-details'));
        }
        if (!empty($pagingbar)) {
            $html .= $this->render($pagingbar);
        }
        $html .= html_writer::start_tag('div', array('class' => 'plugin-list list-group-flush list-group border-bottom border-top'));
        $count = 0;
        foreach ($plugins as $plugin) {
            $count++;
            $class = ($count % 2)?'odd':'even';
            if ($plugin->approved == local_plugins_plugin::PLUGIN_UNAPPROVED) {
                $class .= ' plugin-not-approved';
            }
            if ($plugin->approved == local_plugins_plugin::PLUGIN_PENDINGAPPROVAL) {
                $class .= ' plugin-pending-approval';
            }
            if (!$plugin->visible) {
                $class .= ' plugin-invisible';
            }

            $html .= html_writer::start_div('plugin plugin-card list-group-item '.$class);
            $html .= html_writer::start_div('row');
            $html .= html_writer::start_div('col-md-6');
            $html .= html_writer::start_div('plugin-info-primary');

            $html .= html_writer::start_div('media mb-2');
            if (!empty($plugin->ownlogo)) {
                $html .= html_writer::div(
                    html_writer::link(
                        $plugin->viewlink,
                        html_writer::empty_tag('img', array('src' => new moodle_url($plugin->ownlogo, array('preview' => 'tinyicon')), 'alt' => 'Logo'))
                    ),
                    'plugin-logo p-2 border rounded mr-2'
                );
            }

            $html .= html_writer::start_div('media-body');
            $pluginlink = html_writer::link($plugin->viewlink, $plugin->formatted_name);
            $html .= html_writer::div($pluginlink, 'plugin-name');

            $html .= html_writer::tag('div', html_writer::tag('small', $plugin->frankenstyle), array('class' => 'plugin-frankenstyle text-muted'));
            $html .= html_writer::end_div(); // media-body
            $html .= html_writer::end_div(); // media
            if (!is_null($plugin->searchinfo)) {
                $html .= html_writer::link($plugin->searchinfo->searchurl,
                        get_string('codesearchresults', 'local_plugins', count($plugin->searchinfo->items)),
                        array('class' => 'plugin-searchresults'));
            }

            $html .= html_writer::tag('div', $plugin->formatted_shortdescription, array('class' => 'plugin-shortdescription mb-3'));

            $html .= html_writer::start_div('plugin-labels');
            //$categorylink = html_writer::link($plugin->category->browseurl, $plugin->category->formatted_name,
            //    array('class' => 'plugin-category-link label'));
            //$html .= html_writer::tag('div', $categorylink, array('class' => 'plugin-category'));
            $html .= $this->plugin_status($plugin);
            $html .= html_writer::end_div(); // plugin-labels

            $html .= html_writer::start_div('plugin-stats small mb-2');

            if ($plugin->timelastreleased && $plugin->timelastreleased > $plugin->timecreated) {
                $html .= html_writer::span('<i class="fa fa-rocket" aria-hidden="true"></i> ' .
                    get_string('timelastreleaseddate', 'local_plugins',
                        \local_plugins\human_time_diff::for($plugin->timelastreleased)), 'mr-2');

            } else {
                $html .= html_writer::span('<i class="fa fa-rocket" aria-hidden="true"></i> ' .
                    get_string('timecreateddate', 'local_plugins',
                        \local_plugins\human_time_diff::for($plugin->timecreated)), 'mr-2');
            }

            if (!empty($plugin->aggsites)) {
                $aggsites = $plugin->aggsites;

                $html .= html_writer::span('<i class="fa fa-map-marker" aria-hidden="true"></i> '.$aggsites.' sites', 'mr-2',
                        array('title' => get_string('usageinfo', 'local_plugins', $plugin->aggsites)));
            }

            if (!empty($plugin->aggdownloads)) {
                $aggdownloads = $plugin->aggdownloads;

                if ($aggdownloads > 999) {
                    $aggdownloads = floor($aggdownloads / 1000) . 'k';
                }

                $html .= html_writer::span('<i class="fa fa-download" aria-hidden="true"></i> '.$aggdownloads.' downloads', 'mr-2',
                        array('title' => get_string('downloadsrecent', 'local_plugins', $plugin->aggdownloads)));
            }

            if (!empty($plugin->aggfavs)) {
                $html .= html_writer::span('<i class="fa fa-heart" aria-hidden="true"></i> '.$plugin->aggfavs, 'mr-2',
                        array('title' => get_string('favouritesinfo', 'local_plugins', $plugin->aggfavs)));
            }

            $html .= html_writer::end_div(); // plugin-stats

            $mversions = array();
            foreach ($plugin->moodle_versions as $mversion) {
                $params = array();
                if ($mversion->id == local_plugins_helper::get_user_moodle_version()) {
                    $params['class'] = 'moodleversion userchoice';
                } else {
                    $params['class'] = 'moodleversion notuserchoice';
                }
                $mversions[] = html_writer::link($plugin->get_viewlink($mversion->id), $mversion->releasename, $params);
            }
            if (!empty($mversions)) {
                $html .= html_writer::div(
                    get_string('supportedmoodle', 'local_plugins', join(' | ', $mversions)),
                    'plugin-moodleversions small mb-2'
                );
            }

            $html .= html_writer::end_div(); // plugin-info-primary
            $html .= html_writer::end_div(); // col-md-6

            $html .= html_writer::start_div('col-md-6 d-flex justify-content-center');

            if (!empty($plugin->screenshots)) {
                // Can't use reset($plugin->screenshots) here as it is a
                // read-only property of the object.
                foreach ($plugin->screenshots as $screenshot) {
                    $html .= html_writer::div(
                        html_writer::link($plugin->viewlink, html_writer::empty_tag('img',
                            array('img-fluid', 'alt' => 'Screenshot',
                                'src' => new moodle_url($screenshot->src, array('preview' => 'bigthumb'))
                            )
                        )),
                        'plugin-screenshot bg-light border rounded box-shadow-2 p-2'
                    );
                    break;
                }
            }

            $html .= html_writer::end_div(); // col-md-6
            $html .= html_writer::end_div(); // row
            $html .= html_writer::end_tag('div'); // .plugin
        }

        $html .= html_writer::end_tag('div'); // .plugin-list
        if (!empty($pagingbar)) {
            $html .= $this->render($pagingbar);
        }
        $html .= html_writer::end_tag('div'); // plugin-list-container
        return $html;
    }

    public function plugin_collection(local_plugins_collection_class $collection) {
        $pagingbar = $collection->get_currentpage_pagingbar();
        $plugins = $collection->get_currentpage_plugins();
        return $this->plugin_listing($plugins, $pagingbar);
    }

    /**
     * Generates HTML to display a search form
     *
     * @param string $searchstring
     * @return string
     */
    public function search_form($searchstring = '') {
        $html  = html_writer::start_tag('div', array('class' => 'plugin-search-form mb-2'));
        $html .= html_writer::start_tag('form', array('action' => new local_plugins_url('/local/plugins/index.php'), 'method' => 'get'));
        $html .= html_writer::start_tag('div', array('class' => 'searchfield input-group'));
        $html .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 'q', 'class' => 'form-control', 'value' => s($searchstring)));
        $html .= html_writer::start_tag('div', array('class' => 'searchbutton input-group-append'));
        $html .= html_writer::tag('button',
            get_string('searchplugins', 'local_plugins'),
            array('type' => 'submit', 'class' => 'btn btn-default')
        );
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div'); // plugin-search-form
        return $html;
    }
    /**
     * Generates HTML to display a search code form
     *
     * @param string $searchstring
     * @return string
     */
    public function search_code_form($searchstring = '') {
        $html  = html_writer::start_tag('div', array('class' => 'clearfix plugin-search-form'));
        $html .= html_writer::start_tag('form', array('action' => new local_plugins_url('/local/plugins/search.php'), 'method' => 'get'));
        $html .= html_writer::start_tag('div', array('class' => 'searchfield'));
        $html .= html_writer::empty_tag('input', array('type' => 'text', 'name' => 's', 'value' => s($searchstring)));
        $html .= html_writer::end_tag('div');
        $html .= html_writer::start_tag('div', array('class' => 'searchbutton'));
        $html .= html_writer::empty_tag('input', array('type' => 'submit', 'name' => 'search', 'value' => get_string('searchcode', 'local_plugins')));
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('form');
        $html .= html_writer::end_tag('div'); // plugin-search-form
        return $html;
    }

    /**
     * Generates HTML to display a contib search + its results
     *
     * @param local_plugins_search $search
     * @return string
     */
    protected function render_local_plugins_search(local_plugins_search $search) {
        $html = '';
        $html .= $this->output->heading(get_string('search', 'local_plugins'));
        if (is_a($search, 'local_plugins_search')) {
            $html .= $this->search_form($search->get_searchstring());
        } else {
            $html .= $this->search_form();
        }

        if (has_capability(local_plugins::CAP_APPROVEPLUGIN, context_system::instance())) {
            $html .= $this->search_code_form();
        }
        if ($search->search()) {
            $details = get_string('searchresultsnum', 'local_plugins', $search->get_resultcount());
            $html .= html_writer::start_tag('div', array('class' => 'search-results'));
            $html .= $this->plugin_listing($search->get_plugins(), $search->get_pagingbar($this->page->url), $details);
            $html .= html_writer::end_tag('div');
        }

        return $html;
    }

    /**
     * Generates HTML to display a code github search + its results
     *
     * @param local_plugins_search $search
     * @return string
     */
    protected function render_local_plugins_search_github(local_plugins_search_github $search) {
        $html = '';
        $html .= $this->output->heading(get_string('search', 'local_plugins'));
        $html .= $this->search_form();

        if (has_capability(local_plugins::CAP_APPROVEPLUGIN, context_system::instance())) {
            if (is_a($search, 'local_plugins_search_github')) {
                $html .= $this->search_code_form($search->get_searchstring());
            } else {
                $html .= $this->search_code_form();
            }
        }
        if ($search->search()) {
            $details = get_string('searchresultsnum', 'local_plugins', $search->get_resultcount());
            $html .= html_writer::start_tag('div', array('class' => 'search-results'));
            $html .= $this->plugin_listing($search->get_plugins(), $search->get_pagingbar($this->page->url), $details);
            $html .= html_writer::end_tag('div');
        }

        return $html;
    }
    /**
     * Generates HTML to display the report selector
     *
     * @param array $reports
     * @return string
     */
    public function report_selector(array $reports) {
        $html  = html_writer::start_tag('div', array('class' => 'report-listing'));
        $html .= html_writer::start_tag('ul');
        foreach ($reports as $report) {
            if (!$report->can_view()) {
                continue;
            }
            $html .= html_writer::start_tag('li');
            $html .= html_writer::tag('h3', html_writer::link($report->baseurl, $report->report_title). ' ('. $report->get_totalrows(). ')');
            $html .= html_writer::tag('div', $report->report_description);
            $html .= html_writer::end_tag('li');
        }
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_tag('div'); // report-listing
        return $html;
    }

    /**
     * Generates HTML to display the report summary of reports (to be printed on the top of each page)
     *
     * @param array $reports
     * @return string
     */
    public function report_summary(array $reports, $itemsonly=false) {
        $output = '';
        foreach ($reports as $report) {
            if ($report->can_view() && $report->get_totalrows()) {
                $link = html_writer::link($report->baseurl, $report->report_title). ' ('. $report->get_totalrows(). ')';
                $output .= html_writer::tag('div', $link, array('class' => 'report-summary-item'));
            }
        }
        if (!empty($output) && !$itemsonly) {
            $output  = html_writer::start_tag('div', array('class' => 'report-summary')).
                    $output. html_writer::end_tag('div'); // report-summary
        }
        return $output;
    }

    public function frontpage_category($category, $withchildren = false, $depth = 0) {
        $output = '';
        $output .= html_writer::start_div('category category-depth-'.$depth);
        $link = html_writer::link($category->browseurl, $category->formatted_name);
        $count = html_writer::tag('span', '('. $category->formatted_plugincount. ')', array('class' => 'subcount text-muted'));
        $output .= html_writer::tag('div', $link . ' '. $count, array('class' => 'category-name'));
        $output .= html_writer::tag('div', $category->formatted_shortdescription, array('class' => 'category-shortdescription'));
        if ($category->has_children() && $withchildren) {
            $output .= html_writer::start_tag('div', array('class' => 'category-children'));
            foreach ($category->children as $category2) {
                $output .= $this->frontpage_category($category2, false, $depth + 1);
            }
            $output .= html_writer::end_tag('div'); // category-children
        }
        $output .= html_writer::end_div(); // category

        return $output;
    }

    /**
     * Gets the text form settings and generates html for output.
     *
     * @param string $settingsname
     * @param string $defaultvalue
     * @return string
     */
    public function settings_text($settingsname, $defaultvalue, $attributes) {
        global $CFG;
        if (array_key_exists('class', $attributes)) {
            $attributes['class'] = 'editabletext '.$attributes['class'];
        } else {
            $attributes['class'] = 'editabletext';
        }
        $output = html_writer::start_tag('div', $attributes);

        //if (has_capability('moodle/site:config', context_system::instance())) {
        //    $link = new local_plugins_url('/admin/search.php', array('query' => $settingsname));
        //    $editicons  = $this->output->action_icon($link, new pix_icon('t/edit', get_string('edit')));
        //    $output .= html_writer::tag('div', $editicons, array('class' => 'editbutton'));
        //}

        if (!empty($CFG->$settingsname)) {
            $txt = $CFG->$settingsname;
        } else {
            $txt = $defaultvalue;
        }
        $output .= html_writer::tag('div', $txt, array('class' => 'txt'));
        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Generates HTML to display plugins front page
     */
    public function frontpage() {
        $output = '';
        $output .= $this->settings_text('local_plugins_mainpagetext', '', array('class' => 'mainpagetext'));

        $output .= html_writer::start_tag('div', array('class' => 'clearfix frontpage-collections'));

        $sets = local_plugins_helper::get_frontpage_sets();
        $html = '';
        foreach ($sets as $set) {
            $html .= $this->set_listing($set, 5); //MIM TODO parametrize number of plugins (5)
        }
        if (strlen($html)) {
            $output .= html_writer::tag('div', $html, array('class' => 'collections-group set'));
        }

        $awards = local_plugins_helper::get_frontpage_awards();
        $html = '';
        foreach ($awards as $award) {
            $html .= $this->award_listing($award, 5); //MIM TODO parametrize number of plugins (5)
        }
        if (strlen($html)) {
            $output .= html_writer::tag('div', $html, array('class' => 'collections-group award'));
        }

        $categories = local_plugins_helper::get_frontpage_categories();
        $html = '';
        foreach ($categories as $category) {
            $html .= $this->category_listing($category, 5); //MIM TODO parametrize number of plugins (5)
        }
        if (strlen($html)) {
            $output .= html_writer::tag('div', $html, array('class' => 'collections-group category'));
        }
        $output .= html_writer::end_tag('div'); // clearfix

        $categoriesheading = $this->output->heading(get_string('categories', 'local_plugins'), 2, 'main');
        $categories = local_plugins_helper::get_categories_tree();
        $output .= $this->categories_listing($categories->children, 'frontpage-categories', $categoriesheading);
//        $output .= $this->render(new local_plugins_recentplugins());
        $output .= $this->render(new local_plugins_recentplugins_new());
//        $output .= $this->render(new local_plugins_recentplugins_updated());
        return $output;
    }

    public function display_validation_messages(local_plugins_archive_validator $validator, $minlevel = local_plugins_archive_validator::ERROR_LEVEL_WARNING) {
        $output = '';
        $allmessages = array('errors' => $validator->errors_list);
        if ($minlevel <= local_plugins_archive_validator::ERROR_LEVEL_WARNING) {
            $allmessages['warnings'] = $validator->warnings_list;
        }
        if ($minlevel <= local_plugins_archive_validator::ERROR_LEVEL_INFO) {
            $allmessages['info'] = $validator->infomessages_list;
        }
        foreach ($allmessages as $level => $messages) {
            if (count($messages)) {
                $output .= html_writer::start_tag('div', array('class' => 'validationmessageslevel-'.$level));
                switch ($level) {
                case 'info':
                    $label = html_writer::span(get_string('validationinfolabel', 'local_plugins'), 'label');
                    break;
                case 'errors':
                    $label = html_writer::span(get_string('validationerrorslabel', 'local_plugins'), 'badge badge-danger');
                    break;
                case 'warnings':
                    $label = html_writer::span(get_string('validationwarningslabel', 'local_plugins'), 'badge badge-warning');
                    break;
                default:
                    $label = '';
                }
                $output .= html_writer::tag('div', get_string('validation'. $level, 'local_plugins'), array('class' => 'validationleveltitle'));
                $output .= html_writer::start_tag('ul');
                foreach ($messages as $message) {
                    $output .= html_writer::tag('li', $label.' '.$message);
                }
                $output .= html_writer::end_tag('ul');
                $output .= html_writer::end_tag('div'); // .validationmessages
            }
        }

        if (!empty($output)) {
            $output = html_writer::div($output, 'validationmessages');
        }

        return $output;
    }

    public function moodle_version_select() {
        $module = array('name'=>'local_plugins_moodleversion', 'fullpath'=>'/local/plugins/yui/moodleversion.js');
        $this->page->requires->js_init_call('M.local_plugins_moodleversion.init', array(array()), true, $module);
        $output = html_writer::start_tag('div', array('class' => 'moodleversionselect'));
        $output .= html_writer::start_tag('form', array('method' => 'POST', 'action' =>  new local_plugins_url('/local/plugins/index.php')));
        //$output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'redirect', 'value' => $PAGE->url));
        $output .= html_writer::tag('span', get_string('selectmoodleversion', 'local_plugins'), array('class' => 'fieldlabel mr-2'));
        $options = array();
        foreach (local_plugins_helper::get_moodle_versions() as $id => $mversion) {
            $options[$id] = $mversion->fullname;
        }
        $output .= html_writer::select($options, 'moodle_version', local_plugins_helper::get_user_moodle_version(), false, array('class' => 'local_plugins_moodleversion'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('go'), 'class' => 'local_plugins_moodleversionsubmit'));
        $output .= html_writer::end_tag('form');
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function plugin_category_select() {
        $module = array('name'=>'local_plugins_plugin_category', 'fullpath'=>'/local/plugins/yui/plugincategory.js');
        $this->page->requires->js_init_call('M.local_plugins_plugincategory.init', array(array()), true, $module);
        $output = html_writer::start_tag('div', array('class' => 'plugincategoryselect'));
        $output .= html_writer::start_tag('form', array('method' => 'GET', 'action' =>  new local_plugins_url('/local/plugins/stats.php')));
        $output .= html_writer::tag('span', get_string('selectplugincategory', 'local_plugins'), array('class' => 'fieldlabel')).' ';
        $options = array( 0 => get_string('allcategories', 'local_plugins'));
        foreach (local_plugins_helper::get_category_options() as $id => $plugincategory) {
            $options[$id] = $plugincategory;
        }
        $output .= html_writer::select($options, 'plugin_category', local_plugins_helper::get_user_plugin_category(), false, array('class' => 'local_plugins_plugin_category'));
        $output .= html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('go'), 'class' => 'local_plugins_plugincategorysubmit'));
        $output .= html_writer::end_tag('form');
        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Displays the checker results.
     *
     * Returns empty string if no results are available.
     *
     * @param local_plugins_checker_results $results
     * @return string
     */
    protected function render_local_plugins_checker_results(local_plugins_checker_results $results) {

        $results = $results->get_results();

        if (empty($results)) {
            return '';
        }

        $output = html_writer::start_div('checkerresults bg-light border p-2 mb-3');
        $output .= html_writer::div('<i class="fa fa-wrench" aria-hidden="true"></i> '.get_string('checkerpluginresults', 'local_plugins'), 'checkerresultstitle');

        $output .= html_writer::start_tag('ul');
        foreach ($results as $importance => $resultsgroup) {
            foreach ($resultsgroup as $name => $result) {
                $output .= html_writer::start_tag('li', array('class' => 'result-'.$name.' importance-'.$importance));
                $output .= $this->render($result);
                $output .= html_writer::end_tag('li');
            }
        }
        $output .= html_writer::end_tag('ul');
        $output .= html_writer::end_div();

        return $output;
    }

    /**
     * @param local_plugins_checker_result $result
     * @return string
     */
    protected function render_local_plugins_checker_result(local_plugins_checker_result $result) {

        switch ($result->importance) {
        case $result::IMPORTANCE_REQUIRED:
            $label = html_writer::span(get_string('checkerresultlabelrequired', 'local_plugins'), 'badge badge-danger');
            break;
        case $result::IMPORTANCE_RECOMMENDED:
            $label = html_writer::span(get_string('checkerresultlabelrecommended', 'local_plugins'), 'badge badge-warning');
            break;
        case $result::IMPORTANCE_SUGGESTED:
            $label = html_writer::span(get_string('checkerresultlabelsuggested', 'local_plugins'), 'badge badge-info');
            break;
        }

        $info = get_string('checkerresult_'.$result->name, 'local_plugins');

        if (get_string_manager()->string_exists('checkerresult_'.$result->name.'_help', 'local_plugins')) {
            $help = $this->output->help_icon('checkerresult_'.$result->name, 'local_plugins');
        } else {
            $help = '';
        }

        $output = $label.' '.$info.$help;

        return $output;
    }

    /**
     * Render the informative stats displayed at the front page
     *
     * @return string
     */
    public function frontpage_info_stats() {

        $statsman = new local_plugins_stats_manager();

        $plugins = local_plugins_helper::count_available_plugins();
        $plugins = html_writer::div($plugins, 'figure').html_writer::div('plugins', 'title');

        $contributors = local_plugins_helper::count_contributors();
        $contributors = html_writer::div($contributors, 'figure').html_writer::div('devs', 'title');

        $downloads = $statsman->get_stats_downloads_recent();
        // Round to thousands.
        $downloads = round($downloads / 1000, 1);
        $downloads = html_writer::div($downloads.'K', 'figure').html_writer::div('recent downloads', 'title');

        $output = html_writer::div(
                html_writer::div($plugins, 'item col-lg-4 p-1').
                html_writer::div($contributors, 'item col-lg-4 p-1').
                html_writer::div($downloads, 'item col-lg-4 p-1'),
            'row m-0 frontpage_info_stats text-muted text-center'
        );

        return $output;
    }

    /**
     * Render the Approval queue stats page
     *
     * @param local_plugins_queue_stats_manager $manager
     * @return string
     */
    public function queue_stats_page(local_plugins_queue_stats_manager $manager) {
        global $CFG;

        $out = '';
        $out .= $this->output->heading($this->page->title);

        $out .= $this->output->heading_with_help(get_string('queuestatsdistreview', 'local_plugins'),
            'queuestatsdistreview', 'local_plugins', '', '', 3);
        $out .= html_writer::start_div('stats');

        $reviewtimes = $manager->get_review_times_data();

        if (empty($reviewtimes->distribution)) {
            $out .= html_writer::div(get_string('queuestatsnodata', 'local_plugins'));

        } else {
            $labels = [];
            $data = [];

            foreach ($reviewtimes->distribution as $ndays => $nplugins) {
                $labels[] = ($ndays == 1 ? get_string('numday', 'core', 1) : get_string('numdays', 'core', $ndays));
                $data[] = $nplugins;
            }

            $serie = new core\chart_series(get_string('plugincount', 'local_plugins'), $data);

            $chart = new core\chart_bar();
            $chart->add_series($serie);
            $chart->set_labels($labels);
            $chart->get_xaxis(0, true)->set_label(get_string('queuestatschartaxisx', 'local_plugins'));
            $chart->get_yaxis(0, true)->set_label(get_string('queuestatschartaxisy', 'local_plugins'));

            $out .= html_writer::div(get_string('queuestatschartinfo', 'local_plugins', array(
                'median' => $reviewtimes->mediandays, 'sample' => $reviewtimes->totalplugins)), 'queuestatschartinfo');

            $CFG->chart_colorset = ['#f5892b'];
            $out .= $this->output->render($chart);
            $CFG->chart_colorset = null;
        }

        $out .= html_writer::end_div('stats');

        return $out;
    }
}

/**
 * Renderer for pages in local_plugins plugin that are related to one particular plugin
 *
 * @property-read core_renderer $output
 */
class local_plugins_plugin_renderer extends local_plugins_renderer {
    protected $_plugin;

    /**
     * Constructor method, calls the parent constructor,
     * also adds the CSS class to the body
     * @param moodle_page $page
     * @param string $target one of rendering target constants
     */
    public function __construct(moodle_page $page, $target) {
        parent::__construct($page, $target);
        $this->page->add_body_class('local-plugins-oneplugin');
    }

    public function set_plugin($plugin) {
        $this->_plugin = $plugin;
    }

    /**
     * @param string|null heading page heading to display
     * @param bool $showtoolsblock show moodle version selector and the search bar
     * @param array $options additional options
     * @return string
     */
    public function header($heading = null, $showtoolsblock = false, array $options = array()) {
        $this->page->requires->css('/local/plugins/lightbox.min.css');
        $this->page->requires->js_call_amd('local_plugins/lightbox-wrapper', 'init');
        $output = parent::header(null, $showtoolsblock, $options);
        $output .= $this->plugin_container_start($this->_plugin, $heading, $options);
        return $output;
    }

    public function footer() {
        $output = $this->plugin_container_end();
        $output .= parent::footer();
        return $output;
    }

    public function plugin_heading(local_plugins_plugin $plugin, array $options = array()) {
        global $USER;

        $download = '';
        if (!empty($options['showgetbuttons'])) {
            $mostrecentversion = $plugin->mostrecentversion;
            if ($mostrecentversion) {
                $download = html_writer::start_tag('div', array('class' => 'plugin-download-latest text-center mb-4'));
                $download .= html_writer::tag('div', html_writer::link($mostrecentversion->viewlink,
                    get_string('learnmoreabout', 'local_plugins', $mostrecentversion->formatted_releasename)), array('class' => 'my-1 learnmore'));
                $download .= html_writer::div($this->plugin_version_for_moodle($mostrecentversion, 'span'), 'my-1');
                $download .= html_writer::div($this->plugin_version_download_button($mostrecentversion), 'my-2');
                $download .= html_writer::end_tag('div');
            } else {
                $countlatestversions = count($plugin->latestversions);
                if ($countlatestversions) {
                    $download = html_writer::start_tag('div', array('class' => 'text-center mb-4'));
                    $download .= html_writer::div(get_string('currentversionsnum', 'local_plugins', $countlatestversions), 'my-1');
                    $download .= html_writer::div(local_plugins_helper::get_download_button($plugin->viewversionslink,
                        'btn btn-success'), 'text-center');
                    $download .= html_writer::end_tag('div');
                }
            }
        }

        $precheck = '';
        if (!empty($options['showprecheck'])) {
            $mostrecentversion = $plugin->mostrecentversion;
            if ($mostrecentversion) {
                $smurfresult = $mostrecentversion->smurfresult;
                if ($smurfresult) {
                    $precheck = html_writer::start_tag('div', array('class' => 'plugin-precheck-badges text-center'));
                    $precheck .= html_writer::link($mostrecentversion->viewlink, $this->smurf_summary($smurfresult));
                    $precheck .= html_writer::end_tag('div');
                }
            }
        }

        $output  = html_writer::start_tag('div', array('class' => 'plugin-heading'));
        $output .= html_writer::start_div('row');

        if ($download || $precheck) {
            $output .= html_writer::start_div('col-md-8');
        } else {
            $output .= html_writer::start_div('col-md-12');
        }

        $output .= html_writer::start_div('media mb-3');
        if (!empty($plugin->ownlogo)) {
            $output .= html_writer::div(
                html_writer::empty_tag(
                    'img',
                    array('src' => new moodle_url($plugin->ownlogo, array('preview' => 'thumb')), 'alt' => 'Logo')
                ),
                'plugin-logo mr-2 border p-1 rounded'
            );
        }

        $output .= html_writer::start_div('media-body');
        $output .= $this->output->heading($plugin->formatted_name, 1, 'title');
        $output .= '<div class="small text-muted"> ' . $plugin->category->formatted_name;

        if (!empty($plugin->frankenstyle)) {
            $output .= ' ::: ' . $plugin->frankenstyle;
        }

        $output .= '</div>';
        $output .= $this->plugin_status($plugin);
        $output .= html_writer::end_div(); // media-body
        $output .= html_writer::end_div(); // media

        $maintainedby = $plugin->get_maintained_by(true);
        $maintainers = array();
        foreach ($maintainedby as $contributor) {
            $picture = $this->output->user_picture($contributor->user, array('size' => 18,
                'link' => $contributor->can_view_profile(), 'class' => 'mx-2'));

            if ($contributor->can_view_profile()) {
                $maintainers[] = '<span style="white-space:nowrap">'.$picture.html_writer::link(
                    new local_plugins_url('/user/profile.php', array('id' => $contributor->userid)), $contributor->username,
                    array('class' => 'name')).'</span>';

            } else {
                $maintainers[] = '<span style="white-space:nowrap">'.$picture. html_writer::tag('span', $contributor->username,
                    array('class' => 'name')).'</span>';
            }
        }
        if (!empty($maintainers)) {
            $output .= html_writer::tag('div', get_string('maintainedby', 'local_plugins', join(', ', $maintainers)), array('class' => 'maintainedby mb-4 d-flex align-items-center flex-wrap'));
        }

        $output .= html_writer::tag('div', $plugin->formatted_shortdescription, array('class' => 'shortdescription mb-3'));

        // Area for info labels and tags
        if (!empty($options['showlabels'])) {
            $output .= html_writer::start_div('infolabels d-flex align-items-center mb-3');
            $output .= $this->plugin_label_timelastreleased($plugin);
            $output .= $this->plugin_label_usage_sites($plugin);
            $output .= $this->plugin_label_downloads($plugin);
            $output .= $this->plugin_label_favourites($plugin);
            $output .= html_writer::end_div();
        }

        $output .= html_writer::end_div(); // left .span

        if ($download || $precheck) {
            $output .= html_writer::start_div('col-md-4');
            $output .= $download;
            $output .= $precheck;
            $output .= html_writer::end_div(); // right .span
        }

        $output .= html_writer::end_div(); // .row

        if ($plugin->can_view_plugin_checker_results()) {
            $checker = $this->render(local_plugins_plugin_checker::run($plugin));
            $output .= html_writer::div($checker);
        }

        $output .= html_writer::end_div(); // .plugin-heading

        return $output;
    }

    /**
     * @param local_plugins_plugin $plugin
     * @return string
     */
    protected function plugin_label_timelastreleased(local_plugins_plugin $plugin): string {

        $latestrelease = \local_plugins\human_time_diff::for($plugin->timelastreleased ?? $plugin->timecreated);

        return html_writer::div(
            html_writer::span(
                '<i class="fa fa-rocket" aria-hidden="true"></i> ' .
                    get_string('timelastreleaseddate', 'local_plugins', $latestrelease),
                'stats-timelastreleased'
            ), 'timelastreleased mr-3');
    }

    /**
     * @param local_plugins_plugin $plugin
     * @return string
     */
    protected function plugin_label_downloads(local_plugins_plugin $plugin) {

        $total = $plugin->aggdownloads;

        if (empty($total)) {
            return '';
        }

        $title = get_string('downloadsrecent', 'local_plugins', $total);

        if ($total > 999) {
            $total = floor($total / 1000) . 'k';
        }

        $count = html_writer::span('<i class="fa fa-download" aria-hidden="true"></i> '.$total.' downloads', 'stats-downloads',
            array('title' => $title)).' ';

        $output = html_writer::div($count, 'downloads mr-3');

        return $output;
    }

    /**
     * @param local_plugins_plugin $plugin
     * @return string
     */
    protected function plugin_label_usage_sites(local_plugins_plugin $plugin) {

        $total = $plugin->aggsites;

        if (empty($total)) {
            return '';
        }

        $title = get_string('usageinfo', 'local_plugins', $total);

        $count = html_writer::span('<i class="fa fa-map-marker" aria-hidden="true"></i> '.$total.' sites', 'stats-sites',
            array('title' => $title)).' ';

        $output = html_writer::div($count, 'usage mr-3');

        return $output;
    }

    /**
     * @param local_plugins_plugin $plugin
     * @return string
     */
    protected function plugin_label_favourites(local_plugins_plugin $plugin) {
        global $USER;

        $addurlajax = new local_plugins_url('/local/plugins/ajax/setfavourite.php', array('id' => $plugin->id, 'status' => 1, 'sesskey' => sesskey()));
        $this->page->requires->yui_module('moodle-local_plugins-favourites', 'M.local_plugins.favourites.init', array(array(
            'selectors' => array(
                'container' => '.plugin-heading .infolabels .favourites',
                'addlink' => '.plugin-heading .infolabels .favourites a.favouritesadd',
            ),
            'urls' => array(
                'add' => $addurlajax->out(false),
            ),
            'templates' => array(
                'added' => html_writer::span('<i class="fa fa-heart" aria-hidden="true"></i> {{count}} fans', 'stats-fans',
                    array('title' => get_string('favouritesinfo', 'local_plugins', '{{count}}'))).' ',
                ),
            ),
        ));

        $favourites = $plugin->get_favourites();
        $count = '';
        $link = '';
        $output = '';

        if (!empty($favourites)) {
            $count = count($favourites);
            $count = html_writer::span('<i class="fa fa-heart" aria-hidden="true"></i> '.$count.' fans', 'stats-fans',
                array('title' => get_string('favouritesinfo', 'local_plugins', $count))).' ';
        }

        if (!isset($favourites[$USER->id])) {
            if (has_capability(local_plugins::CAP_MARKFAVOURITE, context_system::instance())) {
                $text = get_string('favouritesadd', 'local_plugins');
                $link = html_writer::link(
                    new local_plugins_url('/local/plugins/setfavourite.php', array('id' => $plugin->id, 'status' => 1, 'sesskey' => sesskey())),
                    $text,
                    array('class' => 'btn btn-default btn-sm favouritesadd')
                );
            }
        }

        $output = html_writer::div($count, 'favourites mr-3');
        $output .= $link;

        return $output;
    }

    /**
     * Renders the smurf summary result as build status badge
     *
     * @param string $smurfresult
     * @return string
     */
    protected function smurf_summary($smurfresult) {

        $main = explode(':', $smurfresult);

        if (count($main) != 2) {
            return '';
        }

        $out = '';

        $data = explode(',', $main[0]);
        if (count($data) != 4) {
            return '';
        }
        $out .= '<span class="precheck-badge precheck-badge-'.$data[1].'">'."\n";
        $out .= '<span class="precheck-badge-label">code&nbsp;prechecks&nbsp;</span>';
        if ($data[1] === 'success') {
            $out .= '<span class="precheck-badge-info"><i class="fa fa-check" aria-hidden="true"></i></span>';
        } else {
            $out .= '<span class="precheck-badge-info">'.$data[2].'&nbsp;|&nbsp;'.$data[3].'</span>';
        }
        $out .= '</span>'."\n";

        return $out;
    }

    /**
     * Renders the smurf results as build status badges
     *
     * @param local_plugins_version $versionid
     * @param string $smurfresult
     * @return string
     */
    protected function smurf_results(local_plugins_version $version, $smurfresult) {

        $main = explode(':', $smurfresult);

        if (count($main) != 2) {
            return '';
        }

        $out = '';

        foreach (explode(';', $main[1]) as $part) {
            $data = explode(',', $part);
            if (count($data) != 4) {
                continue;
            }
            $badge = '<span title="errors: '.$data[2].', warnings: '.$data[3].'" class="precheck-badge precheck-badge-'.$data[1].'">'."\n";
            $badge .= '<span class="precheck-badge-label">'.$data[0].'</span>';
            if ($data[1] === 'success') {
                $badge .= '<span class="precheck-badge-info"><i class="fa fa-check" aria-hidden="true"></i></span>';
            } else {
                $badge .= '<span class="precheck-badge-info">'.$data[2].'&nbsp;|&nbsp;'.$data[3].'</span>';
            }
            $badge .= '</span>'."\n";

            $out .= html_writer::link(new local_plugins_url($version->viewlink, ['smurf' => 'html'], $data[0]),
                $badge, ['target' => '_blank']);
        }

        return $out;
    }

    public function plugin_screenshots(array $screenshots) {
        if (count($screenshots) === 0) {
            return '';
        }
        $output = '';
        $output .= html_writer::start_tag('div', array('class' => 'infoblock border p-3 rounded mb-3 screenshots'));
        $output .= $this->output->heading(get_string('screenshots', 'local_plugins'), 3);
        $output .= html_writer::start_tag('div', array('class' => 'screenshots-list d-flex'));
        foreach ($screenshots as $i => $screenshot) {
            $img = html_writer::empty_tag('img', array('class' => 'img-responsive', 'alt' => 'Screenshot #'.$i,
                'src' => new moodle_url($screenshot->src, array('preview' => 'bigthumb'))));
            $output .= html_writer::tag('div', html_writer::link($screenshot->src, $img,
                array('data-lightbox' => 'plugin-screenshots')), array('class' => 'screenshot card bg-light border rounded p-2 mr-2'));
        }
        $output .= html_writer::end_tag('div'); // .screenshots-list
        $output .= html_writer::end_tag('div'); // .infoblock.screenshots
        return $output;
    }

    /**
     * Renders an array of contributors displaying the username, the users
     * profile picture, links (if applicable) and flags the maintainers.
     *
     * @param array $contributors
     * @param local_plugins_plugin $plugin
     * @return string
     */
    public function plugin_contributors(array $contributors, local_plugins_plugin $plugin = null) {
        $output  = html_writer::start_tag('div', array('class' => 'infoblock border p-3 rounded mb-3 contributors'));
        $output .= $this->output->heading(get_string('contributors', 'local_plugins'), 3);
        $output .= html_writer::start_tag('div', array('class' => 'contributors-list'));

        $contributorsoutput = array();
        foreach ($contributors as $contributor) {
            $canview = $contributor->can_view_profile();

            $output .= html_writer::start_tag('div', array('class' => 'contributors-list-item media mb-3'));
            $output .= html_writer::tag('div', $this->output->user_picture($contributor->user, array('link' => $canview)), array('class' => 'user-picture mr-1'));
            $output .= html_writer::start_tag('div', array('class' => 'media-body'));
            $output .= html_writer::start_tag('div', array('class' => 'user-fullname'));
            if ($canview) {
                $output .= html_writer::link(new local_plugins_url('/user/profile.php', array('id' => $contributor->userid)), $contributor->username, array('class' => 'name'));
            } else {
                $output .= html_writer::tag('span', $contributor->username, array('class' => 'name'));
            }
            if ($contributor->is_lead_maintainer()) {
                $output .= ' '.html_writer::tag('span', get_string('leadmaintainer_postfix', 'local_plugins'), array('class' => 'isleadmaintainer'));
            }
            if (!empty($contributor->formatted_type)) {
                $output .= ': '.html_writer::tag('span', $contributor->formatted_type, array('class' => 'contributortype'));
            }
            if ($plugin->can_manage_contributors()) {
                $output .= ' '.$this->output->action_icon(
                    $contributor->editurl,
                    new pix_icon('t/edit', get_string('edit', 'local_plugins')),
                    null,
                    array('class' => 'editcontributor')
                );
            }
            $output .= html_writer::end_tag('div'); // .user-fullname

            $links = array(html_writer::link($contributor->browseurl, get_string('viewcontributorscontribution', 'local_plugins')));
            if ($contributor->is_maintainer() && $contributor->can_send_message() && !$contributor->is_current_user()) {
                $links[] = html_writer::link(new local_plugins_url('/message/index.php', array('id' => $contributor->userid)), get_string('messageselectadd'));
            }

            $output .= html_writer::tag('div', join(' | ', $links), array('class' => 'user-links small'));
            $output .= html_writer::end_tag('div'); // .media-body
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div');

        if (!isloggedin() || isguestuser()) {
            $output .= html_writer::tag('div', get_string('logintocontact', 'local_plugins'), array('class' => 'logintocontact'));
        }
        if (!empty($plugin) && $plugin->can_add_contributors()) {
            $output .= html_writer::start_tag('div', array('class' => 'addto mt-3'));
            $output .= html_writer::link($plugin->addcontributorlink, get_string('addcontributor', 'local_plugins'), array('class' => 'btn btn-default btn-sm'));
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::end_tag('div'); // .infoblock.contributors
        return $output;
    }

    public function plugin_container_start(local_plugins_plugin $plugin, $heading = null, array $options = array()) {
        $pluginnode = $this->page->navigation->find('plugin-'.$plugin->id, navigation_node::TYPE_CONTAINER);
        $output = html_writer::start_tag('div', array('class'=>'clearfix plugin-wrapper'));
        $output .= $this->plugin_heading($plugin, $options);
        $output .= html_writer::start_tag('ul', array('class'=>'plugin-tabs nav nav-pills p-1 border rounded bg-light mb-3'));
        foreach ($pluginnode->children as $page) {
            $classes = array('nav-link');
            if ($page->isactive) {
                $classes[] = 'active';
            }
            switch ($page->key) {
                case 'description':
                    $icon = '<i class="fa fa-file-text-o" aria-hidden="true"></i> ';
                    break;
                case 'versions':
                    $icon = '<i class="fa fa-tags" aria-hidden="true"></i> ';
                    break;
                case 'reviews':
                    $icon = '<i class="fa fa-balance-scale" aria-hidden="true"></i> ';
                    break;
                case 'stats':
                    $icon = '<i class="fa fa-bar-chart" aria-hidden="true"></i> ';
                    break;
                case 'translations':
                    $icon = '<i class="fa fa-globe" aria-hidden="true"></i> ';
                    break;
                case 'devzone':
                    $icon = '<i class="fa fa-code" aria-hidden="true"></i> ';
                    break;
                default:
                    $icon = '';
            }
            $output .= html_writer::start_tag('li', array('class' => 'nav-item'));
            $output .= html_writer::link($page->action, $icon.$page->get_content(false), array('class' => join(' ', $classes)));
            $output .= html_writer::end_tag('li'); // page
        }
        $output .= html_writer::end_tag('ul'); // plugin-tabs

        $output .= html_writer::start_tag('div', array('class' => 'plugin-page'));

        if (!empty($heading)) {
            $output .= $this->output->heading($heading, 2);
        }
        return $output;
    }

    public function plugin_container_end() {
        $output  = html_writer::end_tag('div'); // .plugin-page
        $output .= html_writer::end_tag('div'); // .plugin-wrapper
        return $output;
    }

    protected function versions_listing_div($heading, $class, array $versions) {
        if (!sizeof($versions)) {
            return '';
        }
        $output  = html_writer::start_tag('div', array('class' => $class));
        $output .= $this->output->heading($heading, 3);
        $output .= html_writer::start_tag('div', array('class' => 'versions-items'));
        $count = 0;
        foreach ($versions as $version) {
            $count++;
            $output .= $this->plugin_version_listitem($version, $count);
        }
        $output .= html_writer::end_tag('div'); // .versions-items
        $output .= html_writer::end_tag('div'); // $class
        return $output;
    }

    /**
     * Display a list of plugin versions as a dropdown widget.
     *
     * @param string $heading
     * @param string $class
     * @param array $versions
     * @return string
     */
    protected function versions_listing_dropdown($heading, $class, array $versions) {

        if (empty($versions)) {
            return '';
        }

        $links = [];
        $unavail = [];

        foreach ($versions as $version) {
            if (!$version->can_view()) {
                continue;
            }
            if ($version->is_available()) {
                $links[$version->viewlink->out_as_local_url()] = $version->formatted_releasename_and_moodle_version;
            } else {
                $unavail[$version->viewlink->out_as_local_url()] = $version->formatted_releasename_and_moodle_version;
            }
        }

        if ($unavail) {
            $links['--unavailable'][get_string('availablenot', 'local_plugins')] = $unavail;
        }

        if (empty($links)) {
            return '';
        }

        $out = html_writer::start_tag('div', array('class' => $class));
        $out .= $this->output->heading($heading, 3);

        $out .= $this->output->url_select($links, '');
        $out .= html_writer::end_tag('div'); // $class

        return $out;
    }

    public function current_versions(array $versions) {
        if (count($versions) > 1) {
            $heading = get_string('currentversions', 'local_plugins');
        } else {
            $heading = get_string('currentversion', 'local_plugins');
        }
        return $this->versions_listing_div($heading, 'versions-list current', $versions);
    }

    public function previous_versions(array $versions) {
    	if (count($versions) > 1) {
            $heading = get_string('previousversions', 'local_plugins');
        } else {
            $heading = get_string('previousversion', 'local_plugins');
        }
        return $this->versions_listing_dropdown($heading, 'versions-list previous', $versions);
    }

    public function current_unavailable_versions(array $versions) {
        if (count($versions) > 1) {
            $heading = get_string('currentunavailableversions', 'local_plugins');
        } else {
            $heading = get_string('currentunavailableversion', 'local_plugins');
        }
        return $this->versions_listing_div($heading, 'versions-list unavailable', $versions);
    }

    public function plugin_version_listitem(local_plugins_version $version, $count = 0) {

        $classes = array('versions-item', 'border', 'p-3', 'mb-2', 'rounded', 'clearfix');
        $classes[] = ($count%2)?'odd':'even';
        if (!$version->is_available()) {
        	$classes[] = 'unavailable';
        }

        $output  = html_writer::start_tag('div', array('class' => join(' ', $classes), 'id' => 'version-'.$version->id));

        if (!$version->can_view()) {
            $output .= $this->plugin_version_information_name_only($version);

        } else {
            $output .= $this->plugin_version_information($version);

            if (optional_param('validation', 0, PARAM_INT) == 1 && $version->plugin->can_viewvalidation() && $version->is_latest_version()) {
                $output .= html_writer::start_div('row');
                $output .= html_writer::start_div('validationresults col-md-12');
                $validator = local_plugins_archive_validator::create_from_version($version);
                $output .= $this->display_validation_messages($validator, $validator::ERROR_LEVEL_NONE);
                $output .= html_writer::end_div();
                $output .= html_writer::end_div();
            }
        }

        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function plugin_version_information_name_only(local_plugins_version $version) {
        // if version is unapproved or invisible but it has other versions linked to it
        // we don't specify why we don't show it, just say it's not available
        $output  = html_writer::start_div('row');
        $output .= html_writer::start_tag('div', array('class' => 'details col-md-12'));

        $output .= html_writer::start_tag('div', array('class' => 'heading'));
        $output .= $this->output->heading($version->formatted_fullname ,4);
        $output .= $this->plugin_version_for_moodle($version, 'span');
        $output .= html_writer::end_tag('div'); // .heading

        $output .= html_writer::start_tag('div', array('class' => 'version-status'));
        $output .= html_writer::tag('span', get_string('notavailable', 'local_plugins'), array('class' => 'unavailable badge badge-info'));
        $output .= html_writer::end_tag('div'); // version-status

        $output .= html_writer::end_tag('div'); // .details
        $output .= html_writer::end_div(); // .row

        return $output;
    }

    public function plugin_version_information(local_plugins_version $version) {

        $output  = html_writer::start_div('row');

        // Details
        $output .= html_writer::start_tag('div', array('class' => 'details col-md-7'));

        // version name
        $output .= html_writer::start_tag('div', array('class' => 'heading'));
        $output .= $this->output->heading($version->formatted_fullname ,4);
        $output .= $this->plugin_version_for_moodle($version, 'span');
        $output .= html_writer::end_tag('div'); // .heading

        // released
        $output .= html_writer::start_tag('div', array('class' => 'created'));
        $output .= html_writer::tag('small', get_string('timecreateddate', 'local_plugins', $version->formatted_timecreated),
            array('class' => 'text-muted'));
        $output .= html_writer::end_tag('div'); // .created

        // Code prechecks.
        $smurfresult = $version->smurfresult;
        if ($smurfresult) {
            $output .= html_writer::start_tag('div', array('class' => 'plugin-precheck-badges'));
            $output .= html_writer::link($version->viewlink, $this->smurf_summary($smurfresult));
            $output .= html_writer::end_tag('div');
        }

        // status
        $output .= $this->plugin_version_status($version);

        $output .= html_writer::end_tag('div'); // .details

        // Actions
        $output .= html_writer::start_tag('div', array('class' => 'actions col-md-5'));

        // download
        $output .= html_writer::tag('div', $this->plugin_version_download_button($version), array('class' => 'downloadcell'));

        // links
        $output .= html_writer::start_tag('div', array('class' => 'version-actions mt-2'));
        $actionview = '';
        foreach ( $version->actions_list('download') as $class => $action) {
            if ($class === 'view') {
                $actionview = html_writer::div(html_writer::link($action[0], $action[1], array('class' => 'action-'.$class)), 'mt-1');
            } else {
                $output .= html_writer::link($action[0], $action[1], array('class' => 'btn btn-default btn-sm mb-1 mr-1 action-'.$class));
            }
        }
        $output .= $actionview;

        $output .= html_writer::end_tag('div'); // .links

        $output .= html_writer::end_tag('div'); // .actions

        // Average review grades
        //$gradeshtml = $this->average_review_grades($version->average_review_grades, $version->reviewlink);
        //$output .= html_writer::tag('td', $gradeshtml, array('class' => 'version-average-grades'));

        $output .= html_writer::end_div(); // .row

        return $output;
    }

    public function plugin_faqs(local_plugins_plugin $plugin, $canedit = false) {
        //TODO this function is not used at the moment
        $output = html_writer::start_tag('div', array('class' => 'plugin-faqs'));
        if (empty($plugin->faqs)) {
            $output .= $this->output->notification(get_string('nofaqs', 'local_plugins'));
            if ($canedit) {
                $output .= html_writer::start_tag('div', array('class' => 'add-faqs'));
                $output .= $this->output->single_button($plugin->editfaqslink, get_string('addfaqs', 'local_plugins'));
                $output .= html_writer::end_tag('div');
            }
        } else {
            if ($canedit && !empty($plugin->editfaqslink)) {
                $output .= html_writer::start_tag('div', array('class' => 'edit-faqs above'));
                $output .= $this->output->single_button($plugin->editfaqslink, get_string('editfaqs', 'local_plugins'));
                $output .= html_writer::end_tag('div');
            }

            $output .= html_writer::start_tag('div', array('class' => 'faqs-wrap'));
            $output .= $plugin->formatted_faqs;
            $output .= html_writer::end_tag('div');

            if ($canedit && !empty($plugin->editfaqslink)) {
                $output .= html_writer::start_tag('div', array('class' => 'edit-faqs below'));
                $output .= $this->output->single_button($plugin->editfaqslink, get_string('editfaqs', 'local_plugins'));
                $output .= html_writer::end_tag('div');
            }
        }
        $output .= html_writer::end_tag('div');
        return $output;
    }

    public function reviews(local_plugins_plugin $plugin, $versionid = 0, $reviewid = 0) {
        $versions = array_merge($plugin->unavailablelatestversions, $plugin->latestversions, $plugin->previousversions);
        $html = '';
        foreach ($versions as $version) {
            $reviews = $version->reviews;
            if (empty($reviews)) {
                continue;
            }
            $output = '';
            $links = array();
            if ($reviewid) {
                if (array_key_exists($reviewid, $reviews)) {
                    $output .= html_writer::start_tag('div', array('class' => 'review-list'));
                    // Display full text of this review.
                    $output .= $this->review_list_item($reviews[$reviewid], true);
                    $output .= html_writer::end_tag('div'); // .review-list
                    if (count($reviews) > 1) {
                        $links[] = html_writer::link(
                            $version->reviewlink,
                            get_string('viewallreviews', 'local_plugins', count($reviews)),
                            array('class' => 'viewallreviews btn btn-default btn-sm')
                        );
                    }
                } else if (count($reviews)) {
                    $links[] = html_writer::link(
                        $version->reviewlink,
                        get_string('viewreviews', 'local_plugins', count($reviews)),
                        array('class' => 'reviewslink btn btn-default btn-sm')
                    );
                }

            } else {
                if (!$versionid || $versionid == $version->id) {
                    $output .= html_writer::start_tag('div', array('class' => 'review-list'));
                    foreach ($reviews as $review) {
                        // Display preview of the review.
                        $output .=  $this->review_list_item($review);
                    }
                    $output .= html_writer::end_tag('div'); // .review-list
                } else {
                    $links[] = html_writer::link(
                        $version->reviewlink,
                        get_string('viewreviews', 'local_plugins', count($reviews)),
                        array('class' => 'reviewslink btn btn-default btn-sm')
                    );
                }
            }

            if (!empty($output) || !empty($links)) {
                $html .= html_writer::start_tag('div', array('class' => 'version-reviews'));
                $html .= html_writer::start_tag('div', array('class' => 'version-information'));
                if ($version->can_view()) {
                    $html .= html_writer::tag('h3', $version->plugin->formatted_name.' '.html_writer::link($version->viewlink,
                        $version->get_formatted_releasename_and_moodle_version(), array('class' => 'version-name')));
                } else {
                    $html .= html_writer::tag('h3',
                        $version->plugin->formatted_name.' '.$version->get_formatted_releasename_and_moodle_version());
                    $html .= html_writer::tag('p', get_string('versionnotavailable', 'local_plugins'));
                }
                if (!empty($links)) {
                    $html .= html_writer::start_tag('div', array('class' => 'links'));
                    $html .= join(' ', $links);
                    $html .= html_writer::end_tag('div'); // .links
                }
                $html .= html_writer::end_tag('div'); // .version-information
                $html .= $output;
                $html .= html_writer::end_tag('div'); // .version-reviews
            }
        }

        if (empty($html)) {
            $html = html_writer::tag('h3', get_string('nopluginreviews', 'local_plugins'));
        }

        return $html;
    }

    /**
     * Render given plugin's downloads stats
     *
     * @param local_plugins_plugin $plugin
     * @return string
     */
    public function stats(local_plugins_plugin $plugin) {
        global $CFG;

        $output = html_writer::start_tag('div', array('class' => 'stats'));

        // Display the plugin usage stats.
        $output .= html_writer::tag('h2', get_string('usagestats', 'local_plugins'));

        if (empty($plugin->aggsites)) {
            $output .= $this->output->box(get_string('usagenostats', 'local_plugins'));

        } else {
            $usageman = new local_plugins_usage_manager();

            $labels = [];
            $data = [];
            $byver = [];

            $stats = $usageman->get_stats_monthly($plugin->id);

            foreach ($stats as $year => $months) {
                foreach ($months as $month => $sites) {
                    $labels[] = date('M', mktime(0, 0, 0, $month, 1, $year))." ".$year;
                    $data[] = $sites['total'];

                    if (count($sites) > 1) {
                        // We have data by version available, too.
                        $byver = $sites;
                    }
                }
            }

            $serie = new core\chart_series(get_string('usagemonthlyserie', 'local_plugins'), $data);

            $chart = new core\chart_bar();
            $chart->add_series($serie);
            $chart->set_labels($labels);

            $output .= html_writer::tag('h3', get_string('usagemonthlychart', 'local_plugins', $plugin->aggsites));

            $CFG->chart_colorset = ['#f5892b'];
            $output .= $this->output->render($chart);
            $CFG->chart_colorset = null;

            if (!empty($byver)) {
                $labels = [];
                $data = [];

                foreach ($byver as $moodlever => $sites) {
                    if ($moodlever === 'total') {
                        continue;
                    }

                    $labels[] = 'Moodle '.$moodlever;
                    $data[] = $sites;
                }

                $serie = new core\chart_series(get_string('usagebyverserie', 'local_plugins'), $data);

                $chart = new core\chart_pie();
                $chart->set_doughnut(true);
                $chart->add_series($serie);
                $chart->set_labels($labels);

                $output .= html_writer::tag('h3', get_string('usagebyverchart', 'local_plugins'));
                $output .= $this->output->render($chart);
            }
        }

        // Display the plugin downloads stats.
        $output .= html_writer::tag('h2', get_string('downloadstats', 'local_plugins'));

        if (empty($plugin->aggdownloads)) {
            $output .= $this->output->box(get_string('downloadnostats', 'local_plugins'));

        } else {
            $statsman = new local_plugins_stats_manager();
            $timefrom = mktime(0, 0, 0, date('n') - 12, 1);
            $timeto = mktime(0, 0, 0, date('n') - 1, 1);

            // Recent downloads of the plugin.
            $recent = $statsman->get_stats_plugin_recent($plugin->id);
            $output .= html_writer::tag('h3', get_string('downloadsrecent', 'local_plugins', $recent));

            // Downloads by month (last 12 months)
            $labels = [];
            $data = [];

            $stats = $statsman->get_stats_plugin_monthly($plugin->id, $timefrom, $timeto);

            foreach ($stats as $year => $months) {
                foreach ($months as $month => $downloads) {
                    $labels[] = date('M', mktime(0, 0, 0, $month, 1, $year)).' '.$year;
                    $data[] = $downloads;
                }
            }

            $serie = new core\chart_series(get_string('downloads', 'local_plugins'), $data);

            $chart = new core\chart_bar();
            $chart->add_series($serie);
            $chart->set_labels($labels);

            $output .= html_writer::tag('h3', get_string('downloadmonth', 'local_plugins'));

            $CFG->chart_colorset = ['#f5892b'];
            $output .= $this->output->render($chart);
            $CFG->chart_colorset = null;

            // Latest (current) versions downloads by month (last 12 months)
            $labels = [];
            $seriesnames = [];
            $seriesdata = [];

            $stats = $statsman->get_stats_plugin_by_version_monthly($plugin->id, $timefrom, $timeto);

            // If there would be too many versions, display just the current ones (e.g. theme_essential).
            $latestversions = $plugin->latestversions;

            foreach ($stats as $year => $months) {
                foreach ($months as $month => $versions) {
                    $labels[] = date('M', mktime(0, 0, 0, $month, 1, $year)).' '.$year;
                    foreach ($versions as $versionid => $info) {
                        if (count($versions) < 11 || isset($latestversions[$info->id]) || $info->timereleased > time() - YEARSECS) {
                            $seriesnames[$versionid] = $info->name;
                            $seriesdata[$versionid][] = $info->downloads;
                        }
                    }
                }
            }

            $chart = new core\chart_line();
            foreach ($seriesnames as $versionid => $name) {
                $chart->add_series(new core\chart_series($name, array_values($seriesdata[$versionid])));
            }
            $chart->set_labels($labels);

            $output .= html_writer::tag('h3', get_string('downloadmonthver', 'local_plugins'));
            $output .= $this->output->render($chart);
        }

        $output .= html_writer::end_tag('div');
        return $output;
    }

    /**
     * Render a link to when the user can contribute strings for the given plugin.
     *
     * @param local_plugins_plugin $plugin
     * @param local_plugins_translation_stats_manager $statsman
     * @return string
     */
    public function translation_contribute(local_plugins_plugin $plugin, local_plugins_translation_stats_manager $statsman) {

        // Find out the latest version of the plugin so that we know the branch number.
        $vers = $plugin->get_moodle_versions();
        $maxversion = new stdClass();
        $maxversion->version = 0;
        foreach ($vers as $id => $mversion) {
            if ($mversion->version > $maxversion->version) {
                $maxversion = $mversion;
            }
        }

        // Construct the URL to the AMOS translator page.
        $amosparams = [];
        $amosparams['v'] = str_replace('.', '', $maxversion->releasename). '00';
        $amosparams['t'] = time();
        $amosparams['l'] = current_language();
        $langs = get_string_manager()->get_list_of_translations();
        $langname = $langs[current_language()];

        if ($amosparams['l'] === 'en') {
            $amosparams['l'] = '';
            $langname = get_string('translationscontributeotherlangs', 'local_plugins');
        }

        if (substr($plugin->frankenstyle, 0, 4) === 'mod_') {
            $amosparams['c'] = substr($plugin->frankenstyle, strpos($plugin->frankenstyle, '_') + 1);

        } else {
            $amosparams['c'] = $plugin->frankenstyle;
        }

        $amosurl = new moodle_url('https://lang.moodle.org/local/amos/view.php', $amosparams);

        $out = '';
        $out .= '<p>'.get_string('translationsnumofstrings', 'local_plugins',
            '<strong>'.$statsman->total_strings().'</strong>').'</p>';
        $out .= '<p>';
        $out .= html_writer::link($amosurl, get_string('translationscontribute', 'local_plugins', $langname), ['class' => 'btn']);
        $out .= '</p>';
        $out .= '<br>';

        return $out;
    }

    /**
     * Render the plugin's translation stats.
     *
     * @param local_plugins_plugin $plugin
     * @param local_plugins_translation_stats_manager $statsman
     * @return string
     */
    public function translation_stats(local_plugins_plugin $plugin, local_plugins_translation_stats_manager $statsman) {
        global $CFG;

        list($labels, $data) = $statsman->get_chart_data();

        $out = $this->output->heading(get_string('translationstats', 'local_plugins'), 4);
        $out .= html_writer::start_tag('div', array('class' => 'stats'));

        $serie = new \core\chart_series(get_string('translationratio', 'local_plugins'), $data);

        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $chart->add_series($serie);
        $chart->set_labels($labels);

        $CFG->chart_colorset = ['#f5892b'];
        $out .= $this->output->render($chart);
        $CFG->chart_colorset = null;

        return $out;
    }

    /**
     * Renders the actual VCS widget content
     *
     * @param array $tags
     * @return string
     */
    public function widget_add_vcs_version($vcsurl, array $tags, moodle_url $actionurl, array $params = array()) {

        $output = $this->output->container_start('addvcsversion d-flex align-items-center my-4');
        $output .= html_writer::link($vcsurl, html_writer::img($this->output->image_url('githublogo', 'local_plugins'),
            'GitHub', array('height' => 20, 'class' => 'mr-2')));

        if (empty($tags)) {
            $output .= get_string('vcswidgetnotags', 'local_plugins');
            $output .= $this->output->help_icon('vcswidgetnotags', 'local_plugins');

        } else {
            $output .= html_writer::start_tag('form', array('method' => 'get', 'action' => $actionurl));
            $output .= html_writer::select($tags, 'vcstag', '', '');
            $output .= html_writer::tag('button', 'Release', array('type' => 'submit', 'class' => 'btn btn-success'));

            foreach ($params as $paramname => $paramvalue) {
                $output .= html_writer::empty_tag('input', array('type' => 'hidden', 'name' => $paramname, 'value' => $paramvalue));
            }
            $output .= html_writer::end_tag('form');
        }

        $output .= $this->output->container_end();

        return $output;
    }

    protected function review_grade(local_plugins_review_outcome $outcome) {
        if (!$outcome->is_graded()) {
            return '';
        }

        $html = html_writer::start_tag('div', array('class' => 'review-grade'));
        $title = $outcome->criterion->prepare_grade($outcome->grade);
        $width = round(100.0 * $outcome->grade);
        if ($width > 60) {
            $barclass = 'success';
        } else if ($width <= 20) {
            $barclass = 'danger';
        } else {
            $barclass = 'warning';
        }
        $html .= html_writer::start_tag('div', array('class' => 'progress', 'title' => $title));
        $html .= html_writer::start_tag('div', array('class' => 'bar bar-'.$barclass, 'style' => 'width:'.$width.'%;'));
        $html .= html_writer::end_tag('div');
        $html .= html_writer::end_tag('div');
        $html .= html_writer::tag('div', $outcome->criterion->formatted_name, array('class' => 'grade-title'));
        $html .= html_writer::tag('div', $title, array('class' => 'grade-title'));
        $html .= html_writer::end_tag('div'); // .review-grade

        return $html;
    }

    protected function average_review_grades($outcomes, $link) {
        if (empty($outcomes)) {
            return '';
        }
        $html = html_writer::start_tag('div', array('class' => 'average-review-grade'));
        $html .= html_writer::start_tag('div', array('class' => 'average-grade-title'));
        $html .= html_writer::link($link, get_string('averagereviewgradestitle', 'local_plugins'));
        $html .= html_writer::end_tag('div'); // .average-grade-title
        foreach ($outcomes as $outcome) {
            $html .= html_writer::start_tag('div', array('class' => 'grade-by-criterion'));
            $html .= html_writer::tag('div', $outcome->criterion->formatted_name, array('class' => 'criterion-name'));
            $html .= $this->review_grade($outcome);
            $html .= html_writer::end_tag('div'); // .grade-by-criterion
        }
        $html .= html_writer::end_tag('div'); // .average-review-grade
        return $html;
    }

    protected function review_list_item(local_plugins_review $review, $fulltext = false) {
        global $USER;

        $output  = html_writer::start_tag('div', array('class' => 'version-review border rounded'));
        $output .= html_writer::start_tag('div', array('class' => 'review-information d-flex p-2 border-bottom bg-light align-items-center'));
        $output .= html_writer::start_tag('div', array('class' => 'media mr-auto'));
        $canviewreviewer = $review->can_view_reviewer_profile();
        $output .= $this->output->user_picture($review->user, array('size' => 35, 'link' => $canviewreviewer)). ' ';
        $output .= html_writer::start_tag('div', array('class' => 'media-body'));
        if ($canviewreviewer) {
            $output .= html_writer::link(new local_plugins_url('/user/profile.php', array('id' => $review->userid)),
                    fullname($review->user), array('class' => 'username'));
        } else {
            $output .= html_writer::tag('div', fullname($review->user), array('class' => 'username'));
        }
        $output .= html_writer::tag('div', $review->formatted_timereviewed, array('class'=>'review-time small'));
        $output .= html_writer::end_tag('div'); // media-body
        $output .= html_writer::end_tag('div'); // media
        if ($USER->id == $review->userid or has_capability('local/plugins:approvereviews', context_system::instance())) {
            if ($review->status == 0) {
                $output .= ' <span class="badge badge-warning">'.get_string('reviewstatus0', 'local_plugins').'</span>';
            }
            if ($review->status == 1) {
                $output .= ' <span class="badge badge-success">'.get_string('reviewstatus1', 'local_plugins').'</span>';
            }
        }
        if ($review->can_edit()) {
            $output .= html_writer::link(
                $review->editreviewlink,
                get_string('editreview', 'local_plugins'),
                array('class' => 'editreview btn btn-default btn-sm ml-1')
            );
        }

        if ($review->can_approve()) {
            if ($review->status == 0) {
                $status = 1;
            } else {
                $status = 0;
            }
            $output .= html_writer::link(
                new local_plugins_url(
                    '/local/plugins/review.php',
                    ['version' => $review->versionid, 'approve' => $review->id, 'status' => $status, 'sesskey' => sesskey()]
                ),
                get_string('reviewsetstatus'.$status, 'local_plugins'),
                ['class' => 'approvereview btn btn-default btn-sm ml-1']
            );
        }

        $output .= html_writer::end_tag('div'); // review-information

        $output .= '<div class="review-grades">';
        foreach ($review->outcomes as $outcome) {
            if ($outcome->is_graded()) {
                $output .= $this->review_grade($outcome);
            }
        }
        $output .= '</div>';

        foreach ($review->outcomes as $outcome) {
            if ($outcome->is_graded() || !empty($outcome->review)) {
                $output .= html_writer::start_tag('dl', array('class'=>'review-by-criterion p-3'));
                $output .= html_writer::tag('dt', $outcome->criterion->formatted_name, array('class' => 'criterion-name'));
                $output .= html_writer::start_tag('dd', array('class'=>'review-contents'));
                if ($fulltext) {
                    $output .= html_writer::tag('div', $outcome->formatted_review, array('class' => 'review-blurb fulltext'));
                } else {
                    $output .= html_writer::tag('div', $outcome->truncated_review, array('class' => 'review-blurb truncated'));
                }
                $output .= html_writer::end_tag('dd'); // .review-contents
                $output .= html_writer::end_tag('dl'); // .review-by-criterion
            }
        }
        if (!$fulltext) {
            $output .= html_writer::start_tag('div', array('class' => 'review-links'));
            $output .= html_writer::link(
                $review->viewreviewlink,
                get_string('viewfullreview', 'local_plugins'),
                array('class' => 'viewfullreview btn')
            );
            $output .= html_writer::end_tag('div'); //review-links
        }
        $output .= html_writer::end_tag('div'); // .version-review
        return $output;
    }

    public function plugin_sets(local_plugins_plugin $plugin) {
        $sets = $plugin->sets;
        $canaddtoset = has_capability(local_plugins::CAP_ADDTOSETS, context_system::instance());
        $canmanagesets = has_capability(local_plugins::CAP_MANAGESETS, context_system::instance());
        if (empty($sets) && !$canaddtoset) {
            return '';
        }
        if (count($sets) > 0) {
            $class = 'with-sets';
        } else {
            $class = 'without-sets';
        }
        $output  = html_writer::start_tag('div', array('class' => 'infoblock plugin-sets border p-3 rounded mb-3 '.$class));
        $output .= $this->output->heading(get_string('pluginssets', 'local_plugins'), 3);
        if (count($sets) > 0) {
            $setslinks = array();
            foreach ($sets as $set) {
                $link = html_writer::link($set->browseurl, $set->formatted_name, array('class' => 'set'));
                if ($canmanagesets) {
                    $link .= ' '.$this->output->action_icon(
                        new local_plugins_url('/local/plugins/admin/sets.php', array('id' => $set->id)),
                        new pix_icon('t/edit', get_string('edit')),
                        null,
                        array('class' => 'editset')
                    );
                }
                if ($canaddtoset) {
                    $link .= ' '.$this->output->action_icon(
                        new local_plugins_url($plugin->viewlink, array('sesskey' => sesskey(), 'removefromset' => $set->id)),
                        new pix_icon('t/delete', get_string('delete')),
                        null,
                        array('class' => 'deletefromset')
                    );
                }
                $setslinks[] = $link;
            }
            $output .= html_writer::start_tag('div', array('class' => 'sets'));
            if (count($sets) > 1) {
                $output .= get_string('plugininsets', 'local_plugins', join(', ', $setslinks));
            } else {
                $output .= get_string('plugininset', 'local_plugins', join(', ', $setslinks));
            }
            $output .= html_writer::end_tag('div'); // .sets
        }

        if ($canaddtoset) {
            $addablesets = local_plugins_helper::get_sets_options();
            foreach ($sets as $set) {
                unset($addablesets[$set->id]);
            }
            $output .= html_writer::start_tag('div', array('class' => 'addto mt-3'));
            $select = new single_select($plugin->baselink, 'addtoset', $addablesets);
            $select->label = get_string('addtoset', 'local_plugins');
            $output .= $this->output->render($select);
            if ($canmanagesets) {
                $output .= html_writer::tag('span', html_writer::link(new local_plugins_url('/local/plugins/admin/sets.php'), get_string('managesets', 'local_plugins'), array('class' => 'btn btn-default btn-sm mx-1')));
            }
            $output .= html_writer::end_tag('div'); // .addto
        }

        $output .= html_writer::end_tag('div'); // .infoblock.plugin-sets
        return $output;
    }

    public function plugin_awards(local_plugins_plugin $plugin) {
        $awards = $plugin->awards;
        $canaddaward = has_capability(local_plugins::CAP_HANDOUTAWARDS, context_system::instance());
        $canmanageawards = has_capability(local_plugins::CAP_MANAGEAWARDS, context_system::instance());
        if (empty($awards) && !$canaddaward) {
            return '';
        }
        if (count($awards) > 0) {
            $class = 'with-awards';
        } else {
            $class = 'without-awards';
        }
        $output  = html_writer::start_tag('div', array('class' => 'infoblock border p-3 rounded mb-3 plugin-awards '.$class));
        $output .= $this->output->heading(get_string('pluginsawards', 'local_plugins'), 3);
        $output .= html_writer::start_tag('div', array('class' => 'plugin-awards-list d-flex flex-wrap'));
        if (count($awards) > 0) {
            foreach ($awards as $award) {
                $output .= html_writer::start_tag('div', array('class' => 'plugin-awards-list-item mx-3 text-center', 'style' => 'width: 8rem;'));
                $output .= html_writer::tag('div', html_writer::link($award->browseurl, html_writer::empty_tag('img', array('src' => $award->icon, 'alt' => $award->formatted_name))), array('class' => 'image'));
                $output .= html_writer::start_tag('div', array('class' => 'name my-2'));
                $output .= html_writer::link($award->browseurl, $award->formatted_name);
                if ($canaddaward) {
                    $output .= '<div>'.$this->output->action_icon(
                        new local_plugins_url($plugin->viewlink, array('sesskey' => sesskey(), 'revokeaward' => $award->id)),
                        new pix_icon('t/delete', get_string('delete')),
                        null,
                        array('class' => 'revokeaward')
                    ).'</div>';
                }
                $output .= html_writer::end_tag('div');
                $output .= html_writer::end_tag('div');
            }
        } else {
            $output .= html_writer::tag('div', get_string('nopluginawards', 'local_plugins'), array('class' => 'li no-awards'));
        }
        $output .= html_writer::end_tag('div');

        if ($canaddaward) {
            $addableawards = local_plugins_helper::get_awards_options();
            foreach ($awards as $award) {
                unset($addableawards[$award->id]);
            }
            $output .= html_writer::start_tag('div', array('class' => 'addto mt-3'));
            $select = new single_select($plugin->baselink, 'addaward', $addableawards);
            $select->label = get_string('addaward', 'local_plugins');
            $output .= $this->output->render($select);
            if ($canmanageawards) {
                $output .= html_writer::tag('span', html_writer::link(new local_plugins_url('/local/plugins/admin/awards.php'), get_string('manageawards', 'local_plugins'), array('class' => 'btn btn-default btn-sm mx-1')));
            }
            $output .= html_writer::end_tag('div');
        }

        $output .= html_writer::end_tag('div'); // .infoblock.plugin-awards
        return $output;
    }

    public function plugin_comments(local_plugins_plugin $plugin) {
        global $CFG, $PAGE, $USER;
        $module = array('name'=>'local_plugins_highlightcomment', 'fullpath'=>'/local/plugins/yui/highlightcomment.js');
        $this->page->requires->js_init_call('M.local_plugins_highlightcomment.init', array(array()), true, $module);
        comment::init($PAGE); // @todo when MDL-37687 is resolved, revert to passing in $this->page.

        if (!empty($CFG->enablerssfeeds)) {
            $rssurl = local_plugins_helper::get_rss_url('plugin_comments', $plugin->id);
            $rssicon = $this->output->action_icon($rssurl, new pix_icon('rss', 'RSS', 'local_plugins'));
        } else {
            $rssicon = '';
        }


        $output  = html_writer::start_tag('div', array('class' => 'infoblock border p-3 rounded mb-3 comments'));
        $output .= $this->output->heading(get_string('comments', 'local_plugins').' '.$rssicon, 3);
        $output .= html_writer::start_tag('div', array('class' => 'plugin-comments'));
        $comments = local_plugins_helper::comment_for_plugin($plugin->id);
        $output .= $comments->output(true);

        $usersubscription = local_plugins_subscription::get_pluginsubscriptions($plugin->id, $USER->id, 'comment');
        $output .= html_writer::end_tag('div');
        if (!$comments->can_view()) {
            $output .= html_writer::tag('div', get_string('commentslogintoview', 'local_plugins'), array('class' => 'comments-error-view'));
        } else if (!$comments->can_post()) {
            $output .= html_writer::tag('div', get_string('commentslogintopost', 'local_plugins'), array('class' => 'comments-error-post'));
        }

        if(isloggedin() && !isguestuser()) {
            $output .= html_writer::start_tag('div', array('class' => 'addto'));
            $subscription = local_plugins_subscription::subscription_for_plugin($plugin,local_plugins::NOTIFY_COMMENT);
            $subscribelinkstring = get_string('subscribecomments', 'local_plugins');
            if ($plugin->sub_is_subscribed($subscription)) {
                    $subscribelinkstring = get_string('unsubscribecomments', 'local_plugins');
            }
            $output .= html_writer::link($plugin->get_subscriptionlink(local_plugins::NOTIFY_COMMENT), $subscribelinkstring);
            $output .= html_writer::end_tag('div');
        }
        $output .= html_writer::end_tag('div'); // .infoblock.comments
        return $output;
    }
}

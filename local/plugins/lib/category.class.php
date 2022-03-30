<?php

/**
 * This file contains the category and category related classes used
 * within the local_plugins plugin
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Instances of this class are categories within the plugin.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 *
 * @property-read int $id
 * @property-read int $parentid
 * @property-read string $name
 * @property-read string $plugintype
 * @property-read string $shortdescription
 * @property-read int $sortorder
 * @property-read string $description
 * @property-read int $descriptionformat
 * @property-read string $installinstructions
 * @property-read int $installinstructionsformat
 * @property-read moodle_url $defaultlogo The default logo for plugin in this category
 * @property-read bool $onfrontpage
 *
 * @property-read array $children
 * @property-read int $plugincount
 * @property-read int $totalplugincount
 *
 * @property-read string $formatted_name
 * @property-read string $formatted_description
 * @property-read string $formatted_shortdescription
 * @property-read string $formatted_installinstructions
 * @property-read moodle_url $viewlink
 * @property-read moodle_url $rssurl
 * @property-read string plugin_frankenstyle_prefix
 */
class local_plugins_category extends local_plugins_collection_class implements renderable, local_plugins_loggable {

    // Database properties
    protected $id;
    protected $parentid = 0;
    protected $name;
    protected $plugintype;
    protected $shortdescription;
    protected $sortorder = 0;
    protected $description = null;
    protected $descriptionformat = 0;
    protected $installinstructions = null;
    protected $installinstructionsformat = 0;
    protected $onfrontpage;

    // Class properties
    protected $children = array();
    protected $plugincount = 0;
    protected $totalplugincount = 0;

    public function has_parent() {
        return !empty($this->parentid);
    }

    public function add_child(local_plugins_category $category) {
        $this->children[$category->id] = $category;
    }

    protected function get_children() {
        return $this->children;
    }

    public function has_children() {
        return (count($this->children) > 0);
    }

    protected function get_formatted_description() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_CATEGORYDESCRIPTION;
        $fileoptions = local_plugins_helper::editor_options_category_description();
        $description = file_rewrite_pluginfile_urls($this->description, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => true,
            'trusted' => true,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->descriptionformat, $formatoptions);
    }

    protected function get_formatted_installinstructions() {
        $context = context_system::instance();

        $filearea = local_plugins::FILEAREA_CATEGORYINSTALLINSTRUCTIONS;
        $fileoptions = local_plugins_helper::editor_options_category_installinstructions();
        $description = file_rewrite_pluginfile_urls($this->installinstructions, 'pluginfile.php', $context->id, 'local_plugins', $filearea, $this->id, $fileoptions);

        $formatoptions = array(
            'noclean' => true,
            'trusted' => true,
            'filter' => true,
            'context' => $context
        );
        return format_text($description, $this->installinstructionsformat, $formatoptions);
    }

    protected function get_formatted_shortdescription() {
        $string = local_plugins_translate_string($this->shortdescription);
        return format_string($string, true, array('context' => context_system::instance()));
    }

    protected function get_formatted_name() {
        $string = local_plugins_translate_string($this->name);
        return format_string($string, true, array('context' => context_system::instance()));
    }

    /**
     * If the user has the capability to view unapproved plugins or manage categories,
     * show also the number of unavailable (unapproved or invisible) plugins in this category
     */
    protected function get_formatted_plugincount() {
        $plugincount = $this->get_plugincount_withchildren();
        $totalplugincount = $this->get_plugincount_withchildren(true);
        if ($totalplugincount > $plugincount) {
            $plugincount .= ' + '. ($totalplugincount - $plugincount);
        }
        return $plugincount;
    }

    protected function get_plugincount_withchildren($total = false) {
        if ($total) {
            $plugincount = $this->totalplugincount;
        } else {
            $plugincount = $this->plugincount;
        }
        if ($this->has_children()) {
            foreach ($this->children as $category) {
                $plugincount += $category->get_plugincount_withchildren($total);
            }
        }
        return $plugincount;
    }

    protected function get_viewlink() {
        debugging('Deprecated: viewlink is a deprecated property, please convert your code to use browseurl', DEBUG_DEVELOPER);
        return $this->get_browseurl();
    }

    public function get_onfrontpage() {
        return $this->onfrontpage ? true : false;
    }

    public function can_edit() {
        return has_capability(local_plugins::CAP_MANAGECATEGORIES, context_system::instance());
    }

    public function can_delete() {
        return ($this->can_edit() && !$this->has_children() && $this->totalplugincount == 0);
    }

    /**
     * @global moodle_database $DB
     * @param type $properties
     */
    public function update($properties) {
        global $DB;
        $properties = (array)$properties;
        $changes = false;
        $fields = array('parentid', 'name', 'plugintype', 'shortdescription', 'description', 'descriptionformat', 'installinstructions', 'installinstructionsformat', 'sortorder');

        $category = new stdClass;
        $category->id = $this->id;
        foreach ($fields as $field) {
            if (array_key_exists($field, $properties) && $properties[$field] != $this->$field) {
                $category->$field = $properties[$field];
                $changes = true;
            }
        }
        if (!$changes) {
            return true;
        }
        $DB->update_record('local_plugins_category', $category);
        foreach ($category as $property => $value) {
            $this->$property = $value;
        }
        return true;
    }

    public function delete() {
        global $PAGE, $DB;
        $plugins = $DB->count_records('local_plugins_plugin', array('categoryid' => $this->id));
        if ($plugins > 0) {
            throw new local_plugins_exception('exc_categorycontainsplugins');
        }
        $subcategories = $DB->count_records('local_plugins_category', array('parentid' => $this->id));
        if ($plugins > 0) {
            throw new local_plugins_exception('exc_categorycontainssubcategories');
        }
        $DB->delete_records('local_plugins_category', array('id'=>$this->id));
        $fs = get_file_storage();
        $fs->delete_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_CATEGORYDESCRIPTION, $this->id);
        $fs->delete_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_CATEGORYINSTALLINSTRUCTIONS, $this->id);
        $fs->delete_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_CATEGORYDEFAULTLOGO, $this->id);
    }

    public function get_browseurl() {
        return new local_plugins_url('/local/plugins/browse.php', array('list' => 'category', 'id' => $this->id));
    }

    protected function plugins_alter_query(&$sql, &$params, $unapproved = false, $invisible = false, $alwaysshowown = false) {
        parent::plugins_alter_query($sql, $params, $unapproved, $invisible, $alwaysshowown);
        $sql["FROM"] .= " JOIN {local_plugins_category} c ON p.categoryid = c.id";
        $sql["WHERE"] .= " AND c.id = :categoryid";
        $sql["ORDER BY"] = "COALESCE(p.aggfavs, 0) DESC, p.timelastreleased DESC, p.id DESC";
        $params['categoryid'] = $this->id;
    }

    public function get_defaultlogo() {
        $fs = get_file_storage();
        $files = $fs->get_area_files(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_CATEGORYDEFAULTLOGO, $this->id);
        foreach ($files as $file) {
            if (strpos($file->get_filename(), '.') !== 0) {
                return local_plugins_url::make_pluginfile_url(SYSCONTEXTID, 'local_plugins', local_plugins::FILEAREA_CATEGORYDEFAULTLOGO, $this->id, $file->get_filepath(), $file->get_filename());
            }
        }

        return false;
    }

    public function get_rssurl() {
        return local_plugins_helper::get_rss_url('recent_plugins', $this->id);
    }

    public function can_create_plugin() {
        return (has_capability(local_plugins::CAP_CREATEPLUGINS, context_system::instance()) &&
                !empty($this->plugintype));
    }

    protected function get_plugin_frankenstyle_prefix() {
        if (empty($this->plugintype) || $this->plugintype == '-') {
            return null;
        }
        return $this->plugintype. '_';
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_action_prefix() {
        return local_plugins_log::LOG_PREFIX_CATEGORY;
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_identifier($forplugin) {
        $link = html_writer::link($this->browseurl, $this->formatted_name);
        return get_string('logidentifiercategory', 'local_plugins', $link);
    }

    /**
     * @see local_plugins_loggable
     */
    public function log_data() {
        // parent category
        if ($this->has_parent()) {
            $category = local_plugins_helper::get_category($this->parentid);
            $parent = html_writer::link($category->browseurl, $category->formatted_name);
        } else {
            $parent = null;
        }
        // default logo
        $deflogo = $this->get_defaultlogo();
        if (!empty($deflogo)) {
            $deflogo = html_writer::empty_tag('img', array('src' => $deflogo, 'class' => 'log-category-defaultlogo'));
        }
        return array(
            'name' => $this->formatted_name,
            'categoryplugintype' => $this->plugintype,
            'parentcategory' => $parent,
            'shortdescription' => $this->formatted_shortdescription,
            'sortorder' => $this->sortorder,
            'defaultlogo' => $deflogo,
            'description' => $this->formatted_description,
            'installinstructions' => $this->installinstructions,
            //'onfrontpage' => $this->onfrontpage ? get_string('yes') : get_string('no'),
        );
    }
}

/**
 * This class is used to represent the base element of a category tree.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */
class local_plugins_category_tree extends local_plugins_category {
    public function __construct() {
        $this->id = false;
        $this->parentid = false;
        $this->name = get_string('categorytreename', 'local_plugins');
        $this->shortdescription = get_string('categorytreedesc', 'local_plugins');
    }
}
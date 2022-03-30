<?php

/**
 * Through this file the user is able to create and edit categories as they desire.
$renderer = local_plugins_get_renderer();
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

require_once('../../../config.php');
require_once($CFG->dirroot.'/local/plugins/lib/setup.php');
require_once($CFG->dirroot.'/local/plugins/admin/categories_form.php');

$id = optional_param('id', null, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$initialsetup = optional_param('initialsetup', false, PARAM_BOOL);
$context = context_system::instance();
$baseurl = new local_plugins_url('/local/plugins/admin/categories.php');
$url = clone($baseurl);

require_login();
require_capability(local_plugins::CAP_MANAGECATEGORIES, $context);

if (!empty($id)) {
    $url->param('id', $id);
    $category = local_plugins_helper::get_category($id);
    $formheading = get_string('editcategory', 'local_plugins');
}
if (empty($category)) {
    $category = null;
    $formheading = get_string('createcategory', 'local_plugins');
}

$PAGE->set_url($url);
$PAGE->set_context($context);
$PAGE->set_title(get_string('pluginname', 'local_plugins'). ': '. get_string('pluginadministration', 'local_plugins'). ': '. get_string('managecategories', 'local_plugins'));
$PAGE->set_heading($PAGE->title);
$PAGE->set_pagelayout('standard');
$PAGE->navbar->add($formheading);
navigation_node::override_active_url($baseurl);
$renderer = local_plugins_get_renderer();

if ($action == 'confirmdelete' && confirm_sesskey() && $category->can_delete()) {
    local_plugins_log::remember_state($category);
    $category->delete();
    local_plugins_log::log_deleted($category);
    redirect($baseurl);
}

if ($action == 'delete' && confirm_sesskey() && $category->can_delete()) {

    $message  = html_writer::tag('p', get_string('confirmcategorydelete', 'local_plugins'));
    $message .= html_writer::tag('p', $category->formatted_name);
    $continueurl = new local_plugins_url($url, array('action'=>'confirmdelete', 'sesskey'=>sesskey()));
    $continue = new single_button($continueurl, get_string('continue'), 'post');
    $cancel = new single_button($url, get_string('cancel'), 'post');

    echo $renderer->header();
    echo $renderer->confirm($message, $continue, $cancel);
    echo $renderer->footer();
    die();
}

if (!empty($category)) {
    $form = new local_plugins_categories_form(null, array('formheading' => $formheading, 'editcategoryid' => $category->id));
} else {
    $form = new local_plugins_categories_form(null, array('formheading' => $formheading, 'editcategoryid' => null));
}
$form->set_category_data($category, $initialsetup);
if ($form->is_cancelled()) {
    redirect($baseurl);
} else if ($form->is_submitted() && $form->is_validated() && confirm_sesskey()) {
    $data = $form->get_data();
    if (empty($category)) {
        $category = local_plugins_helper::create_category(array(
            'parentid' => $data->parentid,
            'name' => $data->name,
            'shortdescription' => $data->shortdescription
        ));
    } else {
        local_plugins_log::remember_state($category);
    }

    // Save the description
    $options = local_plugins_helper::editor_options_category_description();
    $filearea = local_plugins::FILEAREA_CATEGORYDESCRIPTION;
    $data = file_postupdate_standard_editor($data, 'description', $options, $context, 'local_plugins', $filearea, $category->id);

    // Save the install instructions
    $options = local_plugins_helper::editor_options_category_installinstructions();
    $filearea = local_plugins::FILEAREA_CATEGORYINSTALLINSTRUCTIONS;
    $data = file_postupdate_standard_editor($data, 'installinstructions', $options, $context, 'local_plugins', $filearea, $category->id);

    $options = local_plugins_helper::filemanager_options_plugin_logo();
    $filearea = local_plugins::FILEAREA_CATEGORYDEFAULTLOGO;
    $data = file_postupdate_standard_filemanager($data, 'defaultlogo', $options, $context, 'local_plugins', $filearea, $category->id);

    if (!isset($data->parentid)) {
        $data->parentid = null;
    } else if (!array_key_exists($data->parentid, local_plugins_helper::get_category_parent_options($data->id))) {
        // check that we don't create loop in category hierarchy
        throw new local_plugins_exception('exc_categoryinvalidparent');
    }

    // Update the category
    $category->update($data);
    local_plugins_log::log_changed($category, empty($id));
    redirect($baseurl);
}

$categorytree = local_plugins_helper::get_categories_tree();

echo $renderer->header(get_string('managecategories', 'local_plugins'));
if ($initialsetup) {
    echo $renderer->notification(get_string('categoriesrequired', 'local_plugins'));
}
if (empty($category) && $categorytree->has_children()) {
    echo $renderer->editable_category_table($categorytree, true);
}
$form->display();
echo $renderer->footer();
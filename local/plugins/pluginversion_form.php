<?php

/**
 * This file contains moodle forms used to create and edit plugin
 * versions.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Sam Hemelryk
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class local_plugins_edit_version_form extends moodleform {

    protected function definition() {

        $form = $this->_form;

        $form->addElement('hidden', 'id');
        $form->setType('id', PARAM_INT);

        $form->addElement('hidden', 'action', 'edit');
        $form->setType('action', PARAM_ALPHA);

        self::populate_form($form, $this->_customdata, 'edit');

        $this->add_action_buttons(true);
    }

    /**
     * Populates the form with version fields. This function may be used also when building form for
     * registering new plugin or adding a version
     *
     * @param moodleform $form
     * @param array $customdata
     */
    public static function populate_form(&$form, $customdata = array(), $mode = 'edit', $prefix = '') {
        $form->addElement('header', 'versioninfoheading', get_string('versioninformation', 'local_plugins'));

        // version
        $form->addElement('text', $prefix. 'version', get_string('versionnumber', 'local_plugins'));
        if ($mode == 'edit') {
            $form->addRule($prefix. 'version', get_string('required'), 'required');
        }
        $form->addRule($prefix. 'version', get_string('versionformaterror', 'local_plugins'), 'regex', '/^2[0-9]{3}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])[0-9]{2}$/'); //same as version number in version.php validator.
        $form->setType($prefix. 'version', PARAM_TEXT);
        $form->addHelpButton($prefix. 'version', 'versionnumber', 'local_plugins');

        if ($mode == 'add' || $mode == 'new') {
            // source archive
            $form->addElement('filemanager', $prefix. 'archive_filemanager', get_string('uploadversionarchive', 'local_plugins'), null, local_plugins_helper::filemanager_options_version_upload());

            $form->addElement('checkbox', $prefix.'options[renameroot]', get_string('renameroot', 'local_plugins'));
            $form->addHelpButton($prefix.'options[renameroot]', 'renameroot', 'local_plugins');
            $form->setDefault($prefix.'options[renameroot]', 1);

            $form->addElement('checkbox', $prefix. 'options[autoremove]', get_string('autoremove', 'local_plugins'));
            $form->addHelpButton($prefix. 'options[autoremove]', 'autoremove', 'local_plugins');

            $form->addElement('checkbox', $prefix. 'options[renamereadme]', get_string('renamereadme', 'local_plugins'));
            $form->addHelpButton($prefix. 'options[renamereadme]', 'renamereadme', 'local_plugins');
        }

        // releasename
        $form->addElement('text', $prefix. 'releasename', get_string('versionname', 'local_plugins'), array('size'=>52));
        $form->setType($prefix. 'releasename', PARAM_TEXT);
        $form->addHelpButton($prefix. 'releasename', 'versionname', 'local_plugins');

        // maturity
        $form->addElement('select', $prefix. 'maturity', get_string('maturity', 'local_plugins'), local_plugins_helper::get_version_maturity_options());
        $form->setType($prefix. 'maturity', PARAM_INT);
        $form->addHelpButton($prefix.'maturity', 'maturity', 'local_plugins');

        // releasenotes
        $form->addElement('editor', $prefix. 'releasenotes_editor', get_string('releasenotes', 'local_plugins'), null, local_plugins_helper::editor_options_version_releasenotes());
        $form->setType($prefix. 'releasenotes', PARAM_RAW);

        // Change log URL
        $form->addElement('text', $prefix.'changelogurl', get_string('changelogurl', 'local_plugins'), array('size' => 52));
        $form->setType($prefix.'changelogurl', PARAM_TEXT);
        $form->setAdvanced($prefix.'changelogurl');

        // Alternative download URL
        $form->addElement('text', $prefix.'altdownloadurl', get_string('altdownloadurl', 'local_plugins'), array('size'=>52));
        $form->setType($prefix.'altdownloadurl', PARAM_TEXT);
        $form->addHelpButton($prefix.'altdownloadurl', 'altdownloadurl', 'local_plugins');
        $form->setAdvanced($prefix.'altdownloadurl');

        // Updateable versions
        $versions = array();
        if (!empty($customdata['versions']) && is_array($customdata['versions'])) {
            foreach ($customdata['versions'] as $version) {
                if ($mode != 'edit' || $version->id != $customdata['version']->id) {
                    $versions[$version->id] = $version->formatted_fullname_and_moodle_version;
                    if (!$version->is_available()) {
                        $versions[$version->id] = get_string('notavailable', 'local_plugins').': '.$versions[$version->id];
                    }
                }
            }
        }
        if (!empty($versions)) {
            $selectsize = sizeof($versions);
            if ($selectsize > 5) {
                $selectsize = 5;
            }
            $form->addElement('select', $prefix.'updateableid', get_string('updateableversions', 'local_plugins'),
                $versions, array('multiple' => 'multiple', 'size' => $selectsize));
            $form->setType($prefix.'updateableid', PARAM_INT);
            $form->addHelpButton($prefix.'updateableid', 'updateableversions', 'local_plugins');
            $form->setAdvanced($prefix.'updateableid');
        }

        // Supported versions
        $softwareversions = local_plugins_helper::get_software_versions_options();
        if (!empty($softwareversions)) {
            $form->addElement('header', 'softwareversionheading', get_string('supportedsoftware', 'local_plugins'));
            $cnt = 0;
            foreach ($softwareversions as $software) {
                $select = $form->addElement('autocomplete',
                    $prefix.'softwareversion['.$software->name.']',
                    get_string('supportedsoftwarename', 'local_plugins', format_string($software->name)),
                    $software->releasenames,
                    array('size' => 2, 'id' => 'id_softwareversion_'.(++$cnt))
                );
                $select->setMultiple(true);
                if ($software->name === 'Moodle') {
                    if ($prefix === '') {
                        // When this form is used to edit a particular version (no prefix), make the 'Supported Moodle'
                        // field required. If the form is loaded as a part of new plugin or new version wizard, the
                        // caller is supposed to make the field required - depending on the form step.
                        $form->addRule($prefix.'softwareversion[Moodle]', get_string('required'), 'required', null, 'client');
                    }
                    $form->addHelpButton($prefix.'softwareversion[Moodle]', 'supportedmoodleversion', 'local_plugins');
                } else {
                    $form->setAdvanced($prefix.'softwareversion['.$software->name.']');
                }
            }
        }

        $form->addElement('header', 'vcsheading', get_string('versioncontrolinfo', 'local_plugins'));

        // vcssystem
        $form->addElement('select', $prefix. 'vcssystem', get_string('vcssystem', 'local_plugins'), local_plugins_helper::get_version_control_system_options());
        $form->setType($prefix. 'vcssystem', PARAM_TEXT);
        $form->addHelpButton($prefix. 'vcssystem', 'vcssystem', 'local_plugins');

        // vcssystemother
        $form->addElement('text', $prefix. 'vcssystemother', get_string('vcssystemother', 'local_plugins'));
        $form->setType($prefix. 'vcssystemother', PARAM_TEXT);
        $form->disabledIf($prefix. 'vcssystemother', $prefix. 'vcssystem', 'noteq', 'other');

        // vcsrepositoryurl
        $form->addElement('text', $prefix. 'vcsrepositoryurl', get_string('vcsrepositoryurl', 'local_plugins'), array('size'=>52));
        $form->setType($prefix. 'vcsrepositoryurl', PARAM_URL);
        $form->disabledIf($prefix. 'vcsrepositoryurl', $prefix. 'vcssystem', 'eq', 'none');
        $form->addHelpButton($prefix. 'vcsrepositoryurl', 'vcsrepositoryurl', 'local_plugins');

        // vcsbranch
        $form->addElement('text', $prefix. 'vcsbranch', get_string('vcsbranch', 'local_plugins'), array('size'=>52));
        $form->setType($prefix. 'vcsbranch', PARAM_TEXT);
        $form->disabledIf($prefix. 'vcsbranch', $prefix. 'vcssystem', 'eq', 'none');
        $form->disabledIf($prefix. 'vcsbranch', $prefix. 'vcsrepositoryurl', 'eq', '');
        $form->addHelpButton($prefix. 'vcsbranch', 'vcsbranch', 'local_plugins');

        // vcstag
        $form->addElement('text', $prefix. 'vcstag', get_string('vcstag', 'local_plugins'), array('size'=>52));
        $form->setType($prefix. 'vcstag', PARAM_TEXT);
        $form->disabledIf($prefix. 'vcstag', $prefix. 'vcssystem', 'eq', 'none');
        $form->disabledIf($prefix. 'vcstag', $prefix. 'vcsrepositoryurl', 'eq', '');
        $form->addHelpButton($prefix. 'vcstag', 'vcstag', 'local_plugins');
    }

    public static function fields_list($isfirstversion = false) {
        $fields = array('version', 'releasename', 'maturity', 'releasenotes', 'releasenotesformat', 'changelogurl',
                        'altdownloadurl', 'vcssystem', 'vcssystemother', 'vcsrepositoryurl', 'vcsbranch', 'vcstag', 'softwareversion');
        if (!$isfirstversion) {
            $fields[] = 'updateableid';
        }
        return $fields;
    }

    /**
     * Validates the $data and appends errors (if any) to $err array. Returns $err array
     * The fields to be validated: $data[$prefix.'updateableid'] and $data[$prefix.'version']
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $err array of errors already found during validation
     * @param array $customdata this form's custom data (since this is a static method we can't use $this->_customdata)
     * @param string $prefix
     * @return array
     */
    public static function validate_updateable($data, $err, $customdata, $prefix = '') {
        // check that all versions specified that they can be updated to this one have smaller build number
        if (!empty($data[$prefix.'updateableid'])) {
            if (empty($customdata['versions']) || !is_array($customdata['versions'])
                    || sizeof(array_diff($data[$prefix.'updateableid'], array_keys($customdata['versions'])))
                    || (isset($data[$prefix.'id']) && in_array($data[$prefix.'id'], $data[$prefix.'updateableid']))) {
                $err[$prefix.'updateableid'] = ''; // the selected ids are not from the list - reload form (should not happen)
            } else {
                $conflictversions = array();
                foreach ($data[$prefix.'updateableid'] as $id) {
                    if (''.$customdata['versions'][$id]->version >= ''.$data[$prefix.'version']) {
                        $conflictversions[] = $customdata['versions'][$id]->formatted_fullname;
                    }
                }
                if (count($conflictversions)) {
                    $err[$prefix.'updateableid'] = get_string('cannotbeupdated', 'local_plugins', join(', ',$conflictversions));
                }
            }
        }
        // check that all versions that has specified that they can be updated from this version have bigger version build number
        if (!empty($data[$prefix.'id']) && !empty($data[$prefix.'version']) && isset($customdata['version'])) {
            $conflictversions = array();
            foreach ($customdata['version']->get_update_to_versions() as $updatetoversionid) {
                $updatetoversion = $customdata['version']->plugin->get_version($updatetoversionid);
                if (''. $updatetoversion->version < ''. $data[$prefix.'version']) {
                    $conflictversions[] = $updatetoversion->formatted_fullname;
                }
            }
            if (count($conflictversions)) {
                $err[$prefix.'version'] = get_string('cannotbeupdateable', 'local_plugins', join(', ',$conflictversions));
            }
        }
        return $err;
    }

    /**
     * Defines validation rules for this form.
     * If there are errors return array of errors ("fieldname"=>"error message"),
     * otherwise true if ok.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @return array of "element_name"=>"error_description" if there are errors,
     *               or an empty array if everything is OK (true allowed for backwards compatibility too).
     */
    public function validation($data, $files) {
        global $DB;
        $err = parent::validation($data, $files);
        foreach (array('changelogurl', 'altdownloadurl') as $key) {
            if (array_key_exists($key, $data) && !empty($data[$key]) && $data[$key] != clean_param($data[$key], PARAM_URL)) {
                $err[$key] = get_string('invalidurl', 'local_plugins');
            }
        }
        $err = self::validate_updateable($data, $err, $this->_customdata);
        return $err;
    }

    /**
     * Static function to be used in get_data() of forms add version, edit version and register plugin.
     * Analyzes the $data=parent::get_data() and returns software versions as simple array
     * from several select elements (one for each software type)
     *
     * @param stdClass $data
     * @param string $prefix
     * @return stdClass
     */
    public static function retrieve_software_versions($data, $prefix = '') {
        $softwareversions = array();
        if (!empty($data) && isset($data->{$prefix.'softwareversion'})) {
            $dataversions = $data->{$prefix.'softwareversion'};
            if (is_array($dataversions)) {
                $dataversions = array_values($dataversions);
                $softwareversions = array();
                foreach ($dataversions as $versions) {
                    if (is_array($versions)) {
                        $softwareversions = array_merge($softwareversions, array_values($versions));
                    } else {
                        $softwareversions[] = $versions;
                    }
                }
            }
            $data->{$prefix.'softwareversion'} = $softwareversions;
        }
        return $data;
    }

    /**
     * If present in $data, returns the array of Moodle versions
     */
    public static function get_moodle_requirements($data, $prefix = '') {
        $moodleversions = array();
        if (array_key_exists($prefix. 'softwareversion', $data) && array_key_exists('Moodle',  $data[$prefix. 'softwareversion'])) {
            foreach ($data[$prefix. 'softwareversion']['Moodle'] as $id) {
                $version = local_plugins_helper::get_software_version($id);
                $moodleversions[] = $version;
            }
        }
        usort($moodleversions, 'local_plugins_sort_by_version');
        return $moodleversions;
    }

    public static function set_software_versions($data, $prefix = '') {
        if (is_object($data)) {
            $data = (array)$data;
        }
        if (empty($data) || !array_key_exists($prefix. 'softwareversion', $data)) {
            return $data;
        }
        $softwareversions = array();
        foreach ($data[$prefix. 'softwareversion'] as $version) {
            $softwareversions[$version->name][] = $version->id;
        }
        $data[$prefix. 'softwareversion'] = $softwareversions;
        return $data;
    }

    function get_data() {
        $data = parent::get_data();
        $data = self::retrieve_software_versions($data);
        if (!isset($data->updateableid)) {
            $data->updateableid = array();
        }
        if (!isset($data->maturity)) {
            $data->maturity = null;
        }
        return $data;
    }

    function set_data($defaultvalues) {
        $defaultvalues = self::set_software_versions($defaultvalues);
        return parent::set_data($defaultvalues);
    }

}

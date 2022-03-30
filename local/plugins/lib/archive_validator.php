<?php

/**
 * This file contains the class local_plugins_archive_validator. It handles
 * function to validate uploaded archive and parse the helpful information
 * from it.
 *
 * Thie file is part of the local_plugins plugin, a plugin designed
 * to manage plugins contributed by the Moodle community.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Marina Glancy
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/repository/lib.php');
require_once($CFG->libdir. '/ddllib.php');

/**
 * This is the local_plugins_archive_validator class.
 *
 * @package local_plugins
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2011 Marina Glancy
 *
 * @property-read $highest_error_level
 * @property-read array $versioninformation
 * @property-read $file
 * @property-read $errors_list
 * @property-read $warnings_list
 * @property-read $infomessages_list
 * @property-read $frankenstyle
 * @property-read $category
 * @property-read $requires
 * @property-read $plugintype
 */
class local_plugins_archive_validator extends local_plugins_class_base {
    const ERROR_LEVEL_FILE = 40; // problems with the number or type of uploaded files, or empty/corrupted archive
    const ERROR_LEVEL_CLASSIFICATION = 30; // unable to determine category and/or frankenstyle
    const ERROR_LEVEL_CONTENT = 20; // errors in content or archive. Validation not passed
    const ERROR_LEVEL_WARNING = 10; // warnings. Validation passed
    const ERROR_LEVEL_INFO = 5; // information messages. Validation passed
    const ERROR_LEVEL_NONE = 0; // no errors or warnings found

    public static $REMOVE_FILES = array(
        // directories (must be listed first)
        '.git/',
        '.patches/',
        '.idea/',
        'nbproject/',
        'CVS/',
        '.settings/',
        '__MACOSX/',
        // files
        '*~',
        '*.swp',
        '*.bak',
        'cscope.*',
        '__MACOS',
        '.DS_Store',
        '.project',
        '.buildpath',
        '.cache',
    );

    var $files;
    var $errors;
    var $extractdir;
    var $rootdir;
    var $origrootdir;
    var $archiveprocessed;
    protected $versioninformation;
    var $langfiles;
    protected $frankenstyle;
    protected $category;
    protected $requires;
    protected $options;
    var $files2remove;
    protected $needsrepackage = false;
    var $cachedcontents;

    /**
     * Constructor used only from within class. Use static functions create_from_draft() and create_from_version()
     * to initialize validator
     *
     * @param array $files
     * @param local_plugins_category $category
     * @param string $frankenstyle
     * @param string $extractdir
     */
    public function __construct(array $files, $category, $frankenstyle, $requires, $options, $extractdir) {
        $this->errors = array();
        $this->files = $files;
        $this->extractdir = $extractdir;
        $this->versioninformation = array();
        $this->langfiles = array();
        $this->category = $category;
        $this->frankenstyle = $frankenstyle;
        $this->requires = $requires;

        // pre-fill default options
        $this->options = array(
            'renameroot' => false,
            'autoremove' => false,
            'renamereadme' => false,
            'alreadyexists' => false,
        );
        if (!empty($options) && is_array($options)) {
            foreach ($options as $key => $value) {
                $this->options[$key] = $value;
            }
        }

        // parse and validate the archive
        $this->validate();
    }

    /**
     * Creates an instance of local_plugins_archive_validator from the uploaded files in draft area
     *
     * @param $draftitemid
     * @param mixed $category
     * @param string $frankenstyle
     * @param $requires
     * @return local_plugins_archive_validator
     */
    public static function create_from_draft($draftitemid, $category = null, $frankenstyle = null, $requires = null, $options = null) {
        global $USER, $CFG;

        if (empty($category) && !empty($frankenstyle)) {
            $category = local_plugins_helper::get_suggested_category($frankenstyle);
        }

        $categoryid = 0;

        if (!empty($category)) {
            $categoryid = $category->id;
        }

        $usercontext = context_user::instance($USER->id);
        $fs = get_file_storage();
        $files = $fs->get_area_files($usercontext->id, 'user', 'draft', $draftitemid, 'id', false);

        return new local_plugins_archive_validator($files, $category, $frankenstyle, $requires, $options,
            $CFG->tempdir.'/local_plugins/version_upload/'.$draftitemid.'/');
    }

    /**
     * Creates an instance of local_plugins_archive_validator from the existing version
     *
     * @param local_plugins_version $version
     * @return local_plugins_archive_validator
     */
    public static function create_from_version(local_plugins_version $version) {
        global $CFG;

        $category = $version->plugin->category;
        $file = $CFG->dataroot.'/local_plugins/'.$version->plugin->id.'/'.$version->id.'.zip';
        $files = array($file);
        $frankenstyle = $version->plugin->frankenstyle;
        $requires = $version->moodle_versions;

        return new local_plugins_archive_validator($files, $category, $frankenstyle, $requires, ['alreadyexists' => true],
            $CFG->tempdir.'/local_plugins/version_upload/v'.$version->id.'/');
    }

    /**
     * Returns minimum Moodle version required by plugin.
     * Returns the version attribute specified in $attr
     *
     * @param string $attr
     * @return mixed
     */
    protected function get_min_moodle_requires($attr = 'version') {
        if (empty($this->requires)) {
            return null;
        } else {
            return $this->requires[0]->$attr;
        }
    }

    /**
     * Returns maximum Moodle version required by plugin.
     * Returns the version attribute specified in $attr
     *
     * @param string $attr
     * @return mixed
     */
    protected function get_max_moodle_requires($attr = 'version') {
        if (empty($this->requires)) {
            return null;
        } else {
            return $this->requires[sizeof($this->requires) - 1]->$attr;
        }
    }

    /**
     * Returns file mimetype
     *
     * @return string
     */
    protected function get_file_mimetype() {
        if (empty($this->file)) {
            return null;
        } else if ($this->file instanceof stored_file) {
            return $this->file->get_mimetype();
        } else {
            return mimeinfo('type', $this->file);
        }
    }

    /**
     * Help function used in extract_to_pathname():
     * Checks if extracted file needs to be removed or warning should be created about removing it.
     *
     * Returns true only if 'autoremove' option is on and this file needs to be removed
     *
     * @param string $filename
     */
    protected function path_to_remove($filename) {
        static $prefixes = array();

        // check if this file is within the directory already scheduled for removal
        foreach ($prefixes as $prefix) {
            if (substr($filename, 0, strlen($prefix)) == $prefix) {
                return true;
            }
        }

        // check if file path matches one of self::$REMOVE_FILES
        $toremove = false;
        preg_match('#([^/]+/?)$#', $filename, $matches);
        foreach (self::$REMOVE_FILES as $rmfile) {
            if (preg_match('/\*/', $rmfile)) {
                $toremove = preg_match('#^'.str_replace('*', '.*', str_replace('.', '\.', $rmfile)).'$#', $matches[1]);
            } else {
                $toremove = ($rmfile == $matches[1]);
            }
            if ($toremove) {
                break;
            }
        }

        if (!$toremove) {
            return false;
        }
        // remembers the path to create a warning/info message later
        $this->files2remove[$filename] = 1;
        if ($this->options['autoremove']) {
            if (substr($filename, -1) == '/') {
                // remember the dir path so we recursively remove its content
                $prefixes[] = $filename;
            }
            $this->needsrepackage = true;
            return true;
        }
        return false;
    }

    /**
     * Extracts the archive to the specified path. Checks the contents for files that
     * are recommended to be removed (self::$REMOVE_FILES), depending on options
     * either remove them or creates a warning that they need to be removed
     *
     * @param string $pathname
     * @return array|null
     */
    protected function extract_to_pathname($pathname) {
        if (empty($this->file)) {
            return null;
        }
        // extract the archive to the $pathname
        core_php_time_limit::raise();
        $packer = get_file_packer($this->get_file_mimetype());
        if ($this->file instanceof stored_file) {
            $result = $this->file->extract_to_pathname($packer, $pathname);
        } else {
            $result = $packer->extract_to_pathname($this->file, $pathname);
        }
        if (!$result) {
            return null;
        }

        // go through the list of files and check if files need to be removed
        $this->files2remove = array();
        $newresult = array();
        foreach ($result as $filename => $val) {
            if (!$this->path_to_remove($filename)) {
                $newresult[$filename] = $val;
            }
        }
        // actually remove files that need to be removed
        foreach (array_reverse(array_keys($result)) as $filename) {
            if (!array_key_exists($filename, $newresult)) {
                if (is_dir($this->extractdir. $filename)) {
                    rmdir($this->extractdir. $filename);
                } else if (file_exists($this->extractdir. $filename)) {
                    unlink($this->extractdir. $filename);
                }
            }
        }
        // crate an info message or warning about 'bad' files
        if (!empty($this->files2remove)) {
            if ($this->options['autoremove']) {
                // actually, this message is never shown at the moment
                $this->add_message(self::ERROR_LEVEL_INFO, 'archiveautoremoved', join('<br />',array_keys($this->files2remove)));
            } else {
                $this->add_message(self::ERROR_LEVEL_WARNING, 'archiveshouldberemoved', join('<br />',array_keys($this->files2remove)));
            }
        }
        return $newresult;
    }

    /**
     * Validates the uploaded file. Creates arrays of errors and warnings.
     * Returns true if no errors found, false otherwise.
     *
     * Extracts archive to temporary directory and calls validate_contents(),
     * then removes the temporary directory.
     *
     * @return boolean
     */
    private function validate() {
        if (count($this->files) > 1) {
            $this->add_message(self::ERROR_LEVEL_FILE, 'uploadonearchive');
        } else if (empty($this->files) || $this->get_file_mimetype() !== 'application/zip') {
            $this->add_message(self::ERROR_LEVEL_FILE, 'exc_archivenotfound');
        } else {
            // unzip to temporary dir, validate contents and remove temporary dir
            $this->archiveprocessed = $this->extract_to_pathname($this->extractdir);
            $this->validate_contents();
            $this->archiveprocessed = null;
            remove_dir($this->extractdir, false);
        }
    }

    /**
     * Stores the found error, warning or info message
     *
     * @param int $level
     * @param string $identifier
     * @param mixed $a
     * @param boolean $uniquekey - generated unique array key, so multiple errors with the same identifier may be added
     * @return false
     */
    private function add_message($level, $identifier, $a = null, $uniquekey = false) {
        if (!array_key_exists($level, $this->errors)) {
            $this->errors[$level] = array();
        }
        $key = $identifier;
        if ($uniquekey && $a !== null) {
            if (is_object($a)) {
                $key .= ':'. print_r((array)$a, true);
            } else {
                $key .= ':'. $a;
            }
        }
        $this->errors[$level][$key] = get_string($identifier, 'local_plugins', $a);
        return false;
    }

    /**
     * if error (or warning) with $identifier exists, change it's level to $newlevel
     *
     * @return boolean true if error exists
     */
    private function change_error_level($identifier, $newlevel = self::ERROR_LEVEL_CONTENT) {
        $oldmessage = null;
        foreach ($this->errors as $level => $errors) {
            foreach ($errors as $id => $message) {
                if ($id == $identifier) {
                    if ($level == $newlevel) {
                        return true; // nothing to change
                    }
                    $oldmessage = $message;
                    unset($this->errors[$level][$id]);
                    if (empty($this->errors[$level])) {
                        unset($this->errors[$level]);
                    }
                }
            }
        }
        if ($oldmessage !== null) {
            if (!array_key_exists($newlevel, $this->errors)) {
                $this->errors[$newlevel] = array();
            }
            $this->errors[$newlevel][$identifier] = $oldmessage;
            return true;
        }
        return false;
    }

    /**
     * Return highest found error level
     *
     * @return int
     */
    protected function get_highest_error_level() {
        if (empty($this->errors)) {
            return self::ERROR_LEVEL_NONE;
        }
        $levels = array_keys($this->errors);
        sort($levels, SORT_NUMERIC);
        return $levels[count($levels)-1];
    }

    /**
     * Returns all stored messages with the specified error level
     *
     * @param int $errorlevel
     * @return array
     */
    private function get_messages($errorlevel) {
        $messages = array();
        if (array_key_exists($errorlevel, $this->errors)) {
            foreach ($this->errors[$errorlevel] as $identifier => $message) {
                $messages[] = $message;
            }
        }
        return $messages;
    }

    /**
     * Returns all stored messages which are errors (ERROR_LEVEL_FILE, ERROR_LEVEL_CLASSIFICATION, ERROR_LEVEL_CONTENT)
     *
     * @return array
     */
    protected function get_errors_list() {
        return array_merge(
                $this->get_messages(self::ERROR_LEVEL_FILE),
                $this->get_messages(self::ERROR_LEVEL_CLASSIFICATION),
                $this->get_messages(self::ERROR_LEVEL_CONTENT)
                );
    }

    /**
     * Returns all stored messages which are warnings
     *
     * @return array
     */
    protected function get_warnings_list() {
        return $this->get_messages(self::ERROR_LEVEL_WARNING);
    }

    /**
     * Returns all stored messages which are information messages
     *
     * @return array
     */
    protected function get_infomessages_list() {
        return $this->get_messages(self::ERROR_LEVEL_INFO);
    }

    /**
     * Gets the file object (uploaded or stored)
     *
     * @return mixed
     */
    protected function get_file() {
        if (count($this->files) != 1) {
            return null;
        }
        $file = reset($this->files);
        return $file;
    }

    /**
     * Check if category, frankenstyle and Moodle requirements are specified. If not, try
     * to find them from version.php and lang file
     */
    private function validate_parse_attributes() {
        if ($this->is_other_contribution() === true) {
            // this is patch, no frankenstyle name is necessary
            return;
        } else if (empty($this->frankenstyle)) {
            // try to determine frankenstyle component name
            $langfiles = array_keys($this->langfiles);
            if (count($this->langfiles)) {
                // we found at least one language file
                if ($this->is_activity_module() !== null && count($langfiles) == 1) {
                    // we are sure if it is (is not) activity module and we found one langfile:
                    // it's name should be the frankenstyle component name of plugin (with prefix for activity modules)
                    if ($this->is_activity_module()) {
                        $this->frankenstyle = 'mod_'. $langfiles[0];
                    } else {
                        $this->frankenstyle = $langfiles[0];
                    }
                } else if ($this->is_activity_module() !== false && array_key_exists($this->rootdir, $langfiles)) {
                    // there are more than one langfile, try to find the file with the name similar to rootdir
                    $this->frankenstyle = 'mod_'. $this->rootdir;
                } else if ($this->is_activity_module() !== true) {
                    $found = null;
                    foreach ($langfiles as $filename) {
                        if (preg_match("/^[a-z]+_(.*)$/", $filename, $matches) && $matches[1] == $this->rootdir) {
                            if ($found !== null) {
                                $found = null;
                                break; // more than one filename matches, we can not be sure
                            }
                            $found = $filename;
                        }
                    }
                    $this->frankenstyle = $found;
                }
            }
        }

        if (!empty($this->frankenstyle)) {
            $suggestedcategory = local_plugins_helper::get_suggested_category($this->frankenstyle);
            if (!empty($this->category) && !empty($suggestedcategory) && $this->category->id != $suggestedcategory->id) {
                return $this->add_message(self::ERROR_LEVEL_CLASSIFICATION, 'wrongplugincategory', html_writer::link($suggestedcategory->browseurl, $suggestedcategory->formatted_name));
            }
            $this->category = $suggestedcategory;
        }

        $foundmoodleversion = false;
        if (!empty($this->requires) ||
                (array_key_exists('softwareversions', $this->versioninformation) && !empty($this->versioninformation['softwareversions']))) {
            $foundmoodleversion = true;
        }
        if (empty($this->frankenstyle) || empty($this->category) || !$foundmoodleversion) {
            // we were unable to determine either frankenstyle or category or Moodle version
            return $this->add_message(self::ERROR_LEVEL_CLASSIFICATION, 'exc_specifypluginattributes');
        }
        return true;
    }

    /**
     * Validates the contents of the archive. Creates arrays of errors and warnings.
     * Stores all parsed information
     *
     * @return boolean
     */
    private function validate_contents() {
        if (empty($this->archiveprocessed)) {
            return $this->add_message(self::ERROR_LEVEL_FILE, 'exc_emptyarchive');
        }
        if (!empty($this->category) && empty($this->category->plugin_frankenstyle_prefix)) {
            if ($this->category->plugintype === '') {
                // No plugin can be registered in such category, it's supposed
                // to be just a container/node for subcategories.
                return $this->add_message(self::ERROR_LEVEL_CLASSIFICATION, 'exc_categoryforbidden');
            }
            // This is patch, no requirements to the contents of non-plugin
            // (category's plugintype set to '-').
            return true;
        }
        // Validate that archive contains only one directory and store it's name in $this->rootdir
        foreach (array_keys($this->archiveprocessed) as $filename) {
            if (!preg_match("#^([^/]+)/#", $filename, $matches) || (!empty($this->rootdir) && $this->rootdir != $matches[1])) {
                return $this->add_message(self::ERROR_LEVEL_CONTENT, 'exc_archiveonedir');
            }
            $this->rootdir = $matches[1];
        }
        $this->add_message(self::ERROR_LEVEL_INFO, 'archiveonedir', $this->rootdir);
        $this->origrootdir = $this->rootdir;
        $this->parse_version_php();
        $this->locate_lang_file();
        if (!$this->validate_parse_attributes()) {
            return false;
        }
        $rootdir = local_plugins_helper::get_archive_rootdir($this->frankenstyle);
        if ($this->rootdir != $rootdir) {
            if ($this->options['renameroot']) {
                $this->rename_rootdir($rootdir);
                $this->needsrepackage = true;
            } else {
                return $this->add_message(self::ERROR_LEVEL_CONTENT, 'exc_archivecontents', $rootdir);
            }
        }
        $this->locate_release_notes();
        $this->locate_changes_file();
        $this->parse_lang_file();
        $this->validate_additional();

        if (empty($this->options['alreadyexists']) && isset($this->versioninformation['version'])) {
            $this->validate_existing_versions();
        }

        return true;
    }

    /**
     * Renames the root directory to $rootdir
     *
     * @param string $rootdir
     */
    private function rename_rootdir($rootdir) {
        if ($this->origrootdir == $rootdir) {
            return;
        }
        rename($this->extractdir. $this->origrootdir, $this->extractdir. $rootdir);
        $newarchive = array();
        foreach ($this->archiveprocessed as $filename => $val) {
            if (preg_match("#^([^/]+)(/.*)$#", $filename, $matches) && $this->origrootdir == $matches[1]) {
                $newarchive[$rootdir. $matches[2]] = $val;
            }
        }
        $this->archiveprocessed = $newarchive;
        $this->rootdir = $rootdir;
        //debugging('renaming root dir');
    }

    /**
     * Parses bare php code from file.
     * Returns contents without: php opening and closing tags, text outside php code, comments and extra whitespaces
     *
     * @param string $source
     * @return string
     */
    protected function get_stripped_file_contents($path) {
        if (!is_array($this->cachedcontents)) {
            $this->cachedcontents = array();
        }
        if (!array_key_exists($path, $this->cachedcontents)) {
            $source = file_get_contents($this->extractdir. $path);
            $tokens = token_get_all($source);
            $output = '';
            $doprocess = false;
            foreach ($tokens as $token) {
               if (is_string($token)) {
                   // simple 1-character token
                   $id = -1;
                   $text = $token;
               } else {
                   // token array
                   list($id, $text) = $token;
               }

               switch ($id) {
                   case T_WHITESPACE: // remove whitespaces
                   case T_COMMENT: // remove comments
                   case T_DOC_COMMENT: // and this
                       // no action on comments and whitespaces
                       break;
                   case T_OPEN_TAG:
                       $doprocess = true;
                       break;
                   case T_CLOSE_TAG:
                       $doprocess = false;
                       break;
                   default:
                       // anything else within php tags -> return "as is"
                       if ($doprocess) {
                           $output .= $text;
                           if ($text == 'function') {
                               $output .= ' '; // otherwise the next whitespace will be suppressed
                           }
                       }
                       break;
               }
            }
            $this->cachedcontents[$path] = $output;
        }
        return $this->cachedcontents[$path];
    }

    /**
     * Looks for version.php and tries to get the information such as
     * build number, release name, maturity and software requirements.
     * Fills $this->versioninformation with the found info, creates warnings if something is missing
     */
    private function parse_version_php() {
        $filepath = $this->extractdir. $this->rootdir. '/version.php';
        if (empty($this->archiveprocessed) || empty($this->rootdir) || !file_exists($filepath)) {
            return $this->add_message(self::ERROR_LEVEL_WARNING, 'versionphpfilenotfound');
        }

        $this->add_message(self::ERROR_LEVEL_INFO, 'filefoundinarchive', $this->rootdir. '/version.php', true);
        $content = $this->get_stripped_file_contents($this->rootdir. '/version.php');

        preg_match_all('#\$((plugin|module)\->(version|maturity|release|requires|incompatible))=()(\d+(\.\d+)?);#m',
            $content, $matches1);
        preg_match_all('#\$((plugin|module)\->(maturity))=()(MATURITY_\w+);#m', $content, $matches2);
        preg_match_all('#\$((plugin|module)\->(release))=([\'"])(.*?)\4;#m', $content, $matches3);
        preg_match_all('#\$((plugin|module)\->(component))=([\'"])(.*?)\4;#m', $content, $matches4);
        preg_match_all('#\$((plugin|module)\->(incompatible))=([\'"])(.*?)\4;#m', $content, $matches5);
        preg_match_all('#\$((plugin|module)\->(supported))=()(.+);#m', $content, $matches6);

        if (count($matches1[1]) + count($matches2[1]) + count($matches3[1]) + count($matches4[1]) +
                count($matches5[1]) + count($matches6[1])) {
            $assignments = array_combine(
                array_merge($matches1[1], $matches2[1], $matches3[1], $matches4[1], $matches5[1], $matches6[1]),
                array_merge($matches1[5], $matches2[5], $matches3[5], $matches4[5], $matches5[5], $matches6[5])
            );
        } else {
            $assignments = array();
        }

        // Detect the notation used in version.php ($module or $plugin)
        $types = array_unique(array_merge($matches1[2], $matches2[2], $matches3[2], $matches4[2], $matches5[2], $matches6[2]));

        if (empty($types)) {
            // Neither $module nor $plugin found
            return $this->add_message(self::ERROR_LEVEL_CONTENT, 'versionphpinvalidformat');

        } else if (count($types) > 1) {
            // Both $module and $plugin found
            return $this->add_message(self::ERROR_LEVEL_CONTENT, 'versionphpmixedformat');

        } else {
            // Store the used notation ("module" or "plugin") so we can
            // eventually use it in is_activity_module() below.
            $this->versioninformation['notation'] = $types[0];
        }

        if ($this->versioninformation['notation'] === 'module') {
            $this->add_message(self::ERROR_LEVEL_WARNING, 'versionphplegacyformat');
        }

        if ($this->versioninformation['notation'] === 'module' and $this->is_activity_module() !== false) {
            // Assert the $module notation in version.php
            $plugintype = 'module';
        } else {
            // Assert the $plugin notation
            $plugintype = 'plugin';
        }

        foreach ($assignments as $fullkey => $value) {
            if (preg_match('/^'.$plugintype.'->(.*)$/', $fullkey, $matches)) {
                $this->versioninformation[$matches[1]] = $value;
            }
        }

        // Check the component declared in the version.php.
        if (array_key_exists('component', $this->versioninformation)) {
            $this->add_message(self::ERROR_LEVEL_INFO, 'versionphpcomponentfound', $this->versioninformation['component']);
            if (!empty($this->frankenstyle) and $this->frankenstyle !== $this->versioninformation['component']) {
                $this->add_message(self::ERROR_LEVEL_CLASSIFICATION, 'versionphpcomponentmismatch',
                    array('expected' => $this->frankenstyle, 'found' => $this->versioninformation['component']));

            }
        } else {
            $this->add_message(self::ERROR_LEVEL_WARNING, 'versionphpcomponentnotfound');
        }

        // version build number
        if (array_key_exists('version', $this->versioninformation)) {
            $this->add_message(self::ERROR_LEVEL_INFO, 'versionphpversionfound', $this->versioninformation['version']);
            if (!preg_match('/^2[0-9]{3}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])[0-9]{2}$/', $this->versioninformation['version'])) {
                $this->add_message(self::ERROR_LEVEL_CONTENT, 'versionphpversionformaterror', $plugintype);
            } else {
                $this->add_message(self::ERROR_LEVEL_INFO, 'versionphpversionformatok', $plugintype);
            }
        } else {
            $this->add_message(self::ERROR_LEVEL_WARNING, 'versionphpversionnotfound', $plugintype);
        }

        // version maturity
        if (array_key_exists('maturity', $this->versioninformation) && preg_match('/^MATURITY/', $this->versioninformation['maturity'])) {
            if (defined($this->versioninformation['maturity']) && constant($this->versioninformation['maturity']) !== null) {
                $this->versioninformation['maturity'] = constant($this->versioninformation['maturity']);
            } else {
                unset($this->versioninformation['maturity']);
            }
        }
        if (array_key_exists('maturity', $this->versioninformation)) {
            $maturityoptions = local_plugins_helper::get_version_maturity_options();
            $this->add_message(self::ERROR_LEVEL_INFO, 'versionphpmaturityfound', $maturityoptions[$this->versioninformation['maturity']]);
        } else if (''.$this->get_min_moodle_requires() >= '2011070100') {
            // generate warning only for Moodle 2.1 and above
            $this->add_message(self::ERROR_LEVEL_WARNING, 'versionphpmaturitynotfound', $plugintype);
        }

        // release name
        if (array_key_exists('release', $this->versioninformation)) {
            $this->versioninformation['releasename'] = $this->versioninformation['release'];
            $this->add_message(self::ERROR_LEVEL_INFO, 'versionphpreleasefound', $this->versioninformation['releasename']);
        } else {
            $this->add_message(self::ERROR_LEVEL_WARNING, 'versionphpreleasenotfound', $plugintype);
        }

        // Supported branches defined in version.php.
        $minsupported = null;
        $maxsupported = null;

        if (isset($this->versioninformation['supported'])) {
            if (preg_match('#(\d+)\s*,\s*(\d+)#m', $this->versioninformation['supported'], $m)) {
                $minsupported = \local_plugins_helper::get_moodle_version_by_branch_code($m[1]);
                $maxsupported = \local_plugins_helper::get_moodle_version_by_branch_code($m[2]);
            }
        }

        // Required Moodle version.
        $requiresmoodle = null;

        if (array_key_exists('requires', $this->versioninformation)) {
            if (!preg_match('/^2[0-9]{3}(0[1-9]|1[0-2])(0[1-9]|[1-2][0-9]|3[0-1])[0-9]{2}(\\.[0-9]{2})?$/',
                    $this->versioninformation['requires'])) {
                $this->add_message(self::ERROR_LEVEL_CONTENT, 'versionphprequiresformaterror', $plugintype);

            } else {
                $requiresmoodle = $this->versioninformation['requires'];
                $this->add_message(self::ERROR_LEVEL_INFO, 'versionphpsoftwareversionsfound', $requiresmoodle);
            }

            if ($this->get_min_moodle_requires() && ''.$this->versioninformation['requires'] < ''.$this->get_min_moodle_requires()) {
                $a = array('release' => $this->get_min_moodle_requires('releasename'),
                    'version' => $this->get_min_moodle_requires(),
                    'requires' => $this->versioninformation['requires'],
                    'plugintype' => $plugintype);
                $this->add_message(self::ERROR_LEVEL_INFO, 'versionphprequirestoosmall', (object)$a);
            }

        } else {
            $this->add_message(self::ERROR_LEVEL_WARNING, 'versionphpsoftwareversionsnotfound', $plugintype);
        }

        // Incompatible Moodle branch.
        $incompatible = null;

        if (isset($this->versioninformation['incompatible'])) {
            if (preg_match('#^\d{2,}$#', $this->versioninformation['incompatible'])) {
                $incompatible = \local_plugins_helper::get_moodle_version_by_branch_code(
                    $this->versioninformation['incompatible']);

            } else {
                $this->add_message(self::ERROR_LEVEL_CONTENT, 'versionphpincompatibleformaterror', $plugintype);
            }
        }

        $versions = local_plugins_helper::get_software_versions();
        $lowestsupported = null;
        $minrequiredfound = false;
        $this->versioninformation['softwareversions'] = [];

        foreach ($versions as $version) {
            if ($version->name === 'Moodle') {
                if ($requiresmoodle && (string) $version->version < (string) $requiresmoodle) {
                    if ($minrequiredfound) {
                        continue;
                    }

                    $minrequiredfound = true;
                }

                if ($incompatible && (string) $version->version >= (string) $incompatible->version) {
                    continue;
                }

                if ($minsupported && (string) $minsupported->version > (string) $version->version) {
                    continue;
                }

                if ($maxsupported && (string) $maxsupported->version < (string) $version->version) {
                    continue;
                }

                $this->versioninformation['softwareversions'][] = $version->id;

                if (is_null($lowestsupported) || (string) $lowestsupported->version > (string) $version->version) {
                    $lowestsupported = $version;
                }
            }
        }

        if ($this->get_min_moodle_requires() && $lowestsupported && ''.$lowestsupported->version > ''.$this->get_min_moodle_requires('version')) {
            $a = array('release' => $this->get_min_moodle_requires('releasename'),
                'minrelease' => $lowestsupported->releasename,
                'requires' => $this->versioninformation['requires'],
                'plugintype' => $plugintype);
            $this->add_message(self::ERROR_LEVEL_CONTENT, 'versionphprequirestoobig', (object)$a);
        }
    }

    /**
     * Checks that file with name $filename exists. If not, add an error
     *
     * @param string $filename
     */
    private function validate_file_exists($filename) {
        $name = $this->rootdir. '/'. $filename;
        if (!array_key_exists($name, $this->archiveprocessed)) {
            $this->add_message(self::ERROR_LEVEL_CONTENT, 'exc_filenotfoundinarchive', $name, true);
            return false;
        }
        $this->add_message(self::ERROR_LEVEL_INFO, 'filefoundinarchive', $name, true);
        return true;
    }

    /**
     * Checks if function is defined. If not, add an error
     *
     * @param string $filename
     * @param string $funcname
     */
    private function validate_function_exists($filename, $funcname) {
        $contents = $this->get_stripped_file_contents($this->rootdir. '/'. $filename);
        $a = new stdClass();
        $a->filename = $filename;
        $a->funcname = $funcname;
        if (preg_match('/function '.$funcname.'\(/', $contents)) {
            $this->add_message(self::ERROR_LEVEL_INFO, 'functionfoundinfile', $a, true);
        } else {
            $this->add_message(self::ERROR_LEVEL_CONTENT, 'functionnotfoundinfile', $a, true);
        }
    }

    /**
     * Additional validation of contents (different for different plugin types)
     */
    private function validate_additional() {
        // version.php is REQUIRED for all plugins except themes
        if ( (in_array($this->plugintype, array('theme', 'format')) && $this->is_older_than('2013040500')) || ($this->is_old_moodle() && in_array($this->plugintype, array('block', 'auth')))) {
            // version.php is not required for courseformats and themes and 1.x blocks and auth. Leave it as warning
        } else {
            // all plugins 2.5 onwards (formats and themes too) require version.php now. see MDL-39279 && MDLSITE-2178.
            $this->change_error_level('versionphpfilenotfound');
            $this->change_error_level('versionphpversionformaterror');
        }

        // language file is REQUIRED for: activity modules for any Moodle version and for plugins for version 2.0 and above
        if ($this->is_activity_module() || $this->is_old_moodle() === false) {
            $this->change_error_level('langfilenopluginname');
            $this->change_error_level('langfilenotfound');
        }

        /*
            Additional checks for activity plugins
            $module->version defined in version.php
            $module->requires defined in version.php
            lib.php exists
            lib.php contains “function xxxx_add_instance”
            lib.php contains “function xxxx_update_instance”
            view.php exists
            index.php exists
            db/install.xml exists
            db/upgrade.php exists
            db/access.php exists
         */
        if ($this->is_activity_module()) {
            $this->change_error_level('versionphpversionnotfound');
            $this->change_error_level('versionphpsoftwareversionsnotfound');
            $funcprefix = substr($this->frankenstyle, 4);
            if ($this->validate_file_exists('lib.php')) {
                $this->validate_function_exists('lib.php', $funcprefix.'_add_instance');
                $this->validate_function_exists('lib.php', $funcprefix.'_update_instance');
            }
            $this->validate_file_exists('view.php');
            $this->validate_file_exists('index.php');
            $this->validate_file_exists('db/install.xml');
            $this->validate_file_exists('db/upgrade.php');
            $this->validate_file_exists('db/access.php');
        }

        /*
            Additional checks for themes
            config.php exists
         */
        if ($this->plugintype == 'theme') {
            $this->validate_file_exists('config.php');
        }

        /*
            Additional checks for blocks
            block_xxxx.php exists
         */
        if ($this->plugintype == 'block') {
            $this->validate_file_exists($this->frankenstyle. '.php');
        }

        /*
            Additional checks for auth
            auth.php exists
         */
        if ($this->plugintype == 'auth') {
            $this->validate_file_exists('auth.php');
        }

        /*
            Additional checks for course formats
            format.php exists
         */
        if ($this->plugintype == 'format') {
            $this->validate_file_exists('format.php');
        }
    }

    /**
     * Check the version number of the newly added version against existing versions.
     */
    protected function validate_existing_versions() {

        $plugin = local_plugins_helper::get_plugin_by_frankenstyle($this->frankenstyle, IGNORE_MISSING);

        if (!$plugin) {
            // It does not exist yet - so there is nothing to check here.
            return;
        }

        foreach ($plugin->versions as $pluginversion) {
            if ((string) $pluginversion->version === (string) $this->versioninformation['version']) {
                $this->add_message(self::ERROR_LEVEL_CONTENT, 'exc_versionalreadyexists', $this->versioninformation['version']);

            } else if ((string) $pluginversion->version > (string) $this->versioninformation['version']) {
                $newlysupportedmoodleversions = array_diff(
                    $this->versioninformation['softwareversions'],
                    array_keys($pluginversion->supportedsoftware)
                );

                if (empty($newlysupportedmoodleversions)) {
                    if ($pluginversion->is_latest_version()) {
                        $this->add_message(self::ERROR_LEVEL_WARNING, 'exc_addinglowerversionnoeffect',
                            $pluginversion->formatted_releasename);
                    }

                } else if (count($newlysupportedmoodleversions) < count($this->versioninformation['softwareversions'])) {
                    $this->add_message(self::ERROR_LEVEL_WARNING, 'exc_addinglowerversionpartialeffect');
                }
            }
        }
    }

    /**
     * Looks for files README, README.* and grabs
     * text from there to $this->versioninformation['releasenotes']
     * Also renames README files based on renamereadme option and sets needsrepackage property.
     */
    private function locate_release_notes() {
        if (empty($this->archiveprocessed) || empty($this->rootdir)) {
            return $this->add_message(self::ERROR_LEVEL_WARNING, 'releasenotesnotfound');
        }
        $releasenotes = array();
        foreach (array_keys($this->archiveprocessed) as $filename) {
            if (preg_match("#^{$this->rootdir}/readme(\.[\w]{2,4})?$#i", $filename)) {
                $notes = file_get_contents($this->extractdir. $filename);
                if (strlen($notes)) {
                    $releasenotes[$filename] = $notes;
                }
            }
        }
        if (empty($releasenotes)) {
            return $this->add_message(self::ERROR_LEVEL_WARNING, 'releasenotesnotfound');;
        }
        foreach ($releasenotes as $filename => $notes) {
            $this->versioninformation['releasenotes'] = $notes;
            if (preg_match('#(\.html|\.htm)$#i', $filename)) {
                $this->versioninformation['releasenotesformat'] = FORMAT_HTML;
            } else if (preg_match('#\.md$#i', $filename)) {
                $this->versioninformation['releasenotesformat'] = FORMAT_MARKDOWN;
            } else {
                $this->versioninformation['releasenotesformat'] = FORMAT_MOODLE;
            }
            $this->add_message(self::ERROR_LEVEL_INFO, 'releasenotesfound', $filename);
            break;
        }
        if (count($releasenotes) > 1) {
            $this->add_message(self::ERROR_LEVEL_WARNING, 'releasenotesfoundmore', $filename);
        } else if (!preg_match('#(\.txt|\.htm|\.html|\.md)$#i', $filename)) {
            // Rename README.* to README.txt if unsupported extension found and renaming is requested
            if ($this->options['renamereadme']) {
                $newname = dirname($filename).'/README.txt';
                rename($this->extractdir. $filename, $this->extractdir. $newname);
                $this->add_message(self::ERROR_LEVEL_INFO, 'releasenotesrenamed', basename($filename));
                $releasenotes = array($newname => $releasenotes[$filename]);
                $this->archiveprocessed[$newname] = $this->archiveprocessed[$filename];
                unset($this->archiveprocessed[$filename]);
                $this->needsrepackage = true;
            } else {
                $this->add_message(self::ERROR_LEVEL_WARNING, 'releasenotesextension', basename($filename));
            }
        }
    }

    /**
     * Tries to find the CHANGES.md, CHANGES.txt, CHANGES.htm(l), CHANGES or CHANGELOG.* file
     *
     * If found, the file contents is stored in $this->versioninformation['changesfile']
     */
    private function locate_changes_file() {

        if (empty($this->archiveprocessed) || empty($this->rootdir)) {
            return;
        }

        $files = array();

        foreach (array_keys($this->archiveprocessed) as $filename) {
            if (preg_match("#^{$this->rootdir}/change(s|log)(\.[\w]{2,4})?$#i", $filename)) {
                $contents = file_get_contents($this->extractdir.$filename);
                if (strlen($contents)) {
                    $files[$filename] = $contents;
                }
            }
        }

        if (empty($files)) {
            $this->add_message(self::ERROR_LEVEL_INFO, 'changesfilenotfound');
        }

        foreach ($files as $filename => $contents) {
            $this->versioninformation['changesfile'] = $contents;
            if (preg_match('#(\.html|\.htm)$#i', $filename)) {
                $this->versioninformation['changesfileformat'] = FORMAT_HTML;
            } else if (preg_match('#\.md$#i', $filename)) {
                $this->versioninformation['changesfileformat'] = FORMAT_MARKDOWN;
            } else {
                $this->versioninformation['changesfileformat'] = FORMAT_MOODLE;
            }
            $this->add_message(self::ERROR_LEVEL_INFO, 'changesfilefound', $filename);
            break;
        }
    }

    /**
     * returns true if the plugin is for Moodle version earlier than 2.0
     */
    private function is_old_moodle() {
        if ($this->get_max_moodle_requires() !== null) {
            return (''. $this->get_max_moodle_requires() < '2010112400');
        }
        $vers_isold = null; // wether this version is for Moodle<2.0 according to version.php $plugin->requires
        if (is_array($this->versioninformation) && array_key_exists('requires', $this->versioninformation)) {
            $vers_isold = (''. $this->versioninformation['requires'] < '2010112400');
        }
        $lang_isold = null; // wether this version is for Moodle<2.0 according to location of language file
        $isnew = $isold = 0;
        foreach (array_keys($this->archiveprocessed) as $filename) {
            if (preg_match("#^[^/]+/lang/(en|en_utf8)/[^/]+.php?$#i", $filename, $matches)) {
                if ($lang_isold === null) {
                    $lang_isold = ($matches[1] == 'en_utf8');
                } else if ($lang_isold !== ($matches[1] == 'en_utf8')) {
                    // means both lang/en and lang/en_utf8 are found
                    return $vers_isold;
                }
            }
        }
        if ($vers_isold === null) {
            return $lang_isold;
        }
        if ($vers_isold === false && $lang_isold === true) {
            $this->add_message(self::ERROR_LEVEL_WARNING, 'moodleversionmismatchnew');
        } else if ($vers_isold === true && $lang_isold === false) {
            $this->add_message(self::ERROR_LEVEL_WARNING, 'moodleversionmismatchold');
        }
        return $vers_isold;
    }

    /**
     * Checks if this plugin is for Moodle version older (non inclusive) the specified
     *
     * @param mixed $moodleversion
     * @return bool|null null if we could not determine
     */
    private function is_older_than($moodleversion) {
        if ($this->get_max_moodle_requires() !== null) {
            return (''. $this->get_max_moodle_requires() < ''.$moodleversion);
        }
        if (is_array($this->versioninformation) && array_key_exists('requires', $this->versioninformation)) {
            return (''. $this->versioninformation['requires'] < ''.$moodleversion);
        }
        return null; // unknown
    }

    /**
     * Tries to locate lang/en/xxxx.php or lang/en_utf8/xxxx.php in order to determine the
     * plugin frankenstyle name
     */
    private function locate_lang_file() {
        $dirnames = array();
        if ($this->is_old_moodle() !== false) {
            $dirnames[] = 'en_utf8';
        }
        if ($this->is_old_moodle() !== true) {
            $dirnames[] = 'en';
        }
        foreach ($dirnames as $dirname) {
            foreach (array_keys($this->archiveprocessed) as $filename) {
                if (preg_match("#^{$this->rootdir}/lang/{$dirname}/([^/]+).php?$#i", $filename, $matches)) {
                    $this->langfiles[$matches[1]] = 1;
                }
            }
        }
    }

    /**
     * Determine plugin type either from category or from frankenstyle name. For plugins in category
     * 'Other', the function is_other_contribution() should be used.
     */
    protected function get_plugintype() {
        if (!empty($this->category)) {
            return $this->category->plugintype;
        }
        if (!empty($this->frankenstyle)) {
            if (preg_match("/^([a-z]+)_(.*)$/", $this->frankenstyle, $matches)) {
                return $matches[1];
            }
        }
        if (!empty($this->versioninformation['component'])) {
            if (preg_match("/^([a-z]+)_(.*)$/", $this->versioninformation['component'], $matches)) {
                return $matches[1];
            }
        }
        return null; // unknown
    }

    /**
     * Returns true if this is not a plugin (is in category 'Other')
     *
     * @return null|boolean
     */
    private function is_other_contribution() {
        if (!empty($this->category)) {
            return empty($this->category->plugin_frankenstyle_prefix);
        }
        return null; // unknown
    }

    /**
     * Are we validating an activity module?
     *
     * Returns true if we are sure we are validating an activity module. Returns false if
     * we are sure it is not an activity module. Returns null if we have no
     * evident proofs to decide.
     *
     * @todo take $plugin->component into account too
     * @return bool|null
     */
    protected function is_activity_module() {

        if ($this->plugintype !== null) {
            return ($this->plugintype == 'mod');
        }

        if ($this->is_other_contribution()) {
            return false;
        }

        if (!isset($this->versioninformation['notation'])) {
            return false;
        }

        if ($this->versioninformation['notation'] === 'module') {
            return true;
        }

        return null;
    }

    /**
     * Locates and parses the proper language file, generates warnings if language file is not found
     * or found but does not contain $string['pluginname']
     * Warnings may be converted to errors during additional validation
     */
    private function parse_lang_file() {
        $path = $this->rootdir. '/lang/en';
        if ($this->is_old_moodle()) {
            $path .= '_utf8';
        }
        if ($this->is_activity_module()) {
            $path .= '/'. substr($this->frankenstyle, 4). '.php';
        } else {
            $path .= '/'. $this->frankenstyle. '.php';
        }

        if (array_key_exists($path, $this->archiveprocessed)) {
            $langfile = $this->get_stripped_file_contents($path);
            $this->add_message(self::ERROR_LEVEL_INFO, 'langfilefound', $path);
            if ($this->is_activity_module()) {
                $plugintype = 'module';
            } else if ($this->plugintype == 'filter') {
                $plugintype = 'filter';
            } else {
                $plugintype = 'plugin';
            }
            $stringkey = "{$plugintype}name";
            if ($this->plugintype == 'qtype' && $this->is_older_than('2011120100') === true) {
                // Question types before 2.2 required $string[XXX] where XXX was the actual name of the plugin
                $stringkey = substr($this->frankenstyle, strlen($this->plugintype) + 1);
            }
            $a = array('plugintype' => $plugintype, 'filename' => $path, 'stringkey' => $stringkey);
            if (preg_match("/\\\$string\\[('|\"){$stringkey}\\1\\]=('|\")(.*?)\\2;/", $langfile, $matches)) {
                $this->versioninformation['pluginname'] = $a['pluginname'] = $matches[3];
                $this->add_message(self::ERROR_LEVEL_INFO, 'langfilepluginnamefound', (object)$a);
            } else if (preg_match("/\\\$string\\[('|\"){$stringkey}\\1\\]=/", $langfile, $matches)) {
                $this->versioninformation['pluginname'] = $a['pluginname'] = '';
                // found, but could not be parsed
                $this->add_message(self::ERROR_LEVEL_INFO, 'langfilepluginnamefound', (object)$a);
            } else {
                $this->add_message(self::ERROR_LEVEL_WARNING, 'langfilenopluginname', (object)$a);
            }
        } else {
            $this->add_message(self::ERROR_LEVEL_WARNING, 'langfilenotfound', $path);
        }
    }

    /**
     * Populates fields in form with the data parsed from archive
     * @param MoodleQuickForm $form
     * @param string $prefix : 'version_' for registering of plugin and '' for adding a version
     * @param boolean $allfields : whether it is the last step, the archive is validated and confirmed
     */
    public function populate_form(MoodleQuickForm $form, $prefix, $allfields) {
        if (!empty($this->frankenstyle) && $form->elementExists('frankenstyle')) {
            $form->getElement('frankenstyle')->setValue($this->frankenstyle);
        }
        if (!empty($this->category) && $form->elementExists('categoryid')) {
            $form->getElement('categoryid')->setValue($this->category->id);
        }
        $key = 'softwareversions';
        $elname = $prefix. 'softwareversion[Moodle]';
        if (array_key_exists($key, $this->versioninformation) && !empty($this->versioninformation[$key]) && $form->elementExists($elname)) {
            $val = $form->getElementValue($elname);
            if (is_array($val)) {
                $val = array_filter($val, function ($item) {
                    return $item !== '_qf__force_multiselect_submission';
                });
            }
            if (empty($val)) {
                $form->getElement($elname)->setValue($this->versioninformation[$key]);
            }
        }

        $elementparsed = $form->getElement('archiveparsed');
        if ($allfields && $elementparsed->getValue() != 1) {
            foreach (array('version', 'maturity', 'releasename') as $key) {
                if (array_key_exists($key, $this->versioninformation) && $form->elementExists($prefix. $key)) {
                    $form->getElement($prefix. $key)->setValue($this->versioninformation[$key]);
                }
            }

            if ($form->elementExists($prefix.'releasenotes_editor')) {

                if (isset($this->versioninformation['changesfile'])) {
                    // Prefer CHANGES file as the source for the Release notes.
                    $text = $this->versioninformation['changesfile'];
                    $textformat = $this->versioninformation['changesfileformat'];

                } else if (isset($this->versioninformation['releasenotes'])) {
                    // Use README file eventually.
                    $text = $this->versioninformation['releasenotes'];
                    $textformat = $this->versioninformation['releasenotesformat'];
                }

                if (!empty($text)) {
                    $form->getElement($prefix.'releasenotes_editor')->setValue(array(
                        'text' => $text,
                        'format' => $textformat,
                    ));
                }
            }

            if (array_key_exists('pluginname', $this->versioninformation) && $form->elementExists('name')) {
                $form->getElement('name')->setValue($this->versioninformation['pluginname']);
            }
            $elementparsed->setValue(1);
        }
    }

    /**
     * Saves the file to the datadir, calculates and stores MD5.
     * Archive is re-packaged if required (changing root folder name, removing system files, etc.)
     *
     * @param local_plugins_version $version
     * @return boolean
     */
    public function store_archive_in_version($version) {
        $plugindir = $version->plugin->create_storage_directory();
        $versionpath = $plugindir.'/'.$version->id.'.zip';
        if ($this->needsrepackage) {
            $this->archiveprocessed = $this->extract_to_pathname($this->extractdir); // will also remove 'bad' files if needed
            $this->rename_rootdir(local_plugins_helper::get_archive_rootdir($this->frankenstyle)); // will rename rootdir if needed
            $this->locate_release_notes(); // will rename readme.* if needed
            $packer = get_file_packer($this->get_file_mimetype());
            $packer->archive_to_pathname(array($this->rootdir => $this->extractdir. $this->rootdir), $versionpath);
            remove_dir($this->extractdir, false);
        } else {
            if (!$this->file->copy_content_to($versionpath)) {
                throw new local_plugins_exception('exc_couldnotstorearchive', new local_plugins_url('/local/plugins/'));
            }
        }

        $version->save_archive_md5($versionpath);
        return true;
    }
}

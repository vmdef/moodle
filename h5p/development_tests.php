<?php

// ONLY FOR DEVELOPMENT PURPOSES
use core_h5p\framework;
use core_h5p\autoloader;

//require(__DIR__ . '/../../config.php');
require(__DIR__ . '/../config.php');
require_once($CFG->libdir . '/filelib.php');
require_once("locallib.php");

require_login();

autoloader::register();

echo 'Testing script...';

/** Testing has_editor_access */
$GET['contextId'] = 2;
if (!framework::has_editor_access('nopermissiontoviewcontenttypes')) {
    var_dump(true);
}

/** Testing intantiation of editor */
$factory = new core_h5p\factory();
$editor = $factory->get_editor();
var_dump($editor);
<?php
require_once('../../config.php');

require_once($CFG->libdir.'/filelib.php');

if (!extension_loaded('openssl')) {
    $message = '';
}

// Get public key.
$public_key = download_file_content('http://moodle.test/public.pem');

// Encrypt info.
$message = $CFG->release;
$success = openssl_public_encrypt(utf8_encode($message), $encrypted_data, $public_key);

if ($success) {
    echo base64_encode($encrypted_data);
} else {
    echo '';
}

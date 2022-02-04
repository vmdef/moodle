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
 * Endpoint to get the Moodle version
 *
 * Moodle linkchecker will check this endpoint when it cannot get the Moodle version otherwise.
 *
 * @package    core
 * @copyright  2022 Victor Deniz <victor@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/filelib.php');

$public_key_str = null;
// Get public key.
if (isset($_SERVER['HTTP_PUBLICKEY'])) {
    $key = base64_decode($_SERVER['HTTP_PUBLICKEY']);
    // Check $key is a valid public key.
    $public_key = openssl_pkey_get_public($key);
    if ($public_key !== false) {
        $public_key_str = openssl_pkey_get_details($public_key)['key'];
    }
}
if (empty($public_key_str)) {
    die('Error getting a valid public key');
}

// Encrypt info.
$message = $CFG->release;
$success = openssl_public_encrypt(utf8_encode($message), $encrypted_data, $public_key_str);

if ($success) {
    echo base64_encode($encrypted_data);
} else {
    die ('Error encrypting the data');
}

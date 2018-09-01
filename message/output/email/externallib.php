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
 * External functions
 *
 * @package     message_email
 * @category    external
 * @copyright   2018 Victor Deniz <victor@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

/**
 * Implements the external functions provided by the message_email subsystem.
 *
 * @copyright 2018 Victor Deniz <victor@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class message_email_external extends external_api {

    /**
     * Describes the input paramaters of the smtp_test external function.
     *
     * @return external_function_parameters
     */
    public static function smtp_test_parameters() {
        return new external_function_parameters([
                'address' => new external_value(PARAM_EMAIL, 'Email address to send the email test'),
                'hosts' => new external_value(PARAM_RAW, 'SMTP hosts:ports in smtphost1:port1;smtphost2:port2 format.'),
                'secure' => new external_value(PARAM_TEXT, 'SMTP security'),
                'authtype' => new external_value(PARAM_TEXT, 'SMTP Auth Type'),
                'fromaddress' => new external_value(PARAM_EMAIL, 'From address'),
                'username' => new external_value(PARAM_NOTAGS, 'SMTP user.'),
                'password' => new external_value(PARAM_TEXT, 'SMTP password.')
        ]);
    }

    /**
     * Implements the smtp_test external function.
     *
     * @param string $address Email address to send the email test
     * @param string $hosts SMTP hosts and ports in the smtphost1:port1;smtphost2:port2 format.
     * @param string $secure If SMTP server requires secure connection, specify the correct protocol type.
     * @param string $authtype This sets the authentication type to use on smtp server.
     * @param string $fromaddress From address
     * @param string $username SMTP username.
     * @param string $password SMTP password.
     * @return array
     */
    public static function smtp_test($address = '', $hosts = '', $secure = '', $authtype = '', $fromaddress = '', $username = '',
            $password = '') {
        global $USER, $SITE;
        $out = array(array('error' => 1, name => '', 'errormsg' => ''));
        $i = 0;
        $debug = '';

        // PHPMailer Object.
        $mail = get_mailer();

        if (!empty($CFG->noemailever)) {
            $out[$i]['errormsg'] = get_string('smtpemailtestnoemailever', 'message_email');
            return true;
        }

        if ($address == '') {
            $out[$i]['errormsg'] = get_string('smtpemailtestnoaddress', 'message_email');
            return $out;
        }

        if (email_should_be_diverted($address)) {
            $mail->Subject = "[DIVERTED {$address}] ";
            $address = $CFG->divertallemailsto;
        }

        if ($hosts == '') {
            $out[$i]['errormsg'] = get_string('smtpemailtestnohost', 'message_email');
            return $out;
        } else {
            $hosts = explode(';', $hosts);
        }

        if ($fromaddress == '') {
            $fromaddress = $USER->email;
        }

        // Enable SMTP debugging.
        $mail->SMTPDebug = 3;
        // Store debug info in a var.
        $mail->Debugoutput = function($str, $level) use (&$debug) {
            $debug = $str;
        };

        // Set PHPMailer to use SMTP.
        $mail->isSMTP();
        // Set this to true if SMTP host requires authentication to send email.
        $mail->SMTPAuth = false;
        if (!empty($username)) {
            $mail->SMTPAuth = true;
            // Provide username and password.
            $mail->Username = $username;
            $mail->Password = $password;
        }
        // Authentication type.
        $mail->AuthType = $authtype;
        // If SMTP requires TLS encryption then set it.
        $mail->SMTPSecure = $secure;
        // From email address and name.
        $mail->From = $fromaddress;
        // To address and name.
        $mail->addAddress($address);
        // Mail Subject.
        $mail->Subject .= get_string('smtpemailtestsubject', 'message_email', ['sitefullname' => $SITE->fullname]);

        foreach ($hosts as $host) {
            $port = 25;
            if (strpos($host, ":") !== false) {
                $temphost = explode(':', $host);
                $host = $temphost[0];
                $port = $temphost[1];
            }

            // Set SMTP host name.
            $mail->Host = $host;
            // Set TCP port to connect to.
            $mail->Port = $port;
            // Mail Body.
            $mail->Body = get_string('smtpemailtestbody', 'message_email', ['server' => $host]);

            $out[$i]['name'] = $host;

            if (!$mail->send()) {
                $out[$i]['error'] = 1;
                $out[$i]['errormsg'] = $debug;
            } else {
                $out[$i]['error'] = 0;
            }
            $i++;
        }

        return $out;
    }

    /**
     * Describes the output of the smtp_test_configuration external function.
     *
     * @return external_multiple_structure
     */
    public static function smtp_test_returns() {
        return new external_multiple_structure(
                new external_single_structure(
                        array(
                                'name' => new external_value(PARAM_TEXT, 'Server hostname'),
                                'error' => new external_value(PARAM_INT, 'Flag that indicates if the connection failed.'),
                                'errormsg' => new external_value(PARAM_RAW,
                                        'If the connection fails, debug message about the error', VALUE_OPTIONAL)
                        )
                )
        );
    }
}

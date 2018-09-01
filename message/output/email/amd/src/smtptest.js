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
 * AMD module for testing smtp configuration.
 *
 * @module     message_email/smtptest
 * @copyright  2018 Victor Deniz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['jquery',
        'core/str',
        'core/notification',
        'core/modal_factory',
        'core/ajax',
        'core/templates'
    ],
    function($, Str, Notification, ModalFactory, Ajax, Templates) {

        /**
         * Handles tests response.
         *
         * @method runtTest
         * @return {Promise|boolean} The promise resolved when the tests are finished.
         * @private
         */
        function runTest() {
            var args = {
                address: $('#id_s__smtpemailtest').val(),
                hosts: $('#id_s__smtphosts').val(),
                secure: $('#id_s__smtpsecure').val(),
                authtype: $('#id_s__smtpauthtype').val(),
                fromaddress: $('#id_s__noreplyaddress').val(),
                username: $('#id_s__smtpuser').val(),
                password: $('#id_s__smtppass').val()
            };

            return Ajax.call([{
                methodname: 'message_email_smtp_test',
                args: args
            }])[0].then(function(testResult) {
                return testResult;
            });
        }

        /**
         * Show the modal with the test results.
         *
         * @method SmtpTest
         */
        var SmtpTest = function() {
            var outputPromise = ModalFactory.create(
                {
                    type: ModalFactory.types.DEFAULT
                }
            );

            outputPromise.then(function(outputModal) {
                   return outputModal.show();
                }
            ).fail(Notification.exception);

            var stringsPromise = Str.get_strings([
                {key: 'smtpemailtestmodaltitle', component: 'message_email'},
            ]);

            $.when(stringsPromise, outputPromise).then(function(strings, modal) {
                modal.setTitle(strings[0]);

                var modalBodyPromise = $.when(runTest()).then(function(data) {
                    var context = {hosts: []};
                    context.hosts = data;
                    return Templates.render('message_email/smtptest_output', context);
                });

                return modal.setBody(modalBodyPromise);
            }).fail(Notification.exception);
        };

        return {
            init: function() {
                $('#id_s__smtpemailtest_button').click(function() {
                    new SmtpTest();
                });
            }
        };
    }
);
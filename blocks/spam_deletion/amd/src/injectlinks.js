// This file is part of Moodle - https://moodle.org/
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
 * Module for injecting the links to report spam.
 *
 * @module      block_spam_deletion/injectlinks
 * @package     block_spam_deletion
 * @copyright   2019 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([
    'jquery',
    'core/config',
    'core/str',
    'mod_forum/selectors'
], function(
    $,
    Cfg,
    Str,
    Selectors
) {

    'use strict';

    /**
     * Inject links to various places on the page.
     *
     * @param {String} page type such as 'mod-forum-discuss'
     */
    function init(pagetype) {
        // Comments may appear anywhere.
        add_report_link_to_comments();

        // If we are on a forum page, add links to posts.
        if (pagetype == 'mod-forum-discuss') {
            add_report_link_to_forum_posts();
        }
    }

    /**
     * Add report link to comments on the page.
     */
    function add_report_link_to_comments() {
        var comments = $('.comment-list.comments-loaded li');

        if (!comments.length) {
            return true;
        }

        return Str.get_string('reportasspam', 'block_spam_deletion').then(function(strreportasspam) {
            comments.each(function(index, element) {
                var comment = $(element);

                if (!comment.attr('id')) {
                    return;
                }

                var matches = comment.attr('id').match(/comment-(\d+)-.*/);

                if (matches) {
                    var commentid = matches[1];

                    // This is a really ugly hack because the comments API allows the
                    // comment template to be override. But this works on the comments block
                    // and plugins db.
                    var commentcontent = comment.find('div.no-overflow');

                    if (commentcontent.length) {
                        var reporturl = Cfg.wwwroot + '/blocks/spam_deletion/reportspam.php?commentid='
                            + commentid + '&returnurl=' + encodeURIComponent(window.location.href);
                        var reportlink = '<div class="text-right mt-2 mb-1"><a href="' + reporturl + '">'
                            + strreportasspam + '</a></div>';
                        commentcontent.append(reportlink);
                    }
                }
            });
        });
    }

    /**
     * Add report link to forum posts on the page.
     */
    function add_report_link_to_forum_posts() {
        var posts = $(Selectors.post.post);

        if (!posts.length) {
            return true;
        }

        return Str.get_string('reportasspam', 'block_spam_deletion').then(function(strreportasspam) {
            posts.each(function(index, post) {
                var postid = $(post).attr('data-post-id');
                var reporturl = Cfg.wwwroot + '/blocks/spam_deletion/reportspam.php?postid=' + postid;
                var reportlink = '<a data-region="post-action" href="' + reporturl + '" class="btn btn-link">'
                    + strreportasspam + '</a>';
                var actionsContainer = $(post).find(Selectors.post.actionsContainer).first();

                if (actionsContainer.length) {
                    actionsContainer.prepend(reportlink);
                }
            });

            return true;
        });
    }

    return {
        init: init
    };
});

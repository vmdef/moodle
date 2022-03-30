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
 * Provides {@link block_spam_deletion\detector_testcase} class.
 *
 * @package     block_spam_deletion
 * @category    phpunit
 * @copyright   2013, 2015 David Mudrak <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace block_spam_deletion;

use basic_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

/**
 * Unit tests for the forum spam detection hook.
 *
 * @copyright 2015 David Mudrak <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class detector_testcase extends basic_testcase {

    public function test_find_external_urls() {
        global $CFG;

        $d = new detector((object)array('links_whitelist' => ''));

        // Empty text.
        $this->assertSame(array(), $d->find_external_urls(''));

        // No URL present.
        $this->assertSame(array(), $d->find_external_urls('<h1>Lorem ipsum</h1> <p>Dolor sit amet, consectetur.</p>'));

        // No HTTP protocol.
        $this->assertSame(array(), $d->find_external_urls('<a href="/draftfile.php/1/foo_bar/area/0/viruz.exe">Click me!</a>'));

        // Just the protocol.
        $this->assertSame(array(), $d->find_external_urls('The https:// protocol is becoming standard.'));

        // Our draftfile.php is allowed.
        $this->assertSame(array(), $d->find_external_urls('!['.$CFG->wwwroot.'/draftfile.php/1/foo_bar/area/1/p0rn.jpg'));

        // Repeated URLs are returned just once (case insensitive).
        $this->assertSame(array('http://me.you'), $d->find_external_urls('<a href="http://me.you">http://ME.YOU</a>'));

        // Four common HTTP protocols are being detected (http, https, ftp and file).
        $urls = $d->find_external_urls('
            Either of following is considered as an URL for our purposes:

            * [http://google.com](http://google.com)
            * [Google](https://www.google.cz)
            * FTP://download.me
            * [Click me](file:///etc/passwd)

            Other protocols are ignored for the spam detection purposes:

            * git+ssh://github.com
            * rsync://user@server.tld

            Without the protocol, plain domains or paths are ok (e.g. ftp.foo.com, moodle.org, amazon.co.uk or /var/log/error_log).
        ');
        $this->assertEquals(4, count($urls));
    }

    public function test_find_external_urls_whitelist() {
        global $CFG;

        $d = new detector((object)array('links_whitelist' => 'https://moodle.org'));
        $this->assertSame(array(), $d->find_external_urls('Go to https://Moodle.org/community for details.'));
        $this->assertSame(array('http://moodle.org'), $d->find_external_urls('http://moodle.org redirects to https://moodle.org'));

        $d = new detector((object)array('links_whitelist' => "ftp://foo.bar\nftp://foo.baz"));
        $this->assertSame(array(), $d->find_external_urls('Get from ftp://foo.bar/archive or ftp://foo.baz/current/'));

        $d = new detector((object)array('links_whitelist' => "ftp://foo.bar\n\rftp://foo.baz"));
        $this->assertSame(array(), $d->find_external_urls('Get from ftp://foo.bar/archive or ftp://foo.baz/current/'));

        $d = new detector((object)array('links_whitelist' => "ftp://foo.bar\rftp://foo.baz"));
        $this->assertSame(array(), $d->find_external_urls('Get from ftp://foo.bar/archive or ftp://foo.baz/current/'));

        $d = new detector((object)array('links_whitelist' => "https://moodlecloud.com\nhttp://moodlecloud.com"));
        $urls = $d->find_external_urls('
            Check [Moodle cloud site][1] and have [courses for free](http://moodlecloud.com).
            ![Contact us][2]

            <!--
            [1]: http://VIA.GRA.com
            [2]: https://moodlecloud.com
            -->
        ');
        $this->assertSame(array('http://via.gra.com'), $urls);
    }

    public function test_contains_bad_words() {

        // No bad words configured.
        $d = new detector((object)array('badwords' => ''));
        $this->assertFalse($d->contains_bad_words('<p>buy cheap vIaGra online and watch P0RN</p>'));

        $d = new detector((object)array('badwords' => 'viagra, porn,live'));

        $this->assertFalse($d->contains_bad_words(''));
        $this->assertTrue($d->contains_bad_words('<p>buy cheap viagra online</p>'));
        $this->assertTrue($d->contains_bad_words('<p>buy cheap vIaGra online</p>'));
        $this->assertFalse($d->contains_bad_words('<p>best P0RN out there</p>'));

        // Check only whole words match.
        $this->assertTrue($d->contains_bad_words('<p>watch it live!</p>'));
        $this->assertFalse($d->contains_bad_words('<p>its alive!</p>'));
        $this->assertFalse($d->contains_bad_words('<p>its lively!</p>'));
    }

    public function test_invalid_char_percent() {

        $d = new detector((object)array());

        $this->assertEquals(0, $d->invalid_char_percent('', 'ISO-8859-1'));
        $this->assertEquals(0, $d->invalid_char_percent('????', 'ISO-8859-1'));
        $this->assertEquals(0, $d->invalid_char_percent('abcdEFGH!@#$?.1234 (it is all Greek to me)', 'WINDOWS-1253'));

        // 5 Hebrew chars out of 12 where ISO Latin2 was expected (41.667 %).
        $this->assertEquals(42, $d->invalid_char_percent('עברית 12 abřč.?', 'ISO-8859-2'));

        // Because we currently use the iconv extension with //TRANSLIT
        // parameter under the hood, this protection does not really work if
        // the unknown characters can be approximated through one or several
        // similarly looking characters (such as following being converted to
        // 'escrz' and not '?????').
        $this->assertEquals(0, $d->invalid_char_percent('ěščřž', 'ISO-8859-1'));
    }
}

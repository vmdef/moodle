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
 * Unit tests for spam deletion block
 *
 * @package    block_spam_deletion
 * @category   phpunit
 * @copyright  2012 onwards Dan Poltawski
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/blocks/spam_deletion/lib.php');

class block_spam_deletion_lib_testcase extends advanced_testcase {
    public function test_invalid_user() {
        $this->expectException(moodle_exception::class);

        $doesnotexist = new spammerlib(23242342);
    }

    public function test_admin_user() {
        $admin = get_admin();

        $this->expectException(moodle_exception::class);
        $lib = new spammerlib($admin->id);
    }

    public function test_guest_user() {
        $guest = guest_user();

        $this->expectException(moodle_exception::class);
        $lib = new spammerlib($guest->id);
    }

    public function test_current_user() {
        global $USER;

        $this->expectException(moodle_exception::class);
        $lib = new spammerlib($USER->id);
    }

    public function test_old_user() {
        $this->resetAfterTest(true);

        // Set up an old user.
        $firstaccess = time() - YEARSECS;
        $u = $this->getDataGenerator()->create_user(array('firstaccess' => $firstaccess));
        $lib = new spammerlib($u->id);
        $this->assertInstanceOf('spammerlib', $lib);
        $this->assertFalse($lib->is_recentuser());
        $this->assertTrue($lib->is_active());
        $lib->set_spammer();

        // Set up a recent user.
        $firstaccess = time() - DAYSECS;
        $u = $this->getDataGenerator()->create_user(array('firstaccess' => $firstaccess));
        $lib = new spammerlib($u->id);
        $this->assertInstanceOf('spammerlib', $lib);
        $this->assertTrue($lib->is_recentuser());
        $this->assertTrue($lib->is_active());
        $lib->set_spammer();

        // Set up a suspended user.
        $firstaccess = time() - YEARSECS;
        $u = $this->getDataGenerator()->create_user(array('firstaccess' => $firstaccess, 'suspended' => 1));
        $lib = new spammerlib($u->id);
        $this->assertInstanceOf('spammerlib', $lib);
        $this->assertFalse($lib->is_recentuser());
        $this->assertFalse($lib->is_active());
        $this->expectException(moodle_exception::class);
        $lib->set_spammer();
    }

    public function test_suspended_user() {
        $this->resetAfterTest(true);

        // Create a suspended user.
        $u = $this->getDataGenerator()->create_user(array('suspended' => 1));
        $lib = new spammerlib($u->id);

        $this->assertInstanceOf('spammerlib', $lib);

        // Expect exception because can't set an old user as a spammer.
        $this->expectException(moodle_exception::class);
        $lib->set_spammer();
    }

    public function test_clear_profile_fields() {

        $this->resetAfterTest(true);

        $urlfield = $this->getDataGenerator()->create_custom_profile_field([
            'shortname' => 'url',
            'name' => 'url',
            'datatype' => 'social',
        ]);

        $user = $this->getDataGenerator()->create_user([
            'profile_field_url' => 'http://moodle.org',
            'firstaccess' => time(),
        ]);

        $spammer = $this->getDataGenerator()->create_user([
            'profile_field_url' => 'http://spamstuff.com',
            'firstaccess' => time(),
        ]);

        $preupdatespammer = profile_user_record($spammer->id);
        $this->assertEquals('http://spamstuff.com', $preupdatespammer->url);

        $lib = new spammerlib($spammer->id);
        $this->assertInstanceOf('spammerlib', $lib);
        $lib->set_spammer();

        // Test that the spammer url has been cleared.
        $postupdatespammer = profile_user_record($spammer->id);
        $this->assertEmpty($postupdatespammer->url);

        // Test that the non-spammer profile url has been left alone.
        $postupdateuser = profile_user_record($user->id);
        $this->assertEquals('http://moodle.org', $postupdateuser->url);
    }

    public function test_delete_user_comments() {
        global $DB;
        $this->resetAfterTest(true);

        $user = $this->getDataGenerator()->create_user(array('firstaccess' => time()));
        $spammer = $this->getDataGenerator()->create_user(array('firstaccess' => time()));

        $params = new stdClass();
        $params->contextid = context_system::instance()->id;
        $params->commentarea = 'phpunit';
        $params->itemid = '1';
        $params->timecreated = time();
        $params->format = FORMAT_MOODLE;

        for ($i=0; $i<10; $i++) {
            $params->content = $user->username.'comment'.$i;
            $params->userid = $user->id;
            $DB->insert_record('comments', $params);

            $params->content = $spammer->username.'comment'.$i;
            $params->userid = $spammer->id;
            $DB->insert_record('comments', $params);
        }

        $usercommentcount = $DB->count_records('comments', array('userid'=>$user->id));
        $this->assertEquals($usercommentcount, 10);
        $spammercommentcount = $DB->count_records('comments', array('userid'=>$spammer->id));
        $this->assertEquals($spammercommentcount, 10);

        $lib = new spammerlib($spammer->id);
        $this->assertInstanceOf('spammerlib', $lib);
        $lib->set_spammer();

        $usercommentcount = $DB->count_records('comments', array('userid'=>$user->id));
        $this->assertEquals($usercommentcount, 10);
        $spammercommentcount = $DB->count_records('comments', array('userid'=>$spammer->id));
        $this->assertEmpty($spammercommentcount);
    }

}

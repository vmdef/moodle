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
 * block_partners\util tests
 *
 * @package    block_partners
 * @category   testing
 * @copyright  2015 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();


/**
 *  block_partners::util tests
 *
 * @package    block_partners
 * @category   testing
 * @copyright  2015 Dan Poltawski <dan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_partners_util_tests extends advanced_testcase {
    public function setUp(): void {
        global $DB;

        $this->resetAfterTest(true);
        // Setup GB ip.
        $_SERVER['REMOTE_ADDR'] = '10.0.2.2';

        // Setup some test data.
        $countries = array(
            array('ipfrom' => ip2long('10.0.1.1'), 'ipto' => ip2long('10.0.1.255'),
                'code2' => 'AU', 'code3' => 'AU', 'countryname' => 'Australia'),
            array('ipfrom' => ip2long('10.0.2.1'), 'ipto' => ip2long('10.0.2.255'),
                'code2' => 'GB', 'code3' => 'GB', 'countryname' => 'United Kingdom'),
            array('ipfrom' => ip2long('10.0.3.1'), 'ipto' => ip2long('10.0.3.255'),
                'code2' => 'US', 'code3' => 'US', 'countryname' => 'United States'),
        );

        foreach ($countries as $country) {
            $DB->insert_record('block_partners_countries', $country);
        }

        $adverts = array(
            array('country' => 'XX', 'partner' => 'moodle',
                'title' => 'Test title', 'image' => 'moodleimg'),
            array('country' => 'GB', 'partner' => 'ukpartner',
                'title' => 'UK Partner Ltd', 'image' => 'ukpartnerimg'),
            array('country' => 'AU', 'partner' => 'aupartner1',
                'title' => 'First AU Partner', 'image' => 'aupartnerimg1'),
            array('country' => 'AU', 'partner' => 'aupartner2',
                'title' => 'Second AU Partner', 'image' => 'aupartnerimg2'),
            array('country' => 'AU', 'partner' => 'mootau',
                'title' => 'MoodleMoodle Perth 2016', 'image' => 'mootau'),
            array('country' => 'US', 'partner' => 'https://mootus.moodlemoot.org/course/view.php?id=42',
                'title' => 'MoodleMoodle US 2016', 'image' => 'mootus')

        );

        foreach ($adverts as $ad) {
            $DB->insert_record('block_partners_ads', $ad);
        }
    }

    public function test_get_detected_countrycode_ipv4() {
        $util = new block_partners\util();

        // Undetected country.
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $this->assertFalse($util->get_detected_countrycode());

        // AU.
        $_SERVER['REMOTE_ADDR'] = '10.0.1.1';
        $this->assertEquals('AU', $util->get_detected_countrycode());

        // GB.
        $_SERVER['REMOTE_ADDR'] = '10.0.2.255';
        $this->assertEquals('GB', $util->get_detected_countrycode());

        // US.
        $_SERVER['REMOTE_ADDR'] = '10.0.3.55';
        $this->assertEquals('US', $util->get_detected_countrycode());
    }

    public function test_get_detected_countrycode_ipv6() {
        $util = new block_partners\util();

        // Localhost.
        $_SERVER['REMOTE_ADDR'] = '::1';
        $this->assertFalse($util->get_detected_countrycode());

        // Moodle.org ip.
        $_SERVER['REMOTE_ADDR'] = '2400:cb00:2048:1::8d65:70b3';
        $this->assertFalse($util->get_detected_countrycode());
    }

    public function test_get_detected_countrycode_user() {
        $util = new block_partners\util();

        // Detect country based on IP when guest.
        $this->setGuestUser();
        $this->assertEquals('GB', $util->get_detected_countrycode());

        // When logged in as real user, get country from user profile.
        $userinfrance = $this->getDataGenerator()->create_user(array('country' => 'FR'));
        $this->setUser($userinfrance);
        $this->assertEquals('FR', $util->get_detected_countrycode());
    }

    public function test_get_ads() {
        $util = new block_partners\util();

        // Check all adverts are returned.
        $alladverts = $util->get_ads();
        $this->assertCount(6, $alladverts);

        // AU Adverts.
        $auadverts = $util->get_ads('AU');
        $this->assertCount(3, $auadverts);
        foreach($auadverts as $ad) {
            $this->assertEquals('AU', $ad->country);
        }

        // Check non-country dependent advert (XX) matches.
        $moodleads = $util->get_ads('XX');
        $this->assertCount(1, $moodleads);
        $moodlead = array_shift($moodleads);
        $this->assertEquals('XX', $moodlead->country);
        $this->assertEquals('moodle', $moodlead->partner);
        $this->assertEquals('Test title', $moodlead->title);
        $this->assertEquals('moodleimg', $moodlead->image);
    }

    public function test_get_ads_minmum() {
        $util = new block_partners\util();

        // Request 4 AU Adverts (there are only 3, so will need
        // to be padded with one non-AU one).
        $auadverts = $util->get_ads('AU', 4);
        $this->assertCount(4, $auadverts);

        // We order the local (AU) adverts first.
        $this->assertEquals('AU', $auadverts[0]->country);
        $this->assertEquals('AU', $auadverts[1]->country);
        $this->assertEquals('AU', $auadverts[2]->country);
        // Ensure the filler remaining advert is not 'XX' or an 'AU' one.
        $this->assertNotEquals('AU', $auadverts[3]->country);
        $this->assertNotEquals('XX', $auadverts[3]->country);

        // Check for duplicates.
        $seenadverts = array();
        foreach ($auadverts as $ad) {
            // Verify we do not have any duplicate adverts.
            if (isset($seenadverts[$ad->partner])) {
                $this->fail("Duplicate advert by $ad->partner seen");
            } else {
                $seenadverts[$ad->partner] = true;
            }
        }

        // Request 6 AU Adverts (there are only 5 country adverts,
        // so we expect debugging.
        $auadverts = $util->get_ads('AU', 6);
        $this->assertDebuggingCalled('Not enough adverts to fill requested slots (6)');
        $this->assertCount(5, $auadverts);
    }

    public function test_get_grey_ads() {
        $util = new block_partners\util();

        // When requesting more than available, none is returned.
        $this->assertEmpty($util->get_grey_ads(4, 'GB'));

        // AU moot is never returned (because starts with moot*), some other partner is appened to the first AU partners.
        for ($i = 0; $i < 100; $i++) {
            $auadverts = $util->get_grey_ads(3, 'AU');
            $this->assertCount(3, $auadverts);
            $this->assertEquals('AU', $auadverts[0]->country);
            $this->assertNotEquals('mootau',  $auadverts[0]->partner);
            $this->assertEquals('AU', $auadverts[1]->country);
            $this->assertNotEquals('mootau',  $auadverts[1]->partner);
            $this->assertNotEquals('AU', $auadverts[2]->country);
            $this->assertNotEquals('mootau',  $auadverts[2]->partner);
        }

        // US moot is never returned (because has URL as the id), some other partners are used instead.
        for ($i = 0; $i < 100; $i++) {
            $usadverts = $util->get_grey_ads(2, 'US');
            $this->assertCount(2, $usadverts);
            $this->assertNotEquals('US', $usadverts[0]->country);
            $this->assertNotEquals('US', $usadverts[1]->country);
        }

        // Implicit IP detection - should alway return GB first.
        $this->setGuestUser();
        for ($i = 0; $i < 100; $i++) {
            $guestads = $util->get_grey_ads(3);
            $this->assertCount(3, $guestads);
            $guestad = array_shift($guestads);
            $this->assertEquals('GB', $guestad->country);
        }
    }
}

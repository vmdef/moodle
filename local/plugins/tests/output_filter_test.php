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
 * Provides {@link local_plugins_output_filter_testcase} class.
 *
 * @package     local_plugins
 * @category    test
 * @copyright   2016 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * Tests for the local_plugins\output\filter.php.
 */
class local_plugins_output_filter_testcase extends advanced_testcase {

    /**
     * Tests for parsing the raw browser query.
     */
    public function test_parse_query() {

        // Empty string gives null.
        $f = new local_plugins\output\filter("\t  \n ");
        $this->assertEmpty($f->keywords);

        // Quotes are supported for phrases.

        $f = new local_plugins\output\filter('block "Level up" or similar');
        $this->assertEquals(4, count($f->keywords));
        $this->assertContains('block', $f->keywords);
        $this->assertContains('Level up', $f->keywords);
        $this->assertContains('or', $f->keywords);
        $this->assertContains('similar', $f->keywords);

        $f = new local_plugins\output\filter("'Foo Bar' went to Bar");
        $this->assertEquals(4, count($f->keywords));
        $this->assertContains('Foo Bar', $f->keywords);
        $this->assertContains('Bar', $f->keywords);

        $f = new local_plugins\output\filter('"Rock\'n\'roll" rocks');
        $this->assertEquals(2, count($f->keywords));
        $this->assertContains("Rock'n'roll", $f->keywords);
        $this->assertContains("rocks", $f->keywords);

        // Duplicate keywords are removed.

        $f = new local_plugins\output\filter('spam "spam spam" spam spam "spam spam" "spam spam spam" spam " spam  spam"');
        $this->assertEquals(4, count($f->keywords));
        $this->assertContains('spam', $f->keywords);
        $this->assertContains('spam spam', $f->keywords);
        $this->assertContains(' spam  spam', $f->keywords);
        $this->assertContains('spam spam spam', $f->keywords);

        // Attached descriptors.

        $f = new local_plugins\output\filter(' Mission:  Impossible mi$$ion:impossible user_name:mudrd8mz foo:Foo user-id:1601
            foo:bar-Foo Bar:1-2-3 "desc:Sounds good" ');
        $this->assertEquals(3, count($f->keywords));
        $this->assertContains('Mission:', $f->keywords);
        $this->assertContains('Impossible', $f->keywords);
        $this->assertContains('mi$$ion:impossible', $f->keywords); // Not a valid descriptor.
        $this->assertEquals(5, count($f->descriptors));
        $this->assertEquals('mudrd8mz', $f->descriptors['user_name']);

        // Filtering by type.

        $f = new local_plugins\output\filter('type:_other_');
        $this->assertEquals('_other_', $f->type);

        $f = new local_plugins\output\filter('type:numbersn0tallowed');
        $this->assertNull($f->type);

        // The sorting column.

        $f = new local_plugins\output\filter('video sort-by:fans media');
        $this->assertEquals(2, count($f->keywords));
        $this->assertEquals('fans', $f->sortby);

        $f = new local_plugins\output\filter('video sort-by:some-thing-really-stupid-here media');
        $this->assertNull($f->sortby);
        $this->assertEmpty($f->descriptors);
    }

    /**
     * Test the encode_query() method.
     */
    public function test_encode_query() {

        $f = new local_plugins\output\filter('');
        $this->assertSame('', $f->encode_query());

        $f = new local_plugins\output\filter('');
        $f->keywords = ['Bar', 'Foo', 'Baz'];
        $this->assertStringContainsString('Bar', $f->encode_query());
        $this->assertStringContainsString('Foo', $f->encode_query());
        $this->assertStringContainsString('Baz', $f->encode_query());

        $f = new local_plugins\output\filter('');
        $f->keywords = ['Bar', 'Foo Bar', 'Baz'];
        $this->assertStringContainsString('Bar', $f->encode_query());
        $this->assertStringContainsString('"Foo Bar"', $f->encode_query());
        $this->assertStringContainsString('Baz', $f->encode_query());

        $f = new local_plugins\output\filter('');
        $f->descriptors = ['foo' => 'bar'];
        $this->assertStringContainsString('foo:bar', $f->encode_query());

        $f = new local_plugins\output\filter('');
        $f->descriptors = ['foo' => 'bar baz'];
        $this->assertStringContainsString('"foo:bar baz"', $f->encode_query());

        $f = new local_plugins\output\filter('');
        $f->type = 'mod';
        $this->assertStringContainsString('type:mod', $f->encode_query());
    }

    /**
     * Test the order_by_keywords() method.
     */
    public function test_order_by_keywords() {

        $f = new local_plugins\output\filter('single');
        $subsets = $f->order_by_keywords();
        $this->assertEquals(1, count($subsets));
        $this->assertEquals($subsets[0]['text'], 'single');
        $this->assertEquals($subsets[0]['weight'], 1);

        $f = new local_plugins\output\filter('two  words');
        $subsets = $f->order_by_keywords();
        $this->assertEquals(3, count($subsets));
        $this->assertEquals($subsets[0]['text'], 'two words');
        $this->assertEquals($subsets[0]['weight'], 2);
        $this->assertEquals($subsets[1]['text'], 'two');
        $this->assertEquals($subsets[1]['weight'], 1);
        $this->assertEquals($subsets[2]['text'], 'words');
        $this->assertEquals($subsets[2]['weight'], 1);

        $f = new local_plugins\output\filter('three ohyeah words');
        $subsets = $f->order_by_keywords();
        $this->assertEquals(5, count($subsets));
        $this->assertEquals($subsets[0]['text'], 'three ohyeah words');
        $this->assertEquals($subsets[0]['weight'], 3);
        $this->assertEquals($subsets[1]['text'], 'three ohyeah');
        $this->assertEquals($subsets[1]['weight'], 2);
        $this->assertEquals($subsets[2]['text'], 'three');
        $this->assertEquals($subsets[2]['weight'], 1);
        $this->assertEquals($subsets[3]['text'], 'ohyeah');
        $this->assertEquals($subsets[3]['weight'], 1);
        $this->assertEquals($subsets[4]['text'], 'words');
        $this->assertEquals($subsets[4]['weight'], 1);

        $f = new local_plugins\output\filter('some "exact phrase"');
        $subsets = $f->order_by_keywords();
        $this->assertEquals(3, count($subsets));
        $this->assertEquals($subsets[0]['text'], 'some exact phrase');
        $this->assertEquals($subsets[0]['weight'], 2);
        $this->assertEquals($subsets[1]['text'], 'some');
        $this->assertEquals($subsets[1]['weight'], 1);
        $this->assertEquals($subsets[2]['text'], 'exact phrase');
        $this->assertEquals($subsets[2]['weight'], 1);

        $f = new local_plugins\output\filter('one two three four five six seven');
        $subsets = $f->order_by_keywords(4);
        $this->assertEquals(4, count($subsets));
        $this->assertEquals($subsets[0]['text'], 'one two three four five six seven');
        $this->assertEquals($subsets[0]['weight'], 7);
        $this->assertEquals($subsets[3]['text'], 'one two three four');
        $this->assertEquals($subsets[3]['weight'], 4);
    }
}

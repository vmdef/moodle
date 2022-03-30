<?php
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
 * Provides the {@link moodle_org_url_redirection_test} class.
 *
 * @package     local_moodleorg
 * @category    test
 * @copyright   2018 David Mudrák <david@moodle.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
global $CFG;

/**
 * PHPUnit tests for moodle.org redirect rules.
 *
 * @copyright 2018 David Mudrák <david@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class moodle_org_url_redirection_test extends basic_testcase {

    // Base URL to test redirects at.
    //protected $base = 'https://moodle.org';
    protected $base = 'https://next.moodle.org';

    /** @var int Current default docs.moodle.org/en version */
    protected $docsver = '311';

    /**
     * Test the URL redirects machinery.
     */
    public function test_redirects() {

        // Redirect to HTTPS.
        $this->assertStringStartsWith($this->base, $this->visit(str_replace('https://', 'http://', $this->base)));
        $this->assertStringStartsWith($this->base, $this->visit(str_replace('https://', 'http://www.', $this->base)));
        $this->assertStringStartsWith($this->base, $this->visit(str_replace('https://', 'https://www.', $this->base)));

        // External redirects to another URL.
        foreach([
            '/logo' => 'https://moodle.com/trademarks/',
            '/course/view.php?id=10' => $this->base.'/demo/',
            '/mod/forum/discuss.php?d=42952' => $this->base.'/mod/forum/discuss.php?d=42954',
            '/mod/forum/view.php?id=893' => $this->base.'/mod/forum/view.php?f=934',
            // The following fails on Apache because it has internal redirect:
            //'/mod/forum/view.php?id=7169' => $this->base.'/mod/data/view.php?d=54',
            '/mod/resource/view.php?id=7080' => $this->base.'/mod/forum/view.php?id=7128',
            '/wiki' => 'https://docs.moodle.org/',
            '/wiki/' => 'https://docs.moodle.org/',
            '/bugs' => 'https://tracker.moodle.org/',
            '/bugs/' => 'https://tracker.moodle.org/',
            '/hosting' => 'https://moodle.com/services/',
            '/useful' => $this->base,
            '/support/commercial' => 'https://moodle.com/',
            '/support' => $this->base.'/course',
            '/donation' => 'https://moodle.com/donations/',
            '/help' => $this->base.'/course/view.php?id=5',
            '/buzz' => $this->base.'/mod/data/view.php?id=6140',
            '/themes' => $this->base.'/plugins/browse.php?list=category&id=3',
            '/philosophy' => 'https://docs.moodle.org/'.$this->docsver.'/en/Philosophy',
            '/faq' => 'https://docs.moodle.org/'.$this->docsver.'/en/Category:FAQ',
            '/jobs' => $this->base.'/mod/data/view.php?d=54',
            '/books' => $this->base.'/mod/data/view.php?id=7246',
            '/audio' => $this->base,
            '/community' => $this->base.'/course',
            '/forums' => $this->base.'/course',
            '/development' => 'https://docs.moodle.org/dev/',
            '/downloads' => 'https://download.moodle.org/',
            // These need login:
            // '/events' => $this->base.'/calendar',
            // '/course/view.php?id=33' => $this->base.'/calendar',
            '/stories' => 'https://moodle.com/news',
            '/sites' => 'https://stats.moodle.org/sites/',
            '/stats' => 'https://stats.moodle.org',
            '/about' => 'https://docs.moodle.org/'.$this->docsver.'/en/About_Moodle',
            '/features' => 'https://docs.moodle.org/'.$this->docsver.'/en/Features',
            '/privacy' => $this->base.'/admin/tool/policy/view.php',
            '/contact' => $this->base.'/mod/page/view.php?id=8191',
            '/social' => $this->base.'/mod/page/view.php?id=8213',
            // This is old and we do not need it any more:
            // '/educators' => $this->base.'/course/view.php?id=17223',
            '/project_inspire' => $this->base.'/course/view.php?id=17233',
            '/learning_analytics' => $this->base.'/course/view.php?id=17233',
            '/analytics' => $this->base.'/mod/forum/view.php?id=8044',
        ] as $source => $target) {
            $this->assertStringStartsWith($target, $this->visit("{$this->base}{$source}"),
                'When I visit URL: '.$this->base.$source.' I should end up on a page starting with: '.$target);
        }

        // Internal redirects that are just supposed to work.
        foreach([
            '/useful/rss.php',
            '/resources/rss.php',
            '/favicon.ico',
            '/moodle.gif',
            '/robots.txt',
            '/security',
            '/demo',
            '/network',
            '/news',
            '/plugins',
            '/plugins/editor_marklar',
            // Probably related to MDLSITE-2977, we get http:// here.
            // '/register',
            '/dev',
            '/error',
        ] as $url) {
            $this->assertStringStartsWith($this->base.$url, $this->visit($this->base.$url),
                'When I visit URL: '.$this->base.$url.' I should stay there and the page should display');
        }
    }

    /**
     * Prepares a curl session to be used in the tests.
     *
     * @param string $url URL to visit
     * @return resource
     */
    protected function init_curl($url) {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (strpos($url, 'https://') === 0) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        }

        if (strpos($url, 'next') !== false) {
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, 'study:revise');
        }

        return $ch;
    }

    /**
     * What address do we end at when visiting the given URL?
     *
     * @param string $url URL to visit
     * @return string final address
     */
    protected function visit($url) {

        $ch = $this->init_curl($url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);
        $info = curl_getinfo($ch);

        if (!$result) {
            print_r(curl_error($ch).PHP_EOL);
            print_r($info);
            $this->assertTrue(false);
        }

        curl_close($ch);

        if (strpos($url, 'https://moodle.org') === 0) {
            $this->assertEquals(200, $info['http_code'], 'Unexpected HTTP status when accessing '.$url);
        }

        $result = '';

        foreach ([PHP_URL_SCHEME, PHP_URL_HOST, PHP_URL_PATH, PHP_URL_QUERY, PHP_URL_FRAGMENT] as $part) {
            if ($part == PHP_URL_QUERY) {
                $result .= '?';
            }

            $result .= parse_url($info['url'], $part);

            if ($part == PHP_URL_SCHEME) {
                $result .= '://';
            }
        }

        return $result;
    }
}

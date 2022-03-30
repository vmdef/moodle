@block @block_spam_deletion @moodle_org
Feature: New users are prevented from posting a text with too many links
  In order to have my forum post blocked
  As a spammer
  I can include several links to external sites in the forum post

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | spammer1  | Nasty     | Spammer   | spammer1@example.com  |
    And the following "courses" exist:
      | fullname              | shortname | category  | lang  |
      | Moodle in English     | English   | 0         | en    |
    And the following "course enrolments" exist:
      | user      | course    | role            |
      | spammer1  | English   | student         |
    And the following "activities" exist:
      | activity  | name                  | intro                         | course  | idnumber |
      | forum     | General Moodle help   | Standard forum description    | English | GMH      |

  Scenario: Forum post with more than 1 unique link is blocked
    Given I log in as "admin"
    And I set the following administration settings values:
      | Links limit                       | 1                     |
      | Links whitelist                   | https://google.com    |
    And I log out
    And I log in as "spammer1"
    And I follow "Moodle in English"
    And I follow "General Moodle help"
    When I add a new discussion to "General Moodle help" forum with:
      | Subject | How to setup Moodle on freecourses.com                                      |
      | Message | Visit http://freecourses.com or http://freemoodlehosting.com for more info  |
    Then I should see "Potential spam detected!"
    And I should see "Your forum post has been blocked, as our spam prevention system has flagged it as possibly containing spam."
    And I should see "Error code: links_count"

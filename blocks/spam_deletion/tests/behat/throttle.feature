@block @block_spam_deletion @moodle_org
Feature: New users are prevented from posting many posts in short time period
  In order to have my forum post blocked
  As a spammer
  I can post multiple posts in short time

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

  Scenario: Only one forum post is allowed, the second forum post is blocked
    Given I log in as "admin"
    And I set the following administration settings values:
      | throttle_postcount                | 1                     |
    And I log out
    And I log in as "spammer1"
    And I follow "Moodle in English"
    And I follow "General Moodle help"
    And I add a new discussion to "General Moodle help" forum with:
      | Subject | Hello!                                    |
      | Message | I just joined this awesome site!          |
    And I should not see "Potential spam detected!"
    And I am on homepage
    And I follow "Moodle in English"
    And I follow "General Moodle help"
    When I reply "Hello!" post from "General Moodle help" forum with:
      | Subject | I forgot to say ...                       |
      | Message | I need some help                          |
    Then I should see "Potential spam detected!"
    And I should see "Your forum post has been blocked, as our spam prevention system has flagged it as possibly containing spam."
    And I should see "Error code: throttle"

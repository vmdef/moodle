@block @block_spam_deletion @moodle_org
Feature: New users are no longer checked once they have some older non-spammy posts
  In order to bypass the first posts checks
  As a spammer
  I need to have some non-spammy posts

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

  Scenario: First posts checks are not performed once there is some other post
    Given I log in as "admin"
    And I set the following administration settings values:
      | First posts filters duration      | 0                     |
      | Custom spam words list            | fuck                  |
      | Links limit                       | 1                     |
    And I log out
    And I log in as "spammer1"
    And I follow "Moodle in English"
    And I follow "General Moodle help"
    When I add a new discussion to "General Moodle help" forum with:
      | Subject | Hello                                           |
      | Message | I just joined this awesome community!           |
    And I should not see "Potential spam detected!"
    And I am on homepage
    And I follow "Moodle in English"
    And I follow "General Moodle help"
    When I reply "Hello" post from "General Moodle help" forum with:
      | Subject | I forgot to say ... FUCK OFF!                                                           |
      | Message | http://spam1.site.com http://spam2.site.com http://spam3.site.com http://spam4.site.com |
    Then I should not see "Potential spam detected!"

@block @block_spam_deletion @moodle_org @javascript
Feature: Forum posts can be reported to moderators
  In order to help moderators to keep my favourite portal free of spam
  As a user
  I need to be able to report forum posts as spam

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname  | email                 |
      | spammer1  | Nasty     | Spammer   | spammer1@example.com  |
      | goodboy1  | Good      | Boy       | goodboy1@example.com  |
    And the following "courses" exist:
      | fullname              | shortname | category  | lang  |
      | Moodle in English     | English   | 0         | en    |
    And the following "course enrolments" exist:
      | user      | course    | role            |
      | spammer1  | English   | student         |
      | goodboy1  | English   | student         |
    And the following "activities" exist:
      | activity  | name                  | intro                         | course  | idnumber |
      | forum     | General Moodle help   | Standard forum description    | English | GMH      |

  Scenario: Suspicious forum post is reported as a spam
    Given I log in as "admin"
    And I set the following administration settings values:
      | Links limit                       | 5                     |
    And I am on site homepage
    And I follow "Moodle in English"
    And I turn editing mode on
    And I am on site homepage
    And I add the "Spam deletion" block
    And I configure the "Spam deletion" block
    And I set the following fields to these values:
      | Page contexts | Display throughout the entire site |
    And I press "Save changes"
    And I log out
    And I log in as "spammer1"
    And I follow "Moodle in English"
    And I follow "General Moodle help"
    And I add a new discussion to "General Moodle help" forum with:
      | Subject | Please help - what is wrong with my Moodle?                                   |
      | Message | On my [school Moodle](http://freemoodlehosting.com) I can't see login button. |
    And I should not see "Potential spam detected!"
    And I log out
    And I log in as "goodboy1"
    And I follow "Moodle in English"
    And I follow "General Moodle help"
    And I follow "Please help - what is wrong with my Moodle?"
    And I follow "Report as spam"
    And I should see "Are you sure you wish to report this content as spam?"
    And I click on "Yes" "button"
    And I log out
    When I log in as "admin"
    Then I should see "Spam reports: 1"
    And I follow "Spam reports: 1"
    And I should see "Please help - what is wrong with my Moodle?"
    And I should see "Nasty Spammer"
    And I should see "Good Boy"

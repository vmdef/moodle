@block @block_spam_deletion @moodle_org
Feature: New user accounts are automatically suspended once they submit enough spammy posts
  In order to get my account suspended
  As a spammer
  I need to have my posts repeatedly blocked

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

  Scenario: Users are allowed to have just one post blocked before they are suspended
    Given I log in as "admin"
    And I set the following administration settings values:
      | Custom spam words list            | fuck                  |
      | Links limit                       | 1                     |
      | Suspension limit                  | 1                     |
    And I log out
    And I log in as "spammer1"
    And I follow "Moodle in English"
    And I follow "General Moodle help"
    And I add a new discussion to "General Moodle help" forum with:
      | Subject | Try these cool sites                            |
      | Message | http://spam1.site.com http://spam2.site.com     |
    And I should see "Potential spam detected!"
    And I log out
    And I log in as "spammer1"
    And I follow "Moodle in English"
    And I follow "General Moodle help"
    When I add a new discussion to "General Moodle help" forum with:
      | Subject | Fuck you!                                       |
      | Message | Your anti-spam protection sucks!!!              |
    Then I should see "Account suspended"
    And I should see "Your account has been suspended, as our spam prevention system has flagged it as possibly belonging to a spammer."
    And I log out
    And I log in as "spammer1"
    And I should see "Invalid login, please try again"

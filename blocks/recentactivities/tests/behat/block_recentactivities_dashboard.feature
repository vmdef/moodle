@block @block_recentactivities @javascript
Feature: The recent activities block allows users to easily access their most recently visited activities
  In order to access the most recent activities accessed
  As a user
  I can use the recent activities block in my dashboard

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student1 | C2     | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I turn editing mode on
    And I add a "Forum" to section "1" and I fill the form with:
      | Forum name | Test forum name |
      | Forum type | Standard forum for general use |
      | Description | Test forum description |
    And I log out
    And I log in as "student1"
    When I press "Customise this page"
    And I add the "Recent activities" block

  Scenario: User has not accessed any activity
    Then I should see "No recent activities" in the "Recent activities" "block"

  Scenario: User has accessed some activities
    Given I am on "Course 1" course homepage
    When  I follow "Test forum name"
    And I follow "Dashboard" in the user menu
    Then I should see "Test forum name" in the "Recent activities" "block"
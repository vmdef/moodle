@block @block_recentcourses @javascript
Feature: The recent courses block allows users to easily access their most recently visited courses
  In order to access the most recent courses accessed
  As a user
  I can use the recent courses block in my dashboard

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email                |
      | student1 | Student   | 1        | student1@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
      | Course 2 | C2        |
      | Course 3 | C3        |
      | Course 4 | C4        |
      | Course 5 | C5        |
    And the following "course enrolments" exist:
      | user     | course | role    |
      | student1 | C1     | student |
      | student1 | C2     | student |
      | student1 | C3     | student |
      | student1 | C4     | student |
      | student1 | C5     | student |

  Scenario: User has not accessed any course
    Given I log in as "student1"
    Then I should see "No recent courses" in the "Recent courses" "block"

  Scenario: User has access two courses
    Given I log in as "student1"
    And I should not see "Course 1" in the "Recent courses" "block"
    And I should not see "Course 2" in the "Recent courses" "block"
    When I am on "Course 1" course homepage
    And I am on "Course 2" course homepage
    And I follow "Dashboard" in the user menu
    Then I should see "Course 1" in the "Recent courses" "block"
    And I should see "Course 2" in the "Recent courses" "block"

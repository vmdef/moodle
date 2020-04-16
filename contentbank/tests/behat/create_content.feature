@core @core_contentbank
Feature: Create content from the Content Bank
  In order to add/edit content
  As a user
  I need to be able to create a new content.

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | student1 | S1        | Student1 | student1@moodle.com |

    Scenario: Users can't see the Add button if any content type allow create content
      Given I log in as "admin"


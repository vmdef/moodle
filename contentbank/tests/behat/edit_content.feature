@core @core_contentbank @contentbank_h5p @_file_upload @javascript
Feature: Content bank use editor feature
  In order to add/edit content
  As a user
  I need to be able to access the edition options

  Scenario: Users can't see the Add button if there is no content type available for creation
    Given I log in as "admin"
    When I click on "Content bank" "link"
    Then "[data-action=Add-content]" "css_element" should not exist

  Scenario: Users can see the Add button if there is content type available for creation
    Given I log in as "admin"
    And I follow "Manage private files..."
    And I upload "h5p/tests/fixtures/filltheblanks.h5p" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I click on "Content bank" "link"
    And I click on "Upload" "link"
    And I click on "Choose a file..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "filltheblanks.h5p" "link"
    And I click on "Select this file" "button"
    And I click on "Save changes" "button"
    When I click on "Content bank" "link"
    And I click on "filltheblanks.h5p" "link"
    And I click on "Cancel" "link"
    Then I click on "[data-action=Add-content]" "css_element"
    And I should see "Fill in the Blanks"

  Scenario: Users can edit content if they have the required permission
    Given I log in as "admin"
    And I follow "Manage private files..."
    And I upload "h5p/tests/fixtures/filltheblanks.h5p" file to "Files" filemanager
    And I click on "Save changes" "button"
    And I click on "Content bank" "link"
    And I click on "Upload" "link"
    And I click on "Choose a file..." "button"
    And I click on "Private files" "link" in the ".fp-repo-area" "css_element"
    And I click on "filltheblanks.h5p" "link"
    And I click on "Select this file" "button"
    And I click on "Save changes" "button"
    When I click on "Content bank" "link"
    And I click on "filltheblanks.h5p" "link"
    Then I click on "Edit" "link"
    And I switch to "h5p-editor-iframe" class iframe
    And I switch to the main frame
    And I click on "Cancel" "button"
    And I should see "filltheblanks.h5p" in the "h1" "css_element"

  Scenario: Users can create new content if they have the required permission
    Given I log in as "admin"
    And I navigate to "H5P > Manage H5P content types" in site administration
    And I upload "h5p/tests/fixtures/filltheblanks.h5p" file to "H5P content type" filemanager
    And I click on "Upload H5P content types" "button" in the "#fitem_id_uploadlibraries" "css_element"
    And I should see "H5P content types uploaded successfully"
    When I click on "Content bank" "link"
    And I click on "[data-action=Add-content]" "css_element"
    Then I click on "Fill in the Blanks" "link"
    And I switch to "h5p-editor-iframe" class iframe
    And I switch to the main frame
    And I click on "Cancel" "button"

  Scenario: Users can't edit content if they don't have the required permission
    Given the following "users" exist:
      | username | firstname | lastname | email             |
      | teacher1 | Teacher   | 1        | user1@example.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "course enrolments" exist:
      | user     | course | role              |
      | teacher1 | C1     | editingteacher    |
    And I log in as "admin"
    And I navigate to "H5P > Manage H5P content types" in site administration
    And I upload "h5p/tests/fixtures/filltheblanks.h5p" file to "H5P content type" filemanager
    And I click on "Upload H5P content types" "button" in the "#fitem_id_uploadlibraries" "css_element"
    And I should see "H5P content types uploaded successfully"
    And I log out
    And I log in as "teacher1"
    And I am on "Course 1" course homepage with editing mode on
    And I click on "Content bank" "link"
    And "[data-action=Add-content]" "css_element" should exist
    When the following "permission overrides" exist:
      | capability                       | permission | role           | contextlevel | reference |
      | moodle/contentbank:useeditor     | Prohibit   | editingteacher | System       |           |
    And I reload the page
    Then "[data-action=Add-content]" "css_element" should not exist

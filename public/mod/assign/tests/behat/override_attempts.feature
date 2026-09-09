@mod @mod_assign @javascript
Feature: Saved assignment overrides allow extra attempts
  In order to give selected students another submission opportunity
  As a teacher
  I need saved user and group limits to govern individual and bulk actions

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email |
      | teacher1 | Teacher | One | teacher1@example.com |
      | student1 | Student | One | student1@example.com |
      | student2 | Student | Two | student2@example.com |
    And the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |
    And the following "course enrolments" exist:
      | user | course | role |
      | teacher1 | C1 | editingteacher |
      | student1 | C1 | student |
      | student2 | C1 | student |
    And the following "groups" exist:
      | name | course | idnumber |
      | Group 1 | C1 | G1 |
      | Group 2 | C1 | G2 |
    And the following "group members" exist:
      | user | group |
      | student1 | G1 |
    And the following "activities" exist:
      | activity | name | course | maxattempts | attemptreopenmethod | submissiondrafts | assignsubmission_onlinetext_enabled | assignsubmission_file_enabled |
      | assign | Attempts assignment | C1 | 1 | manual | 0 | 1 | 0 |
    And the following "mod_assign > submissions" exist:
      | assign | user | onlinetext |
      | Attempts assignment | student1 | First submission |
      | Attempts assignment | student2 | Other submission |

  Scenario Outline: A saved override permits an individual extra attempt
    Given I am on the "Attempts assignment" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "<mode>" from the "jump" singleselect
    And I press "<button>"
    And I set the following fields to these values:
      | <target> | <value> |
      | Allowed attempts | 2 |
    And I press "Save"
    And I navigate to "Submissions" in current page administration
    When I open the action menu in "Student One" "table_row"
    And I follow "Allow another attempt"
    And I am on the "Attempts assignment" Activity page logged in as student1
    Then I should see "This is attempt 2 ( 2 attempts allowed )."
    And I press "Add a new attempt based on previous submission"
    And I press "Save changes"
    And I am on the "Attempts assignment" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I open the action menu in "Student One" "table_row"
    And I should not see "Allow another attempt"
    And I am on the "Attempts assignment" Activity page logged in as student2
    And "Add a new attempt" "button" should not exist

    Examples:
      | mode | button | target | value |
      | User overrides | Add user override | Override user | Student One |
      | Group overrides | Add group override | Override group | Group 1 |

  Scenario Outline: Duplicate and reset a saved attempt limit
    Given I am on the "Attempts assignment" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "<mode>" from the "jump" singleselect
    And I press "<button>"
    And I set the following fields to these values:
      | <target> | <source> |
      | Allowed attempts | 4 |
    And I press "Save"
    When I click on "copy" "link"
    And I set the field "<target>" to "<destination>"
    Then the field "Allowed attempts" matches value "4"
    And I press "Save"
    And I click on "Edit" "link" in the "<destination>" "table_row"
    And the field "Allowed attempts" matches value "4"
    And I press "Revert to assignment defaults"
    And the field "Allowed attempts" matches value "1"
    And I set the field "Due date" to "##1 Jan 2030 08:00##"
    And I press "Save"
    And I click on "Edit" "link" in the "<destination>" "table_row"
    And the field "Allowed attempts" matches value "1"
    And I press "Cancel"

    Examples:
      | mode | button | target | source | destination |
      | User overrides | Add user override | Override user | Student One | Student Two |
      | Group overrides | Add group override | Override group | Group 1 | Group 2 |

  Scenario Outline: A saved override permits a bulk extra attempt
    Given I am on the "Attempts assignment" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "<mode>" from the "jump" singleselect
    And I press "<button>"
    And I set the following fields to these values:
      | <target> | <value> |
      | Allowed attempts | 2 |
    And I press "Save"
    And I navigate to "Submissions" in current page administration
    And I click on "Student One" "checkbox"
    When I click on "Grant attempt" "button" in the "sticky-footer" "region"
    And I click on "Grant attempt" "button" in the "Allow another attempt" "dialogue"
    And I am on the "Attempts assignment" Activity page logged in as student1
    Then I should see "This is attempt 2 ( 2 attempts allowed )."
    And I am on the "Attempts assignment" Activity page logged in as student2
    And "Add a new attempt" "button" should not exist

    Examples:
      | mode | button | target | value |
      | User overrides | Add user override | Override user | Student One |
      | Group overrides | Add group override | Override group | Group 1 |

  Scenario: Automatic reopening honours a saved user attempt limit
    Given the following "activity" exists:
      | activity | assign |
      | course | C1 |
      | name | Automatic attempts assignment |
      | maxattempts | 1 |
      | attemptreopenmethod | untilpass |
      | gradepass | 50 |
      | submissiondrafts | 0 |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
    And the following "mod_assign > submissions" exist:
      | assign | user | onlinetext |
      | Automatic attempts assignment | student1 | First automatic submission |
    And the following "mod_assign > user overrides" exist:
      | assignment | user | maxattempts |
      | Automatic attempts assignment | student1 | 2 |
    And I am on the "Automatic attempts assignment" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I click on "Quick grading" "checkbox"
    And I set the field "User grade" to "49.0"
    When I click on "Save" "button" in the "sticky-footer" "region"
    And I should see "The grade changes were saved"
    And I press "Continue"
    Then I should see "Reopened" in the "Student One" "table_row"
    And I am on the "Automatic attempts assignment" Activity page logged in as student1
    And I should see "This is attempt 2 ( 2 attempts allowed )."

  Scenario: A group attempt override governs a team submission
    Given the following "group members" exist:
      | user | group |
      | student2 | G1 |
    And the following "activity" exists:
      | activity | assign |
      | course | C1 |
      | name | Team attempts assignment |
      | maxattempts | 1 |
      | attemptreopenmethod | manual |
      | submissiondrafts | 0 |
      | groupmode | 1 |
      | teamsubmission | 1 |
      | assignsubmission_onlinetext_enabled | 1 |
      | assignsubmission_file_enabled | 0 |
    And the following "mod_assign > submissions" exist:
      | assign | user | onlinetext |
      | Team attempts assignment | student1 | First team submission |
    And the following "mod_assign > group overrides" exist:
      | assignment | group | maxattempts |
      | Team attempts assignment | G1 | 2 |
    And I am on the "Team attempts assignment" Activity page logged in as teacher1
    And I navigate to "Submissions" in current page administration
    And I click on "Student One" "checkbox"
    When I click on "Grant attempt" "button" in the "sticky-footer" "region"
    And I click on "Grant attempt" "button" in the "Allow another attempt" "dialogue"
    Then I should see "Reopened" in the "Student Two" "table_row"
    And I am on the "Team attempts assignment" Activity page logged in as student2
    And I should see "This is attempt 2 ( 2 attempts allowed )."

  Scenario: Ordered groups apply before a dates-only user override and deleted overrides fall back
    Given the following "group members" exist:
      | user | group |
      | student1 | G2 |
    And the following "mod_assign > group overrides" exist:
      | assignment | group | maxattempts |
      | Attempts assignment | G1 | 2 |
      | Attempts assignment | G2 | 3 |
    And the following "mod_assign > user overrides" exist:
      | assignment | user | duedate |
      | Attempts assignment | student1 | ##1 Jan 2030 08:00## |
    When I am on the "Attempts assignment" Activity page logged in as student1
    Then I should not see "Attempt number"
    And I am on the "Attempts assignment" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I click on "Delete" "link" in the "Student One" "table_row"
    And I press "Continue"
    And I am on the "Attempts assignment" Activity page logged in as student1
    And I should see "This is attempt 1 ( 2 attempts allowed )."
    And I am on the "Attempts assignment" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I select "Group overrides" from the "jump" singleselect
    And I click on "Delete" "link" in the "Group 1" "table_row"
    And I press "Continue"
    And I am on the "Attempts assignment" Activity page logged in as student1
    And I should see "This is attempt 1 ( 3 attempts allowed )."

  @accessibility
  Scenario: Unlimited and deleted user overrides remain usable on a mobile viewport
    Given I am on the "Attempts assignment" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I change window size to "small"
    And I press "Add user override"
    And the "Allowed attempts" "select" should meet accessibility standards
    And I set the following fields to these values:
      | Override user | Student One |
      | Allowed attempts | Unlimited |
    And I press "Save"
    And the "Student One" "table_row" should meet accessibility standards
    And I am on the "Attempts assignment" Activity page logged in as student1
    Then I should see "This is attempt 1."
    And I am on the "Attempts assignment" Activity page logged in as teacher1
    And I navigate to "Overrides" in current page administration
    And I click on "Delete" "link" in the "Student One" "table_row"
    And I press "Continue"
    And I am on the "Attempts assignment" Activity page logged in as student1
    And I should not see "Attempt number"

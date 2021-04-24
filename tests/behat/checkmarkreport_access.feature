@local @local_checkmarkreport @amc
Feature: The block checkmarkreport should be visible and accessable if there is at least one checkmark activity present in the course.
  If this is not the case, the block is not visible.

  @javascript
  Scenario: Checkmark report link doesn't exist is no checkmark activity is present in course (1.3)
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@teacher.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    Then I should not see "Checkmark report"

  @javascript
  Scenario: Navigate to checkmark report if one checkmark activity is present (1.1)
    Given the following "courses" exist:
      | fullname | shortname | category | groupmode |
      | Course 1 | C1        | 0        | 0         |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | 1        | teacher1@teacher.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | C1     | editingteacher |
    And the following "activities" exist:
      | activity  | course | idnumber | name        | intro         |
      | checkmark | C1     | CM1      | Checkmark 1 | Description 1 |
    And I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark report"
    Then I should see "Overview"

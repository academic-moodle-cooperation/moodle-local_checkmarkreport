@local @local_checkmarkreport @amc
Feature: The overview tab of checkmarkreport gives the teacher an overview over the happenings in all checkmark activities in a given course
Background:
  Given the following "courses" exist:
    | fullname | shortname | category | groupmode |
    | Course 1 | C1        | 0        | 1         |
  And the following "users" exist:
    | username | firstname | lastname | email                | idnumber |
    | teacher1 | Teacher   | 1        | teacher1@example.com | 99       |
    | student1 | Student   | 1        | student1@example.com | 1        |
    | student2 | Student   | 2        | student2@example.com | 2        |
    | student3 | Student   | 3        | student3@example.com | 3        |
    | student4 | Student   | 4        | student4@example.com | 4        |
    | student5 | Student   | 5        | student5@example.com | 5        |
    | student6 | Student   | 6        | student6@example.com | 6        |
    | student7 | Student   | 7        | student7@example.com | 7        |
    | student8 | Student   | 8        | student8@example.com | 8        |
  And the following "groups" exist:
    | name | course | idnumber |
    | Group 1 | C1 | G1 |
    | Group 2 | C1 | G2 |
    | Group 3 | C1 | G3 |
    | Group 4 | C1 | G4 |
  And the following "groupings" exist:
    | name | course | idnumber |
    | Grouping 1 | C1 | GG1 |
    | Grouping 2 | C1 | GG2 |
  And the following "grouping groups" exist:
    | grouping | group |
    | GG1      | G1    |
    | GG1      | G2    |
    | GG2      | G3    |
    | GG2      | G4    |
  And the following "course enrolments" exist:
    | user     | course | role           |
    | teacher1 | C1     | editingteacher |
    | student1 | C1     | student        |
    | student2 | C1     | student        |
    | student3 | C1     | student        |
    | student4 | C1     | student        |
    | student5 | C1     | student        |
    | student6 | C1     | student        |
    | student7 | C1     | student        |
    | student8 | C1     | student        |
  And the following "group members" exist:
    | user | group |
    | student1 | G1 |
    | student2 | G2 |
    | student3 | G3 |
    | student4 | G4 |
    | student5 | G1 |
    | student5 | G2 |
    | student6 | G3 |
    | student6 | G4 |
    | student7 | G1 |
    | student7 | G2 |
    | student7 | G3 |
    | student7 | G4 |
  And the following "activities" exist:
    | activity  | course | idnumber | name        | intro         | timeavailable | timedue | visible   |
    | checkmark | C1     | CM1      | Checkmark 1 | Description 1 | 0             | 0       | 1         |
    | checkmark | C1     | CM2      | Checkmark 2 | Description 2 | 0             | 0       | 1         |
    | checkmark | C1     | CM3      | Checkmark 3 | Description 3 | 0             | 0       | 0         |
  And the following config values are set as admin:
    | showuseridentity | idnumber,email |

  @javascript
  Scenario: Open checkmarkreport and display all students and their check in a tabular manner (2.1)
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark report"
    Then the following should exist in the "overview" table:
      | First name / Surname  | ID number | Email address         |
      | Student 1             | 1         | student1@example.com  |
      | Student 2             | 2         | student2@example.com  |
      | Student 3             | 3         | student3@example.com  |
      | Student 4             | 4         | student4@example.com  |
      | Student 5             | 5         | student5@example.com  |
      | Student 6             | 6         | student6@example.com  |
      | Student 7             | 7         | student7@example.com  |
      | Student 8             | 8         | student8@example.com  |

  @javascript
  Scenario: Teacher filters overview by group (2.2,2.5)
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark report"
    Then the following should exist in the "overview" table:
      | First name / Surname  | ID number | Email address         |
      | Student 1             | 1         | student1@example.com  |
      | Student 2             | 2         | student2@example.com  |
      | Student 3             | 3         | student3@example.com  |
      | Student 4             | 4         | student4@example.com  |
      | Student 5             | 5         | student5@example.com  |
      | Student 6             | 6         | student6@example.com  |
      | Student 7             | 7         | student7@example.com  |
      | Student 8             | 8         | student8@example.com  |
    When I set the field "Groups" to "Group 1"
    And I press "Update"
    Then the following should exist in the "overview" table:
      | First name / Surname  | ID number | Email address         |
      | Student 1             | 1         | student1@example.com  |
      | Student 5             | 5         | student5@example.com  |
      | Student 7             | 7         | student7@example.com  |
    When I set the field "Groups" to "Group 3"
    And I press "Update"
    Then the following should exist in the "overview" table:
      | First name / Surname  | ID number | Email address         |
      | Student 3             | 3         | student3@example.com  |
      | Student 6             | 6         | student6@example.com  |
      | Student 7             | 7         | student7@example.com  |
    When I set the field "Groups" to "All Groups"
    And I press "Update"
    Then the following should exist in the "overview" table:
      | First name / Surname  | ID number | Email address         |
      | Student 1             | 1         | student1@example.com  |
      | Student 2             | 2         | student2@example.com  |
      | Student 3             | 3         | student3@example.com  |
      | Student 4             | 4         | student4@example.com  |
      | Student 5             | 5         | student5@example.com  |
      | Student 6             | 6         | student6@example.com  |
      | Student 7             | 7         | student7@example.com  |
      | Student 8             | 8         | student8@example.com  |

  @javascript
  Scenario: Teacher filters overview by grouping (2.2,2.4)
    Given I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark report"
    Then the following should exist in the "overview" table:
      | First name / Surname  | ID number | Email address         |
      | Student 1             | 1         | student1@example.com  |
      | Student 2             | 2         | student2@example.com  |
      | Student 3             | 3         | student3@example.com  |
      | Student 4             | 4         | student4@example.com  |
      | Student 5             | 5         | student5@example.com  |
      | Student 6             | 6         | student6@example.com  |
      | Student 7             | 7         | student7@example.com  |
      | Student 8             | 8         | student8@example.com  |
    When I set the field "Groupings" to "Grouping 1"
    And I press "Update"
    Then the following should exist in the "overview" table:
      | First name / Surname  | ID number | Email address         |
      | Student 1             | 1         | student1@example.com  |
      | Student 2             | 2         | student2@example.com  |
      | Student 5             | 5         | student5@example.com  |
      | Student 7             | 7         | student7@example.com  |
    When I set the field "Groupings" to "Grouping 2"
    And I press "Update"
    Then the following should exist in the "overview" table:
      | First name / Surname  | ID number | Email address         |
      | Student 3             | 3         | student3@example.com  |
      | Student 4             | 4         | student4@example.com  |
      | Student 6             | 6         | student6@example.com  |
      | Student 7             | 7         | student7@example.com  |
    When I set the field "Groupings" to "All Groupings"
    And I press "Update"
    Then the following should exist in the "overview" table:
      | First name / Surname  | ID number | Email address         |
      | Student 1             | 1         | student1@example.com  |
      | Student 2             | 2         | student2@example.com  |
      | Student 3             | 3         | student3@example.com  |
      | Student 4             | 4         | student4@example.com  |
      | Student 5             | 5         | student5@example.com  |
      | Student 6             | 6         | student6@example.com  |
      | Student 7             | 7         | student7@example.com  |
      | Student 8             | 8         | student8@example.com  |

    @javascript
    Scenario: Group and grouping selectors are only visible if course is in a group mode (2.3)
      Given the following "courses" exist:
        | fullname | shortname | category | groupmode |
        | Course 2 | C2        | 0        | 0         |
      And the following "course enrolments" exist:
        | user     | course | role           |
        | teacher1 | C2     | editingteacher |
        | student1 | C2     | student        |
      And the following "activities" exist:
        | activity  | course | idnumber | name        | intro         | timeavailable | timedue |
        | checkmark | C2     | CM4      | Checkmark 4 | Description 4 | 0             | 0       |
      And I log in as "teacher1"
      And I am on "Course 2" course homepage
      And I follow "Checkmark report"
      Then I should not see "Grouping"
      And I should not see "Group"

    @javascript
    Scenario: Teacher filters overview by checkmark (2.6)
      Given I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Checkmark report"
      When I set the field "Checkmarks" to "Checkmark 1"
      And I press "Update"
      Then I should see "1" occurrences of "Checkmark 1" in the "overview" "table"c
      Then I should not see "Checkmark 2" in the "overview" "table"
      Then I should not see "Checkmark 3" in the "overview" "table"
      When I set the field "Checkmarks" to "Checkmark 2"
      And I press "Update"
      Then I should see "1" occurrences of "Checkmark 2" in the "overview" "table"
      Then I should not see "Checkmark 1" in the "overview" "table"
      Then I should not see "Checkmark 3" in the "overview" "table"

    @javascript
    Scenario: Teacher changes the visible checkmarks and should only see the updated sums (2.8)
      Given the following "mod_checkmark > submissions" exist:
        | checkmark   | user      | example1 | example2 | example3 | example4 | example5 | example6 | example7 | example8 | example9 | example10 |
        | Checkmark 1 | student1  | 1        | 1        | 1        | 1        | 1        | 1        | 0        | 0        | 0        | 0         |
        | Checkmark 2 | student1  | 1        | 1        | 1        | 0        | 0        | 0        | 0        | 0        | 0        | 0         |
      When I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Checkmark report"
      And I set the following fields to these values:
        | Show x/y examples | 1 |
        | Show % of examples/grades | 1 |
      And I press "Update"
      Then I should see "1" occurrences of "9 / 30" in the "overview" "table"
      And I should see "1" occurrences of "6 / 10" in the "overview" "table"
      And I should see "1" occurrences of "3 / 10" in the "overview" "table"
      And I should see "7" occurrences of "0 / 30" in the "overview" "table"
      And I should see "22" occurrences of "0 / 10" in the "overview" "table"
      Then I should see "1" occurrences of "30% (0 %)" in the "overview" "table"
      And I should see "1" occurrences of "60% (-)" in the "overview" "table"
      And I should see "1" occurrences of "30% (-)" in the "overview" "table"
      And I should see "8" occurrences of "0% (0 %)" in the "overview" "table"
      And I should see "24" occurrences of "0% (-)" in the "overview" "table"
      When I set the field "Checkmarks" to "Checkmark 1"
      And I press "Update"
      Then I should see "2" occurrences of "6 / 10" in the "overview" "table"
      And I should see "14" occurrences of "0 / 10" in the "overview" "table"
      Then I should see "1" occurrences of "60% (0 %)" in the "overview" "table"
      And I should see "1" occurrences of "60% (-)" in the "overview" "table"
      And I should see "8" occurrences of "0% (0 %)" in the "overview" "table"
      And I should see "8" occurrences of "0% (-)" in the "overview" "table"

    @javascript
    Scenario: 'Show attendances' check is only displayed if at least one checkmark is tracking attendances (2.17)
      When I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Checkmark report"
      Then I should not see "Show attendances"
      And I log out
      Given the following "activities" exist:
        | activity  | course | idnumber | name        | intro         | timeavailable | timedue | visible   | trackattendance |
        | checkmark | C1     | CM4      | Checkmark 4 | Description 4 | 0             | 0       | 0         | 1               |
      When I log in as "teacher1"
      And I am on "Course 1" course homepage
      And I follow "Checkmark report"
      Then I should see "Show attendances"
      And I log out

  @javascript
  Scenario: 'Show presentation grade' and 'Show number of graded presentations' checks are only displayed if at least one checkmark is tracking presentation grades (2.17)
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark report"
    Then I should not see "Show presentation grade"
    And I log out
    Given the following "activities" exist:
      | activity  | course | idnumber | name        | intro         | timeavailable | timedue | visible   | presentationgrading | presentationgrade |
      | checkmark | C1     | CM4      | Checkmark 4 | Description 4 | 0             | 0       | 0         | 1                   | 1                 |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark report"
    Then I should see "Show presentation grade"
    And I should see "Show number of graded presentations"
    And I log out

  @javascript @currentdev
  Scenario: The sums of grade, attendance and presentationgrade are calculated correctly also when single checkmarks are hidden (2.9; 2.10; 2.11)
    Given the following "activities" exist:
      | activity  | course | idnumber | name        | intro         | timeavailable | timedue | visible   | trackattendance | presentationgrading | presentationgrade |
      | checkmark | C1     | CM4      | Checkmark 4 | Description 4 | 0             | 0       | 1         | 1               | 1                   | 100               |
      | checkmark | C1     | CM5      | Checkmark 5 | Description 5 | 0             | 0       | 1         | 1               | 1                   | 100               |
      | checkmark | C1     | CM6      | Checkmark 6 | Description 6 | 0             | 0       | 1         | 1               | 1                   | 100               |
    Given the following "mod_checkmark > submissions" exist:
      | checkmark   | user      | example1 | example2 | example3 | example4 | example5 | example6 | example7 | example8 | example9 | example10 |
      | Checkmark 4 | student1  | 1        | 1        | 1        | 1        | 1        | 1        | 0        | 0        | 0        | 0         |
      | Checkmark 4 | student2  | 1        | 1        | 1        | 0        | 0        | 0        | 0        | 0        | 0        | 0         |
      | Checkmark 5 | student1  | 1        | 1        | 1        | 1        | 1        | 1        | 0        | 0        | 0        | 0         |
      | Checkmark 5 | student2  | 1        | 1        | 1        | 0        | 0        | 0        | 0        | 0        | 0        | 0         |
      | Checkmark 6 | student1  | 1        | 1        | 1        | 1        | 1        | 1        | 0        | 0        | 0        | 0         |
      | Checkmark 6 | student2  | 1        | 1        | 1        | 0        | 0        | 0        | 0        | 0        | 0        | 0         |
    And the following "mod_checkmark > feedbacks" exist:
      | checkmark   | user      | attendance  | feedback              | grade | presentationgrade | presentationfeedback          |
      | Checkmark 4 | student1  | Attendant   | Lel so bad            | 81    | 100               | OMG that was so good!         |
      | Checkmark 4 | student2  | Absent      | Laurin did it better  | 11    |  10               | This was better than Laurin's |
      | Checkmark 5 | student1  | Absent      | Some feedback         | 80    |  50               | Some presenationfeedback      |
      | Checkmark 5 | student2  | Attendant   | Some feedback         | 10    |  10               | Some presenationfeedback      |
      | Checkmark 6 | student1  | Attendant   | Some feedback         | 20    |  50               | Some presenationfeedback      |
      | Checkmark 6 | student2  | Unknown     | Some feedback         | 10    |  10               | Some presenationfeedback      |
    When I log in as "teacher1"
    And I am on "Course 1" course homepage
    And I follow "Checkmark report"
    And I set the following fields to these values:
      | Show examples                       | 0 |
      | Show grade                          | 1 |
      | Show attendances                    | 1 |
      | Show presentation grade             | 1 |
      | Show number of graded presentations | 1 |
    And I press "Update"
    # Sum Grades
    Then I should see "1" occurrences of "181/600" in the "overview" "table"
    And I should see "1" occurrences of "31/600" in the "overview" "table"
    And I should see "6" occurrences of "0/600" in the "overview" "table"
    # Sum Attendances + Count presentation grades
    Then I should see "2" occurrences of "3/3" in the "overview" "table"
    And I should see "1" occurrences of "2/3" in the "overview" "table"
    And I should see "1" occurrences of "1/3" in the "overview" "table"
    And I should see "20" occurrences of "0/3" in the "overview" "table"
    # Sum presentation grades
    Then I should see "1" occurrences of "200/300" in the "overview" "table"
    And I should see "1" occurrences of "30/300" in the "overview" "table"
    And I should see "8" occurrences of "0/300" in the "overview" "table"
    # Single grades + presentation grades
    Then I should see "1" occurrences of "81/100" in the "overview" "table"
    And I should see "1" occurrences of "11/100" in the "overview" "table"
    And I should see "1" occurrences of "80/100" in the "overview" "table"
    And I should see "5" occurrences of "10/100" in the "overview" "table"
    And I should see "2" occurrences of "50/100" in the "overview" "table"
    And I should see "1" occurrences of "100/100" in the "overview" "table"
    # Dashes in the remaining cells
    And I should see "60" occurrences of "-" in the "overview" "table"
    When I set the field "Checkmarks" to "Checkmark 4"
    And I press "Update"
    # Sum Grades + single grades
    Then I should see "2" occurrences of "81/100" in the "overview" "table"
    And I should see "2" occurrences of "11/100" in the "overview" "table"
    And I should see "16" occurrences of "0/100" in the "overview" "table"
    # Sum Attendances + Count presentation grades
    Then I should see "7" occurrences of "1/1" in the "overview" "table"
    And I should see "29" occurrences of "0/1" in the "overview" "table"
    # Presentation grades
    Then I should see "2" occurrences of "100/100" in the "overview" "table"
    And I should see "2" occurrences of "10/100" in the "overview" "table"
    # Dashes in the remaining cells
    And I should see "12" occurrences of "-" in the "overview" "table"
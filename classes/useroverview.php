<?php
// This file is part of local_checkmarkreport for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Contains local_checkmarkreport_useroverview class (handling checkmarkreport useroverview content)
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager, Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * local_checkmarkreport_useroverview class, handles checkmarkreport useroverview content
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager, Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_checkmarkreport_useroverview extends local_checkmarkreport_base implements renderable {

    /** @var string classes to assign to the reporttables */
    protected $tableclass = 'table table-condensed table-bordered useroverview';

    /**
     * Constructor
     *
     * @param int $id course id
     * @param int[] $groupings (optional) groupings to include
     * @param int[] $groups (optional) groups to include
     * @param int[] $users (optional) users to include
     */
    public function __construct($id, $groupings = [0], $groups = [0], $users = [0]) {
        global $DB;

        if (!in_array(0, $groupings)) {
            list($insql, $params) = $DB->get_in_or_equal($groupings);
            $grpgsgrps = $DB->get_fieldset_select('groupings_groups', 'DISTINCT groupid', 'groupingid ' . $insql, $params);
            if (in_array(0, $groups) || empty($groups)) {
                $groups = $grpgsgrps;
            } else {
                $groups = array_intersect($groups, $grpgsgrps);
            }
        }

        if (!in_array(0, $groups)) {
            // Remove all users who aren't part of the groups!
            list($insql, $params) = $DB->get_in_or_equal($groups);
            $grpusers = $DB->get_fieldset_select('groups_members', 'DISTINCT userid', 'groupid ' . $insql, $params);
            if (in_array(0, $users) || empty($users)) {
                $users = $grpusers;
            } else {
                $users = array_intersect($users, $grpusers);
            }
        }

        parent::__construct($id, $groups, $users, [0]);
    }

    /**
     * get html table object representing report data for 1 user
     *
     * @param object $userdata
     * @param boolean $forexport
     * @return html_table report part for this user as html_table object
     */
    public function get_table($userdata, $forexport = false) {
        global $DB, $PAGE, $OUTPUT;
        $context = context_course::instance($this->courseid);

        $showexamples = get_user_preferences('checkmarkreport_showexamples', 1);
        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showpoints = get_user_preferences('checkmarkreport_showpoints');
        $showattendances = get_user_preferences('checkmarkreport_showattendances');
        $showpresgrades = get_user_preferences('checkmarkreport_showpresentationgrades');
        $showprescount = get_user_preferences('checkmarkreport_showpresentationcount');
        $signature = get_user_preferences('checkmarkreport_signature');

        $table = new \local_checkmarkreport\html_table_colgroups();

        $users = [];

        $table->id = "attempts-$userdata->id";
        $table->attributes['class'] = $this->tableclass;

        $tableheaders = [];
        $tablecolumns = [];
        $table->colgroups = [];
        $table->align = [];

        $sortlink = $this->get_sortlink('checkmark',
                get_string('modulename', 'checkmark'),
                $PAGE->url);
        $tableheaders['checkmark'] = new html_table_cell($sortlink);
        $tableheaders['checkmark']->header = true;
        $table->align['checkmark'] = 'center';
        $tablecolumns[] = 'checkmark';
        $table->colgroups[] = [
                'span' => '1',
                'class' => 'checkmark'
        ];
        $table->colclasses['checkmark'] = 'checkmark';

        if (!empty($showexamples)) {
            $tableheaders['examples'] = new html_table_cell(get_string('example', 'local_checkmarkreport'));
            $tableheaders['examples']->header = true;
            $table->align['examples'] = 'left';
            $tablecolumns[] = 'examples';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'examples'
            ];
            $table->colclasses['examples'] = 'examples';
        }

        if (!empty($showabs) || !empty($showrel)) {
            $tableheaders['checked'] = new html_table_cell(get_string('status', 'local_checkmarkreport'));
            $tableheaders['checked']->header = true;
            $table->align['checked'] = 'center';
            $tablecolumns[] = 'checked';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'checked'
            ];
            $table->colclasses['checked'] = 'checked';
        }

        if (!empty($showgrade)) {
            $tableheaders['points'] = new html_table_cell(get_string('grade', 'local_checkmarkreport') .
                    $OUTPUT->help_icon('grade_useroverview', 'local_checkmarkreport'));
            $tableheaders['points']->header = true;
            $table->align['points'] = 'center';
            $tablecolumns[] = 'points';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'points'
            ];
            $table->colclasses['points'] = 'points';
        }

        if (!empty($showattendances) && $this->attendancestracked()) {
            $tableheaders['attendance'] = new html_table_cell(get_string('attendance', 'checkmark'));
            $tableheaders['attendance']->header = true;
            $table->align['attendance'] = 'left';
            $tablecolumns[] = 'attendance';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'attendance'
            ];
            $table->colclasses['attendance'] = 'attendance';
        }

        if (!empty($showpresgrades) && $this->presentationsgraded()) {
            $tableheaders['presentationgrade'] = new html_table_cell(get_string('presentationgrade', 'checkmark'));
            $tableheaders['presentationgrade']->header = true;
            $table->align['presentationgrade'] = 'right';
            $tablecolumns[] = 'presentationgrade';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'presentationgrade'
            ];
            $table->colclasses['presentationgrade'] = 'presentationgrade';
        }
        if (!empty($showprescount) && $this->presentationsgraded()) {
            $tableheaders['presentationsgraded'] = new html_table_cell('# ' . get_string('presentationgrade', 'checkmark'));
            $tableheaders['presentationsgraded']->header = true;
            $table->align['presentationsgraded'] = 'right';
            $tablecolumns[] = 'presentationsgraded';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'presentationsgraded'
            ];
            $table->colclasses['presentationsgraded'] = 'presentationsgraded';
        }

        $table->head = [];
        $table->head[0] = new html_table_row();
        $table->head[0]->cells = $tableheaders;

        $instances = $this->get_courseinstances();
        $i = 0;

        $attendantstr = strtolower(get_string('attendant', 'checkmark'));
        $absentstr = strtolower(get_string('absent', 'checkmark'));
        $unknownstr = strtolower(get_string('unknown', 'checkmark'));

        foreach ($userdata->instancedata as $key => $instancedata) {
            $instance = $instances[$key];
            $idx = 0;
            if (empty($users[$instancedata->finalgrade->usermodified])) {
                $conditions = ['id' => $instancedata->finalgrade->usermodified];
                $userobj = $DB->get_record('user', $conditions, 'id, ' . implode(', ', get_all_user_name_fields()));
                $usermodified = $instancedata->finalgrade->usermodified;
                $users[$usermodified] = fullname($userobj, has_capability('moodle/site:viewfullnames', $context));
            }
            if (empty($users[$userdata->id])) {
                $conditions = ['id' => $userdata->id];
                $userobj = $DB->get_record('user', $conditions, 'id, ' . implode(', ', get_all_user_name_fields()));
                $userid = $userdata->id;
                $users[$userid] = fullname($userobj, has_capability('moodle/site:viewfullnames', $context));
            }
            if (!isset($examplenames[$instance->id])) {
                $examplenames[$instance->id] = $DB->get_records('checkmark_examples', ['checkmarkid' => $instance->id]);
            }
            $gradepresentation = $this->gradepresentations($instance->id);
            if ($gradepresentation && !$gradepresentation->presentationgrade) {
                // Prevent comment only presentationgrades to mess with table!
                $gradepresentation = false;
            } else if ($gradepresentation && $gradepresentation->presentationgradebook) {
                if (empty($users[$instancedata->finalpresgrade->usermodified])) {
                    $conditions = ['id' => $instancedata->finalpresgrade->usermodified];
                    $userobj = $DB->get_record('user', $conditions, 'id, ' . implode(', ', get_all_user_name_fields()));
                    $usermodified = $instancedata->finalpresgrade->usermodified;
                    $users[$usermodified] = fullname($userobj, has_capability('moodle/site:viewfullnames', $context));
                }
            }

            // We continue this row with the first example data if there are examples and they are shown, else we display sums!
            $row = [];

            $instanceurl = new moodle_url('/mod/checkmark/view.php',
                    ['id' => $instance->coursemodule]);
            $instancelink = html_writer::link($instanceurl, $instance->name);
            $row['checkmark'] = new html_table_cell($instancelink);
            $row['checkmark']->header = true;
            $row['checkmark']->style = ' text-align: left; ';

            if (!empty($showexamples) && (count($userdata->instancedata[$instance->id]->examples) > 0)) {
                foreach ($userdata->instancedata[$instance->id]->examples as $exid => $example) {
                    if ($idx != 0) {
                        $row['checkmark'] = null;
                    }
                    if (!empty($showexamples)) {
                        $row['examples'] = new html_table_cell($examplenames[$instance->id][$exid]->name .
                                ' (' . $examplenames[$instance->id][$exid]->grade . ')');
                    }
                    if (!empty($showabs) || !empty($showrel)) {
                        if ($showpoints) {
                            $row['checked'] = new html_table_cell($example->print_pointsstring());
                        } else {
                            if ($forexport) {
                                $row['checked'] = new html_table_cell($example->get_examplestate_for_export_with_colors());
                            } else {
                                $row['checked'] = new html_table_cell($example->print_examplestate());
                            }
                        }
                    }
                    if (!empty($showgrade)) {
                        $row['points'] = new html_table_cell($example->get_checked_of_max_points());
                    }
                    if ($idx == 0) {
                        if (!empty($showattendances) && $this->attendancestracked()) {
                            if ($this->tracksattendance($instance->id)) {
                                $attendance = $userdata->instancedata[$instance->id]->attendance;
                                if ($attendance == 1) {
                                    $attendancestr = $attendantstr;
                                    $character = '✓';
                                } else if (($attendance == 0) && ($attendance != null)) {
                                    $attendancestr = $absentstr;
                                    $character = '✗';
                                } else {
                                    $attendancestr = $unknownstr;
                                    $character = '?';
                                }
                                $attendance = checkmark_get_attendance_symbol($userdata->instancedata[$instance->id]->attendance) .
                                        $attendancestr;
                            } else {
                                $attendance = '';
                                $character = '';
                            }
                            $row['attendance'] = new html_table_cell($attendance);
                            $row['attendance']->character = $character;
                        }
                        if (!empty($showpresgrades) && $this->presentationsgraded()) {
                            if ($gradepresentation && $gradepresentation->presentationgradebook) {
                                $presentationgrade = $userdata->instancedata[$instance->id]->formattedpresgrade;
                                $finalgrade = $userdata->instancedata[$instance->id]->finalpresgrade;
                                $overridden = $userdata->instancedata[$instance->id]->finalpresgrade->overridden;
                                $locked = $userdata->instancedata[$instance->id]->finalpresgrade->locked;
                            } else if ($gradepresentation && !empty($gradepresentation->presentationgrade)) {
                                $presentationgrade = $this->display_grade($userdata->instancedata[$instance->id]->presentationgrade,
                                        $gradepresentation->presentationgrade);
                            } else {
                                $presentationgrade = '';
                            }

                            $row['presentationgrade'] = new html_table_cell($presentationgrade);

                            // Highlight if overwritten or locked!
                            if ($gradepresentation && $gradepresentation->presentationgradebook) {
                                if ($overridden || $locked) {
                                    $row['presentationgrade']->attributes['class'] = 'current';

                                    $dategraded = $finalgrade->dategraded;
                                    $usermodified = $finalgrade->usermodified;
                                    $row['presentationgrade']->id = "u" . $userdata->id . "i" . $instance->id . "_a";
                                    $row['presentationgrade']->attributes['data-user'] = $userdata->id;
                                    $row['presentationgrade']->attributes['data-username'] = fullname($users[$userdata->id],
                                            has_capability('moodle/site:viewfullnames', $context));
                                    $row['presentationgrade']->attributes['data-item'] = $instance->id;
                                    $row['presentationgrade']->attributes['data-dategraded'] = userdate($dategraded);
                                    $row['presentationgrade']->attributes['data-grader'] = $users[$usermodified];
                                }
                            }
                        }
                    } else {
                        if (!empty($showattendances) && $this->attendancestracked()) {
                            $row['attendance'] = null;
                        }
                        if (!empty($showpresgrades) && $this->presentationsgraded()) {
                            $row['presentationgrade'] = null;
                        }
                    }
                    $table->data[$i] = new html_table_row();
                    $table->data[$i]->cells = $row;
                    $i++;
                    $idx++;
                }
                $row = [];
            }
            if (!empty($showexamples) && ((count($userdata->instancedata[$instance->id]->examples) > 0))) {
                $table->data[$i - $idx]->cells['checkmark']->rowspan = $idx;
                if (!empty($showattendances) && $this->attendancestracked()) {
                    $table->data[$i - $idx]->cells['attendance']->rowspan = $idx;
                }
                if (!empty($showpresgrades) && $this->presentationsgraded()) {
                    $table->data[$i - $idx]->cells['presentationgrade']->rowspan = $idx;
                }
            }

            // If the examples are shown and the examplecount is gt 0 we start a new line for sums!
            if ((!empty($showabs) || !empty($showrel) || !empty($showgrade)) && !empty($showexamples)
                    && ((count($userdata->instancedata[$instance->id]->examples) > 0))) {
                $row['checkmark'] = new html_table_cell('Σ ' . $instance->name);
                $row['checkmark']->header = true;
                $row['checkmark']->style = ' text-align: left; ';
                if (!empty($showexamples)) {
                    $row['examples'] = null;
                    $row['checkmark']->colspan = 2;
                } else {
                    $row['checkmark']->colspan = 1;
                }
            }

            if (!empty($showabs) || !empty($showrel) || !empty($showgrade)
                    || (!empty($showattendances) && $this->attendancestracked())
                    || (!empty($showpresgrades) && $this->presentationsgraded())) {

                if (!empty($showabs)) {
                    $checkedtext = $userdata->instancedata[$instance->id]->checked . '/' .
                            $userdata->instancedata[$instance->id]->maxchecked;
                }
                if (!empty($showrel)) {
                    $percentchecked = round($userdata->instancedata[$instance->id]->percentchecked, 2);
                    if (!empty($showabs)) {
                        $checkedtext .= ' (' . $percentchecked . '%)';
                    } else {
                        $checkedtext = $percentchecked . '%';
                    }
                }
                if (empty($showrel) && empty($showabs)) {
                    $checkedtext = '';
                }
                if ((!empty($showrel) || !empty($showabs))) {
                    $row['checked'] = new html_table_cell($checkedtext);
                    if ($idx != 0) {
                        $row['checked']->header = true;
                    }
                    $row['checked']->style = ' text-align: right; ';
                }

                $grade = empty($userdata->instancedata[$instance->id]->grade) ? 0 : $userdata->instancedata[$instance->id]->grade;
                $maxgrade = $userdata->instancedata[$instance->id]->maxgrade;
                $data = [];
                if (!empty($showgrade)) {
                    $finalgrade = $userdata->instancedata[$instance->id]->finalgrade->grade;
                    $locked = $userdata->instancedata[$instance->id]->finalgrade->locked;
                    if (($userdata->instancedata[$instance->id]->finalgrade->overridden
                                    || $locked || ($grade != $finalgrade))
                            && !is_null($finalgrade)) {
                        $gradetext = $this->display_grade($finalgrade, $maxgrade);
                        $class = "current";
                        $userid = $userdata->id;
                        if (empty($users[$userid])) {
                            $userobj = $DB->get_record('user', ['id' => $userid],
                                    'id, ' . implode(', ', get_all_user_name_fields()));
                            $users[$userid] = fullname($userobj, has_capability('moodle/site:viewfullnames', $context));
                        }
                        $usermodified = $userdata->instancedata[$instance->id]->finalgrade->usermodified;
                        if (empty($users[$usermodified])) {
                            $userobj = $DB->get_record('user', ['id' => $usermodified],
                                    'id, ' . implode(', ', get_all_user_name_fields()));
                            $users[$usermodified] = fullname($userobj,
                                    has_capability('moodle/site:viewfullnames', $context));
                        }
                        $dategraded = $userdata->instancedata[$instance->id]->finalgrade->dategraded;
                        $data['user'] = $userdata->id;
                        $data['username'] = $users[$userdata->id];
                        $data['item'] = $instance->id;
                        $data['dategraded'] = userdate($dategraded);
                        $data['grader'] = $users[$usermodified];
                    } else {
                        $gradetext = $this->display_grade($userdata->instancedata[$instance->id]->grade,
                                $maxgrade);
                        $class = "";
                    }
                    if (!empty($showrel)) {
                        $finalgrade = $userdata->instancedata[$instance->id]->finalgrade->grade;
                        $grade = $userdata->instancedata[$instance->id]->grade;
                        $locked = $userdata->instancedata[$instance->id]->finalgrade->locked;
                        if (($userdata->instancedata[$instance->id]->finalgrade->overridden || $locked || ($grade != $finalgrade))
                                && !is_null($userdata->instancedata[$instance->id]->finalgrade->grade)) {
                            if (empty($maxgrade)) {
                                $gradetext .= ' (' . round(0, 2) . ' %)';
                            } else {
                                $gradetext .= ' (' . round(100 * $finalgrade / $maxgrade, 2) . ' %)';
                            }
                        } else {
                            if (empty($userdata->instancedata[$instance->id]->grade)) {
                                $gradetext .= ' (' . round(0, 2) . ' %)';
                            } else if ($userdata->instancedata[$instance->id]->grade > 0) {
                                $gradetext .= ' (' . round($userdata->instancedata[$instance->id]->percentgrade, 2) . ' %)';
                            }
                        }
                    }
                    $row['points'] = new html_table_cell($gradetext);
                    if ($idx != 0) {
                        $row['points']->header = true;
                    }
                    $row['points']->attributes['class'] = $class;
                    $row['points']->id = "u" . $userdata->id . "i" . $instance->id . "_a";
                    $row['points']->style = ' text-align: right; ';
                    if (!empty($data)) {
                        foreach ($data as $attr => $cur) {
                            $row['points']->attributes['data-' . $attr] = $cur;
                        }
                    }
                }

                // Write attendance and/or grade in line with checkmark name if no examples are shown!
                if ($idx == 0 && (empty($showexamples) || (count($userdata->instancedata[$instance->id]->examples) == 0))) {
                    if (!empty($showattendances) && $this->attendancestracked()) {
                        if ($tracksattendance = $this->tracksattendance($instance->id)) {
                            if ($tracksattendance->attendancegradebook) {
                                // We can't use already formatted grade here, because we have to parse the float value!
                                $attendance = $userdata->instancedata[$instance->id]->finalatgrade->grade;
                                $finalgrade = $userdata->instancedata[$instance->id]->finalatgrade;
                                $overridden = $userdata->instancedata[$instance->id]->finalatgrade->overridden;
                                $locked = $userdata->instancedata[$instance->id]->finalatgrade->locked;
                                $userid = $userdata->id;
                                if (empty($users[$userid])) {
                                    $userobj = $DB->get_record('user', ['id' => $userid],
                                            'id, ' . implode(', ', get_all_user_name_fields()));
                                    $users[$userid] = fullname($userobj,
                                            has_capability('moodle/site:viewfullnames', $context));
                                }
                                $usermodified = $userdata->instancedata[$instance->id]->finalatgrade->usermodified;
                                if (empty($users[$usermodified])) {
                                    $userobj = $DB->get_record('user', ['id' => $usermodified],
                                            'id, ' . implode(', ', get_all_user_name_fields()));
                                    $users[$usermodified] = fullname($userobj,
                                            has_capability('moodle/site:viewfullnames', $context));
                                }
                            } else {
                                $attendance = $userdata->instancedata[$instance->id]->attendance;
                            }
                            if ($attendance == 1) {
                                $attendancestr = $attendantstr;
                            } else if (($attendance == 0) && ($attendance != null)) {
                                $attendancestr = $absentstr;
                            } else {
                                $attendancestr = $unknownstr;
                            }
                            $attendance = checkmark_get_attendance_symbol($attendance) . $attendancestr;
                        } else {
                            $attendance = '';
                        }
                        $row['attendance'] = new html_table_cell($attendance);
                        if ($tracksattendance && $tracksattendance->attendancegradebook && ($overridden || $locked)) {
                            $row['attendance']->attributes['class'] = 'current';
                            $dategraded = $userdata->instancedata[$instance->id]->finalatgrade->dategraded;
                            $row['attendance']->attributes['data-user'] = $userdata->id;
                            $row['attendance']->attributes['data-username'] = $users[$userdata->id];
                            $row['attendance']->attributes['data-item'] = $instance->id;
                            $row['attendance']->attributes['data-dategraded'] = userdate($dategraded);
                            $row['attendance']->attributes['data-grader'] = $users[$usermodified];
                        }
                    }
                    if (!empty($showpresgrades) && $this->presentationsgraded()) {
                        if ($gradepresentation) {
                            if ($gradepresentation->presentationgradebook) {
                                $presentationgrade = $userdata->instancedata[$instance->id]->formattedpresgrade;
                                $finalgrade = $userdata->instancedata[$instance->id]->finalpresgrade;
                                $overridden = $userdata->instancedata[$instance->id]->finalpresgrade->overridden;
                                $locked = $userdata->instancedata[$instance->id]->finalpresgrade->locked;
                                $userid = $userdata->id;
                                if (empty($users[$userid])) {
                                    $userobj = $DB->get_record('user', ['id' => $userid],
                                            'id, ' . implode(', ', get_all_user_name_fields()));
                                    $users[$userid] = fullname($userobj,
                                            has_capability('moodle/site:viewfullnames', $context));
                                }
                                $usermodified = $userdata->instancedata[$instance->id]->finalpresgrade->usermodified;
                                if (empty($users[$usermodified])) {
                                    $userobj = $DB->get_record('user', ['id' => $usermodified],
                                            'id, ' . implode(', ', get_all_user_name_fields()));
                                    $users[$usermodified] = fullname($userobj,
                                            has_capability('moodle/site:viewfullnames', $context));
                                }
                            } else {
                                $presentationgrade = $this->display_grade($userdata->instancedata[$instance->id]->presentationgrade,
                                        $gradepresentation->presentationgrade);
                            }
                        } else {
                            $presentationgrade = '';
                        }
                        $row['presentationgrade'] = new html_table_cell($presentationgrade);
                        if ($gradepresentation && $gradepresentation->presentationgradebook && ($locked || $overridden)) {
                            $row['presentationgrade']->attributes['class'] = 'current';
                            $dategraded = $userdata->instancedata[$instance->id]->finalpresgrade->dategraded;
                            $row['presentationgrade']->attributes['data-user'] = $userdata->id;
                            $row['presentationgrade']->attributes['data-username'] = $users[$userdata->id];
                            $row['presentationgrade']->attributes['data-item'] = $instance->id;
                            $row['presentationgrade']->attributes['data-dategraded'] = userdate($dategraded);
                            $row['presentationgrade']->attributes['data-grader'] = $users[$usermodified];
                        }
                    }
                } else {
                    if (!empty($showattendances) && $this->attendancestracked()) {
                        $row['attendance'] = null;
                    }
                    if (!empty($showpresgrades) && $this->presentationsgraded()) {
                        $row['presentationgrade'] = null;
                    }
                }

                $table->data[$i] = new html_table_row();
                $table->data[$i]->cells = $row;
                $i++;
                $idx++;
                if (((!empty($showattendances) && $this->attendancestracked())
                                || (!empty($showpresgrades) && $this->presentationsgraded()))
                        && (!empty($showexamples) || !empty($showrel) || !empty($showabs))
                        && !(count($userdata->instancedata[$instance->id]->examples) == 0)) {

                    if (empty($showgrade) && empty($showrel) && empty($showabs)) {
                        $table->data[$i - $idx]->cells['checkmark']->rowspan = $idx;
                    }
                    if (!$this->column_is_hidden('attendance') && !empty($showattendances) && $this->attendancestracked()
                            && ($table->data[$i - $idx]->cells['attendance'] !== null)) {
                        $table->data[$i - $idx]->cells['attendance']->rowspan = $idx;
                    }
                    if (!$this->column_is_hidden('presentationgrade') && !empty($showpresgrades)
                            && $this->presentationsgraded() && ($table->data[$i - $idx]->cells['presentationgrade'] !== null)) {
                        $table->data[$i - $idx]->cells['presentationgrade']->rowspan = $idx;
                    }
                }
            }

            if (!empty($showexamples) && !(count($userdata->instancedata[$instance->id]->examples) == 0)) {
                $table->data[$i] = new html_table_row(['']);
                $table->data[$i]->cells[0]->colspan = count($table->data[$i - $idx]->cells);
                $i++;
                $idx++;
            }
        }
        if (!empty($showabs) || !empty($showrel) || !empty($showgrade)
                || (!empty($showattendances) && $this->attendancestracked())
                || ((!empty($showpresgrades) || !empty($showprescount)) && $this->presentationsgraded())) {
            $row = [];
            $row['checkmark'] = new html_table_cell('Σ ' . get_string('total'));
            $row['checkmark']->header = true;
            $row['checkmark']->style = ' text-align: left; ';
            if (!empty($showexamples) && (count($userdata->instancedata[$instance->id]->examples) >= 0)) {
                $row['checkmark']->colspan = 2;
                $row['examples'] = null;
            } else {
                $row['checkmark']->colspan = 1;
            }

            if (!empty($showabs)) {
                $checkedtext = $userdata->checks . '/' . $userdata->maxchecks;
            }
            if (!empty($showrel)) {
                if (!empty($showabs)) {
                    $checkedtext .= ' (' . round($userdata->percentchecked, 2) . '%)';
                } else {
                    $checkedtext = round($userdata->percentchecked, 2) . '%';
                }
            }
            if (!empty($showrel) || !empty($showabs)) {
                $row['checked'] = new html_table_cell($checkedtext);
                $row['checked']->header = true;
                $row['checked']->style = ' text-align: right; ';;
            }

            if (!empty($showgrade)) {
                // Coursesum of course grade.
                if (!empty($showabs)) {
                    $gradetext = $this->display_grade($userdata->coursesum, $userdata->maxgrade);
                }
                if (!empty($showrel)) {
                    if ($userdata->coursesum == -1) {
                        $percgrade = '-';
                    } else {
                        $percgrade = round(empty($userdata->coursesum) ? 0 : 100 * $userdata->coursesum / $userdata->maxgrade, 2);
                    }
                    $gradetext .= ' (' . $percgrade . '%)';
                }
                $row['points'] = new html_table_cell($gradetext);
                $row['points']->header = true;
                $row['points']->attributes['class'] = !empty($userdata->overridden) ? 'current' : '';
                $row['points']->id = "u" . $userdata->id . "i0_a";
                $row['points']->style = ' text-align: right; ';
            }

            if (!empty($showattendances) && $this->attendancestracked()) {
                // Amount of attendances.
                if ($userdata->atoverridden) {
                    $attendances = $userdata->courseatsum;
                } else {
                    $attendances = $userdata->attendances;
                }
                $row['attendance'] = new html_table_cell($attendances . '/' . $userdata->maxattendances);
                $row['attendance']->header = true;
                $row['attendance']->style = ' text-align: right; ';

                if ($userdata->atoverridden) {
                    $row['attendance']->attributes['class'] = 'current';
                }
            }

            if (!empty($showpresgrades) && $this->presentationsgraded()) {
                // Sum of presentationgrades!
                $row['presentationgrade'] = new html_table_cell($this->display_grade($userdata->coursepressum,
                        $userdata->presentationgrademax));
                $row['presentationgrade']->header = true;
                if ($userdata->presoverridden) {
                    $row['presentationgrade']->attributes['class'] = 'current';
                }
            }

            if (!empty($showprescount) && $this->presentationsgraded()) {
                // Amount of graded presentations.
                $row['presentationsgraded'] = new html_table_cell($this->display_grade($userdata->presentationsgraded,
                        $userdata->presentationsgradedmax));
                $row['presentationsgraded']->header = true;
                $row['presentationsgraded']->style = ' text-align: right; ';
                if ($userdata->presoverridden) {
                    $row['presentationsgraded']->attributes['class'] = 'current';
                }
            }

            $table->data[$i] = new html_table_row();
            $table->data[$i]->cells = $row;
        }

        if ($signature) {
            $i++;
            $table->data[$i] = new html_table_row(['']);
            if (!empty($table->data[0])) {
                $table->data[$i]->cells[0]->colspan = count($table->data[0]->cells);
            }
            $i++;
            $table->data[$i] = new html_table_row();
            $table->data[$i]->cells = [
                    'checkmark' => new html_table_cell(get_string('signature', 'local_checkmarkreport')),
                    'examples' => new html_table_cell('')
            ];
            $table->data[$i]->cells['checkmark']->header = true;
            $table->data[$i]->cells['checkmark']->style = ' text-align: left; ';
            if (!empty($table->data[0])) {
                $table->data[$i]->cells['examples']->colspan = count($table->data[0]->cells) - 1;
            }
        }

        // Init JS!
        $params = new \stdClass();
        $params->id = $table->id;
        $PAGE->requires->js_call_amd('local_checkmarkreport/report', 'initializer', [$params]);

        return $table;
    }

    /**
     * get data as xml file (sends to browser, forces download)
     *
     * @return void
     */
    public function get_xml() {
        global $DB;
        $context = context_course::instance($this->courseid);
        $data = $this->get_coursedata();
        $course = $DB->get_record('course', ['id' => $this->courseid]);

        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showexamples = get_user_preferences('checkmarkreport_showexamples');
        $showattendances = get_user_preferences('checkmarkreport_showattendances');
        $showpresgrades = get_user_preferences('checkmarkreport_showpresentationgrades');
        $showprescount = get_user_preferences('checkmarkreport_showpresentationcount');

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $report = $xml->appendChild(new DOMElement('report'));

        $examplenames = [];
        $instances = $this->get_courseinstances();
        foreach ($data as $userid => $row) {
            $user = $report->appendChild(new DOMElement('user'));
            $user->setAttribute('id', $userid);
            $user->setAttribute('fullname', fullname($row, has_capability('moodle/site:viewfullnames', $context)));

            foreach ($row->userdata as $key => $cur) {
                $user->setAttribute($key, $cur);
            }
            if (!$this->column_is_hidden('points') && !empty($showgrade)) {
                $user->setAttribute('checkedgrade', empty($row->checkgrade) ? 0 : $row->checkgrade);
                if ($row->overridden) {
                    $user->setAttribute('overridden', true);
                    $user->setAttribute('grade', empty($row->coursesum) ? 0 : $row->coursesum);
                }
                $user->setAttribute('maxgrade', empty($row->maxgrade) ? 0 : $row->maxgrade);
            }
            if (!$this->column_is_hidden('checked') && !empty($showabs)) {
                $user->setAttribute('checks', $row->checks);
                $user->setAttribute('maxchecks', $row->maxchecks);
            }

            if (!$this->column_is_hidden('checked') && !empty($showrel)) {
                $user->setAttribute('percentchecked', $row->percentchecked . '%');
            }
            if (!$this->column_is_hidden('points') && !empty($showrel)) {
                if ($row->overridden) {
                    $percgrade = round(empty($row->coursesum) ? 0 : 100 * $row->coursesum / $row->maxgrade, 2);
                } else {
                    $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
                }
                $user->setAttribute('percentgrade', $percgrade . '%');
            }

            $instancesnode = $user->appendChild(new DOMElement('instances'));
            if (!$this->column_is_hidden('attendance') && !empty($showattendances) && $this->attendancestracked()) {
                $instancesnode->setAttribute('attendances', $row->attendances);
                $instancesnode->setAttribute('maxattendances', $row->maxattendances);
            }
            if (!$this->column_is_hidden('presentationgrade') && !empty($showpresgrades) && $this->presentationsgraded()) {
                $instancesnode->setAttribute('presentationgrade', $row->presentationgrade);
                $instancesnode->setAttribute('presentationgrademax', $row->presentationgrademax);
            }
            if (!$this->column_is_hidden('presentationsgraded') && !empty($showprescount) && $this->presentationsgraded()) {
                $instancesnode->setAttribute('presentationsgraded', $row->presentationsgraded);
                $instancesnode->setAttribute('presentationsgradedmax', $row->presentationsgradedmax);
            }
            foreach ($instances as $instance) {
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records('checkmark_examples', ['checkmarkid' => $instance->id]);
                }
                $gradepresentation = $this->gradepresentations($instance->id);
                if ($gradepresentation && !$gradepresentation->presentationgrade) {
                    // Prevent comment only presentationgrades to mess with table!
                    $gradepresentation = false;
                }
                $instancedata = $row->instancedata[$instance->id];
                $instnode = $instancesnode->appendChild(new DOMElement('instance'));
                $instnode->setAttribute('name', $instance->name);
                if (!$this->column_is_hidden('points') && !empty($showgrade)) {
                    $instnode->setAttribute('checkedgrade', empty($instancedata->grade) ? 0 : $instancedata->grade);
                    if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
                        $instnode->setAttribute('overridden', true);
                        $grade = empty($instancedata->finalgrade->grade) ? 0 : $instancedata->finalgrade->grade;
                        $instnode->setAttribute('grade', $grade);
                    }
                    $instnode->setAttribute('maxgrade', empty($instancedata->maxgrade) ? 0 : $instancedata->maxgrade);
                }
                if (!$this->column_is_hidden('checked') && !empty($showabs)) {
                    $instnode->setAttribute('checks', $instancedata->checked);
                    $instnode->setAttribute('maxchecks', $instancedata->maxchecked);
                }

                if (!$this->column_is_hidden('checked') && !empty($showrel)) {
                    $instnode->setAttribute('percentchecked', $instancedata->percentchecked . '%');
                }
                if (!$this->column_is_hidden('points') && !empty($showrel)) {
                    if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
                        if (!empty($instancedata->maxgrade)) {
                            $grade = empty($instancedata->finalgrade->grade) ? 0 : $instancedata->finalgrade->grade;
                            $percgrade = round(100 * $grade / $instancedata->maxgrade, 2);
                        } else {
                            $percgrade = round(0, 2);
                        }
                    } else {
                        $percgrade = round((empty($instancedata->percentgrade) ? 0 : $instancedata->percentgrade), 2);
                    }
                    $instnode->setAttribute('percentgrade', $percgrade . '%');
                }

                if (!empty($showattendances)) {
                    $this->add_xml_attendance_data($instnode, $instancedata, $instance->id);
                }

                if (!empty($showpresgrades)) {
                    $this->add_xml_presentation_data($instnode, $instancedata, $instance->id, $gradepresentation);
                }

                if ((!$this->column_is_hidden('examples') || !$this->column_is_hidden('checked')) && !empty($showexamples)) {
                    $exsnode = $instnode->appendChild(new DOMElement('examples'));
                    foreach ($instancedata->examples as $key => $example) {
                        $exnode = $exsnode->appendChild(new DOMElement('example'));
                        if (!$this->column_is_hidden('examples')) {
                            $exnode->setAttribute('name', $examplenames[$instance->id][$key]->name);
                        }
                        if (!$this->column_is_hidden('checked')) {
                            $exnode->setAttribute('state', intval($example->is_checked()));
                            $exnode->setAttribute('overwrite', intval($example->is_forced()));
                            $exnode->setAttribute('statesymbol', $example->get_examplestate_for_export());
                        }
                    }
                }
            }
        }

        $filename = get_string('pluginname', 'local_checkmarkreport') . '_' .
                $course->shortname . '_' . userdate(time());
        $this->output_xml_with_headers($xml, $filename);
    }

    /**
     * get report data as plain text file (sends to browser, forces download)
     *
     * @return void
     */
    public function get_txt() {
        global $DB;
        $context = context_course::instance($this->courseid);
        $data = $this->get_coursedata();
        $course = $DB->get_record('course', ['id' => $this->courseid]);

        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showexamples = get_user_preferences('checkmarkreport_showexamples');
        $showattendances = get_user_preferences('checkmarkreport_showattendances');
        $showpresgrades = get_user_preferences('checkmarkreport_showpresentationgrades');
        $showprescount = get_user_preferences('checkmarkreport_showpresentationcount');

        $txt = '';
        $examplenames = [];
        $instances = $this->get_courseinstances();
        $course = $DB->get_record('course', ['id' => $this->courseid]);
        // Header.
        $txt .= get_string('pluginname', 'local_checkmarkreport') . ': ' . $course->fullname . "\n";
        // Data.
        foreach ($data as $row) {
            $txt .= get_string('fullname') . ': ' . fullname($row,
                            has_capability('moodle/site:viewfullnames', $context)) . "\n";
            if (!$this->column_is_hidden('points') && $showgrade) {
                if ($row->overridden) {
                    $grade = empty($row->coursesum) ? 0 : $row->coursesum;
                } else {
                    $grade = empty($row->checkgrade) ? 0 : $row->checkgrade;
                }
                $txt .= "Σ ".get_string('grade')."\t".$grade.'/'.(empty($row->maxgrade) ? 0 : $row->maxgrade)."\n";
            }
            if (!$this->column_is_hidden('checked') && $showabs) {
                $txt .= "Σ ".get_string('examples', 'local_checkmarkreport')."\t".$row->checks.'/'.$row->maxchecks."\n";
            }
            if ($showrel) {
                $txt .= "Σ % ".get_string('examples', 'local_checkmarkreport')."\t".$row->percentchecked.'%'."\n";
                if ($row->overridden) {
                    $grade = empty($row->coursesum) ? 0 : $row->coursesum;
                    $percgrade = round(100 * $grade / $row->maxgrade, 2);
                } else {
                    $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
                }
                $txt .= "Σ % ".get_string('grade', 'local_checkmarkreport')."\t".$percgrade.'%'."\n";
            }
            if (!empty($showrel)
                    || (!$this->column_is_hidden('checked') && !empty($showabs))
                    || (!$this->column_is_hidden('points') && !empty($showgrade))) {
                $txt .= "\n";
            }
            $instances = $this->get_courseinstances();
            foreach ($instances as $instance) {
                $txt .= $instance->name . "\n";
                // Dynamically add examples!
                // Get example data!
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records('checkmark_examples', ['checkmarkid' => $instance->id],
                            'id ASC');
                }
                $instancedata = $row->instancedata[$instance->id];
                if (!empty($showexamples) && (!$this->column_is_hidden('examples')
                                || !$this->column_is_hidden('checked')
                                || (!$this->column_is_hidden('points') && $showgrade))) {
                    foreach ($instancedata->examples as $key => $example) {
                        if (!$this->column_is_hidden('examples')) {
                            $txt .= "\t" . $example->name .
                                    " (" . $example->grade . 'P)';
                        }
                        if (!$this->column_is_hidden('checked')) {
                            $txt .= "\t" . ($example->get_examplestate_for_export());
                        }
                        if (!$this->column_is_hidden('points') && $showgrade) {
                            $txt .= "\t" . $example->get_checked_of_max_points();
                        }
                        $txt .= "\n";
                    }
                }
                if ($showexamples && !$this->column_is_hidden('examples')) {
                    $txt .= "Σ " . $instance->name;
                }
                if (!$this->column_is_hidden('checked')) {
                    if ($showabs && $showrel) {
                        $txt .= "\t" . $instancedata->checked . '/' . $instancedata->maxchecked .
                                ' (' . round($instancedata->percentchecked, 2) . '%)';
                    } else if ($showabs) {
                        $txt .= "\t" . $instancedata->checked . '/' . $instancedata->maxchecked;
                    } else if ($showrel) {
                        $txt .= "\t" . round($instancedata->percentchecked, 2) . '%';
                    }
                }
                if (!$this->column_is_hidden('points')) {
                    if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
                        $grade = empty($instancedata->finalgrade->grade) ? 0 : $instancedata->finalgrade->grade;
                        if (!empty($instancedata->maxgrade)) {
                            $percgrade = round(100 * $grade / $instancedata->maxgrade, 2);
                        } else {
                            $percgrade = '-';
                        }

                    } else {
                        $grade = empty($instancedata->grade) ? 0 : $instancedata->grade;
                        $percgrade = round((empty($instancedata->percentgrade) ? 0 : $instancedata->percentgrade), 2);
                    }
                    if ($showabs && $showrel) {
                        $txt .= "\t" . $grade . '/' . (empty($instancedata->maxgrade) ? 0 : $instancedata->maxgrade) .
                                '(' . $percgrade . " %)";
                    } else if ($showabs) {
                        $txt .= "\t" . $grade . '/' . (empty($instancedata->maxgrade) ? 0 : $instancedata->maxgrade);
                    } else if ($showrel) {
                        $txt .= "\t" . $percgrade . " %";
                    }
                    $txt .= "\n";
                }
                if (!$this->column_is_hidden('attendance') && !empty($showattendances) && $this->attendancestracked()
                        && $this->tracksattendance($instance->id)) {
                    $attendance = '?';
                    if ($instancedata->attendance == 1) {
                        $attendance = '✓';
                    } else if (($instancedata->attendance == 0) && ($instancedata->attendance !== null)) {
                        $attendance = '✗';
                    }
                    $txt .= "\t" . get_string('attendance', 'checkmark') . ': ' . $attendance . "\n";
                }
                $gradepresentation = $this->gradepresentations($instance->id);
                if ($gradepresentation && !$gradepresentation->presentationgrade) {
                    // Prevent comment only presentationgrades from showing up here!
                    $gradepresentation = false;
                }
                if (!$this->column_is_hidden('presentationgrade') && !empty($showpresgrades) && $this->presentationsgraded()
                        && $gradepresentation) {
                    $presentationgrade = false;
                    if ($gradepresentation->presentationgradebook) {
                        $presentationgrade = $instancedata->formattedpresgrade;
                    } else {
                        $presentationgrade = $this->display_grade($instancedata->presentationgrade,
                                $gradepresentation->presentationgrade);
                    }

                    if ($presentationgrade !== false) {
                        $txt .= "\t" . get_string('presentationgrade', 'checkmark') . ': ' . $presentationgrade . "\n";
                    }
                }
                $txt .= "\n";
            }
            if (!$this->column_is_hidden('attendance') && !empty($showattendances) && $this->attendancestracked()) {
                $txt .= 'Σ ' . get_string('attendance', 'checkmark') . ': ' .
                        $row->attendances . '/' . $row->maxattendances . "\n";
            }
            if (!$this->column_is_hidden('presentationgrade') && !empty($showpresgrades) && $this->presentationsgraded() &&
                    !empty($this->pointsforpresentations())) {
                $txt .= 'Σ ' . get_string('presentationgrade', 'checkmark') . ': ' .
                        $this->display_grade($row->presentationgrade, $row->presentationgrademax) . "\n";
            }
            if (!$this->column_is_hidden('presentationsgraded') && !empty($showprescount) && $this->presentationsgraded()) {
                $txt .= "# " . get_string('presentationgrade', 'checkmark') . ': ' .
                        $this->display_grade($row->presentationsgraded, $row->presentationsgradedmax) . "\n";
            }
            $txt .= "\n";
        }
        $filename = get_string('pluginname', 'local_checkmarkreport') . '_' .
                $course->shortname . '_' . userdate(time());
        $this->output_text_with_headers($txt, $filename);
    }

    /**
     * Write report data to workbook
     *
     * @param MoodleExcelWorkbook|MoodleODSWorkbook $workbook object to write data into
     * @return void
     */
    public function fill_workbook($workbook) {
        // Initialise everything!
        $context = context_course::instance($this->courseid);
        $textonlycolumns = get_extra_user_fields($context);
        array_push($textonlycolumns, 'fullname');
        $worksheets = [];
        $data = $this->get_coursedata();
        $sheetnames = [];
        if (empty($data) || count($data) == 0) {
            $workbook->add_worksheet('');
            return;
        }
        foreach ($data as $userid => $userdata) {
            $x = 0;
            $y = 0;
            $i = 0;
            while (in_array(!empty($i) ? fullname($userdata,
                            has_capability('moodle/site:viewfullnames', $context)) . ' ' .
                    $i : fullname($userdata, has_capability('moodle/site:viewfullnames', $context)), $sheetnames)) {
                $i++;
            }
            if (!empty($i)) {
                $worksheets[$userid] = $workbook->add_worksheet(fullname($userdata,
                                has_capability('moodle/site:viewfullnames', $context)) . ' ' . $i);
                $sheetnames[] = fullname($userdata, has_capability('moodle/site:viewfullnames', $context)) . ' ' . $i;
            } else {
                $worksheets[$userid] = $workbook->add_worksheet(fullname($userdata,
                        has_capability('moodle/site:viewfullnames', $context)));
                $sheetnames[] = fullname($userdata, has_capability('moodle/site:viewfullnames', $context));
            }
            $table = $this->get_table($userdata, true);
            $worksheets[$userid]->write_string($y, $x,
                    strip_tags(fullname($data[$userid], has_capability('moodle/site:viewfullnames', $context))));
            $worksheets[$userid]->merge_cells($y, $x, $y, $x + 3);

            $y++;

            // We may use additional table data to format sheets!
            $this->prepare_worksheet($table, $worksheets[$userid], $x, $y);

            if ($this->column_is_hidden('checkmark')) {
                // Hide column in worksheet!
                $worksheets[$userid]->set_column(0, 0, 0, null, true);
            }
            if ($this->column_is_hidden('examples')) {
                // Hide column in worksheet!
                $worksheets[$userid]->set_column(1, 1, 0, null, true);
            }
            if ($this->column_is_hidden('checked')) {
                // Hide column in worksheet!
                $worksheets[$userid]->set_column(2, 2, 0, null, true);
            }
            if ($this->column_is_hidden('points')) {
                // Hide column in worksheet!
                $worksheets[$userid]->set_column(3, 3, 0, null, true);
            }
            if ($this->column_is_hidden('attendance')) {
                // Hide column attendance in worksheet!
                $worksheets[$userid]->set_column(4, 4, 0, null, true);
            }
            if ($this->column_is_hidden('presentationgrade')) {
                // Hide column presentationgrade in worksheet!
                $worksheets[$userid]->set_column(5, 5, 0, null, true);
            }

            if (!empty($table->data)) {
                foreach ($table->data as $row) {
                    $x = 0;
                    // Convert array rows to html_table_rows and cell strings to html_table_cell objects!
                    if (!($row instanceof html_table_row)) {
                        $newrow = new html_table_row();

                        foreach ($row as $cell) {
                            if ($cell === null) {
                                $newrow->cells[] = null;
                            } else {
                                if (!($cell instanceof html_table_cell)) {
                                    $cell = new html_table_cell($cell);
                                }
                                $newrow->cells[] = $cell;
                            }
                        }
                        $row = $newrow;
                    }

                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; // Flag for sanity checking!
                    foreach ($row->cells as $key => $cell) {
                        if ($gotlastkey) {
                            // This should never happen. Why do we have a cell after the last cell?
                            mtrace("A cell with key ($key) was found after the last key ($lastkey)");
                        }

                        if ($cell === null) {
                            $x++;
                            continue;
                        }

                        if (!($cell instanceof html_table_cell)) {
                            $mycell = new html_table_cell();
                            $mycell->text = $cell;
                            $cell = $mycell;
                        }
                        if ($key == $lastkey) {
                            $gotlastkey = true;
                        }
                        $cell = $this->modify_span($cell);
                        $colorparams = [];
                        if ($this->starts_with($cell->text, '<colorred>')) {
                            $colorparams['bg_color'] = '#e6b8b7';
                        }
                        $format = $workbook->add_format($colorparams);
                        $cell->text = strip_tags($cell->text);
                        // We need this, to overwrite the images for attendance with simple characters!
                        /* If text to be written is numeric, it will be written in number format
                         so it can be used in calculations without further conversion. */
                        if (!empty($cell->character)) {
                            $worksheets[$userid]->write_string($y, $x, $cell->character, $format);
                        } else if (is_numeric($cell->text) && (!in_array($key, $textonlycolumns))) {
                            $worksheets[$userid]->write_number($y, $x, $cell->text, $format);
                        } else {
                            $worksheets[$userid]->write_string($y, $x, $cell->text, $format);
                        }
                        if (($cell->rowspan > 1) || ($cell->colspan > 1)) {
                            $worksheets[$userid]->merge_cells($y, $x, $y + $cell->rowspan - 1, $x + $cell->colspan - 1);
                        }
                        $x++;
                    }
                    $y++;
                }
            }
        }
    }
}

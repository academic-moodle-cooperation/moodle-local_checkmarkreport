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
 * Contains local_checkmarkreport_overview class, (handling checkmarkreport overview content)
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * local_checkmarkreport_overview class, handles checkmarkreport overview content and export
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_checkmarkreport_overview extends local_checkmarkreport_base implements renderable {

    /** @var string classes to assign to the reporttable */
    protected $tableclass = 'table table-condensed table-hover table-striped overview';

    /**
     * Constructor
     *
     * @param int $id course id
     * @param int[] $groupings (optional) groupings to include
     * @param int[] $groups (optional) groups to include
     * @param int[] $instances (optional) instances to include
     */
    public function __construct($id, $groupings=array(0), $groups=array(0), $instances=array(0)) {
        global $DB;

        if (!in_array(0, $groupings)) {
            list($insql, $params) = $DB->get_in_or_equal($groupings);
            $grpgsgrps = $DB->get_fieldset_select('groupings_groups', 'DISTINCT groupid', 'groupingid '.$insql, $params);
            if (in_array(0, $groups) || empty($groups)) {
                $groups = $grpgsgrps;
            } else {
                $groups = array_intersect($groups, $grpgsgrps);
            }
        }

        if (!in_array(0, $groups)) {
            list($insql, $params) = $DB->get_in_or_equal($groups);
            $users = $DB->get_fieldset_select('groups_members', 'DISTINCT userid', 'groupid '.$insql, $params);
        } else {
            $users = array(0);
        }
        parent::__construct($id, $groups, $users, $instances);
    }

    /**
     * get html table object representing report data
     *
     * @return html_table report as html_table object
     */
    public function get_table() {
        global $DB, $PAGE;

        $context = context_course::instance($this->courseid);

        $performance = new stdClass();
        $performance->start = microtime(true);
        $data = $this->get_coursedata();
        $performance->datafetched = microtime(true);

        $showexamples = get_user_preferences('checkmarkreport_showexamples', 1);
        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showpoints = get_user_preferences('checkmarkreport_showpoints');
        $showattendances = get_user_preferences('checkmarkreport_showattendances');
        $showpresgrades = get_user_preferences('checkmarkreport_showpresentationgrades');
        $signature = get_user_preferences('checkmarkreport_signature');

        $table = new html_table();

        $table->id = 'user-grades'; // Was former "attempts"! Changed due to style of overridden grades!
        if (!isset($table->attributes)) {
            $table->attributes = array('class' => 'coloredrows overview');
        } else if (!isset($table->attributes['class'])) {
            $table->attributes['class'] = 'coloredrows overview';
        } else {
            $table->attributes['class'] .= ' coloredrows overview';
        }

        $table->tablealign = 'center';

        $row = array();

        $tableheaders = array();
        $tablecolumns = array();
        $table->colgroups = array();
        $sortable = array();
        $useridentity = get_extra_user_fields($context);
        // Firstname sortlink.
        $firstname = $this->get_sortlink('firstname', get_string('firstname'), $PAGE->url);
        // Lastname sortlink.
        $lastname = $this->get_sortlink('lastname', get_string('lastname'), $PAGE->url);
        $sortable[] = 'lasname';
        $sortable[] = 'firstname';
        $tableheaders['fullnameuser'] = new html_table_cell($firstname.' / '.$lastname);
        $tableheaders['fullnameuser']->header = true;
        $tableheaders['fullnameuser']->rowspan = 2;
        $tableheaders2['fullnameuser'] = null;
        $tablecolumns[] = 'fullnameuser';
        $table->colgroups[] = array('span' => '1',
                                    'class' => 'fullnameuser');
        $table->colclasses['fullnameuser'] = 'fullnameuser';

        foreach ($useridentity as $cur) {
            $sortable[] = $cur;
            $text = ($cur == 'phone1') ? get_string('phone') : get_string($cur);
            $sortlink = $this->get_sortlink($cur, $text, $PAGE->url);
            $tableheaders[$cur] = new html_table_cell($sortlink);
            $tableheaders[$cur]->header = true;
            $tableheaders[$cur]->rowspan = 2;
            $tableheaders2[$cur] = null;
            $tablecolumns[] = $cur;
            $table->colgroups[] = array('span' => '1',
                                        'class' => $cur);
            $table->colclasses[$cur] = $cur;
        }

        // Coursesum of course grade.
        if (!empty($showgrade)) {
            $sortlink = $this->get_sortlink('checkgrade', 'S '.get_string('grade'), $PAGE->url);
            $sortable[] = 'grade';
            $tableheaders['grade'] = new html_table_cell($sortlink);
            $tableheaders['grade']->header = true;
            $tableheaders['grade']->rowspan = 2;
            $tableheaders2['grade'] = null;
            $tablecolumns[] = 'grade';
            $table->colgroups[] = array('span' => '1',
                                        'class' => 'grade');
            $table->colclasses['grade'] = 'grade';
        }

        // Coursesum of course examples.
        if (!empty($showabs)) {
            $text = 'S '.get_string('examples', 'local_checkmarkreport');
            $sortlink = $this->get_sortlink('checks', $text, $PAGE->url);
            $sortable[] = 'examples';
            $tableheaders['examples'] = new html_table_cell($sortlink);
            $tableheaders['examples']->header = true;
            $tableheaders['examples']->rowspan = 2;
            $tableheaders2['examples'] = null;
            $tablecolumns[] = 'examples';
            $table->colgroups[] = array('span' => '1',
                                        'class' => 'examples');
            $table->colclasses['examples'] = 'examples';
        }

        if (!empty($showrel)) {
            // Percent of course examples.
            $text = 'S % '.
                    $this->get_sortlink('percentchecked',
                                        get_string('examples',
                                                   'local_checkmarkreport'),
                                        $PAGE->url).
                    ' ('.
                    $this->get_sortlink('percentgrade', get_string('grade'),
                                        $PAGE->url).')';
            $sortable[] = 'percentex';
            $tableheaders['percentex'] = new html_table_cell($text);
            $tableheaders['percentex']->header = true;
            $tableheaders['percentex']->rowspan = 2;
            $tableheaders2['percentex'] = null;
            $tablecolumns[] = 'percentex';
            $table->colgroups[] = array('span' => '1',
                                        'class' => 'percentex');
            $table->colclasses['percentex'] = 'percentex';
        }

        if (!empty($showattendances) && $this->attendancestracked()) {
            // Amount of attendances.
            $text = get_string('attendance', 'checkmark');
            $sortable[] = 'attendances';
            $text = $this->get_sortlink('attendances', 'S '.get_string('attendances', 'local_checkmarkreport'), $PAGE->url);
            $tableheaders['attendances'] = new html_table_cell($text);
            $tableheaders['attendances']->header = true;
            $tableheaders['attendances']->rowspan = 2;
            $tableheaders2['attendances'] = null;
            $tablecolumns[] = 'attendances';
            $table->colgroups[] = array('span' => '1',
                                        'class' => 'attendances');
            $table->colclasses['attendances'] = 'attendances';
        }

        if (!empty($showpresgrades) && $this->presentationsgraded() && $this->pointsforpresentations()) {
            $sortlink = $this->get_sortlink('presentationgrade', 'S '.get_string('presentationgrade', 'checkmark'), $PAGE->url);
            $sortable[] = 'presentationgrade';
            $tableheaders['presentationgrade'] = new html_table_cell($sortlink);
            $tableheaders['presentationgrade']->header = true;
            $tableheaders['presentationgrade']->rowspan = 2;
            $tableheaders2['presentationgrade'] = null;
            $tablecolumns[] = 'presentationgrade';
            $table->colgroups[] = array('span' => '1',
                                        'class' => 'presentationgrade');
            $table->colclasses['presentationgrade'] = 'presentationgrade';
        }

        $instances = $this->get_courseinstances();
        foreach ($instances as $instance) {
            $span = 0;
            $gradepresentation = $this->gradepresentations($instance->id);
            if ($gradepresentation && !$gradepresentation->presentationgrade) {
                // Prevent comment only presentationgrades to mess with table!
                $gradepresentation = false;
            }
            if (!empty($showgrade) || !empty($showabs) || !empty($showrel)
                || (!empty($showattendances) && $this->attendancestracked() && $this->tracksattendance($instance->id))
                || (!empty($showpresgrades) && $this->presentationsgraded() && $gradepresentation)
                || !empty($showexamples)) {
                $instanceurl = new moodle_url('/mod/checkmark/view.php', array('id' => $instance->coursemodule));
                $instancelink = html_writer::link($instanceurl, $instance->name);
                $tableheaders['instance'.$instance->id] = new html_table_cell($instancelink);
                $tableheaders['instance'.$instance->id]->header = true;
                $tableheaders['instance'.$instance->id]->scope = 'colgroup';
                $table->colclasses['instance'.$instance->id] = 'instance'.$instance->id;
            }
            // Coursesum of course grade.
            if (!empty($showgrade)) {
                $span++;
                $text = get_string('grade');
                $sortable[] = 'grade'.$instance->id;
                $sortlink = $this->get_sortlink('grade'.$instance->id, $text, $PAGE->url);
                $tableheaders2['grade'.$instance->id] = new html_table_cell($sortlink);
                $tableheaders2['grade'.$instance->id]->header = true;
                $tablecolumns[] = 'grade'.$instance->id;
                $table->colclasses['grade'.$instance->id] = 'instance'.$instance->id.' grade'.$instance->id;
            }

            // Coursesum of course examples.
            if (!empty($showabs)) {
                $span++;
                $text = get_string('examples', 'local_checkmarkreport');
                $sortable[] = 'examples'.$instance->id;
                $sortlink = $this->get_sortlink('checks'.$instance->id, $text, $PAGE->url);
                $tableheaders2['examples'.$instance->id] = new html_table_cell($sortlink);
                $tableheaders2['examples'.$instance->id]->header = true;
                $tablecolumns[] = 'examples';
                $table->colclasses['examples'.$instance->id] = 'instance'.$instance->id.' examples'.$instance->id;
            }

            // Percent of course examples.
            if (!empty($showrel)) {
                $span++;
                $title = '% '. $this->get_sortlink('percentchecked'.$instance->id, get_string('examples', 'local_checkmarkreport'),
                                                   $PAGE->url).
                         ' ('. $this->get_sortlink('percentgrade'.$instance->id, get_string('grade'), $PAGE->url).')';
                $sortable[] = 'percentex'.$instance->id;
                $tableheaders2['percentex'.$instance->id] = new html_table_cell($title);
                $tableheaders2['percentex'.$instance->id]->header = true;
                $tablecolumns[] = 'percentex'.$instance->id;
                $table->colclasses['percentex'.$instance->id] = 'instance'.$instance->id.' percentex'.$instance->id;
            }
            if (!empty($showattendances) && $this->attendancestracked() && $this->tracksattendance($instance->id)) {
                $span++;
                $text = $this->get_sortlink('attendance'.$instance->id, get_string('attendance', 'checkmark'), $PAGE->url);
                $sortable[] = 'attendance'.$instance->id;
                $tableheaders2['attendance'.$instance->id] = new html_table_cell($text);
                $tableheaders2['attendance'.$instance->id]->header = true;
                $tablecolumns[] = 'attendance'.$instance->id;
                $table->colclasses['attendance'.$instance->id] = 'instance'.$instance->id.' attendance'.$instance->id;
            }
            if (!empty($showpresgrades) && $this->presentationsgraded() && $gradepresentation) {
                $span++;
                $sortlink = $this->get_sortlink('presentationgrade'.$instance->id, get_string('presentationgrade', 'checkmark'),
                                                $PAGE->url);
                $sortable[] = 'presentationgrade'.$instance->id;
                $tableheaders2['presentationgrade'.$instance->id] = new html_table_cell($sortlink);
                $tableheaders2['presentationgrade'.$instance->id]->header = true;
                $tablecolumns[] = 'presentationgrade'.$instance->id;
                $table->colgroups[] = array('span' => '1',
                                            'class' => 'presentationgrade'.$instance->id);
                $table->colclasses['presentationgrade'.$instance->id] = 'instance'.$instance->id.' presentationgrade'.
                                                                        $instance->id;
            }
            // Dynamically add examples!
            if (!empty($showexamples)) {
                // First get example data!
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
                }
                foreach ($examplenames[$instance->id] as $key => $example) {
                    $span++;
                    $tableheaders2['example'.$key] = new html_table_cell($example->name." (".$example->grade.'P)');
                    $tableheaders2['example'.$key]->header = true;
                    $tablecolumns[] = 'example'.$key;
                    $table->colclasses['example'.$key] = 'instance'.$instance->id.' example'.$key;
                }
            }
            if (!empty($showgrade) || !empty($showabs) || !empty($showrel)
                || (!empty($showattendances) && $this->attendancestracked() && $this->tracksattendance($instance->id))
                || (!empty($showpresgrades) && $this->presentationsgraded() && $gradepresentation)
                || !empty($showexamples)) {
                for ($i = 1; $i < $span; $i++) {
                    // Insert empty cells for the colspan!
                    $tableheaders[] = null;
                }
                $tableheaders['instance'.$instance->id]->colspan = $span;
                $table->colgroups[] = array('span'  => $span,
                                            'class' => 'instancegroup');
            }
        }

        if ($signature) {
            $tableheaders['sig'] = new html_table_cell(get_string('signature', 'local_checkmarkreport'));
            $tableheaders['sig']->header = true;
            $tablecolumns[] = 'sig';
            $tableheaders['sig']->rowspan = 2;
            $tableheaders2['sig'] = null;
            $table->colgroups[] = array('span' => 1,
                                        'class' => 'sig');
            $table->colclasses['sig'] = 'sig';
        }

        $table->head = array();
        $table->head[0] = new html_table_row();
        $table->head[0]->cells = $tableheaders;
        $table->head[1] = new html_table_row();
        $table->head[1]->cells = $tableheaders2;

        foreach ($data as $userid => $curuser) {
            $row = array();
            $userurl = new moodle_url('/user/view.php', array('id'     => $userid,
                                                              'course' => $this->courseid));
            $userlink = html_writer::link($userurl, fullname($curuser));
            $row['fullnameuser'] = new html_table_cell($userlink);
            foreach ($useridentity as $cur) {
                $row[$cur] = new html_table_cell($curuser->$cur);
            }

            // Coursesum of course grade.
            if (!empty($showgrade)) {
                $text = $this->display_grade($curuser->coursesum, $curuser->maxgrade);
                $row['grade'] = new html_table_cell($text);
                $row['grade']->attributes['id'] = 'u'.$curuser->id.'i0';
                if ($curuser->overridden) {
                    // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                    $row['grade']->attributes['class'] = 'current';
                    $row['grade']->id = "u".$curuser->id."i0_a";
                }
            }
            // Coursesum of course examples.
            if (!empty($showabs)) {
                $row['examples'] = new html_table_cell($curuser->checks.' / '.$curuser->maxchecks);
            }
            // Percent of course examples.
            if (!empty($showrel)) {
                // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                if ($curuser->coursesum >= 0) {
                    $percgrade = round(empty($curuser->coursesum) ? 0 : 100 * $curuser->coursesum / $curuser->maxgrade, 2);
                } else {
                    $percgrade = '-';
                }
                if ($curuser->overridden) {
                    $row['percentex'] = new html_table_cell(round($curuser->percentchecked, 2).'% ('.$percgrade.' %)');
                    $row['percentex']->attributes['class'] = 'current';
                    $row['percentex']->id = "u".$curuser->id."i0_r";
                } else {
                    $row['percentex'] = new html_table_cell(round($curuser->percentchecked, 2).'% ('.$percgrade.' %)');
                }
            }

            if (!empty($showattendances) && $this->attendancestracked()) {
                // Amount of attendances.
                $row['attendances'] = new html_table_cell($curuser->attendances.'/'.$curuser->maxattendances);
            }

            if (!empty($showpresgrades) && $this->presentationsgraded() && $this->pointsforpresentations()) {
                $row['presentationgrade'] = new html_table_cell($this->display_grade($curuser->coursepressum,
                                                                                     $curuser->presentationgrademax));
            }

            $instances = $this->get_courseinstances();
            foreach ($instances as $instance) {
                // Coursesum of course grade.
                if (empty($users[$curuser->instancedata[$instance->id]->finalgrade->usermodified])) {
                    $conditions = array('id' => $curuser->instancedata[$instance->id]->finalgrade->usermodified);
                    $userobj = $DB->get_record('user', $conditions, 'id, '.implode(', ', get_all_user_name_fields()));
                    $usermodified = $curuser->instancedata[$instance->id]->finalgrade->usermodified;
                    $users[$usermodified] = fullname($userobj);
                }
                if (empty($users[$curuser->id])) {
                    $conditions = array('id' => $curuser->id);
                    $userobj = $DB->get_record('user', $conditions, 'id, '.implode(', ', get_all_user_name_fields()));
                    $userid = $curuser->id;
                    $users[$userid] = fullname($userobj);
                }
                if (!empty($showgrade)) {
                    $grade = $curuser->instancedata[$instance->id]->grade;
                    $finalgrade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                    $locked = $curuser->instancedata[$instance->id]->finalgrade->locked;
                    if (($curuser->instancedata[$instance->id]->finalgrade->overridden
                                || $locked || ($grade != $finalgrade))
                            && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)) {
                        $grade = $this->display_grade($curuser->instancedata[$instance->id]->finalgrade->grade,
                                                      $curuser->instancedata[$instance->id]->maxgrade);
                    } else {
                        $grade = $this->display_grade($curuser->instancedata[$instance->id]->grade,
                                                      $curuser->instancedata[$instance->id]->maxgrade);
                    }
                    $row['grade'.$instance->id] = new html_table_cell($grade);
                    // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                    $grade = $curuser->instancedata[$instance->id]->grade;
                    $finalgrade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                    $locked = $curuser->instancedata[$instance->id]->finalgrade->locked;
                    if (($curuser->instancedata[$instance->id]->finalgrade->overridden
                            || $locked || ($grade != $finalgrade))
                        && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)) {
                        $row['grade'.$instance->id]->attributes['class'] = 'current';

                        $dategraded = $curuser->instancedata[$instance->id]->finalgrade->dategraded;
                        $usermodified = $curuser->instancedata[$instance->id]->finalgrade->usermodified;
                        $row['grade'.$instance->id]->id = "u".$curuser->id."i".$instance->id."_a";
                        $row['grade'.$instance->id]->attributes['data-user'] = $curuser->id;
                        $row['grade'.$instance->id]->attributes['data-username'] = fullname($users[$curuser->id]);
                        $row['grade'.$instance->id]->attributes['data-item'] = $instance->id;
                        $row['grade'.$instance->id]->attributes['data-dategraded'] = userdate($dategraded);
                        $row['grade'.$instance->id]->attributes['data-grader'] = $users[$usermodified];
                    }
                }
                // Coursesum of course examples.
                if (!empty($showabs)) {
                    $coursesumtext = $curuser->instancedata[$instance->id]->checked.' / '.
                                     $curuser->instancedata[$instance->id]->maxchecked;
                    $row['examples'.$instance->id] = new html_table_cell($coursesumtext);
                }
                // Percent of course examples.
                if (!empty($showrel)) {
                    $grade = $curuser->instancedata[$instance->id]->grade;
                    $finalgrade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                    $locked = $curuser->instancedata[$instance->id]->finalgrade->locked;
                    if (empty($curuser->instancedata[$instance->id]->percentchecked)) {
                        $perccheck = 0;
                    } else {
                        $perccheck = $curuser->instancedata[$instance->id]->percentchecked;
                    }
                    if (($curuser->instancedata[$instance->id]->finalgrade->overridden
                            || $locked || ($grade != $finalgrade))
                        && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)) {
                        $grade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                        $maxgrade = $curuser->instancedata[$instance->id]->maxgrade;
                        if ($maxgrade > 0) {
                            $rel = $grade / $maxgrade;
                            $percgrade = round(100 * $rel, 2);
                        } else {
                            $percgrade = '-';
                            $rel = '-';
                        }
                    } else {
                        if (empty($curuser->instancedata[$instance->id]->grade)) {
                            $percgrade = round(0, 2);
                        } else if ($curuser->instancedata[$instance->id]->grade > 0) {
                            $percgrade = round($curuser->instancedata[$instance->id]->percentgrade, 2);
                        } else {
                            $percgrade = '-';
                        }
                    }
                    if (is_numeric($percgrade)) {
                        $percgrade = round($percgrade, 2).'%';
                    }
                    $row['percentex'.$instance->id] = new html_table_cell(round($perccheck, 2).'% ('.$percgrade.')');
                    // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                    $finalgrade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                    $grade = $curuser->instancedata[$instance->id]->grade;
                    $locked = $curuser->instancedata[$instance->id]->finalgrade->locked;
                    if (($curuser->instancedata[$instance->id]->finalgrade->overridden
                            || $locked || ($grade != $finalgrade))
                        && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)) {
                        $row['percentex'.$instance->id]->attributes['class'] = 'current';

                        $dategraded = $curuser->instancedata[$instance->id]->finalgrade->dategraded;
                        $usermodified = $curuser->instancedata[$instance->id]->finalgrade->usermodified;
                        $row['percentex'.$instance->id]->id = "u".$curuser->id."i".$instance->id."_r";
                        $row['percentex'.$instance->id]->attributes['data-user'] = $curuser->id;
                        $row['percentex'.$instance->id]->attributes['data-username'] = fullname($users[$curuser->id]);
                        $row['percentex'.$instance->id]->attributes['data-item'] = $instance->id;
                        $row['percentex'.$instance->id]->attributes['data-dategraded'] = userdate($dategraded);
                        $row['percentex'.$instance->id]->attributes['data-grader'] = $users[$usermodified];
                    }
                }

                if (!empty($showattendances) && $this->attendancestracked() && $this->tracksattendance($instance->id)) {
                    $text = checkmark_get_attendance_symbol($curuser->instancedata[$instance->id]->attendance);
                    $row['attendance'.$instance->id] = new html_table_cell($text);
                    // We have to get the raw value also out there, so we can display it in spreadsheets!
                    $att = $curuser->instancedata[$instance->id]->attendance;
                    $attendance = '?';
                    if ($att == 1) {
                        $attendance = '✓';
                    } else if (($att == 0) && ($att !== null)) {
                        $attendance = '✗';
                    }
                    $row['attendance'.$instance->id]->character = $attendance;
                }

                $gradepresentation = $this->gradepresentations($instance->id);
                if ($gradepresentation && !$gradepresentation->presentationgrade) {
                    // Prevent comment only presentationgrades to mess with table!
                    $gradepresentation = false;
                } else if ($gradepresentation && $gradepresentation->presentationgradebook) {
                    if (empty($users[$curuser->instancedata[$instance->id]->finalpresgrade->usermodified])) {
                        $conditions = array('id' => $curuser->instancedata[$instance->id]->finalpresgrade->usermodified);
                        $userobj = $DB->get_record('user', $conditions, 'id, '.implode(', ', get_all_user_name_fields()));
                        $usermodified = $curuser->instancedata[$instance->id]->finalpresgrade->usermodified;
                        $users[$usermodified] = fullname($userobj);
                    }
                }
                if (!empty($showpresgrades) && $this->presentationsgraded() && $gradepresentation
                        && $gradepresentation->presentationgrade) {
                    if ($gradepresentation->presentationgradebook) {
                        $presentationgrade = $curuser->instancedata[$instance->id]->formattedpresgrade;
                        $finalgrade = $curuser->instancedata[$instance->id]->finalpresgrade;
                        $overridden = $curuser->instancedata[$instance->id]->finalpresgrade->overridden;
                        $locked = $curuser->instancedata[$instance->id]->finalpresgrade->locked;
                    } else {
                        // Returns '-' or presentationgrade/maxpresentationgrade or scale item!
                        $presentationgrade = $this->display_grade($curuser->instancedata[$instance->id]->presentationgrade,
                                                                  $gradepresentation->presentationgrade);
                    }

                    $row['presentationgrade'.$instance->id] = new html_table_cell($presentationgrade);

                    // Highlight if overwritten or locked!
                    if ($gradepresentation->presentationgradebook) {
                        if ($overridden || $locked) {
                            $row['presentationgrade'.$instance->id]->attributes['class'] = 'current';

                            $dategraded = $finalgrade->dategraded;
                            $usermodified = $finalgrade->usermodified;
                            $row['presentationgrade'.$instance->id]->id = "u".$curuser->id."i".$instance->id."_a";
                            $row['presentationgrade'.$instance->id]->attributes['data-user'] = $curuser->id;
                            $row['presentationgrade'.$instance->id]->attributes['data-username'] = fullname($users[$curuser->id]);
                            $row['presentationgrade'.$instance->id]->attributes['data-item'] = $instance->id;
                            $row['presentationgrade'.$instance->id]->attributes['data-dategraded'] = userdate($dategraded);
                            $row['presentationgrade'.$instance->id]->attributes['data-grader'] = $users[$usermodified];
                        }
                    }
                }

                if (!empty($showexamples)) {
                    // Dynamically add examples!
                    foreach ($curuser->instancedata[$instance->id]->examples as $key => $example) {
                        if (empty($showpoints)) {
                            $row['example'.$key] = new html_table_cell($example ? "☒" : "☐");
                        } else {
                            $row['example'.$key] = new html_table_cell($example ? $examplenames[$instance->id][$key]->grade : "0");
                        }
                    }
                }
            }

            if ($signature) {
                $row['sig'] = new html_table_cell('');
            }

            $table->data[$userid] = new html_table_row();
            $table->data[$userid]->cells = $row;
        }
        $performance->table_built = microtime(true);

        // Init JS!
        $params = new \stdClass();
        $params->id  = $table->id;
        $PAGE->requires->js_call_amd('local_checkmarkreport/report', 'initializer', array($params));

        return $table;
    }

    /**
     * get data as xml file (sends to browser, forces download)
     *
     * @return void
     */
    public function get_xml() {
        global $DB;
        $data = $this->get_coursedata();
        $course = $DB->get_record('course', array('id' => $this->courseid));
        $xml = '';
        $examplenames = array();
        $instances = $this->get_courseinstances();

        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showexamples = get_user_preferences('checkmarkreport_showexamples');
        $showattendances = get_user_preferences('checkmarkreport_showattendances');
        $showpresgrades = get_user_preferences('checkmarkreport_showpresentationgrades');

        $xml = new DOMDocument(1.0, 'UTF-8');
        $xml->formatOutput = true;
        $report = $xml->appendChild(new DOMElement('report'));

        foreach ($data as $userid => $row) {
            $user = $report->appendChild(new DOMElement('user'));
            if (!$this->column_is_hidden('id')) {
                $user->setAttribute('id', $userid);
            }
            if (!$this->column_is_hidden('fullnameuser')) {
                $user->setAttribute('fullname', fullname($row));
            }
            foreach ($row->userdata as $key => $cur) {
                if (!$this->column_is_hidden($key)) {
                    $user->setAttribute($key, $cur);
                }
            }
            if (!$this->column_is_hidden('grade') && !empty($showgrade)) {
                if ($row->overridden) {
                    $user->setAttribute('overridden', true);
                    $user->setAttribute('grade', empty($row->coursesum) ? 0 : round($row->coursesum, 2));
                }
                $user->setAttribute('checkedgrade', empty($row->checkgrade) ? 0 : $row->checkgrade);
                $user->setAttribute('maxgrade', empty($row->maxgrade) ? 0 : $row->maxgrade);
            }
            if (!$this->column_is_hidden('examples') && !empty($showabs)) {
                $user->setAttribute('checks', $row->checks);
                $user->setAttribute('maxchecks', $row->maxchecks);
            }
            if (!$this->column_is_hidden('percentex') && !empty($showrel)) {
                $user->setAttribute('percentchecked', round($row->percentchecked, 2).'%');
                if ($row->overridden) {
                    $percgrade = round(empty($row->coursesum) ? 0 : 100 * $row->coursesum / $row->maxgrade, 2);
                } else {
                    $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
                }
                $user->setAttribute('percentgrade', $percgrade.'%');
            }
            $instancesnode = $user->appendChild(new DOMElement('instances'));
            if (!$this->column_is_hidden('attendance') && !empty($showattendances) && $this->attendancestracked()) {
                $instancesnode->setAttribute('attendant', $row->attendances);
                $instancesnode->setAttribute('attendance_max', $row->maxattendances);
            }
            if (!$this->column_is_hidden('presentationgrade') && !empty($showpresgrades) && $this->presentationsgraded()) {
                $instancesnode->setAttribute('presentationgrade', empty($row->presentationgrade) ? 0 : $row->presentationgrade);
                if (!empty($row->presentationgrademax)) {
                    $instancesnode->setAttribute('presentationgrademax', $row->presentationgrademax);
                } else {
                    $instancesnode->setAttribute('presentationgrademax', 0);
                }
            }
            $examplecounter = 1;
            foreach ($instances as $instance) {
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
                }
                if ($this->column_is_hidden('instance'.$instance->id)) {
                    foreach ($examplenames[$instance->id] as $key => $example) {
                        $examplecounter++;
                    }
                    continue;
                }
                $gradepresentation = $this->gradepresentations($instance->id);
                if ($gradepresentation && !$gradepresentation->presentationgrade) {
                    // Prevent comment only presentationgrades from showing up here!
                    $gradepresentation = false;
                }
                $instancedata = $row->instancedata[$instance->id];
                $instnode = $instancesnode->appendChild(new DOMElement('instance'));
                $instnode->setAttribute('name', $instance->name);
                if (!$this->column_is_hidden('grade'.$instance->id) && !empty($showgrade)) {
                    $instnode->setAttribute('checkedgrade', empty($instancedata->grade) ? 0 : $instancedata->grade);
                    if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
                        $instnode->setAttribute('overridden', true);
                        $instnode->setAttribute('grade', $instancedata->finalgrade->grade);
                    }
                    $instnode->setAttribute('maxgrade', empty($instancedata->maxgrade) ? 0 : $instancedata->maxgrade);
                }
                if (!$this->column_is_hidden('examples'.$instance->id) && !empty($showabs)) {
                    $instnode->setAttribute('checks', $instancedata->checked);
                    $instnode->setAttribute('maxchecks', $instancedata->maxchecked);
                }
                if (!$this->column_is_hidden('percentex'.$instance->id) && !empty($showrel)) {
                    if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
                        $grade = (empty($instancedata->finalgrade->grade) ? 0 : $instancedata->finalgrade->grade);
                        if ($instancedata->maxgrade > 0) {
                            $percgrade = round(100 * $grade / $instancedata->maxgrade, 2).' %';
                        } else {
                            $percgrade = '-';
                        }
                    } else {
                        $percgrade = round((empty($instancedata->percentgrade) ? 0 : $instancedata->percentgrade), 2).' %';
                    }
                    if ($showabs) {
                        $instnode->setAttribute('percentchecked', $instancedata->percentchecked.'%');
                    }
                    if ($showgrade) {
                        $instnode->setAttribute('percentgrade', $percgrade);
                    }
                }

                if (!empty($showattendances)) {
                    $this->add_xml_attendance_data($instnode, $instancedata, $instance->id);
                }

                if (!empty($showpresgrades)) {
                    $this->add_xml_presentation_data($instnode, $instancedata, $instance->id, $gradepresentation);
                }

                if (!empty($showexamples)) {
                    $exsnode = $instnode->appendChild(new DOMElement('examples'));
                    foreach ($instancedata->examples as $key => $example) {
                        if (!$this->column_is_hidden('example'.$examplecounter)) {
                            $exnode = $exsnode->appendChild(new DOMElement('example'));
                            $exnode->setAttribute('name', $examplenames[$instance->id][$key]->name);
                            $exnode->setAttribute('state', $example ? 1 : 0);
                            $exnode->setAttribute('statesymbol', $example ? "☒" : "☐");
                        }
                        $examplecounter++;
                    }
                }
            }
        }

        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.
                    $course->shortname.'_'.userdate(time());
        $this->output_xml_with_headers($xml, $filename);
    }

    /**
     * get report data as plain text file (sends to browser, forces download)
     *
     * @return void
     */
    public function get_txt() {
        global $DB;
        $data = $this->get_coursedata();
        $course = $DB->get_record('course', array('id' => $this->courseid));
        $context = context_course::instance($this->courseid);

        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showexamples = get_user_preferences('checkmarkreport_showexamples');
        $showattendances = get_user_preferences('checkmarkreport_showattendances');
        $showpresgrades = get_user_preferences('checkmarkreport_showpresentationgrades');

        $txt = '';
        $examplenames = array();
        $instances = $this->get_courseinstances();
        $course = $DB->get_record('course', array('id' => $this->courseid));
        // Header.
        $txt .= get_string('pluginname', 'local_checkmarkreport').': '.$course->fullname."\n";
        // Title.
        if (!$this->column_is_hidden('fullnameuser')) {
            $txt .= get_string('fullname');
        }
        $useridentity = get_extra_user_fields($context);
        foreach ($useridentity as $cur) {
            if (!$this->column_is_hidden($cur)) {
                $txt .= "\t".(($cur == 'phone1') ? get_string('phone') : get_string($cur));
            }
        }
        if (!$this->column_is_hidden('grade') && !empty($showgrade)) {
            $txt .= "\tS ".get_string('grade');
        }
        if (!$this->column_is_hidden('examples') && !empty($showabs)) {
            $txt .= "\tS ".get_string('examples', 'local_checkmarkreport');
        }
        if (!$this->column_is_hidden('percentex') && !empty($showrel)) {
            $txt .= "\t";
            $txt .= 'S % '.get_string('examples', 'local_checkmarkreport').' (S % '.get_string('grade').')';
        }
        if (!empty($showattendances) && $this->attendancestracked()) {
            $txt .= "\tS ".get_string('attendance', 'checkmark');
        }
        if (!$this->column_is_hidden('presentationgrade', 'checkmark') && !empty($showpresgrades)
                && $this->presentationsgraded()) {
            $txt .= "\tS ".get_string('presentationgrade', 'checkmark');
        }

        $instances = $this->get_courseinstances();
        $examplecounter = 1;
        foreach ($instances as $instance) {
            // Get example data!
            if (!isset($examplenames[$instance->id])) {
                $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
            }
            if ($this->column_is_hidden('instance'.$instance->id)) {
                foreach ($examplenames[$instance->id] as $key => $example) {
                    $examplecounter++;
                }
                continue;
            }
            $gradepresentation = $this->gradepresentations($instance->id);
            if ($gradepresentation && !$gradepresentation->presentationgrade) {
                // Prevent comment only presentationgrades to mess with table!
                $gradepresentation = false;
            }
            if (!$this->column_is_hidden('grade'.$instance->id) && !empty($showgrade)) {
                $txt .= "\t".$instance->name.' '.get_string('grade');
            }
            if (!$this->column_is_hidden('examples'.$instance->id) && !empty($showabs)) {
                $txt .= "\t".$instance->name.' '.get_string('examples', 'local_checkmarkreport');
            }
            if (!$this->column_is_hidden('percentex'.$instance->id) && !empty($showrel)) {
                $txt .= "\t";
                $txt .= $instance->name.' S % '.get_string('examples', 'local_checkmarkreport').' (S % '.get_string('grade').')';
            }
            if (!$this->column_is_hidden('attendance'.$instance->id) && !empty($showattendances) && $this->attendancestracked()
                    && $this->tracksattendance($instance->id)) {
                $txt .= "\t";
                $txt .= $instance->name.' '.get_string('attendance', 'checkmark');
            }
            if (!$this->column_is_hidden('presentationgrade'.$instance->id) && !empty($showpresgrades)
                    && $this->presentationsgraded() && $gradepresentation) {
                $txt .= "\t";
                $txt .= $instance->name.' '.get_string('presentationgrade', 'checkmark');
            }
            if (!empty($showexamples)) {
                // Dynamically add examples!
                foreach ($examplenames[$instance->id] as $key => $example) {
                    if (!$this->column_is_hidden('example'.$examplecounter)) {
                        $txt .= "\t".$instance->name.' '.$example->name." (".$example->grade.'P)';
                    }
                    $examplecounter++;
                }
            }
        }
        $txt .= "\n";

        // Data.
        foreach ($data as $row) {
            if (!$this->column_is_hidden('fullnameuser')) {
                $txt .= fullname($row);
            }
            foreach ($row->userdata as $key => $cur) {
                if (!$this->column_is_hidden($key)) {
                    $txt .= "\t".$cur;
                }
            }
            if (!$this->column_is_hidden('grade') && !empty($showgrade)) {
                if ($row->overridden) {
                    $txt .= "\t".(empty($row->coursesum) ? 0 : $row->coursesum)."/".(empty($row->maxgrade) ? 0 : $row->maxgrade);
                } else {
                    $txt .= "\t".(empty($row->checkgrade) ? 0 : $row->checkgrade)."/".(empty($row->maxgrade) ? 0 : $row->maxgrade);
                }
            }
            if (!$this->column_is_hidden('examples') && !empty($showabs)) {
                $txt .= "\t".$row->checks."/".$row->maxchecks;
            }
            if (!$this->column_is_hidden('percentex') && !empty($showrel)) {
                if ($row->overridden) {
                    $percgrade = round(100 * (empty($row->coursesum) ? 0 : $row->coursesum) / $row->maxgrade, 2);
                } else {
                    $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
                }
                $txt .= "\t";
                $txt .= $row->percentchecked.'% ('.$percgrade.'%)';
            }
            if (!$this->column_is_hidden('attendance') && !empty($showattendances) && $this->attendancestracked()) {
                $txt .= "\t";
                $txt .= $row->attendances.'/'.$row->maxattendances;
            }
            if (!$this->column_is_hidden('presentationgrade') && !empty($showpresgrades) && $this->presentationsgraded()) {
                $txt .= "\t";
                $txt .= $this->display_grade($row->presentationgrade, $row->presentationgrademax);
            }
            $examplecount = 1;
            foreach ($instances as $instance) {
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
                }
                $instancedata = $row->instancedata[$instance->id];
                $gradepresentation = $this->gradepresentations($instance->id);
                if ($gradepresentation && !$gradepresentation->presentationgrade) {
                    // Prevent comment only presentationgrades to mess with table!
                    $gradepresentation = false;
                }
                if (!$this->column_is_hidden('grade'.$instance->id) && !empty($showgrade)) {
                    if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
                        $txt .= "\t".$this->display_grade($instancedata->finalgrade->grade, $instancedata->maxgrade);
                    } else {
                        $txt .= "\t".$this->display_grade($instancedata->grade, $instancedata->maxgrade);
                    }
                }
                if (!$this->column_is_hidden('examples'.$instance->id) && !empty($showabs)) {
                    $txt .= "\t".$instancedata->checked."/".$instancedata->maxchecked;
                }
                if (!$this->column_is_hidden('percentex'.$instance->id) && !empty($showrel)) {
                    if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
                        $grade = (empty($instancedata->finalgrade->grade) ? 0 : $instancedata->finalgrade->grade);
                        if ($instancedata->maxgrade > 0) {
                            $percgrade = round(100 * $grade / $instancedata->maxgrade, 2).' %';
                        } else {
                            $percgrade = '-';
                        }
                    } else {
                        $percgrade = round((empty($instancedata->percentgrade) ? 0 : $instancedata->percentgrade), 2).' %';
                    }
                    $txt .= "\t";
                    $txt .= $instancedata->percentchecked.'% ('.$percgrade.')';
                }
                if (!$this->column_is_hidden('attendance'.$instance->id) && !empty($showattendances) && $this->attendancestracked()
                        && $this->tracksattendance($instance->id)) {
                    $attendance = '?';
                    if ($instancedata->attendance == 1) {
                        $attendance = '✓';
                    } else if (($instancedata->attendance == 0) && ($instancedata->attendance !== null)) {
                        $attendance = '✗';
                    }
                    $txt .= "\t".$attendance;
                }

                if (!$this->column_is_hidden('presentationgrade'.$instance->id) && !empty($showpresgrades)
                        && $this->presentationsgraded() && $gradepresentation) {
                    if ($gradepresentation->presentationgradebook) {
                        $presentationgrade = $instancedata->formattedpresgrade;
                    } else {
                        $presentationgrade = $this->display_grade($instancedata->presentationgrade,
                                                                  $gradepresentation->presentationgrade);
                    }
                    $txt .= "\t".$presentationgrade;
                }

                if (!empty($showexamples)) {
                    foreach ($instancedata->examples as $key => $example) {
                        if (!$this->column_is_hidden('example'.$examplecount)) {
                            $txt .= "\t".($example ? "☒" : "☐");
                        }
                        $examplecount++;
                    }
                }
            }
            $txt .= "\n";
        }
        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.
                    $course->shortname.'_'.userdate(time());
        $this->output_text_with_headers($txt, $filename);
    }

    /**
     * Write report data to workbook
     *
     * @param MoodleExcelWorkbook|MoodleODSWorkbook $workbook object to write data into
     * @return void
     */
    public function fill_workbook($workbook) {
        $x = $y = 0;

        // We start with the html_table-Object.
        $table = $this->get_table();

        $worksheet = $workbook->add_worksheet(time());

        // We may use additional table data to format sheets!
        $this->prepare_worksheet($table, $worksheet, $x, $y);

        foreach ($table->head as $headrow) {
            $x = 0;
            foreach ($headrow->cells as $key => $heading) {
                if (!empty($heading) && $this->column_is_hidden($key)) {
                    // Hide column in worksheet!
                    $worksheet->set_column($x, $x + $heading->colspan - 1, 0, null, true);
                }
                $x++;
            }
        }

        if (!empty($table->data)) {
            if (empty($table->head)) {
                // Head was empty, we have to check this here!
                $x = 0;
                $cur = current($table->data);
                $keys = array_keys($cur);
                foreach ($keys as $key) {
                    if ($this->column_is_hidden($key)) {
                        // Hide column in worksheet!
                        $worksheet->set_column($x, $x, 0, null, true);
                    }
                    $x++;
                }
            }

            $oddeven    = 1;
            $keys       = array_keys($table->data);
            $lastrowkey = end($keys);

            foreach ($table->data as $key => $row) {
                $x = 0;
                // Convert array rows to html_table_rows and cell strings to html_table_cell objects!
                if (!($row instanceof html_table_row)) {
                    $newrow = new html_table_row();

                    foreach ($row as $cell) {
                        if (!($cell instanceof html_table_cell)) {
                            $cell = new html_table_cell($cell);
                        }
                        $newrow->cells[] = $cell;
                    }
                    $row = $newrow;
                }

                $oddeven = $oddeven ? 0 : 1;
                if (isset($table->rowclasses[$key])) {
                    $row->attributes['class'] .= ' ' . $table->rowclasses[$key];
                }

                $row->attributes['class'] .= ' r' . $oddeven;
                if ($key == $lastrowkey) {
                    $row->attributes['class'] .= ' lastrow';
                }

                $keys2 = array_keys($row->cells);
                $lastkey = end($keys2);

                $gotlastkey = false; // Flag for sanity checking.
                foreach ($row->cells as $key => $cell) {
                    if ($gotlastkey) {
                        // This should never happen. Why do we have a cell after the last cell?
                        mtrace("A cell with key ($key) was found after the last key ($lastkey)");
                    }

                    if ($cell == null) {
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
                    if (!isset($cell->rowspan)) {
                        $cell->rowspan = 1;
                    }
                    if (!isset($cell->colspan)) {
                        $cell->colspan = 1;
                    }
                    // We need this, to overwrite the images for attendance with simple characters!
                    if (!empty($cell->character)) {
                        $worksheet->write_string($y, $x, strip_tags($cell->character));
                    } else {
                        $worksheet->write_string($y, $x, strip_tags($cell->text));
                    }
                    $worksheet->merge_cells($y, $x, $y + $cell->rowspan - 1, $x + $cell->colspan - 1);
                    $x++;
                }
                $y++;
            }
        }
    }
}

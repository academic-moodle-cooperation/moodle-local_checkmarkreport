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
 * @author    Philipp Hager, Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * local_checkmarkreport_overview class, handles checkmarkreport overview content and export
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager, Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_checkmarkreport_overview extends local_checkmarkreport_base implements renderable {
    /** @var string classes to assign to the reporttable */
    protected $tableclass = 'table table-condensed table-hover overview';

    /**
     * Constructor
     *
     * @param int $id course id
     * @param int[] $groupings (optional) groupings to include
     * @param int[] $groups (optional) groups to include
     * @param int[] $instances (optional) instances to include
     */
    public function __construct($id, $groupings = [0], $groups = [0], $instances = [0]) {
        global $DB;

        if (!in_array(0, $groupings)) {
            [$insql, $params] = $DB->get_in_or_equal($groupings);
            $grpgsgrps = $DB->get_fieldset_select('groupings_groups', 'DISTINCT groupid', 'groupingid ' . $insql, $params);
            if (in_array(0, $groups) || empty($groups)) {
                $groups = $grpgsgrps;
            } else {
                $groups = array_intersect($groups, $grpgsgrps);
            }
        }

        if (!in_array(0, $groups)) {
            [$insql, $params] = $DB->get_in_or_equal($groups);
            $users = $DB->get_fieldset_select('groups_members', 'DISTINCT userid', 'groupid ' . $insql, $params);
        } else {
            $users = [0];
        }
        parent::__construct($id, $groups, $users, $instances);
    }

    /**
     * get html table object representing report data
     *
     * @param boolean $forexport
     * @return html_table report as html_table object
     */
    public function get_table($forexport = false) {
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
        $showprescount = get_user_preferences('checkmarkreport_showpresentationcount');
        $signature = get_user_preferences('checkmarkreport_signature');
        $seperatenamecolumns = get_user_preferences('checkmarkreport_seperatenamecolumns');

        $table = new \local_checkmarkreport\html_table_colgroups();

        $table->id = 'user-grades'; // Was former "attempts"! Changed due to style of overridden grades!
        $table->attributes['class'] = $this->tableclass;

        $tableheaders = [];
        $tablecolumns = [];
        $table->colgroups = [];
        $sortable = [];
        $useridentity = \core_user\fields::for_identity($context)->get_required_fields();
        // Firstname sortlink.
        $firstname = $this->get_sortlink('firstname', get_string('firstname'), $PAGE->url);
        // Lastname sortlink.
        $lastname = $this->get_sortlink('lastname', get_string('lastname'), $PAGE->url);
        if ($seperatenamecolumns) {
            // Add names at beginning of $useridentity so they are output together with those fields.
            $namecolumns = $this->get_name_header(
                has_capability('moodle/site:viewfullnames', $context),
                $seperatenamecolumns
            );
            $useridentity = array_merge($namecolumns, $useridentity);
        } else {
            $tableheaders['fullnameuser'] = new html_table_cell($this->get_name_header(has_capability(
                'moodle/site:viewfullnames',
                $context
            ), false, $sortable));

            $tableheaders['fullnameuser']->header = true;
            $tableheaders['fullnameuser']->rowspan = 2;
            $tableheaders2['fullnameuser'] = null;
            $tablecolumns[] = 'fullnameuser';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'fullnameuser',
            ];
            $table->colclasses['fullnameuser'] = 'fullnameuser';
        }
        foreach ($useridentity as $cur) {
            $sortable[] = $cur;
            $text = \core_user\fields::get_display_name($cur);
            $sortlink = $this->get_sortlink($cur, $text, $PAGE->url);
            $tableheaders[$cur] = new html_table_cell($sortlink);
            $tableheaders[$cur]->header = true;
            $tableheaders[$cur]->rowspan = 2;
            $tableheaders2[$cur] = null;
            $tablecolumns[] = $cur;
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => $cur,
            ];
            $table->colclasses[$cur] = $cur;
        }

        // Coursesum of course grade.
        if (!empty($showgrade)) {
            $sortlink = $this->get_sortlink('gradedgrade', 'Σ ' . get_string('modgrade', 'grades'), $PAGE->url);
            $sortable[] = 'grade';
            $tableheaders['grade'] = new html_table_cell($sortlink);
            $tableheaders['grade']->header = true;
            $tableheaders['grade']->rowspan = 2;
            $tableheaders2['grade'] = null;
            $tablecolumns[] = 'grade';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'grade',
            ];
            $table->colclasses['grade'] = 'grade';
        }

        // Coursesum of course examples.
        if (!empty($showabs)) {
            $text = 'Σ ' . get_string('examples', 'local_checkmarkreport');
            $sortlink = $this->get_sortlink('checks', $text, $PAGE->url);
            $sortable[] = 'examples';
            $tableheaders['examples'] = new html_table_cell($sortlink);
            $tableheaders['examples']->header = true;
            $tableheaders['examples']->rowspan = 2;
            $tableheaders2['examples'] = null;
            $tablecolumns[] = 'examples';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'examples',
            ];
            $table->colclasses['examples'] = 'examples';
        }

        if (!empty($showrel)) {
            // Percent of course examples.
            $text = 'Σ % ' .
                    $this->get_sortlink(
                        'percentchecked',
                        get_string(
                            'examples',
                            'local_checkmarkreport'
                        ),
                        $PAGE->url
                    ) .
                    ' (' .
                    $this->get_sortlink(
                        'percentgrade',
                        get_string('modgrade', 'grades'),
                        $PAGE->url
                    ) . ')';
            $sortable[] = 'percentex';
            $tableheaders['percentex'] = new html_table_cell($text);
            $tableheaders['percentex']->header = true;
            $tableheaders['percentex']->rowspan = 2;
            $tableheaders2['percentex'] = null;
            $tablecolumns[] = 'percentex';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'percentex',
            ];
            $table->colclasses['percentex'] = 'percentex';
        }

        if (!empty($showattendances) && $this->attendancestracked()) {
            // Amount of attendances.
            $text = get_string('attendance', 'checkmark');
            $sortable[] = 'attendances';
            $text = $this->get_sortlink('attendances', 'Σ ' . get_string('attendances', 'local_checkmarkreport'), $PAGE->url);
            $tableheaders['attendances'] = new html_table_cell($text);
            $tableheaders['attendances']->header = true;
            $tableheaders['attendances']->rowspan = 2;
            $tableheaders2['attendances'] = null;
            $tablecolumns[] = 'attendances';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'attendances',
            ];
            $table->colclasses['attendances'] = 'attendances';
        }

        if (!empty($showpresgrades) && $this->presentationsgraded() && $this->pointsforpresentations()) {
            $sortlink = $this->get_sortlink('presentationgrade', 'Σ ' . get_string('presentationgrade', 'checkmark'), $PAGE->url);
            $sortable[] = 'presentationgrade';
            $tableheaders['presentationgrade'] = new html_table_cell($sortlink);
            $tableheaders['presentationgrade']->header = true;
            $tableheaders['presentationgrade']->rowspan = 2;
            $tableheaders2['presentationgrade'] = null;
            $tablecolumns[] = 'presentationgrade';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'presentationgrade',
            ];
            $table->colclasses['presentationgrade'] = 'presentationgrade';
        }

        if (!empty($showprescount) && $this->presentationsgraded() && $this->countgradingpresentations()) {
            $sortlink = $this->get_sortlink('presentationsgraded', '# ' . get_string('presentationgrade', 'checkmark'), $PAGE->url);
            $sortable[] = 'presentationsgraded';
            $tableheaders['presentationsgraded'] = new html_table_cell($sortlink);
            $tableheaders['presentationsgraded']->header = true;
            $tableheaders['presentationsgraded']->rowspan = 2;
            $tableheaders2['presentationsgraded'] = null;
            $tablecolumns[] = 'presentationsgraded';
            $table->colgroups[] = [
                    'span' => '1',
                    'class' => 'presentationsgraded',
            ];
            $table->colclasses['presentationsgraded'] = 'presentationsgraded';
        }

        $instances = $this->get_courseinstances_formatted_name();
        foreach ($instances as $instance) {
            $span = 0;
            $gradepresentation = $this->gradepresentations($instance->id);
            if ($gradepresentation && !$gradepresentation->presentationgrade) {
                // Prevent comment only presentationgrades to mess with table!
                $gradepresentation = false;
            }
            if (
                !empty($showgrade) || !empty($showabs) || !empty($showrel)
                    || (!empty($showattendances) && $this->attendancestracked() && $this->tracksattendance($instance->id))
                    || (!empty($showpresgrades) && $this->presentationsgraded() && $gradepresentation)
                    || !empty($showexamples)
            ) {
                $instanceurl = new moodle_url('/mod/checkmark/view.php', ['id' => $instance->coursemodule]);
                $instancelink = html_writer::link($instanceurl, $instance->name);
                $tableheaders['instance' . $instance->id] = new html_table_cell($instancelink);
                $tableheaders['instance' . $instance->id]->header = true;
                $tableheaders['instance' . $instance->id]->scope = 'colgroup';
                $table->colclasses['instance' . $instance->id] = 'instance' . $instance->id;
            }
            // Coursesum of course grade.
            if (!empty($showgrade)) {
                $span++;
                $text = get_string('modgrade', 'grades');
                $sortable[] = 'grade' . $instance->id;
                $sortlink = $this->get_sortlink('grade' . $instance->id, $text, $PAGE->url);
                $tableheaders2['grade' . $instance->id] = new html_table_cell($sortlink);
                $tableheaders2['grade' . $instance->id]->header = true;
                $tablecolumns[] = 'grade' . $instance->id;
                $table->colclasses['grade' . $instance->id] = 'instance' . $instance->id . ' grade' . $instance->id;
            }

            // Coursesum of course examples.
            if (!empty($showabs)) {
                $span++;
                $text = get_string('examples', 'local_checkmarkreport');
                $sortable[] = 'examples' . $instance->id;
                $sortlink = $this->get_sortlink('checks' . $instance->id, $text, $PAGE->url);
                $tableheaders2['examples' . $instance->id] = new html_table_cell($sortlink);
                $tableheaders2['examples' . $instance->id]->header = true;
                $tablecolumns[] = 'examples';
                $table->colclasses['examples' . $instance->id] = 'instance' . $instance->id . ' examples' . $instance->id;
            }

            // Percent of course examples.
            if (!empty($showrel)) {
                $span++;
                $title = '% ' .
                        $this->get_sortlink(
                            'percentchecked' . $instance->id,
                            get_string('examples', 'local_checkmarkreport'),
                            $PAGE->url
                        ) .
                        ' (' .
                        $this->get_sortlink('percentgrade' . $instance->id, get_string('modgrade', 'grades'), $PAGE->url) . ')';
                $sortable[] = 'percentex' . $instance->id;
                $tableheaders2['percentex' . $instance->id] = new html_table_cell($title);
                $tableheaders2['percentex' . $instance->id]->header = true;
                $tablecolumns[] = 'percentex' . $instance->id;
                $table->colclasses['percentex' . $instance->id] = 'instance' . $instance->id . ' percentex' . $instance->id;
            }
            if (!empty($showattendances) && $this->attendancestracked() && $this->tracksattendance($instance->id)) {
                $span++;
                $text = $this->get_sortlink('attendance' . $instance->id, get_string('attendance', 'checkmark'), $PAGE->url);
                $sortable[] = 'attendance' . $instance->id;
                $tableheaders2['attendance' . $instance->id] = new html_table_cell($text);
                $tableheaders2['attendance' . $instance->id]->header = true;
                $tablecolumns[] = 'attendance' . $instance->id;
                $table->colclasses['attendance' . $instance->id] = 'instance' . $instance->id . ' attendance' . $instance->id;
            }
            if (!empty($showpresgrades) && $this->presentationsgraded() && $gradepresentation) {
                $span++;
                $sortlink = $this->get_sortlink(
                    'presentationgrade' . $instance->id,
                    get_string('presentationgrade', 'checkmark'),
                    $PAGE->url
                );
                $sortable[] = 'presentationgrade' . $instance->id;
                $tableheaders2['presentationgrade' . $instance->id] = new html_table_cell($sortlink);
                $tableheaders2['presentationgrade' . $instance->id]->header = true;
                $tablecolumns[] = 'presentationgrade' . $instance->id;
                $table->colgroups[] = [
                        'span' => '1',
                        'class' => 'presentationgrade' . $instance->id,
                ];
                $table->colclasses['presentationgrade' . $instance->id] = 'instance' . $instance->id . ' presentationgrade' .
                        $instance->id;
            }
            // Dynamically add examples!
            if (!empty($showexamples)) {
                // First get example data!
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records(
                        'checkmark_examples',
                        ['checkmarkid' => $instance->id],
                        'id ASC'
                    );
                }
                foreach ($examplenames[$instance->id] as $key => $example) {
                    $span++;
                    $tableheaders2['example' . $key] = new html_table_cell($example->name . " (" . $example->grade . 'P)');
                    $tableheaders2['example' . $key]->header = true;
                    $tablecolumns[] = 'example' . $key;
                    $table->colclasses['example' . $key] = 'instance' . $instance->id . ' example' . $key;
                }
            }
            if (
                !empty($showgrade) || !empty($showabs) || !empty($showrel)
                    || (!empty($showattendances) && $this->attendancestracked() && $this->tracksattendance($instance->id))
                    || (!empty($showpresgrades) && $this->presentationsgraded() && $gradepresentation)
                    || !empty($showexamples)
            ) {
                for ($i = 1; $i < $span; $i++) {
                    // Insert empty cells for the colspan!
                    $tableheaders[] = null;
                }
                $tableheaders['instance' . $instance->id]->colspan = $span;
                $table->colgroups[] = [
                        'span' => $span,
                        'class' => 'instancegroup',
                ];
            }
        }

        if ($signature) {
            $tableheaders['sig'] = new html_table_cell(get_string('signature', 'local_checkmarkreport'));
            $tableheaders['sig']->header = true;
            $tablecolumns[] = 'sig';
            $tableheaders['sig']->rowspan = 2;
            $tableheaders2['sig'] = null;
            $table->colgroups[] = [
                    'span' => 1,
                    'class' => 'sig',
            ];
            $table->colclasses['sig'] = 'sig';
        }

        $table->head = [];
        $table->head[0] = new html_table_row();
        $table->head[0]->cells = $tableheaders;
        $table->head[1] = new html_table_row();
        $table->head[1]->cells = $tableheaders2;

        if (isset($data)) {
            foreach ($data as $userid => $curuser) {
                $row = [];
                $userurl = new moodle_url('/user/view.php', [
                        'id' => $userid,
                        'course' => $this->courseid,
                ]);
                if (!$seperatenamecolumns) {
                    $userlink =
                            html_writer::link($userurl, fullname($curuser, has_capability('moodle/site:viewfullnames', $context)));
                    $row['fullnameuser'] = new html_table_cell($userlink);
                }
                foreach ($useridentity as $cur) {
                    $row[$cur] = new html_table_cell($curuser->$cur);
                }

                // Coursesum of course grade.
                if (!empty($showgrade)) {
                    $text = $this->display_grade($curuser->coursesum, $curuser->maxgrade);
                    $row['grade'] = new html_table_cell($text);
                    $row['grade']->attributes['id'] = 'u' . $curuser->id . 'i0';
                    if ($curuser->overridden) {
                        // Highlight if overwritten/other than due to checked checkmarks!
                        local_checkmarkreport_base::add_cell_tooltip($row['grade']);
                    }
                }
                // Coursesum of course examples.
                if (!empty($showabs)) {
                    $row['examples'] = new html_table_cell($curuser->checks . ' / ' . $curuser->maxchecks);
                }
                // Percent of course examples.
                if (!empty($showrel)) {
                    // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                    if ($curuser->coursesum >= 0) {
                        $percgrade = round(empty($curuser->coursesum) ? 0 : 100 * $curuser->coursesum / $curuser->maxgrade, 2);
                    } else {
                        $percgrade = '-';
                    }
                    $row['percentex'] = new html_table_cell(round($curuser->percentchecked, 2) . '% (' . $percgrade . ' %)');
                    if ($curuser->overridden) {
                        local_checkmarkreport_base::add_cell_tooltip($row['percentex']);
                    }
                }

                if (!empty($showattendances) && $this->attendancestracked()) {
                    // Amount of attendances.
                    if ($curuser->atoverridden) {
                        $attendances = $curuser->courseatsum;
                    } else {
                        $attendances = $curuser->attendances;
                    }
                    $row['attendances'] = new html_table_cell($attendances . '/' . $curuser->maxattendances);
                    if ($curuser->atoverridden) {
                        local_checkmarkreport_base::add_cell_tooltip($row['attendances']);
                    }
                }

                if (!empty($showpresgrades) && $this->presentationsgraded() && $this->pointsforpresentations()) {
                    $row['presentationgrade'] = new html_table_cell($this->display_grade(
                        $curuser->coursepressum,
                        $curuser->presentationgrademax
                    ));
                    if ($curuser->presoverridden) {
                        local_checkmarkreport_base::add_cell_tooltip($row['presentationgrade']);
                    }
                }

                if (!empty($showprescount) && $this->presentationsgraded() && $this->countgradingpresentations()) {
                    $row['presentationsgraded'] = new html_table_cell($this->display_grade(
                        $curuser->presentationsgraded,
                        $curuser->presentationsgradedmax
                    ));
                    if ($curuser->presoverridden) {
                        local_checkmarkreport_base::add_cell_tooltip($row['presentationsgraded']);
                    }
                }

                $instances = $this->get_courseinstances_formatted_name();
                $namefields = \core_user\fields::for_name()->get_required_fields();
                foreach ($instances as $instance) {
                    // Coursesum of course grade.
                    if (empty($users[$curuser->instancedata[$instance->id]->finalgrade->usermodified])) {
                        $conditions = ['id' => $curuser->instancedata[$instance->id]->finalgrade->usermodified];
                        $userobj = $DB->get_record('user', $conditions, 'id, ' . implode(', ', $namefields));
                        $usermodified = $curuser->instancedata[$instance->id]->finalgrade->usermodified;
                        $users[$usermodified] = fullname($userobj, has_capability('moodle/site:viewfullnames', $context));
                    }
                    if (empty($users[$curuser->id])) {
                        $conditions = ['id' => $curuser->id];
                        $userobj = $DB->get_record('user', $conditions, 'id, ' . implode(', ', $namefields));
                        $userid = $curuser->id;
                        $users[$userid] = fullname($userobj, has_capability('moodle/site:viewfullnames', $context));
                    }
                    if (!empty($showgrade)) {
                        $grade = $curuser->instancedata[$instance->id]->grade;
                        $finalgrade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                        $locked = $curuser->instancedata[$instance->id]->finalgrade->locked;
                        if (
                            ($curuser->instancedata[$instance->id]->finalgrade->overridden
                                        || $locked || ($grade != $finalgrade))
                                && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)
                        ) {
                            $grade = $this->display_grade(
                                $curuser->instancedata[$instance->id]->finalgrade->grade,
                                $curuser->instancedata[$instance->id]->maxgrade
                            );
                        } else {
                            $grade = $this->display_grade(
                                $curuser->instancedata[$instance->id]->grade,
                                $curuser->instancedata[$instance->id]->maxgrade
                            );
                        }
                        $row['grade' . $instance->id] = new html_table_cell($grade);
                        // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                        $grade = $curuser->instancedata[$instance->id]->grade;
                        $finalgrade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                        $locked = $curuser->instancedata[$instance->id]->finalgrade->locked;
                        if (
                            ($curuser->instancedata[$instance->id]->finalgrade->overridden
                                        || $locked || ($grade != $finalgrade))
                                && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)
                        ) {
                            $row['grade' . $instance->id]->id = "u" . $curuser->id . "i" . $instance->id . "_a";
                            $dategraded = $curuser->instancedata[$instance->id]->finalgrade->dategraded;
                            $usermodified = $curuser->instancedata[$instance->id]->finalgrade->usermodified;
                            local_checkmarkreport_base::add_cell_tooltip(
                                $row['grade' . $instance->id],
                                $instance->id,
                                $users[$curuser->id],
                                $dategraded,
                                $users[$usermodified]
                            );
                        }
                    }
                    // Coursesum of course examples.
                    if (!empty($showabs)) {
                        $coursesumtext = $curuser->instancedata[$instance->id]->checked . ' / ' .
                                $curuser->instancedata[$instance->id]->maxchecked;
                        $row['examples' . $instance->id] = new html_table_cell($coursesumtext);
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
                        if (
                            ($curuser->instancedata[$instance->id]->finalgrade->overridden
                                        || $locked || ($grade != $finalgrade))
                                && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)
                        ) {
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
                            $percgrade = round($percgrade, 2) . '%';
                        }
                        $row['percentex' . $instance->id] = new html_table_cell(round($perccheck, 2) . '% (' . $percgrade . ')');
                        // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                        $finalgrade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                        $grade = $curuser->instancedata[$instance->id]->grade;
                        $locked = $curuser->instancedata[$instance->id]->finalgrade->locked;
                        if (
                            ($curuser->instancedata[$instance->id]->finalgrade->overridden
                                        || $locked || ($grade != $finalgrade))
                                && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)
                        ) {
                            $row['percentex' . $instance->id]->id = "u" . $curuser->id . "i" . $instance->id . "_r";
                            $dategraded = $curuser->instancedata[$instance->id]->finalgrade->dategraded;
                            $usermodified = $curuser->instancedata[$instance->id]->finalgrade->usermodified;
                            local_checkmarkreport_base::add_cell_tooltip(
                                $row['percentex' . $instance->id],
                                $instance->id,
                                $users[$curuser->id],
                                $dategraded,
                                $users[$usermodified]
                            );
                        }
                    }

                    if (
                        !empty($showattendances) && $this->attendancestracked()
                            && $tracksattendance = $this->tracksattendance($instance->id)
                    ) {
                        if ($tracksattendance->attendancegradebook) {
                            // We can't use already formatted grade here, because we have to parse the float value!
                            $attendance = $curuser->instancedata[$instance->id]->finalatgrade->grade;
                            $finalgrade = $curuser->instancedata[$instance->id]->finalatgrade;
                            $overridden = $curuser->instancedata[$instance->id]->finalatgrade->overridden;
                            $locked = $curuser->instancedata[$instance->id]->finalatgrade->locked;
                            $userid = $curuser->id;
                            if (empty($users[$userid])) {
                                $userobj = $DB->get_record(
                                    'user',
                                    ['id' => $userid],
                                    'id, ' .
                                    implode(', ', \core_user\fields::for_name()->get_required_fields())
                                );
                                $users[$userid] = fullname($userobj, has_capability('moodle/site:viewfullnames', $context));
                            }
                            $usermodified = $curuser->instancedata[$instance->id]->finalatgrade->usermodified;
                            if (empty($users[$usermodified])) {
                                $userobj = $DB->get_record(
                                    'user',
                                    ['id' => $usermodified],
                                    'id, ' .
                                    implode(', ', \core_user\fields::for_name()->get_required_fields())
                                );
                                $users[$usermodified] = fullname($userobj, has_capability('moodle/site:viewfullnames', $context));
                            }
                        } else {
                            $attendance = $curuser->instancedata[$instance->id]->attendance;
                        }
                        $text = checkmark_get_attendance_symbol($attendance);
                        $row['attendance' . $instance->id] = new html_table_cell($text);
                        if ($tracksattendance->attendancegradebook && ($overridden || $locked)) {
                            $dategraded = $curuser->instancedata[$instance->id]->finalatgrade->dategraded;
                            local_checkmarkreport_base::add_cell_tooltip(
                                $row['attendance' . $instance->id],
                                $instance->id,
                                $users[$curuser->id],
                                $dategraded,
                                $users[$usermodified]
                            );
                        }
                        // We have to get the raw value also out there, so we can display it in spreadsheets!
                        $att = $attendance;
                        $attendance = '?';
                        if ($att == 1) {
                            $attendance = '✓';
                        } else if (($att == 0) && ($att !== null)) {
                            $attendance = '✗';
                        }
                        // Changed this from dynamic properties to an attribute used for outputting the correct charater.
                        // Dynamic properties are deprecated since PHP 8.2.
                        $row['attendance' . $instance->id]->attributes['output-character'] = $attendance;
                    }

                    $gradepresentation = $this->gradepresentations($instance->id);
                    if ($gradepresentation && !$gradepresentation->presentationgrade) {
                        // Prevent comment only presentationgrades to mess with table!
                        $gradepresentation = false;
                    } else if ($gradepresentation && $gradepresentation->presentationgradebook) {
                        if (empty($users[$curuser->instancedata[$instance->id]->finalpresgrade->usermodified])) {
                            $conditions = ['id' => $curuser->instancedata[$instance->id]->finalpresgrade->usermodified];
                            $userobj = $DB->get_record('user', $conditions, 'id, ' . implode(
                                ', ',
                                \core_user\fields::for_name()->get_required_fields()
                            ));
                            $usermodified = $curuser->instancedata[$instance->id]->finalpresgrade->usermodified;
                            $users[$usermodified] = fullname($userobj, has_capability('moodle/site:viewfullnames', $context));
                        }
                    }
                    if (
                        !empty($showpresgrades) && $this->presentationsgraded() && $gradepresentation
                            && $gradepresentation->presentationgrade
                    ) {
                        if ($gradepresentation->presentationgradebook) {
                            $presentationgrade = $curuser->instancedata[$instance->id]->formattedpresgrade;
                            $finalgrade = $curuser->instancedata[$instance->id]->finalpresgrade;
                            $overridden = $curuser->instancedata[$instance->id]->finalpresgrade->overridden;
                            $locked = $curuser->instancedata[$instance->id]->finalpresgrade->locked;
                        } else {
                            // Returns '-' or presentationgrade/maxpresentationgrade or scale item!
                            $presentationgrade = $this->display_grade(
                                $curuser->instancedata[$instance->id]->presentationgrade,
                                $gradepresentation->presentationgrade
                            );
                        }

                        $row['presentationgrade' . $instance->id] = new html_table_cell($presentationgrade);

                        // Highlight if overwritten or locked!
                        if ($gradepresentation->presentationgradebook) {
                            if ($overridden || $locked) {
                                $row['presentationgrade' . $instance->id]->id = "u" . $curuser->id . "i" . $instance->id . "_a";
                                $dategraded = $finalgrade->dategraded;
                                $usermodified = $finalgrade->usermodified;
                                local_checkmarkreport_base::add_cell_tooltip(
                                    $row['presentationgrade' . $instance->id],
                                    $instance->id,
                                    $users[$curuser->id],
                                    $dategraded,
                                    $users[$usermodified]
                                );
                            }
                        }
                    }

                    if (!empty($showexamples)) {
                        // Dynamically add examples!
                        foreach ($curuser->instancedata[$instance->id]->examples as $key => $example) {
                            if (empty($showpoints)) {
                                if ($forexport) {
                                    $row['example' . $key] =
                                            new html_table_cell($example->get_examplestate_for_export_with_colors());
                                } else {
                                    $row['example' . $key] = new html_table_cell($example->print_examplestate());
                                }
                            } else {
                                if ($forexport) {
                                    $row['example' . $key] = new html_table_cell($example->get_points_for_export_with_colors());
                                } else {
                                    $row['example' . $key] = new html_table_cell($example->print_pointsstring());
                                }
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
        }
        $performance->table_built = microtime(true);

        // Init JS!
        $PAGE->requires->js_call_amd('local_checkmarkreport/tooltip', 'initializer');

        return $table;
    }


    /**
     * Returns the header for the column user name based on the display settings for fullname
     *
     * @param bool $alternativename - sets whether alternativefullname should be used     *
     * @param bool $seperatecolumns - specifies if the names should be returned as one string seperated by '/' or as an array
     * @param array $sortablearray An array to be filled with all names that can be sorted for. If set the names are returned as
     *                             sortable links. Otherwise the attributes of the names are returned
     * @return string|array fullname field names seperated by '/' or array coltaining all fullname fragments
     */
    private function get_name_header($alternativename = false, $seperatecolumns = false, &$sortablearray = null) {
        global $CFG, $PAGE;
        // Find name fields used in nameformat and create columns in the same order.
        if ($alternativename) {
            $nameformat = $CFG->alternativefullnameformat;
        } else {
            $nameformat = $CFG->fullnamedisplay;
        }
        // Use default setting from language if no other format is defined.
        if ($nameformat == 'language') {
            $nameformat = get_string('fullnamedisplay');
        }
        $allnamefields = \core_user\fields::for_name()->get_required_fields();
        $usednamefields = [];
        foreach ($allnamefields as $name) {
            if (($position = strpos($nameformat, $name)) !== false) {
                $usednamefields[$position] = $name;
            }
        }
        // Sort names in the order stated in $nameformat.
        ksort($usednamefields);
        $links = [];
        foreach ($usednamefields as $name) {
            if (isset($sortablearray)) {
                $links[] = $this->get_sortlink($name, get_string($name), $PAGE->url);
                $sortablearray[] = $name;
            } else {
                $links[] = $name;
            }
        }
        if ($seperatecolumns) {
            return $links;
        }
        return implode(' / ', $links);
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
        $xml = '';
        $examplenames = [];
        $instances = $this->get_courseinstances_formatted_name();

        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showexamples = get_user_preferences('checkmarkreport_showexamples');
        $showattendances = get_user_preferences('checkmarkreport_showattendances');
        $showpresgrades = get_user_preferences('checkmarkreport_showpresentationgrades');
        $showprescount = get_user_preferences('checkmarkreport_showpresentationcount');
        $seperatenamecolumns = get_user_preferences('checkmarkreport_seperatenamecolumns');

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        $report = $xml->appendChild(new DOMElement('report'));

        foreach ($data as $userid => $row) {
            $user = $report->appendChild(new DOMElement('user'));
            if (!$this->column_is_hidden('id')) {
                $user->setAttribute('id', $userid);
            }
            if (!$seperatenamecolumns && !$this->column_is_hidden('fullnameuser')) {
                $user->setAttribute('fullname', fullname($row, has_capability('moodle/site:viewfullnames', $context)));
            } else if ($seperatenamecolumns) {
                // Get name header fields and look them um in the $row object.
                $names = $this->get_name_header(
                    has_capability('moodle/site:viewfullnames', $context),
                    $seperatenamecolumns
                );
                foreach ($names as $name) {
                    if (!$this->column_is_hidden($name) && isset($row->{$name})) {
                        $a = $row->{$name};
                        $user->setAttribute($name, $row->{$name});
                    }
                }
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
                $user->setAttribute('checkedgrade', empty($row->coursesum) ? 0 : round($row->coursesum, 2));
                $user->setAttribute('maxgrade', empty($row->maxgrade) ? 0 : $row->maxgrade);
            }
            if (!$this->column_is_hidden('examples') && !empty($showabs)) {
                $user->setAttribute('checks', $row->checks);
                $user->setAttribute('maxchecks', $row->maxchecks);
            }
            if (!$this->column_is_hidden('percentex') && !empty($showrel)) {
                $user->setAttribute('percentchecked', round($row->percentchecked, 2) . '%');
                if ($row->overridden) {
                    $percgrade = round(empty($row->coursesum) ? 0 : 100 * $row->coursesum / $row->maxgrade, 2);
                } else {
                    $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
                }
                $user->setAttribute('percentgrade', $percgrade . '%');
            }
            $instancesnode = $user->appendChild(new DOMElement('instances'));
            if (!$this->column_is_hidden('attendance') && !empty($showattendances) && $this->attendancestracked()) {
                $instancesnode->setAttribute('attendant', $row->attendances);
                $instancesnode->setAttribute('attendance_max', $row->maxattendances);
            }
            if (!$this->column_is_hidden('presentationgrade') && !empty($showpresgrades) && $this->presentationsgraded()) {
                $instancesnode->setAttribute('presentationgrade', empty($row->presentationgrade) ? 0 : $row->coursepressum);
                if (!empty($row->presentationgrademax)) {
                    $instancesnode->setAttribute('presentationgrademax', $row->presentationgrademax);
                } else {
                    $instancesnode->setAttribute('presentationgrademax', 0);
                }
            }
            if (!$this->column_is_hidden('presentationsgraded') && !empty($showprescount) && $this->presentationsgraded()) {
                $instancesnode->setAttribute(
                    'presentationsgraded',
                    empty($row->presentationsgraded) ? 0 : $row->presentationsgraded
                );
                $instancesnode->setAttribute('presentationsgradedmax', $row->presentationsgradedmax);
            }
            $examplecounter = 1;
            foreach ($instances as $instance) {
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records('checkmark_examples', ['checkmarkid' => $instance->id]);
                }
                if ($this->column_is_hidden('instance' . $instance->id)) {
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
                if (!$this->column_is_hidden('grade' . $instance->id) && !empty($showgrade)) {
                    $instnode->setAttribute('checkedgrade', empty($instancedata->grade) ? 0 : $instancedata->grade);
                    if (
                        $instancedata->finalgrade->overridden || (($instancedata->finalgrade->grade != $instancedata->grade) &&
                        !is_null($instancedata->finalgrade->grade))
                    ) {
                        $instnode->setAttribute('overridden', true);
                        $instnode->setAttribute('grade', $instancedata->finalgrade->grade);
                    } else {
                        $instnode->setAttribute('overridden', 0);
                        $instnode->setAttribute('grade', $instancedata->finalgrade->grade ?? -1);
                    }
                    $instnode->setAttribute('maxgrade', empty($instancedata->maxgrade) ? 0 : $instancedata->maxgrade);
                }
                if (!$this->column_is_hidden('examples' . $instance->id) && !empty($showabs)) {
                    $instnode->setAttribute('checks', $instancedata->checked);
                    $instnode->setAttribute('maxchecks', $instancedata->maxchecked);
                }
                if (!$this->column_is_hidden('percentex' . $instance->id) && !empty($showrel)) {
                    $percgrade = $this->get_instance_percgrade($instancedata);
                    if ($showabs) {
                        $instnode->setAttribute('percentchecked', $instancedata->percentchecked . '%');
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
                        if (!$this->column_is_hidden('example' . $examplecounter)) {
                            $exnode = $exsnode->appendChild(new DOMElement('example'));
                            $exnode->setAttribute('name', $examplenames[$instance->id][$key]->name);
                            $exnode->setAttribute('state', intval($example->is_checked()));
                            $exnode->setAttribute('overwrite', intval($example->is_forced()));
                            $exnode->setAttribute('statesymbol', $example->get_examplestate_for_export());
                        }
                        $examplecounter++;
                    }
                }
            }
        }

        $filename = get_string('pluginname', 'local_checkmarkreport') . '_' .
                $course->shortname . '_' . userdate(time());
        $filename = $this->replace_quote_chars($filename);
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
        $course = $DB->get_record('course', ['id' => $this->courseid]);
        $context = context_course::instance($this->courseid);

        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showexamples = get_user_preferences('checkmarkreport_showexamples');
        $showattendances = get_user_preferences('checkmarkreport_showattendances');
        $showpresgrades = get_user_preferences('checkmarkreport_showpresentationgrades');
        $showprescount = get_user_preferences('checkmarkreport_showpresentationcount');
        $seperatenamecolumns = get_user_preferences('checkmarkreport_seperatenamecolumns');

        $txt = '';
        $examplenames = [];
        $instances = $this->get_courseinstances_formatted_name();
        $course = $DB->get_record('course', ['id' => $this->courseid]);
        // Header.
        $txt .= get_string('pluginname', 'local_checkmarkreport') . ': ' . format_string($course->fullname) . "\n";
        // Title.
        if (!$seperatenamecolumns && !$this->column_is_hidden('fullnameuser')) {
            $txt .= get_string('fullname');
        } else if ($seperatenamecolumns) {
            $names = $this->get_name_header(has_capability('moodle/site:viewfullnames', $context), $seperatenamecolumns);
            $nameheader = [];
            foreach ($names as $index => $name) {
                if (!$this->column_is_hidden($name)) {
                    $nameheader[] = get_string($name);
                }
            }
            $txt .= implode("\t", $nameheader);
        }
        $useridentity = \core_user\fields::for_identity($context)->get_required_fields();
        foreach ($useridentity as $cur) {
            if (!$this->column_is_hidden($cur)) {
                $txt .= "\t" . (\core_user\fields::get_display_name($cur));
            }
        }
        if (!$this->column_is_hidden('grade') && !empty($showgrade)) {
            $txt .= "\tΣ " . get_string('modgrade', 'grades');
        }
        if (!$this->column_is_hidden('examples') && !empty($showabs)) {
            $txt .= "\tΣ " . get_string('examples', 'local_checkmarkreport');
        }
        if (!$this->column_is_hidden('percentex') && !empty($showrel)) {
            $txt .= "\t";
            $txt .= 'Σ % ' . get_string('examples', 'local_checkmarkreport') . ' (Σ % ' . get_string('modgrade', 'grades') . ')';
        }
        if (!empty($showattendances) && $this->attendancestracked()) {
            $txt .= "\tΣ " . get_string('attendance', 'checkmark');
        }
        if (
            !$this->column_is_hidden('presentationgrade') && !empty($showpresgrades)
                && $this->presentationsgraded()
        ) {
            $txt .= "\tΣ " . get_string('presentationgrade', 'checkmark');
        }
        if (
            !$this->column_is_hidden('presentationsgraded') && !empty($showprescount)
                && $this->presentationsgraded()
        ) {
            $txt .= "\t# " . get_string('presentationgrade', 'checkmark');
        }

        $instances = $this->get_courseinstances_formatted_name();
        $examplecounter = 1;
        foreach ($instances as $instance) {
            // Get example data!
            if (!isset($examplenames[$instance->id])) {
                $examplenames[$instance->id] = $DB->get_records('checkmark_examples', ['checkmarkid' => $instance->id], 'id ASC');
            }
            if ($this->column_is_hidden('instance' . $instance->id)) {
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
            if (!$this->column_is_hidden('grade' . $instance->id) && !empty($showgrade)) {
                $txt .= "\t" . $instance->name . ' ' . get_string('modgrade', 'grades');
            }
            if (!$this->column_is_hidden('examples' . $instance->id) && !empty($showabs)) {
                $txt .= "\t" . $instance->name . ' ' . get_string('examples', 'local_checkmarkreport');
            }
            if (!$this->column_is_hidden('percentex' . $instance->id) && !empty($showrel)) {
                $txt .= "\t";
                $txt .= $instance->name . ' Σ % ' . get_string('examples', 'local_checkmarkreport') . ' (Σ % ' .
                        get_string('modgrade', 'grades') . ')';
            }
            if (
                !$this->column_is_hidden('attendance' . $instance->id) && !empty($showattendances) && $this->attendancestracked()
                    && $this->tracksattendance($instance->id)
            ) {
                $txt .= "\t";
                $txt .= $instance->name . ' ' . get_string('attendance', 'checkmark');
            }
            if (
                !$this->column_is_hidden('presentationgrade' . $instance->id) && !empty($showpresgrades)
                    && $this->presentationsgraded() && $gradepresentation
            ) {
                $txt .= "\t";
                $txt .= $instance->name . ' ' . get_string('presentationgrade', 'checkmark');
            }
            if (!empty($showexamples)) {
                // Dynamically add examples!
                foreach ($examplenames[$instance->id] as $key => $example) {
                    if (!$this->column_is_hidden('example' . $examplecounter)) {
                        $txt .= "\t" . $instance->name . ' ' . $example->name . " (" . $example->grade . 'P)';
                    }
                    $examplecounter++;
                }
            }
        }
        $txt .= "\n";

        // Data.
        foreach ($data as $row) {
            if (!$seperatenamecolumns && !$this->column_is_hidden('fullnameuser')) {
                $txt .= fullname($row, has_capability('moodle/site:viewfullnames', $context));
            } else if ($seperatenamecolumns) {
                // Get name header fields and look them up in the $row object.
                $names = $this->get_name_header(
                    has_capability('moodle/site:viewfullnames', $context),
                    $seperatenamecolumns
                );
                $namefields = [];
                foreach ($names as $name) {
                    if (!$this->column_is_hidden($name) && isset($row->{$name})) {
                        $namefields[] = $row->{$name};
                    }
                }
                $txt .= implode("\t", $namefields);
            }
            foreach ($row->userdata as $key => $cur) {
                if (!$this->column_is_hidden($key)) {
                    $txt .= "\t" . $cur;
                }
            }
            if (!$this->column_is_hidden('grade') && !empty($showgrade)) {
                    $txt .= "\t" . (empty($row->coursesum) ? 0 : $row->coursesum) . "/" .
                            (empty($row->maxgrade) ? 0 : $row->maxgrade);
            }
            if (!$this->column_is_hidden('examples') && !empty($showabs)) {
                $txt .= "\t" . $row->checks . "/" . $row->maxchecks;
            }
            if (!$this->column_is_hidden('percentex') && !empty($showrel)) {
                if ($row->overridden) {
                    $percgrade = round(100 * (empty($row->coursesum) ? 0 : $row->coursesum) / $row->maxgrade, 2);
                } else {
                    $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
                }
                $txt .= "\t";
                $txt .= $row->percentchecked . '% (' . $percgrade . '%)';
            }
            if (!$this->column_is_hidden('attendance') && !empty($showattendances) && $this->attendancestracked()) {
                $txt .= "\t";
                if ($row->atoverridden) {
                    $attendances = $row->courseatsum;
                } else {
                    $attendances = $row->attendances;
                }
                $txt .= $attendances . '/' . $row->maxattendances;
            }
            if (
                !$this->column_is_hidden('presentationgrade') && !empty($showpresgrades) && $this->presentationsgraded() &&
                    !empty($this->pointsforpresentations())
            ) {
                $txt .= "\t";
                $presgrade = $row->coursepressum;
                $txt .= $this->display_grade($presgrade, $row->presentationgrademax);
            }
            if (
                !$this->column_is_hidden('presentationsgraded') && !empty($showprescount)
                    && $this->presentationsgraded()
            ) {
                $txt .= "\t" . $this->display_grade($row->presentationsgraded, $row->presentationsgradedmax);
            }
            $examplecount = 1;
            foreach ($instances as $instance) {
                $instancedata = $row->instancedata[$instance->id];
                $gradepresentation = $this->gradepresentations($instance->id);
                if ($gradepresentation && !$gradepresentation->presentationgrade) {
                    // Prevent comment only presentationgrades to mess with table!
                    $gradepresentation = false;
                }
                if (!$this->column_is_hidden('grade' . $instance->id) && !empty($showgrade)) {
                    if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
                        $txt .= "\t" . $this->display_grade($instancedata->finalgrade->grade, $instancedata->maxgrade);
                    } else {
                        $txt .= "\t" . $this->display_grade($instancedata->grade, $instancedata->maxgrade);
                    }
                }
                if (!$this->column_is_hidden('examples' . $instance->id) && !empty($showabs)) {
                    $txt .= "\t" . $instancedata->checked . "/" . $instancedata->maxchecked;
                }
                if (!$this->column_is_hidden('percentex' . $instance->id) && !empty($showrel)) {
                    $percgrade = $this->get_instance_percgrade($instancedata);
                    $txt .= "\t";
                    $txt .= $instancedata->percentchecked . '% (' . $percgrade . ')';
                }
                if (
                    !$this->column_is_hidden('attendance' . $instance->id) &&
                    !empty($showattendances) &&
                        $this->attendancestracked() &&
                    $this->tracksattendance($instance->id)
                ) {
                    $tracksattendance = $this->tracksattendance($instance->id);
                    if ($tracksattendance->attendancegradebook) {
                        // We can't use already formatted grade here, because we have to parse the float value!
                        $att = $instancedata->finalatgrade->grade;
                    } else {
                        $att = $instancedata->attendance;
                    }
                    $attendance = '?';
                    if ($att == 1) {
                        $attendance = '✓';
                    } else if (($att == 0) && ($att !== null)) {
                        $attendance = '✗';
                    }
                    $txt .= "\t" . $attendance;
                }

                if (
                    !$this->column_is_hidden('presentationgrade' . $instance->id) && !empty($showpresgrades)
                        && $this->presentationsgraded() && $gradepresentation
                ) {
                    if ($gradepresentation->presentationgradebook) {
                        $presentationgrade = $instancedata->formattedpresgrade;
                    } else {
                        $presentationgrade = $this->display_grade(
                            $instancedata->presentationgrade,
                            $gradepresentation->presentationgrade
                        );
                    }
                    $txt .= "\t" . $presentationgrade;
                }

                if (!empty($showexamples)) {
                    foreach ($instancedata->examples as $key => $example) {
                        if (!$this->column_is_hidden('example' . $examplecount)) {
                            $txt .= "\t" . ($example->get_examplestate_for_export());
                        }
                        $examplecount++;
                    }
                }
            }
            $txt .= "\n";
        }
        $filename = get_string('pluginname', 'local_checkmarkreport') . '_' .
                $course->shortname . '_' . userdate(time());
        $filename = $this->replace_quote_chars($filename);
        $this->output_text_with_headers($txt, $filename);
    }

    /**
     * Returns the grade percentage (if applicable) or '-' for the instance!
     *
     * @param stdClass $instancedata Instancedata to process
     * @return string grade percentage (human readable)
     */
    protected function get_instance_percgrade($instancedata) {
        if (is_null($instancedata->grade) && is_null($instancedata->finalgrade->grade)) {
            return '-';
        }
        if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
            if (is_null($instancedata->finalgrade->grade) || $instancedata->maxgrade <= 0) {
                $percgrade = '-';
            } else {
                $grade = $instancedata->finalgrade->grade;
                $percgrade = round(100 * $grade / $instancedata->maxgrade, 2) . ' %';
            }
        } else {
            if (is_null($instancedata->grade) || empty($instancedata->percentgrade) || $instancedata->maxgrade <= 0) {
                $percgrade = '-';
            } else {
                $percgrade = round($instancedata->percentgrade, 2) . ' %';
            }
        }

        return $percgrade;
    }

    /**
     * Write report data to workbook
     *
     * @param MoodleExcelWorkbook|MoodleODSWorkbook $workbook object to write data into
     * @return void
     */
    public function fill_workbook($workbook) {
        $x = $y = 0;
        $context = context_course::instance($this->courseid);
        $textonlycolumns = \core_user\fields::for_identity($context)->get_required_fields();
        array_push($textonlycolumns, 'fullname');
        // We start with the html_table-Object.
        $table = $this->get_table(true);

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

            $oddeven = 1;
            $keys = array_keys($table->data);
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
                    if (!empty($cell->attributes['output-character'])) {
                        $worksheet->write_string($y, $x, strip_tags($cell->attributes['output-character']), $format);
                    } else if (is_numeric($cell->text) && (!in_array($key, $textonlycolumns))) {
                        $worksheet->write_number($y, $x, $cell->text, $format);
                    } else {
                        $worksheet->write_string($y, $x, $cell->text, $format);
                    }
                    $worksheet->merge_cells($y, $x, $y + $cell->rowspan - 1, $x + $cell->colspan - 1);
                    $x++;
                }
                $y++;
            }
        }
    }

    /**
     * get report data as CSV file with configurable separator (sends to browser, forces download)
     *
     * @param string $delimiter CSV field delimiter to use
     * @return void
     */
    public function get_csv(string $delimiter = ';') {
        global $DB;
        $data = $this->get_coursedata();
        $course = $DB->get_record('course', ['id' => $this->courseid]);
        $context = context_course::instance($this->courseid);

        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showexamples = get_user_preferences('checkmarkreport_showexamples');
        $showpoints = get_user_preferences('checkmarkreport_showpoints');
        $showattendances = get_user_preferences('checkmarkreport_showattendances');
        $showpresgrades = get_user_preferences('checkmarkreport_showpresentationgrades');
        $showprescount = get_user_preferences('checkmarkreport_showpresentationcount');
        $seperatenamecolumns = get_user_preferences('checkmarkreport_seperatenamecolumns');

        $csv = '';
        $examplenames = [];
        $instances = $this->get_courseinstances_formatted_name();
        $course = $DB->get_record('course', ['id' => $this->courseid]);

        // Add UTF-8 BOM for proper encoding in Excel.
        $csv .= "\xEF\xBB\xBF";

        // Build column headers (single row, no merged cells).
        $headers = [];

        // Add user name columns.
        if (!$seperatenamecolumns && !$this->column_is_hidden('fullnameuser')) {
            $headers[] = get_string('fullname');
        } else if ($seperatenamecolumns) {
            $names = $this->get_name_header(has_capability('moodle/site:viewfullnames', $context), $seperatenamecolumns);
            foreach ($names as $name) {
                if (!$this->column_is_hidden($name)) {
                    $headers[] = get_string($name);
                }
            }
        }

        // Add user identity fields.
        $useridentity = \core_user\fields::for_identity($context)->get_required_fields();
        foreach ($useridentity as $cur) {
            if (!$this->column_is_hidden($cur)) {
                $headers[] = \core_user\fields::get_display_name($cur);
            }
        }

        // Add summary columns.
        if (!$this->column_is_hidden('grade') && !empty($showgrade)) {
            $headers[] = 'Σ ' . get_string('modgrade', 'grades');
        }
        if (!$this->column_is_hidden('examples') && !empty($showabs)) {
            $headers[] = 'Σ ' . get_string('examples', 'local_checkmarkreport');
        }
        if (!$this->column_is_hidden('percentex') && !empty($showrel)) {
            $headers[] = 'Σ % ' . get_string('examples', 'local_checkmarkreport')
                . ' (' . get_string('grade', 'local_checkmarkreport') . ')';
        }
        if (!empty($showattendances) && $this->attendancestracked()) {
            $headers[] = 'Σ ' . get_string('attendance', 'checkmark');
        }
        if (!$this->column_is_hidden('presentationgrade') && !empty($showpresgrades) && $this->presentationsgraded()) {
            $headers[] = 'Σ ' . get_string('presentationgrade', 'checkmark');
        }
        if (!$this->column_is_hidden('presentationsgraded') && !empty($showprescount) && $this->presentationsgraded()) {
            $headers[] = '# ' . get_string('presentationgrade', 'checkmark');
        }

        // Add instance-specific columns (with instance names in headers).
        $examplecounter = 1;
        foreach ($instances as $instance) {
            // Get example data.
            if (!isset($examplenames[$instance->id])) {
                $examplenames[$instance->id] = $DB->get_records('checkmark_examples', ['checkmarkid' => $instance->id], 'id ASC');
            }
            if ($this->column_is_hidden('instance' . $instance->id)) {
                foreach ($examplenames[$instance->id] as $key => $example) {
                    $examplecounter++;
                }
                continue;
            }

            $gradepresentation = $this->gradepresentations($instance->id);
            if ($gradepresentation && !$gradepresentation->presentationgrade) {
                $gradepresentation = false;
            }

            if (!$this->column_is_hidden('grade' . $instance->id) && !empty($showgrade)) {
                $headers[] = $instance->name . ': ' . get_string('modgrade', 'grades');
            }
            if (!$this->column_is_hidden('examples' . $instance->id) && !empty($showabs)) {
                $headers[] = $instance->name . ': ' . get_string('examples', 'local_checkmarkreport');
            }
            if (!$this->column_is_hidden('percentex' . $instance->id) && !empty($showrel)) {
                $headers[] = $instance->name . ': % ' . get_string('examples', 'local_checkmarkreport') .
                    ' (% ' . get_string('modgrade', 'grades') . ')';
            }
            if (
                !$this->column_is_hidden('attendance' . $instance->id) && !empty($showattendances) &&
                $this->attendancestracked() && $this->tracksattendance($instance->id)
            ) {
                $headers[] = $instance->name . ': ' . get_string('attendance', 'checkmark');
            }
            if (
                !$this->column_is_hidden('presentationgrade' . $instance->id) && !empty($showpresgrades) &&
                $this->presentationsgraded() && $gradepresentation
            ) {
                $headers[] = $instance->name . ': ' . get_string('presentationgrade', 'checkmark');
            }

            // Add example columns with instance name.
            if (!empty($showexamples)) {
                foreach ($examplenames[$instance->id] as $key => $example) {
                    if (!$this->column_is_hidden('example' . $examplecounter)) {
                        $headers[] = $instance->name . ': ' . $example->name . ' (' . $example->grade . 'P)';
                    }
                    $examplecounter++;
                }
            }
        }

        // Write header row.
        $escapedheaders = [];
        foreach ($headers as $header) {
            $escapedheaders[] = $this->escape_csv_value($header, $delimiter);
        }
        $csv .= implode($delimiter, $escapedheaders) . "\n";

        // Data rows.
        foreach ($data as $row) {
            $rowdata = [];

            // Add user name/identity fields.
            if (!$seperatenamecolumns && !$this->column_is_hidden('fullnameuser')) {
                $rowdata[] = fullname($row, has_capability('moodle/site:viewfullnames', $context));
            } else if ($seperatenamecolumns) {
                $names = $this->get_name_header(
                    has_capability('moodle/site:viewfullnames', $context),
                    $seperatenamecolumns
                );
                foreach ($names as $name) {
                    if (!$this->column_is_hidden($name) && isset($row->{$name})) {
                        $rowdata[] = $row->{$name};
                    }
                }
            }

            // Add user data fields.
            foreach ($row->userdata as $key => $cur) {
                if (!$this->column_is_hidden($key)) {
                    $rowdata[] = $cur;
                }
            }

            // Add summary data.
            if (!$this->column_is_hidden('grade') && !empty($showgrade)) {
                $rowdata[] = $this->format_fraction(
                    (empty($row->coursesum) ? 0 : $row->coursesum),
                    (empty($row->maxgrade) ? 0 : $row->maxgrade)
                );
            }
            if (!$this->column_is_hidden('examples') && !empty($showabs)) {
                $rowdata[] = $this->format_fraction($row->checks, $row->maxchecks);
            }
            if (!$this->column_is_hidden('percentex') && !empty($showrel)) {
                if ($row->maxgrade > 0 && $row->coursesum >= 0) {
                    $percgrade = round(100 * (empty($row->coursesum) ? 0 : $row->coursesum) / $row->maxgrade, 2);
                } else {
                    $percgrade = '-';
                }
                $percentchecked = round($row->percentchecked, 2);
                if (is_numeric($percgrade)) {
                    $rowdata[] = $percentchecked . '% (' . $percgrade . '%)';
                } else {
                    $rowdata[] = $percentchecked . '% (' . $percgrade . ')';
                }
            }
            if (!$this->column_is_hidden('attendance') && !empty($showattendances) && $this->attendancestracked()) {
                if ($row->atoverridden) {
                    $attendances = $row->courseatsum;
                } else {
                    $attendances = $row->attendances;
                }
                $rowdata[] = $this->format_fraction($attendances, $row->maxattendances);
            }
            if (
                !$this->column_is_hidden('presentationgrade') && !empty($showpresgrades) && $this->presentationsgraded() &&
                !empty($this->pointsforpresentations())
            ) {
                $presgrade = $row->coursepressum;
                $rowdata[] = $this->format_fraction($presgrade, $row->presentationgrademax, true);
            }
            if (!$this->column_is_hidden('presentationsgraded') && !empty($showprescount) && $this->presentationsgraded()) {
                $rowdata[] = $this->format_fraction($row->presentationsgraded, $row->presentationsgradedmax);
            }

            // Add instance-specific data.
            $examplecount = 1;
            foreach ($instances as $instance) {
                $instancedata = $row->instancedata[$instance->id];
                $gradepresentation = $this->gradepresentations($instance->id);
                if ($gradepresentation && !$gradepresentation->presentationgrade) {
                    $gradepresentation = false;
                }

                if (!$this->column_is_hidden('grade' . $instance->id) && !empty($showgrade)) {
                    if ($instancedata->finalgrade->overridden || ($instancedata->finalgrade->grade != $instancedata->grade)) {
                        $rowdata[] = $this->format_fraction($instancedata->finalgrade->grade, $instancedata->maxgrade, true);
                    } else {
                        $rowdata[] = $this->format_fraction($instancedata->grade, $instancedata->maxgrade, true);
                    }
                }
                if (!$this->column_is_hidden('examples' . $instance->id) && !empty($showabs)) {
                    $rowdata[] = $this->format_fraction($instancedata->checked, $instancedata->maxchecked);
                }
                if (!$this->column_is_hidden('percentex' . $instance->id) && !empty($showrel)) {
                    $percgrade = $this->get_instance_percgrade($instancedata);
                    $percchecked = round($instancedata->percentchecked, 2);
                    $rowdata[] = $percchecked . '% (' . $percgrade . ')';
                }
                if (
                    !$this->column_is_hidden('attendance' . $instance->id) && !empty($showattendances) &&
                    $this->attendancestracked() && $this->tracksattendance($instance->id)
                ) {
                    $tracksattendance = $this->tracksattendance($instance->id);
                    if ($tracksattendance->attendancegradebook) {
                        $att = $instancedata->finalatgrade->grade;
                    } else {
                        $att = $instancedata->attendance;
                    }
                    $attendance = '?';
                    if ($att == 1) {
                        $attendance = '✓';
                    } else if (($att == 0) && ($att !== null)) {
                        $attendance = '✗';
                    }
                    $rowdata[] = $attendance;
                }
                if (
                    !$this->column_is_hidden('presentationgrade' . $instance->id) && !empty($showpresgrades) &&
                    $this->presentationsgraded() && $gradepresentation
                ) {
                    if ($gradepresentation->presentationgradebook) {
                        $presentationgrade = $instancedata->formattedpresgrade;
                        $rowdata[] = $this->wrap_fraction_for_csv($presentationgrade);
                    } else {
                        $rowdata[] = $this->format_fraction(
                            $instancedata->presentationgrade,
                            $gradepresentation->presentationgrade,
                            true
                        );
                    }
                }
                if (!empty($showexamples)) {
                    foreach ($instancedata->examples as $key => $example) {
                        if (!$this->column_is_hidden('example' . $examplecount)) {
                            if (!empty($showpoints)) {
                                $pointvalue = $example->get_points_for_export_with_colors();
                                $pointvalue = str_replace('<colorred>', '', $pointvalue);
                                if ($example->is_forced_checked() || $example->is_forced_unchecked()) {
                                    $rowdata[] = $this->wrap_bracket_text_for_csv($pointvalue);
                                } else {
                                    $rowdata[] = $pointvalue;
                                }
                            } else {
                                $rowdata[] = $example->get_examplestate_for_export();
                            }
                        }
                        $examplecount++;
                    }
                }
            }

            // Write data row.
            $escapedrow = [];
            foreach ($rowdata as $cell) {
                $escapedrow[] = $this->escape_csv_value($cell, $delimiter);
            }
            $csv .= implode($delimiter, $escapedrow) . "\n";
        }

        // Output the CSV file.
        $filename = get_string('pluginname', 'local_checkmarkreport') . '_' . $course->shortname;
        $filename = $this->replace_quote_chars($filename);
        $this->output_csv_with_headers($csv, $filename);
    }

    /**
     * Escape and quote CSV values for delimited export.
     *
     * @param mixed $value Value to escape
     * @param string $delimiter CSV field delimiter to use
     * @return string Escaped value ready for CSV
     */
    private function escape_csv_value($value, string $delimiter = ';'): string {
        // Handle empty/null values.
        if ($value === null || $value === '') {
            return '';
        }

        // Remove non-breaking spaces.
        $value = str_replace(chr(194) . chr(160), '', (string)$value);
        // Escape double quotes by doubling them.
        $value = str_replace('"', '""', $value);

        // Quote the value if it contains delimiter, quotes, or newlines.
        if (strpos($value, $delimiter) !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            $value = '"' . $value . '"';
        }

        return $value;
    }

    /**
     * Wrap a value containing a fraction so Excel keeps it as text.
     *
     * @param string $value Value that may contain a fraction
     * @return string Value wrapped if it contained a fraction, otherwise original value
     */
    private function wrap_fraction_for_csv($value) {
        if ($value === null || $value === '') {
            return $value;
        }

        if (strpos((string)$value, '/') === false) {
            return $value;
        }

        $parts = preg_split('/\s*\/\s*/', (string)$value, 2);
        $left = $parts[0] ?? '';
        $right = $parts[1] ?? '';

        return $this->format_fraction($left, $right);
    }

    /**
     * Format a fraction as text to avoid Excel date conversion.
     *
     * @param mixed $numerator Numerator value
     * @param mixed $denominator Denominator value
     * @param bool $dashfornograde Whether to return '-' for no grade
     * @return string Formatted fraction prefixed for text
     */
    private function format_fraction($numerator, $denominator, bool $dashfornograde = false): string {
        if (
            $dashfornograde && (
                $numerator === null || $numerator === '' || $numerator === false ||
                (is_numeric($numerator) && (float)$numerator < 0)
            )
        ) {
            return '-';
        }

        $left = $this->normalize_fraction_part($numerator);
        $right = $this->normalize_fraction_part($denominator);

        // Using an ="..." wrapper forces Excel/Sheets to treat the value as text without coercion.
        return '="' . $left . '/' . $right . '"';
    }

    /**
     * Wrap overwritten example points with brackets and force text interpretation in spreadsheets.
     *
     * @param string $value Points value
     * @return string Wrapped value
     */
    private function wrap_bracket_text_for_csv(string $value): string {
        return '="(' . $value . ')"';
    }

    /**
     * Normalize a fraction part to trim trailing zeros while keeping non-numeric values intact.
     *
     * @param mixed $value Value to normalize
     * @return string Normalized value
     */
    private function normalize_fraction_part($value): string {
        if ($value === null || $value === '') {
            return '0';
        }

        if (is_numeric($value)) {
            return (string)(0 + $value); // Cast removes trailing zeros like 20.00000 -> 20.
        }

        return (string)$value;
    }
}

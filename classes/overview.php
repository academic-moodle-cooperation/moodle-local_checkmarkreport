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
 * local_checkmarkreport_overview class, handles checkmarkreport overview content
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class local_checkmarkreport_overview extends local_checkmarkreport_base implements renderable {

    protected $tableclass = 'table table-condensed table-hover table-striped overview';

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

    public function get_table() {
        global $CFG, $DB, $SESSION, $PAGE;

        $context = context_course::instance($this->courseid);

        $sortarray = &$SESSION->checkmarkreport->{$this->courseid}->sort;

        $performance = new stdClass();
        $performance->start = microtime(true);
        $data = $this->get_coursedata();
        $performance->datafetched = microtime(true);

        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showpoints = get_user_preferences('checkmarkreport_showpoints');

        $table = new html_table();

        $jsarguments = array(
            'id'        => '#user-grades',
            'cfg'       => array('ajaxenabled' => false),
            'items'     => array(),
            'users'     => array(),
            'grade'  => array()
        );
        $jsscales = array();

        $table->id = 'user-grades'; // Was former "attempts"! Changed due to style of overridden grades!
        if (!isset($table->attributes)) {
            $table->attributes = array('class' => 'coloredrows overview');
        } else if (!isset($table->attributes['class'])) {
            $table->attributes['class'] = 'coloredrows overview';
        } else {
            $table->attributes['class'] .= ' coloredrows overview';
        }

        $table->tablealign = 'center';

        $tabledata = array();
        $row = array();

        $cellwidth = array();
        $columnformat = array();
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

        $instances = $this->get_courseinstances();
        foreach ($instances as $instance) {
            $span = 0;
            $instanceurl = new moodle_url('/mod/checkmark/view.php', array('id' => $instance->coursemodule));
            $instancelink = html_writer::link($instanceurl, $instance->name);
            $tableheaders['instance'.$instance->id] = new html_table_cell($instancelink);
            $tableheaders['instance'.$instance->id]->header = true;
            $tableheaders['instance'.$instance->id]->scope = 'colgroup';
            $table->colclasses['instance'.$instance->id] = 'instance'.$instance->id;
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
                $title = '% '.
                         $this->get_sortlink('percentchecked'.$instance->id,
                                             get_string('examples',
                                                        'local_checkmarkreport'),
                                             $PAGE->url).
                         ' ('.
                         $this->get_sortlink('percentgrade'.$instance->id,
                                             get_string('grade'), $PAGE->url).')';
                $sortable[] = 'percentex'.$instance->id;
                $tableheaders2['percentex'.$instance->id] = new html_table_cell($title);
                $tableheaders2['percentex'.$instance->id]->header = true;
                $tablecolumns[] = 'percentex'.$instance->id;
                $table->colclasses['percentex'.$instance->id] = 'instance'.$instance->id.' percentex'.$instance->id;
            }
            // Dynamically add examples!
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
            for ($i = 1; $i < $span; $i++) {
                // Insert empty cells for the colspan!
                $tableheaders[] = null;
            }
            $tableheaders['instance'.$instance->id]->colspan = $span;
            $table->colgroups[] = array('span' => $span,
                                        'class' => 'instancegroup');
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
                $row['grade'] = new html_table_cell((empty($curuser->checkgrade) ?
                                                    0 : $curuser->checkgrade).' / '.$curuser->maxgrade);
                // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                if ($curuser->overridden) {
                    $row['grade'] = new html_table_cell((empty($curuser->coursesum) ?
                                                        0 : round($curuser->coursesum, 2)).' / '.$curuser->maxgrade);
                    $row['grade']->attributes['class'] = 'current';
                    $row['grade']->id = "u".$curuser->id."i0_a";
                } else {
                    $row['grade'] = new html_table_cell((empty($curuser->checkgrade) ?
                                                        0 : $curuser->checkgrade).' / '.$curuser->maxgrade);
                }
                $row['grade']->attributes['id'] = 'u'.$curuser->id.'i0';
            }
            // Coursesum of course examples.
            if (!empty($showabs)) {
                $row['examples'] = new html_table_cell($curuser->checks.' / '.$curuser->maxchecks);
            }
            // Percent of course examples.
            if (!empty($showrel)) {
                // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                if ($curuser->overridden) {
                    $percgrade = empty($curuser->coursesum) ? 0 : 100 * $curuser->coursesum / $curuser->maxgrade;
                    $row['percentex'] = new html_table_cell(round($curuser->percentchecked, 2).'% ('.
                                                            round($percgrade, 2).' %)');
                    $row['percentex']->attributes['class'] = 'current';
                    $row['percentex']->id = "u".$curuser->id."i0_r";
                    // TODO add data to jsarguments!
                } else {
                    $percgrade = empty($curuser->percentgrade) ? 0 : $curuser->percentgrade;
                    $row['percentex'] = new html_table_cell(round($curuser->percentchecked, 2).'% ('.
                                                            round($percgrade, 2).' %)');
                }
            }

            $instances = $this->get_courseinstances();
            foreach ($instances as $instance) {
                // Coursesum of course grade.
                if (!empty($showgrade)) {
                    $grade = $curuser->instancedata[$instance->id]->grade;
                    $finalgrade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                    $locked = $curuser->instancedata[$instance->id]->finalgrade->locked;
                    if (($curuser->instancedata[$instance->id]->finalgrade->overridden
                            || $locked || ($grade != $finalgrade))
                        && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)) {
                        $grade = (empty($curuser->instancedata[$instance->id]->finalgrade->grade) ? 0 :
                                  round($curuser->instancedata[$instance->id]->finalgrade->grade, 2)).' / '.
                                  $curuser->instancedata[$instance->id]->maxgrade;
                        // TODO add data to js arguments!
                        if (empty($jsarguments['users'][$userid])) {
                            $userobj = $DB->get_record('user', array('id' => $userid),
                                                       'id, '.implode(', ', get_all_user_name_fields()));
                            $jsarguments['users'][$userid] = fullname($userobj);
                        }
                        if (empty($jsarguments['users'][$curuser->instancedata[$instance->id]->finalgrade->usermodified])) {
                            $conditions = array('id' => $curuser->instancedata[$instance->id]->finalgrade->usermodified);
                            $userobj = $DB->get_record('user', $conditions, 'id, '.implode(', ', get_all_user_name_fields()));
                            $usermodified = $curuser->instancedata[$instance->id]->finalgrade->usermodified;
                            $jsarguments['users'][$usermodified] = fullname($userobj);
                        }
                        $jsarguments['grade'][] = array(
                            'user'       => $curuser->id,
                            'item'       => $instance->id,
                            'dategraded' => userdate($curuser->instancedata[$instance->id]->finalgrade->dategraded),
                            'grader'     => $jsarguments['users'][$curuser->instancedata[$instance->id]->finalgrade->usermodified]);
                    } else {
                        $grade = (empty($curuser->instancedata[$instance->id]->grade) ?
                                  0 : $curuser->instancedata[$instance->id]->grade).
                                 ' / '.$curuser->instancedata[$instance->id]->maxgrade;
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
                        // TODO add data to jsarguments!
                        $row['grade'.$instance->id]->id = "u".$curuser->id."i".$instance->id."_a";
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
                    $perccheck = empty($curuser->instancedata[$instance->id]->percentchecked) ?
                                 0 : $curuser->instancedata[$instance->id]->percentchecked;
                    if (($curuser->instancedata[$instance->id]->finalgrade->overridden
                            || $locked || ($grade != $finalgrade))
                        && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)) {
                        // TODO add data to jsarguments!
                        $grade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                        $maxgrade = $curuser->instancedata[$instance->id]->maxgrade;
                        $rel = $grade / $maxgrade;
                        $percgrade = round(100 * $rel, 2);
                        if (empty($jsarguments['users'][$userid])) {
                            $userobj = $DB->get_record('user', array('id' => $userid),
                                                       'id, '.implode(', ', get_all_user_name_fields()));
                            $jsarguments['users'][$userid] = fullname($userobj);
                        }
                        if (empty($jsarguments['users'][$curuser->instancedata[$instance->id]->finalgrade->usermodified])) {
                            $usermodified = $curuser->instancedata[$instance->id]->finalgrade->usermodified;
                            $userobj = $DB->get_record('user', array('id' => $usermodified),
                                                       'id, '.implode(', ', get_all_user_name_fields()));
                            $jsarguments['users'][$usermodified] = fullname($userobj);
                        }
                        $jsarguments['grade'][] = array(
                            'user'       => $curuser->id,
                            'item'       => $instance->id,
                            'dategraded' => userdate($curuser->instancedata[$instance->id]->finalgrade->dategraded),
                            'grader'     => $jsarguments['users'][$curuser->instancedata[$instance->id]->finalgrade->usermodified]);
                    } else {
                        $percgrade = empty($curuser->instancedata[$instance->id]->percentgrade) ?
                                     0 : $curuser->instancedata[$instance->id]->percentgrade;
                    }
                    $row['percentex'.$instance->id] = new html_table_cell(round($perccheck, 2).'% ('.
                                                                          round($percgrade, 2).'%)');
                    // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                    $finalgrade = $curuser->instancedata[$instance->id]->finalgrade->grade;
                    $grade = $curuser->instancedata[$instance->id]->grade;
                    $locked = $curuser->instancedata[$instance->id]->finalgrade->locked;
                    if (($curuser->instancedata[$instance->id]->finalgrade->overridden
                            || $locked || ($grade != $finalgrade))
                        && !is_null($curuser->instancedata[$instance->id]->finalgrade->grade)) {
                        $row['percentex'.$instance->id]->attributes['class'] = 'current';
                        // TODO add data to jsarguments!
                        $row['percentex'.$instance->id]->id = "u".$curuser->id."i".$instance->id."_r";
                    }
                }
                // Dynamically add examples!
                foreach ($curuser->instancedata[$instance->id]->examples as $key => $example) {
                    if (empty($showpoints)) {
                        $row['example'.$key] = new html_table_cell($example ? "☒" : "☐");
                    } else {
                        $row['example'.$key] = new html_table_cell($example ? $examplenames[$instance->id][$key]->grade : "0");
                    }
                }
            }
            $table->data[$userid] = new html_table_row();
            $table->data[$userid]->cells = $row;
        }
        $performance->table_built = microtime(true);

        $jsarguments['cfg']['ajaxenabled'] = true;

        // Student grades and feedback are already at $jsarguments['feedback'] and $jsarguments['grades']!
        $jsarguments['cfg']['courseid'] = $this->courseid;

        $module = array(
            'name'      => 'local_checkmarkreport',
            'fullpath'  => '/local/checkmarkreport/module.js',
            'requires'  => array('base', 'dom', 'event', 'event-mouseenter', 'event-key', 'io-queue', 'json-parse', 'overlay')
        );
        $PAGE->requires->js_init_call('M.local_checkmarkreport.init_report', $jsarguments, false, $module);

        $PAGE->requires->string_for_js('overwritten', 'local_checkmarkreport');
        $PAGE->requires->string_for_js('by', 'local_checkmarkreport');

        return $table;
    }

    public function get_xml() {
        global $CFG, $DB;
        $data = $this->get_coursedata();
        $course = $DB->get_record('course', array('id' => $this->courseid));
        $xml = '';
        $examplenames = array();
        $instances = $this->get_courseinstances();
        foreach ($data as $userid => $row) {
            $xml .= "\t".html_writer::start_tag('user')."\n".
                    "\t\t".html_writer::tag('id', $userid)."\n".
                    "\t\t".html_writer::tag('fullname', fullname($row))."\n";
            foreach ($row->userdata as $key => $cur) {
                $xml .= "\t\t".html_writer::tag($key, $cur)."\n";
            }
            $xml .= "\t\t".html_writer::tag('checkedgrade',
                                     empty($row->checkgrade) ? 0 : $row->checkgrade)."\n";
            $xml .= "\t\t".html_writer::tag('maxgrade',
                                     empty($row->maxgrade) ? 0 : $row->maxgrade)."\n";
            $xml .= "\t\t".html_writer::tag('checks', $row->checks)."\n";
            $xml .= "\t\t".html_writer::tag('maxchecks', $row->maxchecks)."\n";

            $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
            $xml .= "\t\t".html_writer::tag('percentchecked', $row->percentchecked.'%')."\n";
            $xml .= "\t\t".html_writer::tag('percentgrade', $percgrade.'%')."\n";
            $xml .= "\t\t".html_writer::start_tag('instances')."\n";
            foreach ($instances as $instance) {
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
                }
                $instancedata = $row->instancedata[$instance->id];
                $xml .= "\t\t\t".html_writer::start_tag('instance')."\n";
                $xml .= "\t\t\t\t".html_writer::tag('name', $instance->name)."\n";
                $xml .= "\t\t\t\t".html_writer::tag('checkedgrade',
                                         empty($instancedata->grade) ? 0 : $instancedata->grade)."\n";
                $xml .= "\t\t\t\t".html_writer::tag('maxgrade',
                                         empty($instancedata->maxgrade) ? 0 : $instancedata->maxgrade)."\n";
                $xml .= "\t\t\t\t".html_writer::tag('checks', $instancedata->checked)."\n";
                $xml .= "\t\t\t\t".html_writer::tag('maxchecks', $instancedata->maxchecked)."\n";

                $percgrade = round((empty($instancedata->percentgrade) ? 0 : $instancedata->percentgrade), 2);
                $xml .= "\t\t\t\t".html_writer::tag('percentchecked', $instancedata->percentchecked.'%')."\n";
                $xml .= "\t\t\t\t".html_writer::tag('percentgrade', $percgrade.'%')."\n";
                $xml .= "\t\t\t\t".html_writer::start_tag('examples')."\n";
                foreach ($instancedata->examples as $key => $example) {
                    $xml .= "\t\t\t\t\t".html_writer::start_tag('example')."\n";
                    $xml .= "\t\t\t\t\t\t".html_writer::tag('name', $examplenames[$instance->id][$key]->name)."\n";
                    $xml .= "\t\t\t\t\t\t".html_writer::tag('state', $example ? 1 : 0)."\n";
                    $xml .= "\t\t\t\t\t\t".html_writer::tag('statesymbol', $example ? "☒" : "☐")."\n";
                    $xml .= "\t\t\t\t\t".html_writer::end_tag('example')."\n";
                }
                $xml .= "\t\t\t\t".html_writer::end_tag('examples')."\n";
                $xml .= "\t\t\t".html_writer::end_tag('instance')."\n";
            }
            $xml .= "\t\t".html_writer::end_tag('instances')."\n";
            $xml .= "\t".html_writer::end_tag('user')."\n";
        }

        $xml = '<?xml version="1.0"  encoding="utf-8" ?>'."\n".html_writer::tag('report', "\n".$xml);
        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.
                    $course->shortname.'_'.userdate(time());
        header("Content-type: application/xml; charset=utf-8");
        header('Content-Length: ' . strlen($xml));
        header('Content-Disposition: attachment;filename="'.$filename.'.xml";'.
                                               'filename*="'.rawurlencode($filename).'.xml"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Encoding: utf-8');
        echo $xml;
    }

    public function get_txt() {
        global $CFG, $DB;
        $data = $this->get_coursedata();
        $course = $DB->get_record('course', array('id' => $this->courseid));
        $context = context_course::instance($this->courseid);
        $txt = '';
        $examplenames = array();
        $instances = $this->get_courseinstances();
        $course = $DB->get_record('course', array('id' => $this->courseid));
        // Header.
        $txt .= get_string('pluginname', 'local_checkmarkreport').': '.$course->fullname."\n";
        // Title.
        $txt .= get_string('fullname');
        $useridentity = get_extra_user_fields($context);
        foreach ($useridentity as $cur) {
            $txt .= "\t".(($cur == 'phone1') ? get_string('phone') : get_string($cur));
        }
        $txt .= "\tS ".get_string('grade');
        $txt .= "\tS ".get_string('examples', 'local_checkmarkreport');
        $txt .= "\tS % ".get_string('examples', 'local_checkmarkreport');

        $instances = $this->get_courseinstances();
        foreach ($instances as $instance) {
            $txt .= "\t".$instance->name.' '.get_string('grade');
            $txt .= "\t".$instance->name.' '.get_string('examples', 'local_checkmarkreport');
            $txt .= "\t".$instance->name.' % '.get_string('examples', 'local_checkmarkreport');
            // Dynamically add examples!
            // Get example data!
            if (!isset($examplenames[$instance->id])) {
                $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
            }
            foreach ($examplenames[$instance->id] as $key => $example) {
                $txt .= "\t".$instance->name.' '.$example->name." (".$example->grade.'P)';
            }
        }
        $txt .= "\n";

        // Data.
        foreach ($data as $userid => $row) {
            $txt .= fullname($row);
            foreach ($row->userdata as $key => $cur) {
                $txt .= "\t".html_writer::tag($key, $cur);
            }
            $txt .= "\t".(empty($row->checkgrade) ? 0 : $row->checkgrade);
            $txt .= "\t".(empty($row->maxgrade) ? 0 : $row->maxgrade);
            $txt .= "\t".$row->checks;
            $txt .= "\t".$row->maxchecks;

            $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
            $txt .= "\t".$row->percentchecked.'%';
            $txt .= "\t".$percgrade.'%';
            foreach ($instances as $instance) {
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
                }
                $instancedata = $row->instancedata[$instance->id];
                $txt .= "\t".(empty($instancedata->grade) ? 0 : $instancedata->grade);
                $txt .= "\t".(empty($instancedata->maxgrade) ? 0 : $instancedata->maxgrade);
                $txt .= "\t".$instancedata->checked;
                $txt .= "\t".$instancedata->maxchecked;

                $percgrade = round((empty($instancedata->percentgrade) ? 0 : $instancedata->percentgrade), 2);
                $txt .= "\t".$instancedata->percentchecked.'%';
                $txt .= "\t".$percgrade.'%';
                foreach ($instancedata->examples as $key => $example) {
                    $txt .= "\t".($example ? "☒" : "☐");
                }
            }
            $txt .= "\n";
        }
        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.
                    $course->shortname.'_'.userdate(time());
        header("Content-type: text/txt; charset=utf-8");
        header('Content-Length: ' . strlen($txt));
        header('Content-Disposition: attachment;filename="'.$filename.'.txt";'.
                                               'filename*="'.rawurlencode($filename).'.txt"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Encoding: utf-8');
        echo $txt;
    }

    public function get_ods() {
        global $CFG, $DB;

        require_once($CFG->libdir . "/odslib.class.php");

        $workbook = new MoodleODSWorkbook("-");

        $this->fill_workbook($workbook);

        $course = $DB->get_record('course', array('id' => $this->courseid));

        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.$course->shortname;
        $workbook->send($filename.'.ods');
        $workbook->close();
    }

    public function get_xlsx() {
        global $CFG, $DB;

        require_once($CFG->libdir . "/excellib.class.php");

        $workbook = new MoodleExcelWorkbook("-", 'Excel2007');

        $this->fill_workbook($workbook);

        $course = $DB->get_record('course', array('id' => $this->courseid));

        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.$course->shortname;
        $workbook->send($filename);
        $workbook->close();
    }

    public function fill_workbook($workbook) {
        $x = $y = 0;

        // We start with the html_table-Object.
        $table = $this->get_table();

        $worksheet = $workbook->add_worksheet(time());

        // Prepare table data and populate missing properties with reasonable defaults!
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = fix_align_rtl($aa);  // Fix for RTL languages!
                } else {
                    $table->align[$key] = null;
                }
            }
        }
        if (!empty($table->size)) {
            foreach ($table->size as $key => $ss) {
                if ($ss) {
                    $table->size[$key] = $ss;
                } else {
                    $table->size[$key] = null;
                }
            }
        }

        if (!empty($table->head)) {
            foreach ($table->head as $key => $val) {
                if (!isset($table->align[$key])) {
                    $table->align[$key] = null;
                }
                if (!isset($table->size[$key])) {
                    $table->size[$key] = null;
                }
            }
        }

        $countcols = 0;

        if (!empty($table->head)) {
            $countrows = count($table->head);

            foreach ($table->head as $headrow) {
                $x = 0;
                $keys = array_keys($headrow->cells);
                $lastkey = end($keys);
                $countcols = count($headrow->cells);

                foreach ($headrow->cells as $key => $heading) {
                    // Convert plain string headings into html_table_cell objects!
                    if (!($heading instanceof html_table_cell)) {
                        $headingtext = $heading;
                        $heading = new html_table_cell();
                        $heading->text = $headingtext;
                        $heading->header = true;
                    }

                    if ($heading->text == null) {
                        $x++;
                        continue;
                    }

                    if ($heading->header !== false) {
                        $heading->header = true;
                    }

                    $heading->attributes['class'] = trim($heading->attributes['class']);
                    $attributes = array_merge($heading->attributes, array(
                            'style'     => $heading->style,
                            'scope'     => $heading->scope,
                            'colspan'   => $heading->colspan,
                            'rowspan'   => $heading->rowspan
                        ));
                    if (!isset($heading->colspan)) {
                        $heading->colspan = 1;
                    }
                    if (!isset($heading->rowspan)) {
                        $heading->rowspan = 1;
                    }
                    $worksheet->merge_cells($y, $x, $y + $heading->rowspan - 1, $x + $heading->colspan - 1);
                    $worksheet->write_string($y, $x, strip_tags($heading->text));
                    $x++;
                }
                $y++;
            }
        }
        if (!empty($table->data)) {
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
                    $worksheet->write_string($y, $x, strip_tags($cell->text));
                    $worksheet->merge_cells($y, $x, $y + $cell->rowspan - 1, $x + $cell->colspan - 1);
                    $x++;
                }
                $y++;
            }
        }
    }
}

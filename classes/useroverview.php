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
 * local_checkmarkreport_useroverview class, handles checkmarkreport useroverview content
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class local_checkmarkreport_useroverview extends local_checkmarkreport_base implements renderable {

    protected $tableclass = 'table table-condensed table-hover table-striped useroverview';

    public function __construct($id, $groupings=array(0), $groups=array(0), $users=array(0)) {
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
            // Remove all users who aren't part of the groups!
            list($insql, $params) = $DB->get_in_or_equal($groups);
            $grpusers = $DB->get_fieldset_select('groups_members', 'DISTINCT userid', 'groupid '.$insql, $params);
            if (in_array(0, $users) || empty($users)) {
                $users = $grpusers;
            } else {
                $users = array_intersect($users, $grpusers);
            }
        }

        parent::__construct($id, $groups, $users, array(0));
    }

    public function get_table($userdata) {
        global $CFG, $DB, $PAGE;

        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showpoints = get_user_preferences('checkmarkreport_showpoints');

        $table = new html_table();

        $jsarguments = array(
            'id'        => "#attempts-$userdata->id",
            'cfg'       => array('ajaxenabled' => false),
            'items'     => array(),
            'users'     => array(),
            'grade'  => array()
        );
        $jsscales = array();

        $table->id = "attempts-$userdata->id";
        if (!isset($table->attributes)) {
            $table->attributes = array('class' => $this->tableclass);
        } else if (!isset($table->attributes['class'])) {
            $table->attributes['class'] = $this->tableclass;
        } else {
            $table->attributes['class'] .= $this->tableclass;
        }

        $table->tablealign = 'center';

        $tabledata = array();
        $row = array();

        $cellwidth = array();
        $columnformat = array();
        $tableheaders = array();
        $tablecolumns = array();
        $table->colgroups = array();
        $table->align = array();

        $sortlink = $this->get_sortlink('checkmark',
                                        get_string('modulename', 'checkmark'),
                                        $PAGE->url);
        $tableheaders['checkmark'] = new html_table_cell($sortlink);
        $tableheaders['checkmark']->header = true;
        $table->align['checkmark'] = 'center';
        $tablecolumns[] = 'checkmark';
        $table->colgroups[] = array('span' => '1',
                                    'class' => 'checkmark');
        $table->colclasses['checkmark'] = 'checkmark';

        $tableheaders['examples'] = new html_table_cell(get_string('example', 'local_checkmarkreport'));
        $tableheaders['examples']->header = true;
        $table->align['examples'] = 'center';
        $tablecolumns[] = 'examples';
        $table->colgroups[] = array('span' => '1',
                                    'class' => 'examples');
        $table->colclasses['examples'] = 'examples';

        $tableheaders['checked'] = new html_table_cell(get_string('status', 'local_checkmarkreport'));
        $tableheaders['checked']->header = true;
        $table->align['checked'] = 'center';
        $tablecolumns[] = 'checked';
        $table->colgroups[] = array('span' => '1',
                                    'class' => 'checked');
        $table->colclasses['checked'] = 'checked';

        if (!empty($showgrade)) {
            $tableheaders['points'] = new html_table_cell(get_string('grade', 'local_checkmarkreport'));
            $tableheaders['points']->header = true;
            $table->align['points'] = 'center';
            $tablecolumns[] = 'points';
            $table->colgroups[] = array('span' => '1',
                                        'class' => 'points');
            $table->colclasses['points'] = 'points';
        }

        $table->head = array();
        $table->head[0] = new html_table_row();
        $table->head[0]->cells = $tableheaders;

        $instances = $this->get_courseinstances();
        $i = 0;

        foreach ($userdata->instancedata as $key => $instancedata) {
            $instance = $instances[$key];
            $idx = 0;
            if (!isset($examplenames[$instance->id])) {
                $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
            }
            if (count($userdata->instancedata[$instance->id]->examples) == 0) {
                $row = array();
                $instanceurl = new moodle_url('/mod/checmark/view.php',
                                              array('id' => $instance->coursemodule));
                $instancelink = html_writer::link($instanceurl, $instance->name);
                $row['checkmark'] = new html_table_cell($instancelink);
                $row['checkmark']->colspan = 4;
                $row['checkmark']->header = true;
                $row['checkmark']->style = ' text-align: left; ';
                $row['examples'] = null;
                $row['checked'] = null;
                if (!empty($showpoints)) {
                    $row['points'] = null;
                }
                $table->data[$i] = new html_table_row();
                $table->data[$i]->cells = $row;
                $i++;
            } else {
                foreach ($userdata->instancedata[$instance->id]->examples as $exid => $example) {
                    $row = array();
                    if ($idx == 0) {
                        $instanceurl = new moodle_url('/mod/checkmark/view.php',
                                                      array('id' => $instance->coursemodule));
                        $instancelink = html_writer::link($instanceurl, $instance->name);
                        $row['checkmark'] = new html_table_cell($instancelink);
                        $row['checkmark']->header = true;
                        $row['checkmark']->style = ' text-align: left; ';
                    } else {
                        $row['checkmark'] = null;
                    }
                    $row['examples'] = new html_table_cell($examplenames[$instance->id][$exid]->name.
                                                           ' ('.$examplenames[$instance->id][$exid]->grade.')');
                    if ($showpoints) {
                        $row['checked'] = new html_table_cell($example ?
                                                              $examplenames[$instance->id][$exid]->grade :
                                                              0);
                    } else {
                        $row['checked'] = new html_table_cell($example ? "☒" : "☐");
                    }
                    if (!empty($showgrade)) {
                        $row['points'] = new html_table_cell(($example ?
                                                              $examplenames[$instance->id][$exid]->grade :
                                                              0).'/'.
                                                              $examplenames[$instance->id][$exid]->grade);
                    }
                    $table->data[$i] = new html_table_row();
                    $table->data[$i]->cells = $row;
                    $i++;
                    $idx++;
                }
                $table->data[$i - $idx]->cells['checkmark']->rowspan = $idx;
            }
            if (!empty($showabs) || !empty($showrel) || !empty($showgrade)) {
                $row = array();
                $row['checkmark'] = new html_table_cell('S '.$instance->name);
                $row['checkmark']->header = true;
                $row['checkmark']->colspan = 2;
                $row['checkmark']->style = ' text-align: left; ';
                $row['examples'] = null;
                if (!empty($showabs)) {
                    $checkedtext = $userdata->instancedata[$instance->id]->checked.'/'.
                                   $userdata->instancedata[$instance->id]->maxchecked;
                }
                if (!empty($showrel)) {
                    $percentchecked = round($userdata->instancedata[$instance->id]->percentchecked, 2);
                    if (!empty($showabs)) {
                        $checkedtext .= ' ('.$percentchecked.'%)';
                    } else {
                        $checkedtext = $percentchecked.'%';
                    }
                }
                if (!empty($showrel) || !empty($showabs)) {
                    $row['checked'] = new html_table_cell($checkedtext);
                    $row['checked']->header = true;
                    $row['checked']->style = ' text-align: right; ';
                }
                $grade = empty($userdata->instancedata[$instance->id]->grade) ?
                         0 : $userdata->instancedata[$instance->id]->grade;
                if (!empty($showgrade)) {
                    $grade = $userdata->instancedata[$instance->id]->grade;
                    $finalgrade = $userdata->instancedata[$instance->id]->finalgrade->grade;
                    $locked = $userdata->instancedata[$instance->id]->finalgrade->locked;
                    if (($userdata->instancedata[$instance->id]->finalgrade->overridden
                            || $locked || ($grade != $finalgrade))
                        && !is_null($userdata->instancedata[$instance->id]->finalgrade->grade)) {
                        $gradetext = (empty($finalgrade) ? 0 : round($finalgrade, 2)).' / '.$userdata->maxgrade;
                        $class = "current";
                        // TODO add data to jsarguments!
                        $userid = $userdata->id;
                        if (empty($jsarguments['users'][$userid])) {
                            $userobj = $DB->get_record('user', array('id' => $userid),
                                                       'id, '.implode(', ', get_all_user_name_fields()));
                            $jsarguments['users'][$userid] = fullname($userobj);
                        }
                        $usermodified = $userdata->instancedata[$instance->id]->finalgrade->usermodified;
                        if (empty($jsarguments['users'][$usermodified])) {
                            $userobj = $DB->get_record('user', array('id' => $usermodified),
                                                       'id, '.implode(', ', get_all_user_name_fields()));
                            $jsarguments['users'][$usermodified] = fullname($userobj);
                        }
                        $dategraded = $userdata->instancedata[$instance->id]->finalgrade->dategraded;
                        $jsarguments['grade'][] = array('user'       => $userdata->id,
                                                        'item'       => $instance->id,
                                                        'dategraded' => userdate($dategraded),
                                                        'grader'     => $jsarguments['users'][$usermodified]);
                    } else {
                        $gradetext = (empty($userdata->checkgrade) ? 0 :
                                     $userdata->checkgrade).'/'.$userdata->maxgrade;
                            $class = "";
                    }
                    if (!empty($showrel)) {
                        $finalgrade = $userdata->instancedata[$instance->id]->finalgrade->grade;
                        $grade = $userdata->instancedata[$instance->id]->grade;
                        $locked = $userdata->instancedata[$instance->id]->finalgrade->locked;
                        if (($userdata->instancedata[$instance->id]->finalgrade->overridden
                                || $locked || ($grade != $finalgrade))
                            && !is_null($userdata->instancedata[$instance->id]->finalgrade->grade)) {
                            $percentgrade = round(100 * $grade / $userdata->maxgrade, 2);
                        } else {
                            $percentgrade = round($userdata->instancedata[$instance->id]->percentgrade, 2);
                        }
                        $gradetext .= ' ('.$percentgrade.' %)';
                    }
                    $row['points'] = new html_table_cell($gradetext);
                    $row['points']->header = true;
                    $row['points']->attributes['class'] = $class;
                    $row['points']->id = "u".$userdata->id."i".$instance->id."_a";
                    $row['points']->style = ' text-align: right; ';
                }
                $table->data[$i] = new html_table_row();
                $table->data[$i]->cells = $row;
                $i++;
                $idx++;
            }
            $table->data[$i] = new html_table_row(array(''));
            $table->data[$i]->cells[0]->colspan = count($table->data[$i - 1]->cells);
            $i++;
            $idx++;
        }
        if (!empty($showabs) || !empty($showrel) || !empty($showgrade)) {
            $row = array();
            $row['checkmark'] = new html_table_cell('S '.get_string('total'));
            $row['checkmark']->header = true;
            $row['checkmark']->colspan = 2;
            $row['checkmark']->style = ' text-align: left; ';
            $row['examples'] = null;
            $checkgrade = empty($userdata->checkgrade) ? 0 : $userdata->checkgrade;
            if (!empty($showabs)) {
                $checkedtext = $userdata->checks.'/'.$userdata->maxchecks;
            }
            if (!empty($showrel)) {
                if (!empty($showabs)) {
                    $checkedtext .= ' ('.round($userdata->percentchecked, 2).'%)';
                } else {
                    $checkedtext = round($userdata->percentchecked, 2).'%';
                }
            }
            if (!empty($showrel) || !empty($showabs)) {
                $row['checked'] = new html_table_cell($checkedtext);
                $row['checked']->header = true;
                $row['checked']->style = ' text-align: right; ';
            }
            if (!empty($showgrade)) {
                // Coursesum of course grade.
                if (!empty($showgrade)) {
                    // Highlight if overwritten/other than due to checked checkmarks in university-clean theme!
                    if ($userdata->overridden) {
                        $gradetext = (empty($userdata->coursesum) ? 0 :
                                     round($userdata->coursesum, 2)).' / '.$userdata->maxgrade;
                        // TODO add data to jsarguments!
                    } else {
                        $gradetext = (empty($userdata->checkgrade) ? 0 :
                                     $userdata->checkgrade).'/'.$userdata->maxgrade;
                    }
                }
                if (!empty($showrel)) {
                    if ($userdata->overridden) {
                        $percgrade = empty($userdata->coursesum) ? 0 : 100 * $userdata->coursesum / $userdata->maxgrade;
                        $gradetext .= ' ('.round($percgrade, 2).'%)';
                    } else {
                        $gradetext .= ' ('.round($userdata->percentgrade, 2).'%)';
                    }
                }
                $row['points'] = new html_table_cell($gradetext);
                $row['points']->header = true;
                $row['points']->attributes['class'] = !empty($userdata->overridden) ? 'current' : '';
                $row['points']->id = "u".$userdata->id."i0_a";
                $row['points']->style = ' text-align: right; ';
            }
            $table->data[$i] = new html_table_row();
            $table->data[$i]->cells = $row;
        }

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
        $txt = '';
        $examplenames = array();
        $instances = $this->get_courseinstances();
        $course = $DB->get_record('course', array('id' => $this->courseid));
        // Header.
        $txt .= get_string('pluginname', 'local_checkmarkreport').': '.$course->fullname."\n";
        // Data.
        foreach ($data as $userid => $row) {
            $txt .= get_string('fullname').': '.fullname($row)."\n";
            $txt .= "S ".get_string('grade')."\t".(empty($row->checkgrade) ? 0 : $row->checkgrade).
                    '/'.(empty($row->maxgrade) ? 0 : $row->maxgrade)."\n";
            $txt .= "S ".get_string('examples', 'local_checkmarkreport')."\t".$row->checks.'/'.$row->maxchecks."\n";
            $txt .= "S % ".get_string('examples', 'local_checkmarkreport')."\t".$row->percentchecked.'%'."\n";
            $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
            $txt .= "S % ".get_string('grade', 'local_checkmarkreport')."\t".$percgrade.'%'."\n";
            $instances = $this->get_courseinstances();
            foreach ($instances as $instance) {
                $txt .= $instance->name."\n";
                // Dynamically add examples!
                // Get example data!
                if (!isset($examplenames[$instance->id])) {
                    $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
                }
                $instancedata = $row->instancedata[$instance->id];
                foreach ($examplenames[$instance->id] as $key => $example) {
                    $txt .= "\t".$examplenames[$instance->id][$key]->name." (".$examplenames[$instance->id][$key]->grade.'P)';
                    $txt .= "\t".($instancedata->examples[$key] ? "☒" : "☐");
                    $txt .= "\t".($instancedata->examples[$key] ? $examplenames[$instance->id][$key]->grade : 0).'/'.
                            $examplenames[$instance->id][$key]->grade."\n";
                }
                $txt .= "S ".$instance->name;
                $txt .= "\t".$instancedata->checked.'/'.$instancedata->maxchecked.
                        '('.round($instancedata->percentchecked, 2).'%)';
                $percgrade = round((empty($instancedata->percentgrade) ? 0 : $instancedata->percentgrade), 2);
                $txt .= "\t".(empty($instancedata->grade) ? 0 : $instancedata->grade).'/'.
                        (empty($instancedata->maxgrade) ? 0 : $instancedata->maxgrade).
                        '('.$percgrade." %)\n";
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
        // Initialise everything!
        $worksheets = array();
        $data = $this->get_coursedata();
        $sheetnames = array();
        foreach ($data as $userid => $userdata) {
            $x = 0;
            $y = 0;
            $i=0;
            while(in_array(!empty($i) ? fullname($userdata).' '.$i : fullname($userdata), $sheetnames)) {
                $i++;
            }
            if (!empty($i)) {
                $worksheets[$userid] = $workbook->add_worksheet(fullname($userdata).' '.$i);
                $sheetnames[] = fullname($userdata).' '.$i;
            } else {
                $worksheets[$userid] = $workbook->add_worksheet(fullname($userdata));
                $sheetnames[] = fullname($userdata);
            }
            $table = $this->get_table($userdata);
            $worksheets[$userid]->write_string($y, $x, strip_tags(fullname($data[$userid])));
            $y++;
            // We may use additional table data to format sheets!
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

                        if (!isset($heading->rowspan)) {
                            $heading->rowspan = 1;
                        }
                        if (!isset($heading->colspan)) {
                            $heading->colspan = 1;
                        }

                        $worksheets[$userid]->write_string($y, $x, strip_tags($heading->text));
                        $worksheets[$userid]->merge_cells($y, $x, $y + $heading->rowspan - 1, $x + $heading->colspan - 1);
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

                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; // Flag for sanity checking!
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

                        $worksheets[$userid]->write_string($y, $x, strip_tags($cell->text));
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

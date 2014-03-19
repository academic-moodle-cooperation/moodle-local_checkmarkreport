<?php
// This file is made for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Prints a list of all checkmarkreport instances in the given course (via id)
 *
 * @package       local_checkmarkreport
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2013 onwards TSC TU Vienna
 * @since         Moodle 2.5.3
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->dirroot.'/local/checkmarkreport/checkmarkreport.class.php';
require_once $CFG->dirroot.'/local/checkmarkreport/reportfilterform.class.php';

class checkmarkreport_overview extends checkmarkreport implements renderable {
    function __construct($id, $groupings=array(0), $groups=array(0), $instances=array(0)) {
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

    function get_table() {
        global $CFG, $DB, $SESSION, $PAGE;
        
        $sortarray = &$SESSION->checkmarkreport->{$this->courseid}->sort;
        
        $data = $this->get_coursedata();
        
        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showpoints = get_user_preferences('checkmarkreport_showpoints');
        
        $table = new html_table();
        
        $table->id = "attempts";
        if (!isset($table->attributes)) {
            $table->attributes = array('class' => 'coloredrows');
        } else if (!isset($table->attributes['class'])) {
            $table->attributes['class'] = 'coloredrows';
        } else {
            $table->attributes['class'] .= ' coloredrows';
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
        $useridentity = explode(',', $CFG->showuseridentity);
        //Firstname sortlink
        $firstname = $this->get_sortlink('firstname', get_string('firstname'), $PAGE->url);
        //Lastname sortlink
        $lastname = $this->get_sortlink('lastname', get_string('lastname'), $PAGE->url);
        $sortable[] = 'lasname';
        $sortable[] = 'firstname';
        $tableheaders['fullnameuser'] = new html_table_cell($firstname.' '.$lastname);
        $tableheaders['fullnameuser']->header = true;
        $tableheaders['fullnameuser']->rowspan = 2;
        $tableheaders2['fullnameuser'] = null;
        $tablecolumns[] = 'fullnameuser';
        $table->colgroups[] = array('span' => '1',
                                    'class' => 'fullnameuser');
        $table->colclasses['fullnameuser'] = 'fullnameuser';

        foreach ($useridentity as $cur) {
            $sortable[] = $cur;
            $text = ($cur=='phone1') ? get_string('phone') : get_string($cur);
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
        
        //coursesum of course grade
        if (!empty($showgrade)) {
            $sortlink = $this->get_sortlink('checkgrade', 'Σ '.get_string('grade'), $PAGE->url);
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

        //coursesum of course examples
        if (!empty($showabs)) {
            $text = 'Σ '.get_string('examples', 'local_checkmarkreport');
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
            //percent of course examples
            $text = 'Σ % '.
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
        foreach($instances as $instance) {
            $span = 0;
            $instanceurl = new moodle_url('/mod/checkmark/view.php', array('id'=>$instance->coursemodule));
            $instancelink = html_writer::link($instanceurl, $instance->name);
            $tableheaders['instance'.$instance->id] = new html_table_cell($instancelink);
            $tableheaders['instance'.$instance->id]->header = true;
            $tableheaders['instance'.$instance->id]->scope = 'colgroup';
            $table->colclasses['instance'.$instance->id] = 'instance'.$instance->id;
            //coursesum of course grade
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

            //coursesum of course examples
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

            //percent of course examples
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
            // get example data
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
            for($i=1;$i<$span;$i++) {
                //insert empty cells for the colspan
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
            
            //coursesum of course grade
            if (!empty($showgrade)) {
                $row['grade'] = new html_table_cell((empty($curuser->checkgrade) ? 0 : $curuser->checkgrade).' / '.$curuser->maxgrade);
            }
            //coursesum of course examples
            if (!empty($showabs)) {
                $row['examples'] = new html_table_cell($curuser->checks.' / '.$curuser->maxchecks);
            }
            //percent of course examples
            if (!empty($showrel)) {
                $percgrade = empty($curuser->percentgrade) ? 0 : $curuser->percentgrade;
                $row['percentex'] = new html_table_cell(round($curuser->percentchecked, 2).'% ('.round($percgrade, 2).' %)');
            }
            
            $instances = $this->get_courseinstances();
            foreach($instances as $instance) {
                //coursesum of course grade
                if (!empty($showgrade)) {
                    $grade = empty($curuser->instancedata[$instance->id]->grade) ? 0 : $curuser->instancedata[$instance->id]->grade;
                    $row['grade'.$instance->id] = new html_table_cell($grade.' / '.$curuser->instancedata[$instance->id]->maxgrade);
                }
                //coursesum of course examples
                if (!empty($showabs)) {
                    $row['examples'.$instance->id] = new html_table_cell($curuser->instancedata[$instance->id]->checked.' / '.$curuser->instancedata[$instance->id]->maxchecked);
                }
                //percent of course examples
                if (!empty($showrel)) {
                    $perccheck = empty($curuser->instancedata[$instance->id]->percentchecked) ? 0 : $curuser->instancedata[$instance->id]->percentchecked;
                    $percgrade = empty($curuser->instancedata[$instance->id]->percentgrade) ? 0 : $curuser->instancedata[$instance->id]->percentgrade;
                    $row['percentex'.$instance->id] = new html_table_cell(round($perccheck, 2).'% ('.round($percgrade, 2).' %)');
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
        return $table;
    }
    
    public function get_xml() {
        global $CFG, $DB;
        $data = $this->get_coursedata();
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        $xml = '';
        $examplenames = array();
        $instances = $this->get_courseinstances();
        foreach($data as $userid => $row) {
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
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        $txt = '';
        $examplenames = array();
        $instances = $this->get_courseinstances();
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        //Header
        $txt .= get_string('pluginname', 'local_checkmarkreport').': '.$course->fullname."\n";
        //Title
        $txt .= get_string('fullname');
        $useridentity = explode(',', $CFG->showuseridentity);
        foreach ($useridentity as $cur) {
            $txt .= "\t".(($cur=='phone1') ? get_string('phone') : get_string($cur));
        }
        $txt .= "\tΣ ".get_string('grade');
        $txt .= "\tΣ ".get_string('examples', 'local_checkmarkreport');
        $txt .= "\tΣ % ".get_string('examples', 'local_checkmarkreport');
        
        $instances = $this->get_courseinstances();
        foreach($instances as $instance) {
            $txt .= "\t".$instance->name.' '.get_string('grade');
            $txt .= "\t".$instance->name.' '.get_string('examples', 'local_checkmarkreport');
            $txt .= "\t".$instance->name.' % '.get_string('examples', 'local_checkmarkreport');
            // Dynamically add examples!
            // get example data
            if (!isset($examplenames[$instance->id])) {
                $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
            }
            foreach ($examplenames[$instance->id] as $key => $example) {
                $txt .= "\t".$instance->name.' '.$example->name." (".$example->grade.'P)';
            }
        }
        $txt .= "\n";
        
        //Data
        foreach($data as $userid => $row) {
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
        
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        
        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.$course->shortname;
        $workbook->send($filename.'.ods');
        $workbook->close();
    }

    public function get_xls() {
        global $CFG, $DB;
    
        require_once($CFG->libdir . "/excellib.class.php");
        
        $workbook = new MoodleExcelWorkbook("-",'excel5');
    
        $this->fill_workbook($workbook);
        
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        
        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.$course->shortname;    
        $workbook->send($filename.'.xls');
        $workbook->close();
    }

    public function get_xlsx() {
        global $CFG, $DB;

        require_once($CFG->libdir . "/excellib.class.php");

        $workbook = new MoodleExcelWorkbook("-", 'Excel2007');
    
        $this->fill_workbook($workbook);
        
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        
        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.$course->shortname;
        $workbook->send($filename);
        $workbook->close();
    }

    public function fill_workbook($workbook) {
        $x = $y = 0;
        
        // We start with the html_table-Object
        $table = $this->get_table();
        
        $worksheet = $workbook->add_worksheet(time());
        
        // prepare table data and populate missing properties with reasonable defaults
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = fix_align_rtl($aa);  // Fix for RTL languages
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

            foreach($table->head as $headrow) {
                $x = 0;
                $keys = array_keys($headrow->cells);
                $lastkey = end($keys);
                $countcols = count($headrow->cells);

                foreach ($headrow->cells as $key => $heading) {
                    // Convert plain string headings into html_table_cell objects
                    if (!($heading instanceof html_table_cell)) {
                        $headingtext = $heading;
                        $heading = new html_table_cell();
                        $heading->text = $headingtext;
                        $heading->header = true;
                    }
                    
                    if($heading->text == null) {
                        //$worksheet->write_blank($y, $x);
                        $x++;
                        continue;
                    }

                    if ($heading->header !== false) {
                        $heading->header = true;
                    }

/*                    if (isset($heading->colspan) && $heading->colspan > 1) {
                        $countcols += $heading->colspan - 1;
                    }*/

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
                    $worksheet->merge_cells($y, $x, $y+$heading->rowspan-1, $x+$heading->colspan-1);
                    $worksheet->write_string($y, $x, strip_tags($heading->text)/*, $headline_format*/);
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
                $x=0;
                if (($row === 'hr') && ($countcols)) {
                    //$output .= html_writer::tag('td', html_writer::tag('div', '', array('class' => 'tabledivider')), array('colspan' => $countcols));
                } else {
                    // Convert array rows to html_table_rows and cell strings to html_table_cell objects
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

                    //$output .= html_writer::start_tag('tr', array('class' => trim($row->attributes['class']), 'style' => $row->style, 'id' => $row->id)) . "\n";
                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; //flag for sanity checking
                    foreach ($row->cells as $key => $cell) {
                        if ($gotlastkey) {
                            //This should never happen. Why do we have a cell after the last cell?
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

                        if (($cell->header === true) && empty($cell->scope)) {
                            $cell->scope = 'row';
                        }

                        if (isset($table->colclasses[$key])) {
                            $cell->attributes['class'] .= ' ' . $table->colclasses[$key];
                        }

                        $cell->attributes['class'] .= ' cell c' . $key;
                        if ($key == $lastkey) {
                            $cell->attributes['class'] .= ' lastcol';
                            $gotlastkey = true;
                        }
                        $tdstyle = '';
                        $tdstyle .= isset($table->align[$key]) ? $table->align[$key] : '';
                        $tdstyle .= isset($table->size[$key]) ? $table->size[$key] : '';
                        $tdstyle .= isset($table->wrap[$key]) ? $table->wrap[$key] : '';
                        $cell->attributes['class'] = trim($cell->attributes['class']);
                        $tdattributes = array_merge($cell->attributes, array(
                                'style' => $tdstyle . $cell->style,
                                'colspan' => $cell->colspan,
                                'rowspan' => $cell->rowspan,
                                'id' => $cell->id,
                                'abbr' => $cell->abbr,
                                'scope' => $cell->scope,
                            ));
                        $tagtype = 'td';
                        if ($cell->header === true) {
                            $tagtype = 'th';
                        }
                        if (!isset($cell->rowspan)) {
                            $cell->rowspan = 1;
                        }
                        if (!isset($cell->colspan)) {
                            $cell->colspan = 1;
                        }
                        $worksheet->write_string($y, $x, strip_tags($cell->text));
                        $worksheet->merge_cells($y, $x, $y+$cell->rowspan-1, $x+$cell->colspan-1);
                        //$output .= html_writer::tag($tagtype, $cell->text, $tdattributes) . "\n";
                        $x++;
                    }
                }
                //$output .= html_writer::end_tag('tr') . "\n";
                $y++;
            }
        }
    }
}

class checkmarkreport_useroverview extends checkmarkreport implements renderable {
    function __construct($id, $groupings=array(0), $groups=array(0), $users=array(0)) {
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
            //remove all users who aren't part of the groups
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

    function get_table($userdata) {
        global $CFG, $DB, $PAGE;
        
        $showgrade = get_user_preferences('checkmarkreport_showgrade');
        $showabs = get_user_preferences('checkmarkreport_sumabs');
        $showrel = get_user_preferences('checkmarkreport_sumrel');
        $showpoints = get_user_preferences('checkmarkreport_showpoints');
        
        $table = new html_table();
        $table->id = "attempts";
        if (!isset($table->attributes)) {
            $table->attributes = array('class' => 'coloredrows userview');
        } else if (!isset($table->attributes['class'])) {
            $table->attributes['class'] = 'coloredrows userview';
        } else {
            $table->attributes['class'] .= ' coloredrows userview';
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
        
        //foreach ($instances as $key => $instance) {
        foreach ($userdata->instancedata as $key => $instancedata) {
            $instance = $instances[$key];
            $idx = 0;
            if (!isset($examplenames[$instance->id])) {
                $examplenames[$instance->id] = $DB->get_records('checkmark_examples', array('checkmarkid' => $instance->id));
            }
            if (count($userdata->instancedata[$instance->id]->examples) == 0) {
                $row = array();
                $instanceurl = new moodle_url('/mod/checmark/view.php',
                                              array('id'=>$instance->coursemodule));
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
                                                      array('id'=>$instance->coursemodule));
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
                $table->data[$i-$idx]->cells['checkmark']->rowspan = $idx;
            }
            if (!empty($showabs) || !empty($showrel) || !empty($showgrade)) {
                $row = array();
                $row['checkmark'] = new html_table_cell('Σ '.$instance->name);
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
                $grade = empty($userdata->instancedata[$instance->id]->grade) ? 0 : $userdata->instancedata[$instance->id]->grade;
                if (!empty($showgrade)) {
                    $gradetext = $grade.'/'.$userdata->instancedata[$instance->id]->maxgrade;
                    if (!empty($showrel)) {
                        $percentgrade = round($userdata->instancedata[$instance->id]->percentgrade, 2);
                        $gradetext .= ' ('.$percentgrade.' %)';
                    }
                    $row['points'] = new html_table_cell($gradetext);
                    $row['points']->header = true;
                    $row['points']->style = ' text-align: right; ';
                }
                $table->data[$i] = new html_table_row();
                $table->data[$i]->cells = $row;
                $i++;
                $idx++;
            }
            $table->data[$i] = new html_table_row(array(''));
            $table->data[$i]->cells[0]->colspan = count($table->data[$i-1]->cells);
            $i++;
            $idx++;
        }
        if (!empty($showabs) || !empty($showrel) || !empty($showgrade)) {
            $row = array();
            $row['checkmark'] = new html_table_cell('Σ '.get_string('total'));
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
                $gradetext = (empty($userdata->checkgrade) ? 0 : $userdata->checkgrade).'/'.
                             $userdata->maxgrade;
                if (!empty($showrel)) {
                    $gradetext .= ' ('.round($userdata->percentgrade, 2).'%)';
                }
                $row['points'] = new html_table_cell($gradetext);
                $row['points']->header = true;
                $row['points']->style = ' text-align: right; ';
            }
            $table->data[$i] = new html_table_row();
            $table->data[$i]->cells = $row;
        }

        return $table;
    }
    
    public function get_xml() {
        global $CFG, $DB;
        $data = $this->get_coursedata();
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        $xml = '';
        $examplenames = array();
        $instances = $this->get_courseinstances();
        foreach($data as $userid => $row) {
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
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        $txt = '';
        $examplenames = array();
        $instances = $this->get_courseinstances();
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        //Header
        $txt .= get_string('pluginname', 'local_checkmarkreport').': '.$course->fullname."\n";
        //Data
        foreach($data as $userid => $row) {
            $txt .= get_string('fullname').': '.fullname($row)."\n";
            $txt .= "Σ ".get_string('grade')."\t".(empty($row->checkgrade) ? 0 : $row->checkgrade).'/'.(empty($row->maxgrade) ? 0 : $row->maxgrade)."\n";
            $txt .= "Σ ".get_string('examples', 'local_checkmarkreport')."\t".$row->checks.'/'.$row->maxchecks."\n";
            $txt .= "Σ % ".get_string('examples', 'local_checkmarkreport')."\t".$row->percentchecked.'%'."\n";
            $percgrade = round((empty($row->percentgrade) ? 0 : $row->percentgrade), 2);
            $txt .= "Σ % ".get_string('grade', 'local_checkmarkreport')."\t".$percgrade.'%'."\n";
            $instances = $this->get_courseinstances();
            foreach($instances as $instance) {
                $txt .= $instance->name."\n";
                // Dynamically add examples!
                // get example data
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
                $txt .= "Σ ".$examplenames[$instance->id][$key]->name;
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
        
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        
        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.$course->shortname;
        $workbook->send($filename.'.ods');
        $workbook->close();
    }

    public function get_xls() {
        global $CFG, $DB;
    
        require_once($CFG->libdir . "/excellib.class.php");
        
        $workbook = new MoodleExcelWorkbook("-",'excel5');
    
        $this->fill_workbook($workbook);
        
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        
        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.$course->shortname;    
        $workbook->send($filename.'.xls');
        $workbook->close();
    }

    public function get_xlsx() {
        global $CFG, $DB;

        require_once($CFG->libdir . "/excellib.class.php");

        $workbook = new MoodleExcelWorkbook("-", 'Excel2007');
    
        $this->fill_workbook($workbook);
        
        $course = $DB->get_record('course', array('id'=>$this->courseid));
        
        $filename = get_string('pluginname', 'local_checkmarkreport').'_'.$course->shortname;
        $workbook->send($filename);
        $workbook->close();
    }

    public function fill_workbook($workbook) {
        //initialise everything
        $worksheets = array();
        $data = $this->get_coursedata();
        foreach($data as $userid => $userdata) {
            $x = 0;
            $y = 0;
            $worksheets[$userid] = $workbook->add_worksheet(fullname($userdata));
            $table = $this->get_table($userdata);
            $worksheets[$userid]->write_string($y, $x, strip_tags(fullname($data[$userid])));
            $y++;
            // We may use additional table data to format sheets!
            if (!empty($table->align)) {
                foreach ($table->align as $key => $aa) {
                    if ($aa) {
                        $table->align[$key] = fix_align_rtl($aa);  // Fix for RTL languages
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

                foreach($table->head as $headrow) {
                    $x = 0;
                    $keys = array_keys($headrow->cells);
                    $lastkey = end($keys);
                    $countcols = count($headrow->cells);

                    foreach ($headrow->cells as $key => $heading) {
                        // Convert plain string headings into html_table_cell objects
                        if (!($heading instanceof html_table_cell)) {
                            $headingtext = $heading;
                            $heading = new html_table_cell();
                            $heading->text = $headingtext;
                            $heading->header = true;
                        }
                        
                        if($heading->text == null) {
                            //$worksheet->write_blank($y, $x);
                            $x++;
                            continue;
                        }

                        if ($heading->header !== false) {
                            $heading->header = true;
                        }

                        if (!isset($heading->rowspan)) {
                            $heading->rowspan = 1;
                        }
                        if (!isset($heading->colspan)) {
                            $heading->colspan = 1;
                        }

                        $heading->attributes['class'] = trim($heading->attributes['class']);
                        $attributes = array_merge($heading->attributes, array(
                                'style'     => $heading->style,
                                'scope'     => $heading->scope,
                                'colspan'   => $heading->colspan,
                                'rowspan'   => $heading->rowspan
                            ));
                        $worksheets[$userid]->write_string($y, $x, strip_tags($heading->text));
                        $worksheets[$userid]->merge_cells($y, $x, $y+$heading->rowspan-1, $x+$heading->colspan-1);
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
                    $x=0;
                    // Convert array rows to html_table_rows and cell strings to html_table_cell objects
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

                    //$output .= html_writer::start_tag('tr', array('class' => trim($row->attributes['class']), 'style' => $row->style, 'id' => $row->id)) . "\n";
                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; //flag for sanity checking
                    foreach ($row->cells as $key => $cell) {
                        if ($gotlastkey) {
                            //This should never happen. Why do we have a cell after the last cell?
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

                        if (($cell->header === true) && empty($cell->scope)) {
                            $cell->scope = 'row';
                        }

                        if (isset($table->colclasses[$key])) {
                            $cell->attributes['class'] .= ' ' . $table->colclasses[$key];
                        }

                        $cell->attributes['class'] .= ' cell c' . $key;
                        if ($key == $lastkey) {
                            $cell->attributes['class'] .= ' lastcol';
                            $gotlastkey = true;
                        }
                        $tdstyle = '';
                        $tdstyle .= isset($table->align[$key]) ? $table->align[$key] : '';
                        $tdstyle .= isset($table->size[$key]) ? $table->size[$key] : '';
                        $tdstyle .= isset($table->wrap[$key]) ? $table->wrap[$key] : '';
                        $cell->attributes['class'] = trim($cell->attributes['class']);
                        $tdattributes = array_merge($cell->attributes, array(
                                'style' => $tdstyle . $cell->style,
                                'colspan' => $cell->colspan,
                                'rowspan' => $cell->rowspan,
                                'id' => $cell->id,
                                'abbr' => $cell->abbr,
                                'scope' => $cell->scope,
                            ));
                        $tagtype = 'td';
                        if ($cell->header === true) {
                            $tagtype = 'th';
                        }
                        if (!isset($cell->rowspan)) {
                            $cell->rowspan = 1;
                        }
                        if (!isset($cell->colspan)) {
                            $cell->colspan = 1;
                        }
                        $worksheets[$userid]->write_string($y, $x, strip_tags($cell->text));
                        if (($cell->rowspan > 1) || ($cell->colspan > 1)) {
                            $worksheets[$userid]->merge_cells($y, $x, $y+$cell->rowspan-1, $x+$cell->colspan-1);
                        }
                        $x++;
                    }
                    $y++;
                }
            }
        }
    }
}

class checkmarkreport_userview extends checkmarkreport_useroverview implements renderable {
    function __construct($id) {
        global $USER;
        set_user_preference('checkmarkreport_showgrade', 1);
        set_user_preference('checkmarkreport_sumabs', 1);
        set_user_preference('checkmarkreport_sumrel', 1);
        parent::__construct($id, array(0), array(0), array($USER->id));
    }
}

class local_checkmarkreport_renderer extends plugin_renderer_base {

    protected function render_checkmarkreport_overview(checkmarkreport_overview $report) {
        global $CFG, $DB;

        //Render download links
//        http_build_query()
        $data = array('id'         => $report->get_courseid(),
                      'tab'        => optional_param('tab', null, PARAM_ALPHANUM),
                      'showgrade'  => false,
                      'showabs'    => false,
                      'showrel'    => false,
                      'showpoints' => false,
                      'sesskey'    => sesskey(),
                      'format'     => checkmarkreport::FORMAT_XLSX);
        $groups = $report->get_groups();
        $checkmarks = $report->get_instances();
        $arrays = http_build_query(array('groups'     => $groups,
                                         'checkmarks' => $checkmarks));
        $uri = new moodle_url('/local/checkmarkreport/download.php?'.$arrays, $data);
        $downloadlinks = get_string('exportas', 'local_checkmarkreport');
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XLSX'),
                                           array('class'=>'downloadlink'));
        $uri = new moodle_url($uri, array('format' => checkmarkreport::FORMAT_XLS));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XLS'),
                                           array('class'=>'downloadlink'));
        $uri = new moodle_url($uri, array('format' => checkmarkreport::FORMAT_ODS));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.ODS'),
                                           array('class'=>'downloadlink'));
        $uri = new moodle_url($uri, array('format' => checkmarkreport::FORMAT_XML));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XML'),
                                           array('class'=>'downloadlink'));
        $uri = new moodle_url($uri, array('format' => checkmarkreport::FORMAT_TXT));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.TXT'),
                                           array('class'=>'downloadlink'));
        // Append warning message for XLS if there are more than 256 Columns
        $columns = 1; //Fullname
        $columns += count(explode(',', $CFG->showuseridentity));
        $addfact = 0;
        if (get_user_preferences('checkmarkreport_showgrade')) {
            $addfact++;
        }
        if (get_user_preferences('checkmarkreport_sumabs')) {
            $addfact++;
        }
        if (get_user_preferences('checkmarkreport_sumrel')) {
            $addfact++;
        }
        if (in_array(0, $checkmarks)) {
            $checkmarks = $DB->get_fieldset_select('checkmark', 'id', 'course = ?', array($report->get_courseid()));
        }
        $columns += $addfact * (count($checkmarks)+1);
        foreach($checkmarks as $checkmarkid) {
            $columns += $DB->count_records('checkmark_examples',
                                           array('checkmarkid' => $checkmarkid));
        }
        $out = '';
        if ($columns >= 256) {
            $out .= $this->output->notification(get_string('xlsover256', 'local_checkmarkreport'),
                                          'notifyproblem');
        }

        $out .= html_writer::tag('div', $downloadlinks, array('class'=>'download'));
        
        // Render the table!
        $table = $report->get_table();

        $out .= html_writer::tag('div', $this->table($table, $report),
                                array('class'=>'scrollforced'));

        return $this->output->container($out, 'submission');
    }
    
    protected function render_checkmarkreport_useroverview(checkmarkreport_useroverview $report, $hidefilter = false) {
        global $CFG, $DB, $PAGE, $COURSE;

        $out = '';
        $grade      = get_user_preferences('checkmarkreport_showgrade');
        $sumabs     = get_user_preferences('checkmarkreport_sumabs');
        $sumrel     = get_user_preferences('checkmarkreport_sumrel');
        $showpoints = get_user_preferences('checkmarkreport_showpoints');

        //Render download links
        $data = array('id'         => $report->get_courseid(),
                      'tab'        => optional_param('tab', null, PARAM_ALPHANUM),
                      'showgrade'  => false,
                      'showabs'    => false,
                      'showrel'    => false,
                      'showpoints' => false,
                      'sesskey'    => sesskey(),
                      'format'     => checkmarkreport::FORMAT_XLSX);
        $groups = $report->get_groups();
        $users = $report->get_user();
        $arrays = http_build_query(array('groups' => $groups,
                                         'users'  => $users));
        $uri = new moodle_url('/local/checkmarkreport/download.php?'.$arrays, $data);
        $downloadlinks = get_string('exportas', 'local_checkmarkreport');
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XLSX'),
                                           array('class'=>'downloadlink'));
        $uri = new moodle_url($uri, array('format' => checkmarkreport::FORMAT_XLS));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XLS'),
                                           array('class'=>'downloadlink'));
        $uri = new moodle_url($uri, array('format' => checkmarkreport::FORMAT_ODS));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.ODS'),
                                           array('class'=>'downloadlink'));
        $uri = new moodle_url($uri, array('format' => checkmarkreport::FORMAT_XML));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.XML'),
                                           array('class'=>'downloadlink'));
        $uri = new moodle_url($uri, array('format' => checkmarkreport::FORMAT_TXT));
        $downloadlinks .= html_writer::tag('span',
                                           html_writer::link($uri, '.TXT'),
                                           array('class'=>'downloadlink'));
        $out .= html_writer::tag('div', $downloadlinks, array('class'=>'download'));

        // Render the tables!
        $data = $report->get_coursedata();
        $users = $report->get_user();
        if (!empty($data) && !empty($users)) {
            foreach($data as $userdata) {
                if (!in_array($userdata->id, $users) && !in_array(0, $users)) {
                    continue;
                }
                $table = $report->get_table($userdata);
                $url = new moodle_url('/user/view.php', array('id'     => $userdata->id,
                                                              'course' => $report->get_courseid()));
                $userlink = html_writer::link($url, fullname($userdata));
                $headingtext = get_string('overview', 'local_checkmarkreport').' - '.$userlink;
                $out .= $this->output->heading($headingtext, 1, $headingtext);
                $out .= html_writer::tag('div', $this->table($table, $report), array('class'=>'collapsible'));
            }
        } else {
            $out .= $this->output->notification(get_string('nousers', 'checkmark'), 'notifyproblem');
        }

        return $this->output->container($out, 'report');
    }
    
    protected function render_checkmarkreport_userview(checkmarkreport_userview $report) {

        /*$out  = $this->output->heading(format_string($submission->title), 2);
        $out .= $this->output->container(format_string($submission->authorname), 'author');
        $out .= $this->output->container(format_text($submission->content, FORMAT_HTML), 'content');*/
        
        
        // Render the table!
        $out = $this->render_checkmarkreport_useroverview($report, true);

        return $this->output->container($out, 'submission');
    }

    /**
     * Renders HTML table
     *
     * This method may modify the passed instance by adding some default properties if they are not set yet.
     * If this is not what you want, you should make a full clone of your data before passing them to this
     * method. In most cases this is not an issue at all so we do not clone by default for performance
     * and memory consumption reasons.
     *
     * @param html_table $table data to be rendered
     * @param checkmarkreport $report optional if given table can hide columns
     * @return string HTML code
     */
    protected function table(html_table $table, checkmarkreport $report = null) {

        if ($report == null) {
            $nohide = true;
        } else {
            $nohide = false;
        }
    
        // prepare table data and populate missing properties with reasonable defaults
        if (!empty($table->align)) {
            foreach ($table->align as $key => $aa) {
                if ($aa) {
                    $table->align[$key] = 'text-align:'. fix_align_rtl($aa) .';';  // Fix for RTL languages
                } else {
                    $table->align[$key] = null;
                }
            }
        }
        if (!empty($table->size)) {
            foreach ($table->size as $key => $ss) {
                if ($ss) {
                    $table->size[$key] = 'width:'. $ss .';';
                } else {
                    $table->size[$key] = null;
                }
            }
        }
        if (!empty($table->wrap)) {
            foreach ($table->wrap as $key => $ww) {
                if ($ww) {
                    $table->wrap[$key] = 'white-space:nowrap;';
                } else {
                    $table->wrap[$key] = '';
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
                if (!isset($table->wrap[$key])) {
                    $table->wrap[$key] = null;
                }
            }
        }
        if (empty($table->attributes['class'])) {
            $table->attributes['class'] = 'generaltable';
        }
        if (!empty($table->tablealign)) {
            $table->attributes['class'] .= ' boxalign' . $table->tablealign;
        }

        // explicitly assigned properties override those defined via $table->attributes
        $table->attributes['class'] = trim($table->attributes['class']);
        $attributes = array_merge($table->attributes, array(
                'id'            => $table->id,
                'width'         => $table->width,
                'summary'       => $table->summary,
                'cellpadding'   => $table->cellpadding,
                'cellspacing'   => $table->cellspacing,
            ));
        $output = html_writer::start_tag('table', $attributes) . "\n";

        $countcols = 0;

        if (!empty($table->colgrps)) {
            $output .= html_writer::start_tag('colgroup');
            foreach ($table->colgrps as $colgrp) {
                $output .= html_writer::empty_tag('col', $colgrp);
            }
            $output .= html_writer::end_tag('colgroup');
        }

        if (!empty($table->head)) {
            $countrows = count($table->head);

            $output .= html_writer::start_tag('thead', array()) . "\n";

            $output .= html_writer::start_tag('tr', array()) . "\n";
            foreach($table->head as $headrow) {
                $keys = array_keys($headrow->cells);
                $lastkey = end($keys);
                $countcols = count($headrow->cells);
                $idx = 0;
                foreach ($headrow->cells as $key => $heading) {
                    // Convert plain string headings into html_table_cell objects
                    if (!($heading instanceof html_table_cell)) {
                        $headingtext = $heading;
                        $heading = new html_table_cell();
                        $heading->text = $headingtext;
                        $heading->header = true;
                    }
                    if ($heading->text == null) {
                        $idx++;
                        continue;
                    }
                    if ($heading->header !== false) {
                        $heading->header = true;
                    }

                    if ($heading->header && empty($heading->scope)) {
                        $heading->scope = 'col';
                    }

                    $heading->attributes['class'] .= ' header c' . $idx;
                    if (isset($heading->colspan) && $heading->colspan > 1) {
                        $countcols += $heading->colspan - 1;
                    }

                    if ($key == $lastkey) {
                        $heading->attributes['class'] .= ' lastcol';
                    }
                    if (isset($table->colclasses[$key])) {
                        $heading->attributes['class'] .= ' ' . $table->colclasses[$key];
                        $classes = explode(' ', $table->colclasses[$key]);
                    } else {
                        $classes = '';
                    }
                    $heading->attributes['class'] = trim($heading->attributes['class']);
                    $attributes = array_merge($heading->attributes, array(
                            'style'     => $heading->style,
                            'scope'     => $heading->scope,
                            'colspan'   => $heading->colspan,
                            'rowspan'   => $heading->rowspan
                        ));

                    $tagtype = 'td';
                    if ($heading->header === true) {
                        $tagtype = 'th';
                    }

                    if (!$nohide && ($report->column_is_hidden($key) || $report->column_is_hidden($classes))) {
                        $attributes['class'] .= ' hidden';
                    }
                    $content = html_writer::tag('div', $heading->text,
                                                array('class'=>'content')).
                               $this->get_toggle_links($key, $heading->text, $report);
                    $output .= html_writer::tag($tagtype, $content, $attributes) . "\n";
                    $idx++;
                }
                $output .= html_writer::end_tag('tr') . "\n";
            }
            $output .= html_writer::end_tag('thead') . "\n";

            if (empty($table->data)) {
                // For valid XHTML strict every table must contain either a valid tr
                // or a valid tbody... both of which must contain a valid td
                $output .= html_writer::start_tag('tbody', array('class' => 'empty'));
                $output .= html_writer::tag('tr', html_writer::tag('td', '', array('colspan'=>count($table->head))));
                $output .= html_writer::end_tag('tbody');
            }
        }

        if (!empty($table->data)) {
            $oddeven    = 1;
            $keys       = array_keys($table->data);
            $lastrowkey = end($keys);
            $output .= html_writer::start_tag('tbody', array());

            foreach ($table->data as $key => $row) {
                if (($row === 'hr') && ($countcols)) {
                    $output .= html_writer::tag('td', html_writer::tag('div', '', array('class' => 'tabledivider')), array('colspan' => $countcols));
                } else {
                    $idx = 0;
                    // Convert array rows to html_table_rows and cell strings to html_table_cell objects
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
                    
                    if ($heading->text == null) {
                        $idx++;
                        continue;
                    }

                    $output .= html_writer::start_tag('tr', array('class' => trim($row->attributes['class']), 'style' => $row->style, 'id' => $row->id)) . "\n";
                    $keys2 = array_keys($row->cells);
                    $lastkey = end($keys2);

                    $gotlastkey = false; //flag for sanity checking
                    foreach ($row->cells as $key => $cell) {
                        if ($gotlastkey) {
                            //This should never happen. Why do we have a cell after the last cell?
                            mtrace("A cell with key ($key) was found after the last key ($lastkey)");
                        }

                        if ($cell == null) {
                            $idx++;
                            continue;
                        }
                        
                        if (!($cell instanceof html_table_cell)) {
                            $mycell = new html_table_cell();
                            $mycell->text = $cell;
                            $cell = $mycell;
                        }

                        if (($cell->header === true) && empty($cell->scope)) {
                            $cell->scope = 'row';
                        }

                        if (isset($table->colclasses[$key])) {
                            $cell->attributes['class'] .= ' ' . $table->colclasses[$key];
                        }

                        $cell->attributes['class'] .= ' cell c' . $idx;
                        if ($key == $lastkey) {
                            $cell->attributes['class'] .= ' lastcol';
                            $gotlastkey = true;
                        }
                        $tdstyle = '';
                        $tdstyle .= isset($table->align[$key]) ? $table->align[$key] : '';
                        $tdstyle .= isset($table->size[$key]) ? $table->size[$key] : '';
                        $tdstyle .= isset($table->wrap[$key]) ? $table->wrap[$key] : '';
                        $cell->attributes['class'] = trim($cell->attributes['class']);
                        $tdattributes = array_merge($cell->attributes, array(
                                'style' => $tdstyle . $cell->style,
                                'colspan' => $cell->colspan,
                                'rowspan' => $cell->rowspan,
                                'id' => $cell->id,
                                'abbr' => $cell->abbr,
                                'scope' => $cell->scope,
                            ));
                        $tagtype = 'td';
                        if ($cell->header === true) {
                            $tagtype = 'th';
                        }
                        if (isset($table->colclasses[$key])) {
                            $classes = explode(' ', $table->colclasses[$key]);
                        } else {
                            $clases = '';
                        }
                        if (!$nohide && ($report->column_is_hidden($key) || $report->column_is_hidden($classes))) {
                            $tdattributes['class'] .= ' hidden';
                        }
                        $content = html_writer::tag('div', $cell->text, array('class'=>'content'));
                        $output .= html_writer::tag($tagtype, $content, $tdattributes) . "\n";
                        $idx++;
                    }
                }
                $output .= html_writer::end_tag('tr') . "\n";
            }
            $output .= html_writer::end_tag('tbody') . "\n";
        }
        $output .= html_writer::end_tag('table') . "\n";

        return $output;
    }
    
    protected function get_toggle_links($column = '', $columnstring = '', checkmarkreport $report = null) {
        global $PAGE;
        $html = '';
        if (empty($report)) {
            return '';
        }
        $showicon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/switch_plus'),
                                                        'alt' => get_string('show')));
        $hideicon = html_writer::empty_tag('img', array('src' => $this->output->pix_url('t/switch_minus'),
                                                        'alt' => get_string('hide')));
        if ($report->column_is_hidden($column)) {
            //show link
            $html = html_writer::link(new moodle_url($PAGE->url, array('tshow' => $column)),
                                      $showicon,
                                      array('class' => $column.' showcol',
                                            'title' => get_string('show').
                                                       ' '.$columnstring));
        } else {
            //hide link
            $html = html_writer::link(new moodle_url($PAGE->url, array('thide' => $column)),
                                      $hideicon,
                                      array('class' => $column.' hidecol',
                                            'title' => get_string('hide').
                                                       ' '.$columnstring));
        }
        return $html;
    }

}
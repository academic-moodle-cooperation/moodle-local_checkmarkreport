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

class checkmarkreport {

    protected $courseid = 0;
    
    const FORMAT_XLSX = 0;
    const FORMAT_XLS = 1;
    const FORMAT_ODS = 2;
    const FORMAT_XML = 3;
    const FORMAT_TXT = 4;
    
    protected $data = null;
    protected $users = array(0);
    protected $instances = array(0);

    function __construct($id, $users=array(0), $instances=array(0)) {
        $this->courseid = $id;
        $this->users = $users;
        $this->instances = $instances;
        $this->init_hidden();
    }
    
    public function get_coursedata() {
        global $PAGE, $DB;

        $course = $DB->get_record('course', array('id'=>$this->courseid), '*', MUST_EXIST);

        $context = context_course::instance($course->id);
        
        //get all checkmarkinstances in course
        $checkmarks = get_all_instances_in_course('checkmark', $course);
        if (!in_array(0, $this->instances)) {
            foreach($checkmarks as $key => $inst) {
                if (!in_array($inst->id, $this->instances)) {
                    unset($checkmarks[$key]);
                }
            }
        }
        
        $data = array();
        
        //get all userdata in 1 query
        $context = context_course::instance($course->id);

        // Get general data from users!
        list($esql, $params) = get_enrolled_sql($context, 'mod/checkmark:submit', 0);

        $sql = 'SELECT u.id FROM {user} u '.
               'LEFT JOIN ('.$esql.') eu ON eu.id=u.id '.
               'WHERE u.deleted = 0 AND eu.id=u.id ';
        if (!in_array(0, $this->users)) {
            list($insql, $inparams) = $DB->get_in_or_equal($this->users, SQL_PARAMS_NAMED, 'user');
            $sql .= ' AND u.id '.$insql;
            $params = array_merge($params, $inparams);
        }

        $users = $DB->get_fieldset_sql($sql, $params);

        $data = $this->get_general_data($course, $users, $this->instances);

        // Get examples states for user and instance!
        foreach($checkmarks as $checkmark) {
            foreach($data as $userid => $user) {
                $data[$userid]->instancedata[$checkmark->id]->examples = $this->get_examples_data($checkmark->id, $userid);
            }
        }

        $this->data = $data;

        return $this->data;
    }

    /*
     * 
     *
     */
    public function get_general_data($course = null, $userids=0, $instances = array(0)) {
        global $DB, $COURSE, $CFG;

        $summary_abs = get_user_preferences('checkmark_sumabs', 1);
        $summary_rel = get_user_preferences('checkmark_sumrel', 1);

        $useridentity = explode(',', $CFG->showuseridentity);

        // Construct the SQL!
        $conditions = array();
        $params = array();

        $sort = '';
        $ufields = user_picture::fields('u');

        if ($course == null) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', array('id'=>$course->id), '*', MUST_EXIST);
        }
        $courseid = $course->id;

        if (empty($userids)) {
            $context = context_course::instance($courseid);
            $userids = get_enrolled_users($context, '', 0, 'u.*', 'lastname ASC');
        }
        
        $checkmarks = get_all_instances_in_course('checkmark', $course);
        $checkmarkids = array();
        $cmids = array();
        $noinstancefilter = in_array(0, $instances);
        foreach($checkmarks as $checkmark) {
            if ($noinstancefilter || in_array($checkmark->id, $instances)) {
                $checkmarkids[] = $checkmark->id;
                $cmids[$checkmark->id] = $checkmark->coursemodule;
            }
        }
        
        if (!empty($userids) && !empty($checkmarkids)) {
            list($sqluserids, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'user');
            $params = array_merge_recursive($params, $userparams);
            
            list($sqlcheckmarkids, $checkmarkparams) = $DB->get_in_or_equal($checkmarkids, SQL_PARAMS_NAMED, 'checkmark');
            $params = array_merge_recursive($params, $checkmarkparams);
            list($sqlcheckmarkbids, $checkmarkbparams) = $DB->get_in_or_equal($checkmarkids, SQL_PARAMS_NAMED, 'checkmarkb');
            $params = array_merge_recursive($params, $checkmarkbparams);
            
            $useridentityfields = 'u.'.str_replace(',', ',u.', $CFG->showuseridentity);
            $grades = $DB->get_records_sql_menu('
                            SELECT 0 as id, SUM(gex.grade) as grade
                              FROM {checkmark_examples} as gex
                             WHERE gex.checkmarkid '.$sqlcheckmarkbids.'
                             UNION 
                            SELECT gex.checkmarkid as id, SUM(gex.grade) as grade
                              FROM {checkmark_examples} as gex
                             WHERE gex.checkmarkid '.$sqlcheckmarkids.'
                          GROUP BY gex.checkmarkid', $params);
            $examples = $DB->get_records_sql_menu('
                            SELECT 0 as id, COUNT(DISTINCT gex.id) as examples
                              FROM {checkmark_examples} as gex
                             WHERE gex.checkmarkid '.$sqlcheckmarkbids.'
                             UNION 
                            SELECT gex.checkmarkid as id, COUNT(DISTINCT gex.id) as examples
                              FROM {checkmark_examples} as gex
                             WHERE gex.checkmarkid '.$sqlcheckmarkids.'
                          GROUP BY gex.checkmarkid', $params);
            $params['maxgrade'] = $grades[0];
            $params['maxgradeb'] = $grades[0];
            $params['maxchecks'] = $examples[0];
            $params['maxchecksb'] = $examples[0];
            $sql = 'SELECT '.$ufields.', '.$useridentityfields.',
                           100 * COUNT( DISTINCT cchks.id) / :maxchecks AS percentchecked,
                           COUNT( DISTINCT cchks.id ) as checks, :maxchecksb as maxchecks,
                           100 * SUM( cex.grade ) / :maxgrade as percentgrade,
                           SUM( cex.grade ) as checkgrade, :maxgradeb as maxgrade
                      FROM {user} AS u 
                 LEFT JOIN {checkmark_submissions} AS s ON u.id = s.userid
                                                       AND s.checkmarkid '.$sqlcheckmarkids.'
                 LEFT JOIN {checkmark_checks} AS cchks ON cchks.submissionid = s.id
                                                      AND cchks.state = 1
                 LEFT JOIN {checkmark_examples} as cex ON cchks.exampleid = cex.id
                     WHERE u.id '.$sqluserids.'
                  GROUP BY u.id';

            $data = $DB->get_records_sql($sql, $params);

            //Add per instance data
            $sql = 'SELECT u.id,
                           100 * COUNT( DISTINCT cchks.id) / :maxchecks AS percentchecked,
                           COUNT( DISTINCT cchks.id ) as checks,
                           :maxchecksb as maxchecks,
                           100 * SUM( cex.grade ) / :maxgrade as percentgrade,
                           SUM( cex.grade ) as grade,
                           :maxgradeb as maxgrade
                      FROM {user} AS u
                 LEFT JOIN {checkmark_submissions} AS s ON u.id = s.userid
                                                       AND s.checkmarkid = :chkmkid
                 LEFT JOIN {checkmark_checks} AS cchks ON cchks.submissionid = s.id AND cchks.state = 1
                 LEFT JOIN {checkmark_examples} as cex ON cchks.exampleid = cex.id
                     WHERE u.id '.$sqluserids.'
                  GROUP BY u.id';
            $params = $userparams;
            $instancedata = array();
            foreach ($checkmarkids as $chkmkid) {
                $params['chkmkid'] = $chkmkid;
                $params['chkmkidb'] = $chkmkid;
                $params['maxchecks'] = $examples[$chkmkid];
                $params['maxchecksb'] = $examples[$chkmkid];
                $params['maxgrade'] = $grades[$chkmkid];
                $params['maxgradeb'] = $grades[$chkmkid];
                $instancedata[$chkmkid] = $DB->get_records_sql($sql, $params);
            }

            if (!empty($data)) {
                foreach ($data as $key => $row) {
                    $data[$key]->userdata = array();
                    foreach ($useridentity as $useridfield) {
                        $data[$key]->userdata[$useridfield] = $data[$key]->$useridfield;
                        unset($useridfield);
                    }
                    $data[$key]->instancedata = array();
                    foreach ($checkmarkids as $chkmkid) {
                        $data[$key]->instancedata[$chkmkid] = new stdClass();
                        $grade = empty($instancedata[$chkmkid][$key]->grade) ?
                                 0 : $instancedata[$chkmkid][$key]->grade;
                        $data[$key]->instancedata[$chkmkid]->grade = $grade;
                        $data[$key]->instancedata[$chkmkid]->maxgrade = $instancedata[$chkmkid][$key]->maxgrade;
                        $checks = empty($instancedata[$chkmkid][$key]->checks) ?
                                  0 : $instancedata[$chkmkid][$key]->checks;
                        $data[$key]->instancedata[$chkmkid]->checked = $checks;
                        $data[$key]->instancedata[$chkmkid]->maxchecked = $instancedata[$chkmkid][$key]->maxchecks;
                        $percentchecked = empty($instancedata[$chkmkid][$key]->percentchecked) ?
                                          0 : $instancedata[$chkmkid][$key]->percentchecked;
                        $data[$key]->instancedata[$chkmkid]->percentchecked = $percentchecked;
                        $percentgrade = empty($instancedata[$chkmkid][$key]->percentgrade) ?
                                        0 : $instancedata[$chkmkid][$key]->percentgrade;
                        $data[$key]->instancedata[$chkmkid]->percentgrade = $percentgrade;
                        $data[$key]->instancedata[$chkmkid]->cmid = $cmids[$chkmkid];
                    }
                }
            }

            return $data;
        }

        return null;
    }

    public function get_examples_data($checkmarkid=0, $userid=0) {
        global $DB;

        // Get instances examples
        $sql = 'SELECT ex.id, chks.state
                  FROM {checkmark_examples} as ex
             LEFT JOIN {checkmark_submissions} as sub ON sub.checkmarkid = ex.checkmarkid 
                                                     AND sub.userid = :userid
             LEFT JOIN {checkmark_checks} as chks ON chks.submissionid = sub.id
                                                 AND chks.exampleid = ex.id
                 WHERE ex.checkmarkid = :checkmarkid';
        $params = array('checkmarkid' => $checkmarkid,
                        'userid'      => $userid);

        return $DB->get_records_sql_menu($sql, $params);
    }
    
    public function get_instances() {
        global $DB;
        if (!empty($this->courseid)) {
            $course = $DB->get_record('course', array('id'=>$this->courseid), '*', MUST_EXIST);
            $instances = get_all_instances_in_course('checkmark', $course);
            if(!in_array(0, $this->instances)) {
                foreach ($instances as $key => $inst) {
                    if (!in_array($inst->id, $this->instances)) {
                        unset($instances[$key]);
                    }
                }
            }
            return $instances;
        } else {
            return null;
        }
    }

    public function get_courseid() {
        return $this->courseid;
    }
    
    public function init_hidden() {
        global $SESSION;
        $thide = optional_param('thide', null, PARAM_ALPHANUM);
        $tshow = optional_param('tshow', null, PARAM_ALPHANUM);
        if (!isset($SESSION->checkmarkreport)) {
            $SESSION->checkmarkreport = new stdClass();
            $SESSION->checkmarkreport->{$this->courseid} = new stdClass();
            $SESSION->checkmarkreport->{$this->courseid}->hidden = array();
            return 0;
        }
        if (!isset($SESSION->checkmarkreport->{$this->courseid})) {
            $SESSION->checkmarkreport->{$this->courseid} = new stdClass();
            $SESSION->checkmarkreport->{$this->courseid}->hidden = array();
            return 0;
        }
        if (!isset($SESSION->checkmarkreport->{$this->courseid}->hidden)) {
            $SESSION->checkmarkreport->{$this->courseid}->hidden = array();
            return 0;
        }
        if (!empty($thide) && !in_array($thide, $SESSION->checkmarkreport->{$this->courseid}->hidden)) {
            $SESSION->checkmarkreport->{$this->courseid}->hidden[] = $thide;
        }
        if (!empty($tshow)) {
            foreach($SESSION->checkmarkreport->{$this->courseid}->hidden as $idx => $hidden) {
                if ($hidden == $tshow) {
                    unset($SESSION->checkmarkreport->{$this->courseid}->hidden[$idx]);
                }
            }
        }
    }
    
    public function column_is_hidden($column='nonexistend') {
        global $SESSION;
        if (!isset($SESSION->checkmarkreport)) {
            $SESSION->checkmarkreport = new stdClass();
            $SESSION->checkmarkreport->{$this->courseid} = new stdClass();
            $SESSION->checkmarkreport->{$this->courseid}->hidden = array();
            return 0;
        }
        if (!isset($SESSION->checkmarkreport->{$this->courseid})) {
            $SESSION->checkmarkreport->{$this->courseid} = new stdClass();
            $SESSION->checkmarkreport->{$this->courseid}->hidden = array();
            return 0;
        }
        if (!isset($SESSION->checkmarkreport->{$this->courseid}->hidden)) {
            $SESSION->checkmarkreport->{$this->courseid}->hidden = array();
            return 0;
        }
        
        if ((array)$column !== $column) {
            return in_array($column, $SESSION->checkmarkreport->{$this->courseid}->hidden);
        } else {
            $return = false;
            foreach($column as $cur) {
                $return = $return || in_array($cur, $SESSION->checkmarkreport->{$this->courseid}->hidden);
            }
            return $return;
        }
    }
}
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
 * Prints a list of all checkmarkreport instances in the given course (via id)
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/querylib.php');

class local_checkmarkreport_base {

    protected $courseid = 0;

    const FORMAT_XLSX = 0;
    const FORMAT_XLS = 1; // Unused since 2.8!
    const FORMAT_ODS = 2;
    const FORMAT_XML = 3;
    const FORMAT_TXT = 4;

    protected $data = null;
    protected $groups = array(0);
    protected $users = array(0);
    protected $instances = array(0);

    public function __construct($id, $groups=array(0), $users=array(0), $instances=array(0)) {
        $this->courseid = $id;
        $this->groups = $groups;
        $this->users = $users;
        $this->instances = $instances;
        $this->init_hidden();
        $this->init_sortby();
    }

    public function get_instances() {
        return $this->instances;
    }

    public function get_user() {
        return $this->users;
    }

    public function get_groups() {
        return $this->groups;
    }

    public function get_coursedata() {
        global $PAGE, $DB;

        $course = $DB->get_record('course', array('id' => $this->courseid), '*', MUST_EXIST);

        $context = context_course::instance($course->id);

        // Get all checkmark instances in course!
        $checkmarks = get_all_instances_in_course('checkmark', $course);
        if (!in_array(0, $this->instances)) {
            foreach ($checkmarks as $key => $inst) {
                if (!in_array($inst->id, $this->instances)) {
                    unset($checkmarks[$key]);
                }
            }
        }

        $data = array();

        // Get all userdata in 1 query!
        $context = context_course::instance($course->id);

        // Get general data from users!
        list($esql, $params) = get_enrolled_sql($context, 'mod/checkmark:submit', 0);

        $sql = 'SELECT u.id FROM {user} u '.
               'LEFT JOIN ('.$esql.') eu ON eu.id=u.id '.
               'WHERE u.deleted = 0 AND eu.id=u.id ';
        if (!empty($this->users) && !in_array(0, $this->users)) {
            list($insql, $inparams) = $DB->get_in_or_equal($this->users, SQL_PARAMS_NAMED, 'user');
            $sql .= ' AND u.id '.$insql;
            $params = array_merge($params, $inparams);
        }

        $users = $DB->get_fieldset_sql($sql, $params);

        $data = $this->get_general_data($course, $users, $this->instances);
        if (!empty($data)) {
            // Get examples states for user and instance!
            foreach ($checkmarks as $checkmark) {
                foreach ($data as $userid => $user) {
                    $data[$userid]->instancedata[$checkmark->id]->examples = $this->get_examples_data($checkmark->id, $userid);
                }
            }
        }

        $this->data = $data;

        return $this->data;
    }

    /*
     * TODO document this function
     */
    public function get_general_data($course = null, $userids=0, $instances = array(0)) {
        global $DB, $COURSE, $CFG, $SESSION;

        // Construct the SQL!
        $conditions = array();
        $params = array();

        $sort = '';
        $ufields = user_picture::fields('u');

        if ($course == null) {
            $course = $COURSE;
        } else {
            $course = $DB->get_record('course', array('id' => $course->id), '*', MUST_EXIST);
        }
        $courseid = $course->id;

        $context = context_course::instance($courseid);
        $useridentity = get_extra_user_fields($context);

        if ($userids == 0) {
            $userids = get_enrolled_users($context, '', 0, 'u.*', 'lastname ASC');
        }

        $checkmarks = get_all_instances_in_course('checkmark', $course);
        $checkmarkids = array();
        $cmids = array();
        $noinstancefilter = in_array(0, $instances);
        foreach ($checkmarks as $checkmark) {
            if ($noinstancefilter || in_array($checkmark->id, $instances)) {
                $checkmarkids[] = $checkmark->id;
                $cmids[$checkmark->id] = $checkmark->coursemodule;
            }
        }

        if (!empty($userids) && !empty($checkmarkids)) {
            // Get gradebook grades!
            // Get course grade!
            $gbgrades = grade_get_course_grades($courseid, $userids);

            list($sqluserids, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'user');
            $params = array_merge_recursive($params, $userparams);

            list($sqlcheckmarkids, $checkmarkparams) = $DB->get_in_or_equal($checkmarkids, SQL_PARAMS_NAMED, 'checkmark');
            $params = array_merge_recursive($params, $checkmarkparams);
            list($sqlcheckmarkbids, $checkmarkbparams) = $DB->get_in_or_equal($checkmarkids, SQL_PARAMS_NAMED, 'checkmarkb');
            $params = array_merge_recursive($params, $checkmarkbparams);

            $useridentityfields = get_extra_user_fields_sql($context, 'u');
            $grades = $DB->get_records_sql_menu('
                            SELECT 0 id, SUM(gex.grade) grade
                              FROM {checkmark_examples} gex
                             WHERE gex.checkmarkid '.$sqlcheckmarkbids.'
                             UNION
                            SELECT gex.checkmarkid id, SUM(gex.grade) grade
                              FROM {checkmark_examples} gex
                             WHERE gex.checkmarkid '.$sqlcheckmarkids.'
                          GROUP BY gex.checkmarkid', $params);
            $examples = $DB->get_records_sql_menu('
                            SELECT 0 id, COUNT(DISTINCT gex.id) examples
                              FROM {checkmark_examples} gex
                             WHERE gex.checkmarkid '.$sqlcheckmarkbids.'
                             UNION
                            SELECT gex.checkmarkid id, COUNT(DISTINCT gex.id) examples
                              FROM {checkmark_examples} gex
                             WHERE gex.checkmarkid '.$sqlcheckmarkids.'
                          GROUP BY gex.checkmarkid', $params);
            $params['maxgrade'] = $grades[0];
            $params['maxgradeb'] = $grades[0];
            $params['maxchecks'] = $examples[0];
            $params['maxchecksb'] = $examples[0];
            $sortable = array('firstname', 'lastname',
                              'percentchecked', 'checks',
                              'percentgrade', 'checkgrade');
            $sortable = array_merge($sortable, get_extra_user_fields($context));

            $sortarr = $SESSION->checkmarkreport->{$courseid}->sort;
            $sort = '';
            foreach ($sortarr as $field => $direction) {
                if (in_array($field, $sortable)) {
                    if (!empty($sort)) {
                        $sort .= ', ';
                    }
                    $sort .= $field.' '.$direction;
                }
            }
            if (!empty($sort)) {
                $sort = ' ORDER BY '.$sort;
            }
            $sql = 'SELECT '.$ufields.' '.$useridentityfields.',
                           100 * COUNT( DISTINCT cchks.id) / :maxchecks percentchecked,
                           COUNT( DISTINCT cchks.id ) checks,
                           100 * SUM( cex.grade ) / :maxgrade percentgrade,
                           SUM( cex.grade ) checkgrade
                      FROM {user} u
                 LEFT JOIN {checkmark_submissions} s ON u.id = s.userid
                                                       AND s.checkmarkid '.$sqlcheckmarkids.'
                 LEFT JOIN {checkmark_checks} cchks ON cchks.submissionid = s.id
                                                      AND cchks.state = 1
                 LEFT JOIN {checkmark_examples} cex ON cchks.exampleid = cex.id
                     WHERE u.id '.$sqluserids.'
                  GROUP BY u.id'.
                  $sort;

            $data = $DB->get_records_sql($sql, $params);
            foreach ($data as $key => $cur) {
                $data[$key]->maxgrade = $grades[0];
                $data[$key]->maxchecks = $examples[0];
                $data[$key]->coursegrade = $gbgrades->grades[$key];
                $data[$key]->coursesum = 0; // Sum it up during per-instance-data!
                $data[$key]->overridden = false;
            }

            // Add per instance data!
            $sql = 'SELECT u.id,
                           100 * COUNT( DISTINCT cchks.id) / :maxchecks percentchecked,
                           COUNT( DISTINCT cchks.id ) checks,
                           100 * SUM( cex.grade ) / :maxgrade percentgrade,
                           SUM( cex.grade ) grade
                      FROM {user} u
                 LEFT JOIN {checkmark_submissions} s ON u.id = s.userid
                                                       AND s.checkmarkid = :chkmkid
                 LEFT JOIN {checkmark_checks} cchks ON cchks.submissionid = s.id AND cchks.state = 1
                 LEFT JOIN {checkmark_examples} cex ON cchks.exampleid = cex.id
                     WHERE u.id '.$sqluserids.'
                  GROUP BY u.id';
            $params = $userparams;
            $instancedata = array();
            $reorder = false;
            reset($sortarr);
            $primesort = key($sortarr);
            $gradinginfo = array();
            foreach ($checkmarkids as $chkmkid) {
                // Get instance gradebook data!
                $gradinginfo[$chkmkid] = grade_get_grades($courseid, 'mod', 'checkmark',
                                                          $chkmkid, $userids);
                $grademax[$chkmkid] = $gradinginfo[$chkmkid]->items[0]->grademax;

                $params['chkmkid'] = $chkmkid;
                $params['chkmkidb'] = $chkmkid;
                if (!isset($examples[$chkmkid])) {
                    $examples[$chkmkid] = 0;
                }
                if (!isset($grades[$chkmkid])) {
                    $grades[$chkmkid] = 0;
                }
                $params['maxchecks'] = $examples[$chkmkid];
                $params['maxgrade'] = $grades[$chkmkid];
                $sort = '';
                if ($primesort == 'checks'.$chkmkid) {
                    $sort = ' ORDER BY checks '.current($sortarr);
                    $reorder = $chkmkid;
                }
                if ($primesort == 'percentchecked'.$chkmkid) {
                    $sort = ' ORDER BY percentchecked '.current($sortarr);
                    $reorder = $chkmkid;
                }
                if ($primesort == 'grade'.$chkmkid) {
                    $sort = ' ORDER BY grade '.current($sortarr);
                    $reorder = $chkmkid;
                }
                if ($primesort == 'percentgrade'.$chkmkid) {
                    $sort = ' ORDER BY percentgrade '.current($sortarr);
                    $reorder = $chkmkid;
                }
                $sql .= $sort;
                $instancedata[$chkmkid] = $DB->get_records_sql($sql, $params);

                foreach ($instancedata[$chkmkid] as $key => $cur) {
                    $instancedata[$chkmkid][$key]->maxchecks = $examples[$chkmkid];
                    $instancedata[$chkmkid][$key]->maxgrade = $grades[$chkmkid];
                }
            }

            if (!empty($data)) {
                if ($reorder !== false) {
                    $userids = array_keys($instancedata[$reorder]);
                    $returndata = array();
                } else {
                    $userids = array_keys($data);
                    $returndata = $data;
                }
                if (key_exists('checkmark', $sortarr)) {
                    $params = array_merge(array('courseid' => $courseid),
                                          $checkmarkparams);
                    $checkmarkids = $DB->get_fieldset_sql('
SELECT id
  FROM {checkmark}
 WHERE {checkmark}.course = :courseid
       AND {checkmark}.id '.$sqlcheckmarkids.'
ORDER BY {checkmark}.name '.$sortarr['checkmark'], $params);
                }
                foreach ($userids as $key) {
                    if ($reorder !== false) {
                        $returndata[$key] = $data[$key];
                    }
                    $returndata[$key]->userdata = array();
                    foreach ($useridentity as $useridfield) {
                        $returndata[$key]->userdata[$useridfield] = $data[$key]->$useridfield;
                        unset($useridfield);
                    }
                    $data[$key]->instancedata = array();
                    foreach ($checkmarkids as $chkmkid) {
                        $returndata[$key]->instancedata[$chkmkid] = new stdClass();
                        $grade = empty($instancedata[$chkmkid][$key]->grade) ?
                                 0 : $instancedata[$chkmkid][$key]->grade;
                        $returndata[$key]->instancedata[$chkmkid]->grade = $grade;
                        $returndata[$key]->instancedata[$chkmkid]->maxgrade = $instancedata[$chkmkid][$key]->maxgrade;
                        $checks = empty($instancedata[$chkmkid][$key]->checks) ?
                                  0 : $instancedata[$chkmkid][$key]->checks;
                        $returndata[$key]->instancedata[$chkmkid]->checked = $checks;
                        $returndata[$key]->instancedata[$chkmkid]->maxchecked = $instancedata[$chkmkid][$key]->maxchecks;
                        $percentchecked = empty($instancedata[$chkmkid][$key]->percentchecked) ?
                                          0 : $instancedata[$chkmkid][$key]->percentchecked;
                        $returndata[$key]->instancedata[$chkmkid]->percentchecked = $percentchecked;
                        $percentgrade = empty($instancedata[$chkmkid][$key]->percentgrade) ?
                                        0 : $instancedata[$chkmkid][$key]->percentgrade;
                        $returndata[$key]->instancedata[$chkmkid]->percentgrade = $percentgrade;
                        $returndata[$key]->instancedata[$chkmkid]->cmid = $cmids[$chkmkid];

                        // Add gradebook data!
                        $finalgrade = $gradinginfo[$chkmkid]->items[0]->grades[$key];
                        $returndata[$key]->instancedata[$chkmkid]->finalgrade = $gradinginfo[$chkmkid]->items[0]->grades[$key];
                        $returndata[$key]->instancedata[$chkmkid]->formatted_grade = round($finalgrade->grade, 2).
                                                                                     ' / '.round($grademax, 2);

                        if (($finalgrade->locked || $finalgrade->overridden || ($finalgrade->grade != $grade))
                             && !is_null($finalgrade->grade)) {
                            $returndata[$key]->coursesum += $finalgrade->grade;
                            $returndata[$key]->overridden = true;
                        } else {
                            $returndata[$key]->coursesum += $grade;
                        }
                    }
                }
            }

            return $returndata;
        }

        return null;
    }

    public function get_examples_data($checkmarkid=0, $userid=0) {
        global $DB;

        // Get instances examples!
        $sql = 'SELECT ex.id, chks.state
                  FROM {checkmark_examples} ex
             LEFT JOIN {checkmark_submissions} sub ON sub.checkmarkid = ex.checkmarkid
                                                     AND sub.userid = :userid
             LEFT JOIN {checkmark_checks} chks ON chks.submissionid = sub.id
                                                 AND chks.exampleid = ex.id
                 WHERE ex.checkmarkid = :checkmarkid';
        $params = array('checkmarkid' => $checkmarkid,
                        'userid'      => $userid);

        return $DB->get_records_sql_menu($sql, $params);
    }

    public function get_courseinstances() {
        global $DB;
        if (!empty($this->courseid)) {
            $course = $DB->get_record('course', array('id' => $this->courseid), '*', MUST_EXIST);
            $instances = get_all_instances_in_course('checkmark', $course);
            $newinstances = array();
            if (!in_array(0, $this->instances)) {
                foreach ($instances as $key => $inst) {
                    if (in_array($inst->id, $this->instances)) {
                        $newinstances[$inst->id] = $inst;
                    }
                }
            } else {
                foreach ($instances as $key => $inst) {
                    $newinstances[$inst->id] = $inst;
                }
            }
            return $newinstances;
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
        }
        if (!isset($SESSION->checkmarkreport->{$this->courseid})) {
            $SESSION->checkmarkreport->{$this->courseid} = new stdClass();
        }
        if (!isset($SESSION->checkmarkreport->{$this->courseid}->hidden)) {
            $SESSION->checkmarkreport->{$this->courseid}->hidden = array();
        }
        if (!empty($thide) && !in_array($thide, $SESSION->checkmarkreport->{$this->courseid}->hidden)) {
            $SESSION->checkmarkreport->{$this->courseid}->hidden[] = $thide;
        }
        if (!empty($tshow)) {
            foreach ($SESSION->checkmarkreport->{$this->courseid}->hidden as $idx => $hidden) {
                if ($hidden == $tshow) {
                    unset($SESSION->checkmarkreport->{$this->courseid}->hidden[$idx]);
                }
            }
        }
    }

    public function init_sortby() {
        global $SESSION;

        $tsort = optional_param('tsort', null, PARAM_ALPHANUM);

        if (!isset($SESSION->checkmarkreport)) {
            $SESSION->checkmarkreport = new stdClass();
        }
        if (!isset($SESSION->checkmarkreport->{$this->courseid})) {
            $SESSION->checkmarkreport->{$this->courseid} = new stdClass();
        }
        if (!isset($SESSION->checkmarkreport->{$this->courseid}->sort)) {
            $SESSION->checkmarkreport->{$this->courseid}->sort = array();
        }

        if (!empty($tsort)) {
            $arr = $SESSION->checkmarkreport->{$this->courseid}->sort;
            if (!key_exists($tsort, $SESSION->checkmarkreport->{$this->courseid}->sort)) {
                // Like array_unshift with associative key preservation!
                $arr = array_reverse($arr, true);
                $arr[$tsort] = 'ASC';
                $SESSION->checkmarkreport->{$this->courseid}->sort = array_reverse($arr, true);
            } else {
                switch($tsort) {
                    case 'checkmark':
                        if ($arr[$tsort] == 'ASC') {
                                $arr[$tsort] = 'DESC';
                        } else {
                            unset($arr[$tsort]);
                        }
                        break;
                    default:
                        reset($arr);
                        // Bring to front!
                        if (key($arr) != $tsort) {
                            $tmp = $arr[$tsort];
                            unset($arr[$tsort]);
                            $arr = array_reverse($arr, true);
                            $arr[$tsort] = $tmp;
                            $arr = array_reverse($arr, true);
                        }
                        // Reverse sort order!
                        $arr[$tsort] = $arr[$tsort] == 'ASC' ? 'DESC' : 'ASC';
                        break;
                }
                $SESSION->checkmarkreport->{$this->courseid}->sort = $arr;
            }
        }
    }

    public function get_sortlink($column, $text, $url) {
        global $SESSION, $OUTPUT;
        // Sortarray has to be initialized!
        $sortarr = $SESSION->checkmarkreport->{$this->courseid}->sort;
        reset($sortarr);
        $primesort = key($sortarr);
        if (($primesort == 'checkmark') && ($column != 'checkmark')) {
            next($sortarr);
            $primesort = key($sortarr);
        }
        if (($column == $primesort)
            || (($column == 'checkmark') && key_exists($column, $sortarr))) {
            // We show only the first sortby column and checkmark!
            switch ($sortarr[$column]) {
                case 'ASC':
                    $picattr = array('src' => $OUTPUT->pix_url('t/up'),
                                     'alt' => get_string('desc'));
                    break;
                case 'DESC':
                    $picattr = array('src' => $OUTPUT->pix_url('t/down'),
                                     'alt' => get_string('asc'));
                    break;
            }
            $text .= html_writer::empty_tag('img', $picattr);
        }
        $sorturl = new moodle_url($url, array('tsort' => $column));
        $sortlink = html_writer::link($sorturl, $text);
        return $sortlink;
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
            foreach ($column as $cur) {
                $return = $return || in_array($cur, $SESSION->checkmarkreport->{$this->courseid}->hidden);
            }
            return $return;
        }
    }
}
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
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/grade/querylib.php');

/**
 * Base class for checkmarkreports with common logic and definitions
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_checkmarkreport_base {

    /** @var protected the courses id */
    protected $courseid = 0;

    /** xml based excel format */
    const FORMAT_XLSX = 0;
    /** binary excel format - unused since 2.8! */
    const FORMAT_XLS = 1;
    /** open document format */
    const FORMAT_ODS = 2;
    /** xml format */
    const FORMAT_XML = 3;
    /** plain text file format */
    const FORMAT_TXT = 4;

    /** @var object[] report's data */
    protected $data = null;
    /** @var int[] groups */
    protected $groups = array(0);
    /** @var int[] user ids */
    protected $users = array(0);
    /** @var int[] instance ids */
    protected $instances = array(0);

    /** @var int tracksattendances amount of attendance tracking instances */
    protected $trackattendances = null;
    /** @var bool attendancestracked tells if at least 1 instance tracks attendance */
    protected $attendancestracked = null;
    /** @var bool[] tracksattendance boolean array stating which instances track attendance */
    protected $tracksattendance = null;

    /** @var bool presentationsgraded tells if at least 1 instance grades presentations */
    protected $presentationsgraded = null;
    /** @var bool prescommented tells if at least 1 instance comments presentations */
    protected $prescommented = null;
    /** @var object[] gradingpresentations array stating which instances grade presentation */
    protected $gradespresentations = null;
    /** @var int[] presentationpoints array with maximum points for presentation grade */
    protected $presentationpoints = null;

    /**
     * Base constructor
     *
     * @param int $id course id
     * @param int[] $groups (optional) array of groups to include
     * @param int[] $users (optional) array of users to include
     * @param int[] $instances (optional) array of checkmark instances to include
     */
    public function __construct($id, $groups=array(0), $users=array(0), $instances=array(0)) {
        $this->courseid = $id;
        $this->groups = $groups;
        $this->users = $users;
        $this->instances = $instances;
        $this->init_hidden();
        $this->init_sortby();
    }

    /**
     * returns instances to include
     *
     * @return int[]
     */
    public function get_instances() {
        return $this->instances;
    }

    /**
     * returns users to include
     *
     * @return int[]
     */
    public function get_user() {
        return $this->users;
    }

    /**
     * returns groups to include
     *
     * @return int[]
     */
    public function get_groups() {
        return $this->groups;
    }

    /**
     * returns if at least 1 instance in course uses attendance tracking
     *
     * @return bool True if at least 1 instance in course uses attendance tracking
     */
    public static function attendancestrackedincourse($courseid) {
        global $DB;

        return $DB->record_exists("checkmark", array('trackattendance' => 1, 'course' => $courseid)) ? true : false;
    }

    /**
     * returns if at least 1 instance uses attendance tracking
     *
     * @return bool True if at least 1 instance in course/from selected instances uses attendance tracking
     */
    public function attendancestracked() {
        global $DB;

        if ($this->attendancestracked === null) {
            if (!in_array(0, $this->instances)) {
                list($select, $params) = $DB->get_in_or_equal($this->instances);
                $params = array_merge(array($this->courseid), $params);
                $select = "trackattendance = 1 AND course = ? AND id ".$select;
            } else {
                $select = "trackattendance = 1 AND course = ? ";
                $params = array($this->courseid);
            }

            $this->attendancestracked = $DB->record_exists_select("checkmark", $select, $params) ? true : false;
        }

        return $this->attendancestracked;
    }

    /**
     * returns how many instances track attendance
     *
     * @return int amount of instances tracking attendance (with filters applied)
     */
    public function trackingattendances() {
        global $DB, $COURSE;

        if ($this->attendancestracked() === false) {
            $this->trackingattendances = 0;
        } else {
            if (!in_array(0, $this->instances)) {
                list($select, $params) = $DB->get_in_or_equal($this->instances);
                $select = "trackattendance = 1 AND course = ? AND id ".$select;
                $params = array_merge(array($COURSE->id), $params);
            } else {
                $select = "trackattendance = 1 AND course = ?";
                $params = array($COURSE->id);
            }

            $this->trackingattendances = $DB->count_records_select("checkmark", $select, $params);
        }

        return $this->trackingattendances;
    }

    /**
     * returns which instances track attendance
     *
     * @return bool[]|bool array of bool values for each instance or bool value if filtered for 1 instance
     */
    public function tracksattendance($chkmkid = 0) {
        global $DB, $COURSE;

        if ($this->tracksattendance === null) {
            $select = "course = ?";
            $params = array($COURSE->id);
            $this->tracksattendance = $DB->get_records_select_menu("checkmark", $select, $params, '', "id, trackattendance");
        }

        if (!empty($chkmkid) && !array_key_exists($chkmkid, $this->tracksattendance)) {
            return false;
        }

        if (empty($chkmkid)) {
            return $this->tracksattendance;
        } else {
            return $this->tracksattendance[$chkmkid];
        }
    }

    /**
     * returns if at least 1 instance in course grades presentations
     *
     * @return bool True if at least 1 instance in course grades presentations
     */
    public static function presentationsgradedincourse($courseid) {
        global $DB;

        $select = "presentationgrading = 1 AND presentationgrade <> 0 AND course = ?";
        return $DB->record_exists_select("checkmark", $select, array($courseid)) ? true : false;
    }

    /**
     * returns if at least 1 instance in course comments presentations
     *
     * @return bool True if at least 1 instance in course comments presentations
     */
    public static function presentationscommentedincourse($courseid) {
        global $DB;

        return $DB->record_exists("checkmark", array('presentationgrading' => 1, 'course' => $courseid)) ? true : false;
    }

    /**
     * returns if at least 1 instance grades presentations
     *
     * @return bool True if at least 1 instance in course/from selected instances grades presentations
     */
    public function presentationsgraded() {
        global $DB;

        if ($this->prescommented === false) {
            // Shortcut: to have presentations graded, also presentation commenting must be possible!
            $this->presentationsgraded = false;
        }

        if ($this->presentationsgraded === null) {
            if (!in_array(0, $this->instances)) {
                list($select, $params) = $DB->get_in_or_equal($this->instances);
                $params = array_merge(array($this->courseid), $params);
                $select = "presentationgrading = 1 AND presentationgrade <> 0 AND course = ? AND id ".$select;
            } else {
                $select = "presentationgrading = 1 AND presentationgrade <> 0 AND course = ? ";
                $params = array($this->courseid);
            }

            $this->presentationsgraded = $DB->record_exists_select("checkmark", $select, $params) ? true : false;
        }

        return $this->presentationsgraded;
    }

    /**
     * returns if at least 1 instance comments presentations
     *
     * @return bool True if at least 1 instance in course/from selected instances comments presentations
     */
    public function presentationscommented() {
        global $DB;

        if ($this->prescommented === null) {
            if (!in_array(0, $this->instances)) {
                list($select, $params) = $DB->get_in_or_equal($this->instances);
                $params = array_merge(array($this->courseid), $params);
                $select = "presentationgrading = 1 AND course = ? AND id ".$select;
            } else {
                $select = "presentationgrading = 1 AND course = ? ";
                $params = array($this->courseid);
            }

            $this->prescommented = $DB->record_exists_select("checkmark", $select, $params) ? true : false;
        }

        return $this->prescommented;
    }

    /**
     * returns which instances use presentationgrading
     *
     * @param int $chkmkid (optional) return data only for 1 checkmark instance (or all in course if omitted)
     * @return object[]|object|bool (array of) object(s) with information about presentationgrading in instances
     *                                  or false if selected instance won't
     */
    public function gradepresentations($chkmkid = 0) {
        global $DB, $COURSE;

        if ($this->gradespresentations === null) {
            $select = "course = ?";
            $params = array($COURSE->id);
            $fields = "id, presentationgrading, presentationgrade, presentationgradebook";
            $this->gradespresentations = $DB->get_records_select("checkmark", $select, $params, '', $fields);
            foreach ($this->gradespresentations as $id => $cur) {
                if ($cur->presentationgrading == 0) {
                    $this->gradespresentations[$id] = false;
                }
            }
        }

        if (!empty($chkmkid) && !array_key_exists($chkmkid, $this->gradespresentations)) {
            return false;
        }

        if (empty($chkmkid)) {
            return $this->gradespresentations;
        } else {
            return $this->gradespresentations[$chkmkid];
        }
    }

    /**
     * returns which instances use points as grade for presentations
     *
     * @param int $chkmkid (optional) return data only for 1 checkmark instance (or all in course if omitted)
     * @return int[]|int (array of) integer(s) with maximum grade for presentation (in instances)
     */
    public function pointsforpresentations($chkmkid = 0) {
        global $DB, $COURSE;

        if ($this->presentationpoints === null) {
            $select = "presentationgrading = 1 AND presentationgrade > 0 AND course = ?";
            $params = array($COURSE->id);
            $fields = "id, presentationgrade";
            $this->presentationpoints = $DB->get_records_select_menu("checkmark", $select, $params, '', $fields);
        }

        if (!empty($chkmkid) && !array_key_exists($chkmkid, $this->presentationpoints)) {
            return 0;
        }

        if (empty($chkmkid)) {
            return $this->presentationpoints;
        } else {
            return $this->presentationpoints[$chkmkid];
        }
    }

    /**
     * Get's the course data from the DB, saves it and returns it
     *
     * @return object[]
     */
    public function get_coursedata() {
        global $DB;

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
                $userkeys = array_keys($data);
                foreach ($userkeys as $userid) {
                    $data[$userid]->instancedata[$checkmark->id]->examples = $this->get_examples_data($checkmark->id, $userid);
                }
            }
        }

        $this->data = $data;

        return $this->data;
    }

    /**
     * Get's the general data from the DB, saves it and returns it
     *
     * @param object $course (optional) course object
     * @param int[] $userids (optional) array of user ids to include
     * @param int[] $instances (optional) array of checkmark ids to include
     * @return object[]|null
     */
    public function get_general_data($course = null, $userids=0, $instances = array(0)) {
        global $DB, $COURSE, $SESSION;

        // Construct the SQL!
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
            // TODO: this can be done in a single SQL query!
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
                           100 * COUNT( DISTINCT cchks.id) / :maxchecks AS percentchecked,
                           COUNT( DISTINCT cchks.id ) AS checks,
                           100 * SUM( cex.grade ) / :maxgrade AS percentgrade,
                           SUM( cex.grade ) AS checkgrade
                      FROM {user} u
                 LEFT JOIN {checkmark_submissions} s ON u.id = s.userid AND s.checkmarkid '.$sqlcheckmarkids.'
                 LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid '.$sqlcheckmarkbids.'
                 LEFT JOIN {checkmark_checks} cchks ON cchks.submissionid = s.id
                                                      AND cchks.state = 1
                 LEFT JOIN {checkmark_examples} cex ON cchks.exampleid = cex.id
                     WHERE u.id '.$sqluserids.'
                  GROUP BY u.id'.
                  $sort;

            $attendances = "SELECT u.id, SUM( f.attendance ) AS attendances
                              FROM {user} u
                         LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid ".$sqlcheckmarkids."
                             WHERE u.id ".$sqluserids."
                          GROUP BY u.id";
            if (key_exists('attendances', $sortarr)) {
                $attendances .= " ORDER BY attendances ".$sortarr['attendances'];
            }
            $attendances = $DB->get_records_sql_menu($attendances, array_merge($checkmarkparams, $userparams));

            $presentationpoints = $this->pointsforpresentations();
            if (!empty($presentationpoints)) {
                list($prespointssql, $prespointsparams) = $DB->get_in_or_equal(array_keys($presentationpoints), SQL_PARAMS_NAMED,
                                                                               'presids');
                if (in_array(0, $this->instances)) {
                    $presentationgrademax = array_sum($presentationpoints);
                } else {
                    $presentationgrademax = 0;
                    $tmp = array_intersect($this->instances, array_keys($presentationpoints));
                    foreach ($tmp as $chkmkid) {
                        $presentationgrademax += $presentationpoints[$chkmkid];
                    }
                }
                $prespercsql = "100 * SUM( f.presentationgrade ) / :presentationgrademax";
            } else {
                $prespointssql = ' = :presids';
                $prespointsparams = array('presids' => -1);
                $presentationgrademax = 0;
                $prespercsql = "0";
            }

            $presentationgrades = "SELECT u.id, SUM( f.presentationgrade ) AS presentationgrade,
                                          ".$prespercsql." AS presentationpercent
                                     FROM {user} u
                                LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid ".$sqlcheckmarkids."
                                    WHERE u.id ".$sqluserids." AND f.checkmarkid ".$prespointssql."
                                 GROUP BY u.id";
            if (key_exists('presentationgrade', $sortarr)) {
                $presentationgrades .= " ORDER BY presentationgrade ".$sortarr['presentationgrade'];
            }

            $presentationgrades = $DB->get_records_sql($presentationgrades,
                                                       array_merge(array('presentationgrademax' => $presentationgrademax),
                                                                   $checkmarkparams,
                                                                   $userparams,
                                                                   $prespointsparams));

            $data = $DB->get_records_sql($sql, $params);
            foreach ($data as $key => $cur) {
                $data[$key]->maxgrade = $grades[0];
                $data[$key]->maxchecks = $examples[0];
                $data[$key]->coursegrade = $gbgrades->grades[$key];
                $data[$key]->coursesum = 0; // Sum it up during per-instance-data!
                $data[$key]->maxattendances = $this->trackingattendances();
                $data[$key]->attendances = $attendances[$key];
                if ($data[$key]->attendances == null) {
                    $data[$key]->attendances = 0;
                }
                $data[$key]->overridden = false;
                if (!empty($presentationpoints)) {
                    $data[$key]->presentationgrademax = $presentationgrademax;
                    if (!key_exists($key, $presentationgrades)) {
                        $data[$key]->presentationgrade = null;
                        $data[$key]->presentationpercent = 0;
                    } else {
                        $data[$key]->presentationgrade = $presentationgrades[$key]->presentationgrade;
                        $data[$key]->presentationpercent = $presentationgrades[$key]->presentationpercent;
                    }
                }
                $data[$key]->coursepressum = 0; // Sum it up during per-instance-data!
                $data[$key]->presoverridden = false;
            }

            // Add per instance data!
            $sql = 'SELECT u.id,
                           100 * COUNT( DISTINCT cchks.id) / :maxchecks AS percentchecked,
                           COUNT( DISTINCT cchks.id ) AS checks,
                           100 * SUM( cex.grade ) / :maxgrade AS percentgrade,
                           SUM( cex.grade ) AS grade,
                           f.attendance AS attendance,
                           f.presentationgrade AS presentationgrade
                      FROM {user} u
                 LEFT JOIN {checkmark_submissions} s ON u.id = s.userid AND s.checkmarkid = :chkmkid
                 LEFT JOIN {checkmark_feedbacks} f ON u.id = f.userid AND f.checkmarkid = :chkmkidb
                 LEFT JOIN {checkmark_checks} cchks ON cchks.submissionid = s.id AND cchks.state = 1
                 LEFT JOIN {checkmark_examples} cex ON cchks.exampleid = cex.id
                     WHERE u.id '.$sqluserids.'
                  GROUP BY u.id';
            $params = $userparams;
            $instancedata = array();
            $reorder = false;
            reset($sortarr);
            $primesort = key($sortarr);
            if ($primesort === "attendances") {
                $reorder = "attendances";
            } else if ($primesort === "presentationgrade") {
                $reorder = "presentationgrade";
            }

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
                if ($primesort == 'attendance'.$chkmkid) {
                    $sort = ' ORDER BY attendance '.current($sortarr);
                    $reorder = $chkmkid;
                }
                if ($this->gradepresentations($chkmkid) && ($primesort == 'presentationgrade'.$chkmkid)) {
                    $sort = ' ORDER BY presentationgrade '.current($sortarr);
                    $reorder = $chkmkid;
                }
                $sql .= $sort;
                $instancedata[$chkmkid] = $DB->get_records_sql($sql, $params);

                foreach ($instancedata[$chkmkid] as $key => $cur) {
                    $instancedata[$chkmkid][$key]->maxchecks = $examples[$chkmkid];
                    $instancedata[$chkmkid][$key]->maxgrade = $grades[$chkmkid];
                    if (key_exists($chkmkid, $presentationpoints)) {
                        $maxpres = $presentationpoints[$chkmkid];
                        $presperc = 100 * $cur->presentationgrade / $presentationpoints[$chkmkid];
                    } else {
                        $maxpres = 0;
                        $presperc = 0;
                    }
                    $instancedata[$chkmkid][$key]->maxpresentation = $maxpres;
                    $instancedata[$chkmkid][$key]->presentationpercent = $presperc;
                }
            }

            if (!empty($data)) {
                if ($reorder === "attendances") {
                    $userids = array_keys($attendances);
                    $returndata = array();
                } else if ($reorder === "presentationgrade") {
                    $userids = array_keys($presentationgrades);
                    $returndata = array();
                } else if ($reorder !== false) {
                    $userids = array_keys($instancedata[$reorder]);
                    $returndata = array();
                } else {
                    $userids = array_keys($data);
                    $returndata = $data;
                }
                if (key_exists('checkmark', $sortarr)) {
                    $params = array_merge(array('courseid' => $courseid), $checkmarkparams);
                    $checkmarkids = $DB->get_fieldset_sql('SELECT id
                                                             FROM {checkmark}
                                                            WHERE {checkmark}.course = :courseid
                                                                  AND {checkmark}.id '.$sqlcheckmarkids.'
                                                         ORDER BY {checkmark}.name '.$sortarr['checkmark'], $params);
                }
                foreach ($userids as $key) {
                    if ($reorder !== false) {
                        /* If we have to sort again, there's no data in $returndata by now!
                           If we don't sort again, $returndata has been filled with $data already! */
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
                        $grade = empty($instancedata[$chkmkid][$key]->grade) ? 0 : $instancedata[$chkmkid][$key]->grade;
                        $returndata[$key]->instancedata[$chkmkid]->grade = $grade;
                        $returndata[$key]->instancedata[$chkmkid]->maxgrade = $instancedata[$chkmkid][$key]->maxgrade;
                        $checks = empty($instancedata[$chkmkid][$key]->checks) ? 0 : $instancedata[$chkmkid][$key]->checks;
                        $returndata[$key]->instancedata[$chkmkid]->checked = $checks;
                        $returndata[$key]->instancedata[$chkmkid]->maxchecked = $instancedata[$chkmkid][$key]->maxchecks;
                        if (empty($instancedata[$chkmkid][$key]->percentchecked)) {
                            $percentchecked = 0;
                        } else {
                            $percentchecked = $instancedata[$chkmkid][$key]->percentchecked;
                        }
                        $returndata[$key]->instancedata[$chkmkid]->percentchecked = $percentchecked;
                        if (empty($instancedata[$chkmkid][$key]->percentgrade)) {
                            $percentgrade = 0;
                        } else {
                            $percentgrade = $instancedata[$chkmkid][$key]->percentgrade;
                        }
                        $returndata[$key]->instancedata[$chkmkid]->percentgrade = $percentgrade;
                        $returndata[$key]->instancedata[$chkmkid]->cmid = $cmids[$chkmkid];

                        $returndata[$key]->instancedata[$chkmkid]->attendance = $instancedata[$chkmkid][$key]->attendance;

                        // Add gradebook data!
                        $finalgrade = $gradinginfo[$chkmkid]->items[CHECKMARK_GRADE_ITEM]->grades[$key];
                        $returndata[$key]->instancedata[$chkmkid]->finalgrade = $finalgrade;
                        $returndata[$key]->instancedata[$chkmkid]->formatted_grade = round($finalgrade->grade, 2).
                                                                                     ' / '.round($grademax, 2);

                        if (($finalgrade->locked || $finalgrade->overridden || ($finalgrade->grade != $grade))
                             && !is_null($finalgrade->grade)) {
                            $returndata[$key]->coursesum += $finalgrade->grade;
                            $returndata[$key]->overridden = true;
                        } else {
                            $returndata[$key]->coursesum += $grade;
                        }

                        // Add presentation data!
                        $gradepresentation = $this->gradepresentations($chkmkid);
                        if ($gradepresentation) {
                            if (empty($instancedata[$chkmkid][$key]->presentationgrade)) {
                                $presgrade = 0;
                            } else {
                                $presgrade = $instancedata[$chkmkid][$key]->presentationgrade;
                            }
                            $returndata[$key]->instancedata[$chkmkid]->presentationgrade = $presgrade;
                            if (empty($instancedata[$chkmkid][$key]->maxpresentation)) {
                                $maxpresentation = 0;
                            } else {
                                $maxpresentation = $instancedata[$chkmkid][$key]->maxpresentation;
                            }
                            $returndata[$key]->instancedata[$chkmkid]->maxpresentation = $maxpresentation;
                            if (empty($instancedata[$chkmkid][$key]->presentationpercent)) {
                                $presentationpercent = 0;
                            } else {
                                $presentationpercent = $instancedata[$chkmkid][$key]->presentationpercent;
                            }
                            $returndata[$key]->instancedata[$chkmkid]->presentationpercent = $presentationpercent;
                            if ($gradepresentation->presentationgradebook) {
                                $finalgrade = $gradinginfo[$chkmkid]->items[CHECKMARK_PRESENTATION_ITEM]->grades[$key];
                                $returndata[$key]->instancedata[$chkmkid]->finalpresgrade = $finalgrade;
                                $returndata[$key]->instancedata[$chkmkid]->formattedpresgrade = $finalgrade->str_grade;
                                $returndata[$key]->instancedata[$chkmkid]->formattedlongpresgrade = $finalgrade->str_long_grade;

                                if (empty($gradinginfo[$chkmkid]->items[CHECKMARK_PRESENTATION_ITEM]->scaleid)
                                        && !empty($gradinginfo[$chkmkid]->items[CHECKMARK_PRESENTATION_ITEM]->grademax)
                                        && $this->pointsforpresentations($chkmkid) && ($finalgrade->grade > 0)) {
                                    // We use gradebook grades whereever it's possible!
                                    $returndata[$key]->coursepressum += $finalgrade->grade;
                                    if ($finalgrade->overridden || $finalgrade->locked) {
                                        // Overridden scales don't count for course sum, so we mark it only here!
                                        $returndata[$key]->presoverridden = true;
                                    }
                                } // Should we check, if the grade item was changed in gradebook only? (For the course sum's calc?)
                            } else if ($this->pointsforpresentations($chkmkid) && ($presgrade > 0)) {
                                $returndata[$key]->coursepressum += $presgrade;
                            }
                        }
                    }
                }
            }

            return $returndata;
        }

        return null;
    }

    /**
     * Get's the examples data for 1 user in 1 checkmark instance
     *
     * @param int $checkmarkid (optional) id of checkmark instance to fetch data for
     * @param int $userid (optional) id of user to fetch data for
     *
     * @return int[] associative array of example-states indexed by example ids
     */
    public function get_examples_data($checkmarkid=0, $userid=0) {
        global $DB;

        // Get instances examples!
        $sql = 'SELECT ex.id, chks.state
                  FROM {checkmark_examples} ex
             LEFT JOIN {checkmark_submissions} sub ON sub.checkmarkid = ex.checkmarkid AND sub.userid = :userid
             LEFT JOIN {checkmark_checks} chks ON chks.submissionid = sub.id
                                                 AND chks.exampleid = ex.id
                 WHERE ex.checkmarkid = :checkmarkid';
        $params = array('checkmarkid' => $checkmarkid,
                        'userid'      => $userid);

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Get all checkmark instances in course indexed by checkmark id
     *
     * @return object[] associative array of checkmark instances indexed by checkmark ids
     */
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

    /**
     * Get's the course id
     *
     * @return int course id
     */
    public function get_courseid() {
        return $this->courseid;
    }

    /**
     * Prepares session object to contain data about hidden columns
     *
     * @return void
     */
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

    /**
     * Prepares session object to contain data about sorting order of the report table
     *
     * @return void
     */
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

    /**
     * Returns link to change sort order of the table including icon to visualize current sorting
     *
     * @param string $column internal column name
     * @param string $text displayed column name / link text
     * @param string|moodle_url $url the base url for all links
     * @return string HTML snippet
     */
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

    /**
     * Checks if a column is currently hidden
     *
     * @param string $column internal column name
     * @return bool true if column is hidden
     */
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

    /**
     * get report as open document file (sends to browser, forces download)
     *
     * @return void
     */
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

    /**
     * get report as xml based excel file (sends to browser, forces download)
     *
     * @return void
     */
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

    public function prepare_worksheet(&$table, &$worksheet, &$x, &$y) {
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
            $keys = array_keys($table->head);
            foreach ($keys as $key) {
                if (!isset($table->align[$key])) {
                    $table->align[$key] = null;
                }
                if (!isset($table->size[$key])) {
                    $table->size[$key] = null;
                }
            }
        }

        if (!empty($table->head)) {
            foreach ($table->head as $row => $headrow) {
                $x = 0;
                $keys = array_keys($headrow->cells);

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
                        $table->head[$row]->cells[$key] = $heading;
                        continue;
                    }

                    if ($heading->header !== false) {
                        $heading->header = true;
                    }

                    if (!isset($heading->colspan)) {
                        $heading->colspan = 1;
                    }
                    if (!isset($heading->rowspan)) {
                        $heading->rowspan = 1;
                    }
                    $table->head[$row]->cells[$key] = $heading;

                    $worksheet->write_string($y, $x, strip_tags($heading->text));
                    $worksheet->merge_cells($y, $x, $y + $heading->rowspan - 1, $x + $heading->colspan - 1);

                    $x++;
                }
                $y++;
            }
        }
    }

    public function add_xml_attendance_data(&$instnode, $instancedata, $instanceid) {
        if ($this->attendancestracked() && $this->tracksattendance($instanceid)) {
            $instnode->setAttribute('attendant', $instancedata->attendance);
        }
    }

    public function add_xml_presentation_data(&$instnode, $instancedata, $instanceid, $gradepresentation) {
        global $DB;

        if (!$this->column_is_hidden('presentationgrade'.$instanceid) && $this->presentationsgraded() && $gradepresentation) {
            $presnode = $instnode->appendChild(new DOMElement('presentation'));
            // TODO replace empty node with node with text-comment for presentation in future version!
            if ($gradepresentation->presentationgradebook) {
                $presentationgrade = $instancedata->formattedpresgrade;
                $overridden = $instancedata->finalpresgrade->overridden;
                $locked = $instancedata->finalpresgrade->locked;
                $presnode->setAttribute('grade', $presentationgrade);
                if ($gradepresentation->presentationgrade > 0) {
                    $presnode->setAttribute('maxgrade', $instancedata->maxpresentation);
                }
                if ($overridden) {
                    $presnode->setAttribute('overridden', true);
                }
                if ($locked) {
                    $presnode->setAttribute('locked', true);
                }
            } else if ($gradepresentation->presentationgrade > 0) {
                if (empty($instancedata->presentationgrade)) {
                    $presentationgrade = 0;
                } else {
                    $presentationgrade = $instancedata->presentationgrade;
                }
                $presentationgrademax = $instancedata->maxpresentation;
                $presnode->setAttribute('grade', $presentationgrade);
                $presnode->setAttribute('maxgrade', $presentationgrademax);
            } else if ($gradepresentation->presentationgrade < 0) {
                if ($scale = $DB->get_record('scale', array('id' => -$gradepresentation->presentationgrade))) {
                    if (isset($scale[(int)$instancedata->presentationgrade])) {
                        $presentationgrade = $scale[(int)$instancedata->presentationgrade];
                    } else {
                        $presentationgrade = '-';
                    }
                } else {
                    $presentationgrade = '-';
                }
                $presnode->setAttribute('grade', $presentationgrade);
            }
        }
    }

    /**
     * Outputs XML for download with filename as specified!
     *
     * @param string $xml XML string
     * @param string $filename filename for download
     */
    public function output_xml_with_headers($xml, $filename) {
        $str = $xml->saveXML();
        header("Content-type: application/xml; charset=utf-8");
        header('Content-Length: ' . strlen($str));
        header('Content-Disposition: attachment;filename="'.$filename.'.xml";'.
                                               'filename*="'.rawurlencode($filename).'.xml"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Encoding: utf-8');
        echo $str;
    }

    /**
     * Outputs plain text for download with filename as specified!
     *
     * @param string $text File content
     * @param string $filename filename for download
     */
    public function output_text_with_headers($text, $filename) {
        header("Content-type: text/txt; charset=utf-8");
        header('Content-Length: ' . strlen($text));
        header('Content-Disposition: attachment;filename="'.$filename.'.txt";'.
                                               'filename*="'.rawurlencode($filename).'.txt"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Encoding: utf-8');
        echo $text;
    }
}
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
 * example.php
 *
 * @package  local_checkmarkreport
 * @author    Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


namespace local_checkmarkreport;
defined('MOODLE_INTERNAL') || die;

/**
 * Class example
 * @author    Daniel Binder
 * @copyright 2019 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @package local_checkmarkreport
 */
class example extends \mod_checkmark\example {

    /**
     * function that returns the state
     *
     * @param int|null $state
     * @return mixed
     */
    public function get_state($state) {
        return $state;
    }

    /**
     * create an instance from id
     *
     * @param int $id
     * @param bool $userid
     * @return example|\mod_checkmark\example|null
     * @throws \dml_exception
     */
    public static function from_id($id, $userid=false) {
        global $DB;

        if ($userid > 0) {
            $checkfields = ", state";
            $checkjoin = "LEFT JOIN {checkmark_checks} cc ON ex.id = cc.exampleid
                          JOIN {checkmark_submissions} s ON cc.submissionid = s.id AND s.userid = :userid";
            $checkparams = ['userid' => $userid];
        } else {
            $checkfields = ", NULL as state";
            $checkjoin = "";
            $checkparams = [];
        }


        $sql = "SELECT ex.id AS id, ex.checkmarkid, ex.name AS shortname, ex.grade,
                       ".$DB->sql_concat('c.exampleprefix', 'ex.name')." AS name, c.exampleprefix AS prefix
                       $checkfields
                  FROM {checkmark_examples} ex
                  JOIN {checkmark} c ON ex.checkmarkid = c.id
                  $checkjoin
                 WHERE ex.id = :id
        ";

        $example = $DB->get_record_sql($sql, ['id' => $id] + $checkparams);
        if ($example) {
            return new self($id, $example->shortname, $example->grade, $example->prefix, $example->state);
        }
        //Call the function again if a userid is present without userid when no example was found.
        // By doing so, the function returns an example with no user allocation what we need for
        // displaying examples without present submissions.
        if ($userid) {
            return self::from_id($id);
        }
        return null;
    }


    /**
     * renders the point string
     *
     * @return mixed
     * @throws \coding_exception
     */
    public function print_pointsstring() {
        global $OUTPUT;

        return $OUTPUT->render_from_template('local_checkmarkreport/examplepoints', $this);
    }

    /**
     * checks whether points are checked
     *
     * @return int|string
     */
    public function get_points_if_checked() {

        if ($this->is_checked()) {
            return $this->grade;
        }
        return 0;
    }

    /**
     * gets the maximum number of points
     *
     * @return string
     */
    public function get_checked_of_max_points() {
        if ($this->is_checked()) {
            return $this->grade . '/' . $this->grade;
        }
        return '0/' . $this->grade;
    }

    /**
     * get points for export
     *
     * @return int|string
     */
    public function get_points_for_export() {
        if ($this->is_forced_checked()) {
            return '(' . $this->grade . ')' ;
        } else if ($this->is_forced_unchecked()) {
            return '(0)';
        } else if ($this->is_checked()) {
            return $this->grade;
        } else {
            return 0;
        }
    }
}


<?php
// This file is part of mtablepdf for Moodle - http://moodle.org/
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
 * @author    Philipp Hager
 * @copyright 2018 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_checkmarkreport;

defined('MOODLE_INTERNAL') || die();


class example {
    const BITMASK_USER = 0x0001;
    const BITMASK_TEACHER = 0x0002;
    const BITMASK_FORCED = 0x0004;

    /** EMPTYBOX UTF-8 empty box = &#x2610; = '☐'! */
    const EMPTYBOX = '';
    /** CHECKEDBOX UTF-8 box with x-mark = &#x2612; = '☒'! */
    const CHECKEDBOX = 'X';
    /** FORCED_EMPTYBOX UTF-8 empty box surrounded by parenthesis = &#x0028;&#x2610;&#x0029; = '(☐)'! */
    const FORCED_EMPTYBOX = '()';
    /** FORCED_EMPTYBOX UTF-8 box with x-mark surrounded by parenthesis = &#x0028;&#x2612;&#x0029; = '(☒)'! */
    const FORCED_CHECKEDBOX = '(X)';



    const UNCHECKED = 0x0000;             // Equals: 0b000000000000000!
    const CHECKED = 0x0001;               // Equals: 0b000000000000001!
    const UNCHECKED_OVERWRITTEN = 0x0006; // Equals: 0b000000000000110!
    const CHECKED_OVERWRITTEN = 0x0005;   // Equals: 0b000000000000101!

    protected $id = 0;
    public $name = '';
    public $grade = 0;
    protected $prefix = '';
    public $state = 0x0000;

    /**
     * example constructor.
     *
     * @param string $name
     * @param int|string $grade
     * @param string $prefix
     * @param int|null $state
     */
    public function __construct($id, $name, $grade, $prefix, $state=null) {
        $this->id = $id;
        $this->name = $name;
        $this->grade = $grade;
        $this->prefix = $prefix;

        if ($state !== null) {
            $this->state = $state;
        }
    }

    public function __get($name) {
        switch($name) {
            case 'id':
            case 'grade':
            case 'state':
            case 'prefix':
                return $this->$name;
                break;
            case 'name':
                return $this->prefix . $this->name;
                break;
            case 'shortname':
                return $this->name;
                break;
            case 'pointsstring':
                switch ($this->grade) {
                    case '1':
                        return get_string('strpoint', 'checkmark');
                        break;
                    case '2':
                    default:
                        return get_string('strpoints', 'checkmark');
                        break;
                }
                break;
        }
    }

    public function __set($name, $value) {
        switch($name) {
            case 'id':
            case 'grade':
            case 'state':
            case 'prefix':
                $this->$name = $value;
                break;
            case 'shortname':
            case 'name':
                $this->name = $value;
                break;
        }
    }

    public function export_for_snapshot() {
        $record = new \stdClass;
        $record->id = $this->id;
        $record->name = $this->name;
        $record->grade = $this->grade;
        $record->prefix = $this->prefix;
        $record->state = $this->state;
        return $record;
    }

    public function set_state($state) {
        $this->state = $state;
    }

    public function get_state($state) {
        return $state;
    }

    public function get_name() {
        return $this->prefix . $this->name;
    }

    public function get_grade() {
        return $this->grade;
    }

    public function get_pointsstring() {
        switch ($this->grade) {
            case '1':
                return get_string('strpoint', 'checkmark');
                break;
            case '2':
            default:
                return get_string('strpoints', 'checkmark');
                break;
        }
    }

    public function get_forcedstring() {
        if($this->is_forced()) {
            return '[' . get_string('forced','checkmark') . ']';
        }
        return '';
    }

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
        if($example) {
            return new self($id, $example->shortname, $example->grade, $example->prefix, $example->state);
        }
        if($userid) {
            return self::from_id($id);
        }
        return null;

    }

    public function print_examplestate() {
        global $OUTPUT;

        return $OUTPUT->render_from_template('local_checkmarkreport/examplestate', $this);
    }
    public function print_pointsstring() {
        global $OUTPUT;

        return $OUTPUT->render_from_template('local_checkmarkreport/examplepoints', $this);
    }
    public function get_points_if_checked() {
        if($this->is_checked()) {
            return $this->grade;
        }
        return 0;
    }
    public function get_checked_of_max_points() {
        if($this->is_checked()) {
            return $this->grade . '/' . $this->grade;
        }
        return '0/' . $this->grade;
    }

    public function get_examplestate_for_export() {
        if ($this->is_forced_checked()) {
            return self::FORCED_CHECKEDBOX;
        } else if ($this->is_forced_unchecked()) {
            return self::FORCED_EMPTYBOX;
        } else if ($this->is_checked()) {
            return self::CHECKEDBOX;
        } else {
            return self::EMPTYBOX;
        }
    }
    public function get_points_for_export() {
        if ($this->is_forced_checked()) {
            return "(" . $this->grade . ")";
        } else if ($this->is_forced_unchecked()) {
            return "(" . 0 . ")";
        } else if ($this->is_checked()) {
            return $this->grade;
        } else {
            return 0;
        }
    }

    /**
     * @param $state
     * @return bool
     */
    public static function static_is_checked($state) {
        return (bool)(($state & self::BITMASK_FORCED) ?
                ($state & self::BITMASK_TEACHER) :
                ($state & self::BITMASK_USER));
    }

    /**
     * @param $state
     * @return bool
     */
    public static function static_is_forced($state) {
        return (bool)($state & self::BITMASK_FORCED);
    }

    /**
     * @param $state
     * @return bool
     */
    public static function static_is_forced_checked($state) {
        return (bool)($state & self::BITMASK_FORCED) && ($state & self::BITMASK_TEACHER);
    }

    /**
     * @param $state
     * @return bool
     */
    public function static_is_forced_unchecked($state) {
        return (bool)($state & self::BITMASK_FORCED) & !($state & self::BITMASK_TEACHER);
    }

    /**
     * @return bool
     */
    public function is_checked() {
        return self::static_is_checked($this->state);
    }

    /**
     * @return bool
     */
    public function is_forced() {
        return self::static_is_forced($this->state);
    }

    /**
     * @return bool
     */
    public function is_forced_checked() {
        return self::static_is_forced_checked($this->state);
    }

    /**
     * @return bool
     */
    public function is_forced_unchecked() {
        return self::static_is_forced_unchecked($this->state);
    }
    public static function get_static_pointstring($grade) {
        switch (grade) {
            case '1':
                return get_string('strpoint', 'checkmark');
                break;
            case '2':
            default:
                return get_string('strpoints', 'checkmark');
                break;
        }
}
}

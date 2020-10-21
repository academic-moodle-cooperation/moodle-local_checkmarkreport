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
 * Contains checkmarkreport's filter from class
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Filter form
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_checkmarkreport_reportfilterform extends moodleform {
    /**
     * constructor method
     *
     * local_checkmarkreport_reportfilterform constructor.
     * @param null $action
     * @param null $customdata
     * @param string $method
     * @param string $target
     * @param null $attributes
     * @param bool $editable
     * @param null $ajaxformdata
     */
    public function __construct($action=null, $customdata=null, $method='post', $target='', $attributes=null, $editable=true,
                                $ajaxformdata=null) {
        $attributes['id'] = 'reportfilterform';
        parent::__construct($action, $customdata, $method, $target, $attributes, $editable, $ajaxformdata);
    }

    /**
     * Definition of filter form
     */
    protected function definition() {
        global $COURSE, $DB, $USER, $OUTPUT;
        $mform = $this->_form;

        $mform->addElement('header', 'checkmarkreport', get_string('pluginname', 'local_checkmarkreport'));

        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        $context = CONTEXT_COURSE::instance($COURSE->id);
        $groupmode = $DB->get_field('course', 'groupmode', ['id' => $COURSE->id], MUST_EXIST);

        // Groupings filter!
        if (empty($this->_customdata['hidegroups'])
                && $groupmode != NOGROUPS) {
            $groupings = groups_get_all_groupings($COURSE->id);
            $groupingsel = $mform->createElement('select', 'groupings[]',
                    get_string('groupings', 'local_checkmarkreport'),
                    null, ['id' => 'groupings']);
            $groupingsel->addOption(get_string('all') . ' ' . get_string('groupings', 'local_checkmarkreport'), 0);
            if (count($groupings)) {
                list($grpgssql, $grpgsparams) = $DB->get_in_or_equal(array_keys($groupings));
                $groupinggroups = $DB->get_records_sql_menu("
                SELECT groupingid, COUNT(DISTINCT groupid)
                  FROM {groupings_groups}
                 WHERE groupingid " . $grpgssql . "
              GROUP BY groupingid", $grpgsparams);
                foreach ($groupings as $grouping) {
                    if (empty($groupinggroups[$grouping->id])) {
                        $disabled = ['disabled' => 'disabled'];
                    } else {
                        $disabled = [];
                    }
                    $groupingsel->addOption($grouping->name, $grouping->id, $disabled);
                }
            }
            $groupings = $mform->addElement($groupingsel);
            $mform->setDefault('groupings[]', optional_param_array('groupings', [0], PARAM_INT));
            $groupings->setMultiple(false);
        }

        // Groups filter!
        if (empty($this->_customdata['hidegroups'])
                && $groupmode != NOGROUPS) {
            $groupingids = optional_param_array('groupings', [0], PARAM_INT);
            $groups = [];
            foreach ($groupingids as $groupingid) {
                $groupinggroups = groups_get_all_groups($COURSE->id, 0, $groupingid);
                foreach ($groupinggroups as $group) {
                    $groups[$group->id] = $group;
                }
            }
            $groupsel = $mform->createElement('select', 'groups[]', get_string('groups'), null,
                    ['id' => 'groups']);
            $groupsel->addOption(get_string('all') . ' ' . get_string('groups'), 0);
            if (count($groups)) {
                list($grpssql, $grpsparams) = $DB->get_in_or_equal(array_keys($groups));
                $groupmembers = $DB->get_records_sql_menu("
                SELECT groupid, COUNT(DISTINCT userid)
                  FROM {groups_members}
                 WHERE groupid " . $grpssql . "
              GROUP BY groupid", $grpsparams);
                foreach ($groups as $group) {
                    if (empty($groupmembers[$group->id])) {
                        $disabled = ['disabled' => 'disabled'];
                    } else {
                        $disabled = [];
                    }
                    if (strlen($group->name) > 27) {
                        $name = substr($group->name, 0, 22) . '...' . substr($group->name, -2);
                    } else {
                        $name = $group->name;
                    }
                    $groupsel->addOption($name, $group->id, $disabled);
                }
            }
            $groups = $mform->addElement($groupsel);
            $groups->setMultiple(false);
            $mform->setDefault('groups[]', optional_param_array('groups', [0], PARAM_INT));
            $mform->addHelpButton('groups[]', 'groups', 'local_checkmarkreport');
        }

        // User filter!
        if (empty($this->_customdata['hideusers'])) {
            $userselects = [get_string('all') . ' ' . get_string('user')];
            $groups = optional_param_array('groups', [0], PARAM_INT);
            foreach ($groups as $curgrp) {
                $users = get_enrolled_users($context, 'mod/checkmark:submit', $curgrp, 'u.*', 'lastname ASC');
                foreach ($users as $user) {
                    $userselects[$user->id] = fullname($user, has_capability('moodle/site:viewfullnames', $context));
                }
            }
            $users = $mform->addElement('select', 'users[]', get_string('users'), $userselects, ['id' => 'users']);
            $mform->setDefault('users[]', optional_param_array('users', [0], PARAM_INT));
            $users->setMultiple(false);
        }

        // Instance filter!
        if (empty($this->_customdata['hideinstances'])) {
            $checkmarkselects = [
                    get_string('all') . ' ' .
                    get_string('modulenameplural', 'checkmark')
            ];
            if ($checkmarks = get_all_instances_in_course('checkmark', $COURSE)) {
                foreach ($checkmarks as $checkmark) {
                    $checkmarkselects[$checkmark->id] = $checkmark->name;
                }
                $instances = $mform->addElement('select', 'instances',
                        get_string('modulenameplural', 'checkmark'),
                        $checkmarkselects, ['size' => 5]);
                $instances->setMultiple(true);
                $mform->addRule('instances', get_string('required'), 'required', '', 'client');
            }
        }

        // Add Grade!
        if (empty($this->_customdata['header'])) {
            $this->_customdata['header'] = get_string('additional_columns', 'local_checkmarkreport');
        }

        $addcolumns = [];
        $exampleshelp = new help_icon('showexamples', 'local_checkmarkreport');
        $gradehelp = new help_icon('grade', 'local_checkmarkreport');
        $sumabshelp = new help_icon('sumabs', 'local_checkmarkreport');
        $sumrelhelp = new help_icon('sumrel', 'local_checkmarkreport');
        $break = html_writer::tag('div', '', ['class' => 'break']);

        $formelement =& $mform->createElement('advcheckbox', 'showexamples', '',
                get_string('showexamples', 'local_checkmarkreport'));
        $formelement->_helpbutton = $OUTPUT->render($exampleshelp);
        $addcolumns[] = $formelement;
        $mform->setDefault('showexamples', get_user_preferences('checkmarkreport_showexamples', 1));

        $formelement =& $mform->createElement('advcheckbox', 'grade', '',
                get_string('showgrade', 'local_checkmarkreport'));
        $formelement->_helpbutton = $OUTPUT->render($gradehelp);
        $addcolumns[] = $formelement;
        $mform->setDefault('grade', get_user_preferences('checkmarkreport_showgrade'));
        // Add x/y ex!
        $formelement =& $mform->createElement('advcheckbox', 'sumabs', '',
                get_string('sumabs', 'local_checkmarkreport'));
        $formelement->_helpbutton = $OUTPUT->render($sumabshelp);
        $addcolumns[] = $formelement;
        $mform->setDefault('sumabs', get_user_preferences('checkmarkreport_sumabs'));
        // Add % ex!
        $formelement =& $mform->createElement('advcheckbox', 'sumrel', '',
                get_string('sumrel', 'local_checkmarkreport'));
        $formelement->_helpbutton = $OUTPUT->render($sumrelhelp);
        $addcolumns[] = $formelement;
        $mform->setDefault('sumrel', get_user_preferences('checkmarkreport_sumrel'));

        $mform->addGroup($addcolumns, 'additionalcolumns', $this->_customdata['header'], $break, false);

        // Additional settings ?? don't need them...
        $addsettings = [];
        $pointshelp = new help_icon('showpoints', 'local_checkmarkreport');
        $formelement =& $mform->createElement('advcheckbox', 'showpoints', '',
                get_string('showpoints', 'local_checkmarkreport'));
        $formelement->_helpbutton = $OUTPUT->render($pointshelp);
        $addsettings[] = $formelement;

        if (\local_checkmarkreport_base::attendancestrackedincourse($COURSE->id)) {
            $attendanceshelp = new help_icon('showattendances', 'local_checkmarkreport');
            $formelement =& $mform->createElement('advcheckbox', 'showattendances', '',
                    get_string('showattendances', 'local_checkmarkreport'));
            $formelement->_helpbutton = $OUTPUT->render($attendanceshelp);
            $addsettings[] = $formelement;
            $mform->setDefault('showattendances', get_user_preferences('checkmarkreport_showattendances', 0));
        } else {
            $mform->addElement('hidden', 'showattendances');
            $mform->setDefault('showattendances', get_user_preferences('checkmarkreport_showattendances', 0));
        }
        $mform->setType('showattendances', PARAM_BOOL);

        if (\local_checkmarkreport_base::presentationsgradedincourse($COURSE->id)) {
            $presgradehelp = new help_icon('showpresentationgrades', 'local_checkmarkreport');
            $formelement =& $mform->createElement('advcheckbox', 'showpresentationgrades', '',
                    get_string('showpresentationgrades', 'local_checkmarkreport'));
            $formelement->_helpbutton = $OUTPUT->render($presgradehelp);
            $addsettings[] = $formelement;
            $mform->setDefault('showpresentationgrades', get_user_preferences('checkmarkreport_showpresentationgrades', 0));
            $prescounthelp = new help_icon('showpresentationcount', 'local_checkmarkreport');
            $formelement =& $mform->createElement('advcheckbox', 'showpresentationsgraded', '',
                    get_string('showpresentationcount', 'local_checkmarkreport'));
            $formelement->_helpbutton = $OUTPUT->render($prescounthelp);
            $addsettings[] = $formelement;
            $mform->setDefault('showpresentationsgraded', get_user_preferences('checkmarkreport_showpresentationcount', 0));
        } else {
            $mform->addElement('hidden', 'showpresentationgrades');
            $mform->setDefault('showpresentationgrades', get_user_preferences('checkmarkreport_showpresentationgrades', 0));
            $mform->addElement('hidden', 'showpresentationsgraded');
            $mform->setDefault('showpresentationsgraded', get_user_preferences('checkmarkreport_showpresentationcount', 0));
        }
        $mform->setType('showpresentationgrades', PARAM_BOOL);
        $mform->setType('showpresentationsgraded', PARAM_BOOL);

        $sighelp = new help_icon('showsignature', 'local_checkmarkreport');
        $formelement =& $mform->createElement('advcheckbox', 'signature', '',
                get_string('showsignature', 'local_checkmarkreport'));
        $formelement->_helpbutton = $OUTPUT->render($sighelp);
        $addsettings[] = $formelement;
        $mform->setDefault('showpoints', get_user_preferences('checkmarkreport_showpoints'));
        $mform->setDefault('signature', get_user_preferences('checkmarkreport_signature'));

        $mform->addGroup($addsettings, 'additionalsettings', get_string('additional_settings', 'local_checkmarkreport'), $break,
                false);

        $mform->addElement('submit', 'submitbutton', get_string('update', 'local_checkmarkreport'));
        $mform->disable_form_change_checker();
    }
}

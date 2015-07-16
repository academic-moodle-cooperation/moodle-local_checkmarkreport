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
 * The variable name for the capability definitions array is $capabilities
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir .'/formslib.php');

/**
 * Filter form
 *
 * @package       local_checkmarkreport
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2013 onwards TSC TU Vienna
 * @since         Moodle 2.5.3
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_checkmarkreport_reportfilterform extends moodleform {
    /**
     * Definition of filter form
     *
     * @global object $CFG
     * @global object $COURSE
     * @global object $DB
     * @global object $PAGE
     */
    protected function definition() {
        global $CFG, $COURSE, $DB, $PAGE, $USER, $OUTPUT;
        $mform = $this->_form;

        $mform->addElement('header', 'checkmarkreport', get_string('pluginname', 'local_checkmarkreport'));

        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        $context = CONTEXT_COURSE::instance($COURSE->id);
        $groupmode = $DB->get_field('course', 'groupmode', array('id' => $COURSE->id), MUST_EXIST);

        // Groupings filter!
        if (empty($this->_customdata['hidegroups'])
            && $groupmode != NOGROUPS) {
            $groupings = groups_get_all_groupings($COURSE->id);
            $groupingsel = $mform->createElement('select', 'groupings[]',
                                                 get_string('groupings', 'local_checkmarkreport'),
                                                 null, array('id' => 'groupings'));
            $groupingsel->addOption(get_string('all').' '.get_string('groupings', 'local_checkmarkreport'), 0);
            if (count($groupings)) {
                list($grpgssql, $grpgsparams) = $DB->get_in_or_equal(array_keys($groupings));
                $groupinggroups = $DB->get_records_sql_menu("
                SELECT groupingid, COUNT(DISTINCT groupid)
                  FROM {groupings_groups}
                 WHERE groupingid ".$grpgssql."
              GROUP BY groupingid", $grpgsparams);
                foreach ($groupings as $grouping) {
                    if (empty($groupinggroups[$grouping->id])) {
                        $disabled = array('disabled' => 'disabled');
                    } else {
                        $disabled = array();
                    }
                    $groupingsel->addOption($grouping->name, $grouping->id, $disabled);
                }
            }
            $groupings = $mform->addElement($groupingsel);
            $mform->setDefault('groupings[]', optional_param_array('groupings', array(0), PARAM_INT));
            $groupings->setMultiple(false);
        }

        // Groups filter!
        if (empty($this->_customdata['hidegroups'])
            && $groupmode != NOGROUPS) {
            $groupingids = optional_param_array('groupings', array(0), PARAM_INT);
            $groups = array();
            foreach ($groupingids as $groupingid) {
                $groupinggroups = groups_get_all_groups($COURSE->id, 0, $groupingid);
                foreach ($groupinggroups as $group) {
                    $groups[$group->id] = $group;
                }
            }
            $groupsel = $mform->createElement('select', 'groups[]', get_string('groups'), null,
                                              array('id' => 'groups'));
            $groupsel->addOption(get_string('all').' '.get_string('groups'), 0);
            if (count($groups)) {
                list($grpssql, $grpsparams) = $DB->get_in_or_equal(array_keys($groups));
                $groupmembers = $DB->get_records_sql_menu("
                SELECT groupid, COUNT(DISTINCT userid)
                  FROM {groups_members}
                 WHERE groupid ".$grpssql."
              GROUP BY groupid", $grpsparams);
                foreach ($groups as $group) {
                    if (empty($groupmembers[$group->id])) {
                        $disabled = array('disabled' => 'disabled');
                    } else {
                        $disabled = array();
                    }
                    if (strlen($group->name) > 27) {
                        $name = substr($group->name, 0, 22).'...'.substr($group->name, -2);
                    } else {
                        $name = $group->name;
                    }
                    $groupsel->addOption($name, $group->id, $disabled);
                }
            }
            $groups = $mform->addElement($groupsel);
            $groups->setMultiple(false);
            $mform->setDefault('groups[]', optional_param_array('groups', array(0), PARAM_INT));
            $mform->addHelpButton('groups[]', 'groups', 'local_checkmarkreport');
        }

        // User filter!
        if (empty($this->_customdata['hideusers'])) {
            $userselects = array(get_string('all').' '.get_string('user'));
            $groups = optional_param_array('groups', array(0), PARAM_INT);
            foreach ($groups as $curgrp) {
                $users = get_enrolled_users($context, '', $curgrp, 'u.*', 'lastname ASC');
                foreach ($users as $user) {
                    $userselects[$user->id] = fullname($user);
                }
            }
            $users = $mform->addElement('select', 'users[]', get_string('users'), $userselects, array('id' => 'users'));
            $mform->setDefault('users[]', optional_param_array('users', array(0), PARAM_INT));
            $users->setMultiple(false);
        }

        // Instance filter!
        if (empty($this->_customdata['hideinstances'])) {
            $checkmarkselects = array(get_string('all').' '.
                                      get_string('modulenameplural', 'checkmark'));
            if ($checkmarks = get_all_instances_in_course('checkmark', $COURSE)) {
                foreach ($checkmarks as $checkmark) {
                    $checkmarkselects[$checkmark->id] = $checkmark->name;
                }
                $instances = $mform->addElement('select', 'instances',
                                   get_string('modulenameplural', 'checkmark'),
                                   $checkmarkselects, array('size' => 5));
                $instances->setMultiple(true);
            }
        }

        // Add Grade!
        if (empty($this->_customdata['header'])) {
            $this->_customdata['header'] = get_string('additional_columns', 'local_checkmarkreport');
        }

        $addcolumns = array();
        $gradehelp = new help_icon('grade', 'local_checkmarkreport');
        $sumabshelp = new help_icon('sumabs', 'local_checkmarkreport');
        $sumrelhelp = new help_icon('sumrel', 'local_checkmarkreport');

        $addcolumns[] =& $mform->createElement('advcheckbox', 'grade', '',
                                               get_string('showgrade', 'local_checkmarkreport').$OUTPUT->render($gradehelp));
        $mform->setDefault('grade', get_user_preferences('checkmarkreport_showgrade'));
        // Add x/y ex!
        $addcolumns[] =& $mform->createElement('advcheckbox', 'sumabs', '',
                                               get_string('sumabs', 'local_checkmarkreport').$OUTPUT->render($sumabshelp));
        $mform->setDefault('sumabs', get_user_preferences('checkmarkreport_sumabs'));
        // Add % ex!
        $addcolumns[] =& $mform->createElement('advcheckbox', 'sumrel', '',
                                               get_string('sumrel', 'local_checkmarkreport').$OUTPUT->render($sumrelhelp));
        $mform->setDefault('sumrel', get_user_preferences('checkmarkreport_sumrel'));

        $mform->addGroup($addcolumns, 'additionalcolumns', $this->_customdata['header'], html_writer::empty_tag('br'), false);

        // Additional settings ?? don't need them...
        $addsettings = array();
        $pointshelp = new help_icon('showpoints', 'local_checkmarkreport');
        $addsettings[] =& $mform->createElement('advcheckbox', 'showpoints', '',
                                                get_string('showpoints', 'local_checkmarkreport').$OUTPUT->render($pointshelp));
        $mform->setDefault('showpoints', get_user_preferences('checkmarkreport_showpoints'));

        $mform->addGroup($addsettings, 'additionalsettings', get_string('additional_settings', 'local_checkmarkreport'),
                         html_writer::empty_tag('br'), false);

        $mform->addElement('submit', 'submitbutton', get_string('update', 'local_checkmarkreport'));
        $mform->disable_form_change_checker();
        $PAGE->requires->string_for_js('all', 'moodle');
        $PAGE->requires->string_for_js('users', 'moodle');
        $PAGE->requires->string_for_js('loading', 'local_checkmarkreport');
        $PAGE->requires->string_for_js('error_retriefing_members', 'local_checkmarkreport');
        $PAGE->requires->yui_module('moodle-local_checkmarkreport-filterform',
                                    'M.local_checkmarkreport.init_filterform');
    }
}
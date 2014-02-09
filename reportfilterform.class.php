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
 * The variable name for the capability definitions array is $capabilities
 *
 * @package       local_checkmarkreport
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2013 onwards TSC TU Vienna
 * @since         Moodle 2.5.3
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once $CFG->libdir .'/formslib.php';

/**
 * Filter form
 *
 * @package       local_checkmarkreport
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2013 onwards TSC TU Vienna
 * @since         Moodle 2.5.3
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class reportfilterform extends moodleform {
    /**
     * Definition of filter form
     *
     * @global object $CFG
     * @global object $COURSE
     * @global object $DB
     * @global object $PAGE
     */
    protected function definition() {
        global $CFG, $COURSE, $DB, $PAGE, $USER;
        $mform = $this->_form;

        $mform->addElement('hidden', 'userid', $USER->id);
        $mform->setType('userid', PARAM_INT);

        $mform->addElement('html', html_writer::start_tag('div', array('class'=>'columns')));
        $context = CONTEXT_COURSE::instance($COURSE->id);
        $groupmode = $DB->get_field('course', 'groupmode', array('id'=>$COURSE->id), MUST_EXIST);
        // Groups filter
        if (empty($this->_customdata['hidegroups'])
            && $groupmode != NOGROUPS) {
            $groups = groups_get_all_groups($COURSE->id);
            $groupsel = $mform->createElement('select', 'groups', get_string('groups'), null, array('id'=>'groups'));
            //$groupselects = array(get_string('all').' '.get_string('groups'));
            $groupsel->addOption(get_string('all').' '.get_string('groups'), 0);
            if(count($groups)) {
                list($grpssql, $grpsparams) = $DB->get_in_or_equal(array_keys($groups));
                $groupmembers = $DB->get_records_sql_menu("
                SELECT groupid, COUNT(DISTINCT userid)
                  FROM {groups_members}
                 WHERE groupid ".$grpssql."
              GROUP BY groupid", $grpsparams);
                foreach($groups as $group) {
                    //$groupselects[$group->id] = $group->name;
                    if (empty($groupmembers[$group->id])) {
                        $disabled = array('disabled'=>'disabled');
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
            $groups->setMultiple(true);
            $mform->addHelpButton('groups', 'groups', 'local_checkmarkreport');
        }

        // User filter
        if (empty($this->_customdata['hideusers'])) {
            $mform->closeHeaderBefore('users');
            $userselects = array(get_string('all').' '.get_string('user'));
            $groups = optional_param_array('groups', array(0), PARAM_INT);
            foreach($groups as $curgrp) {
                $users = get_enrolled_users($context, '', $curgrp, 'u.*', 'lastname ASC');
                foreach($users as $user) {
                    $userselects[$user->id] = fullname($user);
                }
            }
            $users = $mform->addElement('select', 'users', get_string('users'), $userselects, array('id'=>'users'));
            $users->setMultiple(true);
        }

        // Instance filter
        if (empty($this->_customdata['hideinstances'])) {
            $mform->closeHeaderBefore('instances');
            $checkmarkselects = array(get_string('all').' '.
                                      get_string('modulenameplural', 'checkmark'));
            if ($checkmarks = get_all_instances_in_course('checkmark', $COURSE)) {
                foreach($checkmarks as $checkmark) {
                    $checkmarkselects[$checkmark->id] = $checkmark->name;
                }
                $instances = $mform->addElement('select', 'instances',
                                   get_string('modulenameplural', 'checkmark'),
                                   $checkmarkselects);
                $instances->setMultiple(true);
            }
        }

        // Additional columns
        $mform->closeHeaderBefore('grade');
        //grade
        $mform->addElement('advcheckbox', 'grade', get_string('additional_information', 'local_checkmarkreport'), get_string('showgrade', 'local_checkmarkreport'));
        $mform->setDefault('grade', get_user_preferences('checkmarkreport_showgrade'));
        //x/y ex
        $mform->addElement('advcheckbox', 'sumabs', null, get_string('sumabs', 'local_checkmarkreport'));
        $mform->setDefault('sumabs', get_user_preferences('checkmarkreport_sumabs'));
        //% ex
        $mform->addElement('advcheckbox', 'sumrel', null, get_string('sumrel', 'local_checkmarkreport'));
        $mform->setDefault('sumrel', get_user_preferences('checkmarkreport_sumrel'));

        // Additional settings ?? don't need them...
        $mform->closeHeaderBefore('showpoints');
        $mform->addElement('advcheckbox', 'showpoints', get_string('additional_settings', 'local_checkmarkreport'), get_string('showpoints', 'local_checkmarkreport'));
        $mform->setDefault('showpoints', get_user_preferences('checkmarkreport_showpoints'));

        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->closeHeaderBefore('submitbutton');

        $mform->addElement('submit', 'submitbutton', get_string('update', 'local_checkmarkreport'));
        $mform->disable_form_change_checker();
        $PAGE->requires->string_for_js('all', 'moodle');
        $PAGE->requires->string_for_js('users', 'moodle');
        $PAGE->requires->string_for_js('error_retriefing_members', 'local_checkmarkreport');
        $PAGE->requires->yui_module('moodle-local_checkmarkreport-filterform',
                                    'M.local_checkmarkreport.init_filterform');
    }
}
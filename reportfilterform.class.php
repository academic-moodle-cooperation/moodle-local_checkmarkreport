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
 * Capability definitions for the checkmarkreport
 *
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 *
 * It is important that capability names are unique. The naming convention
 * for capabilities that are specific to modules and blocks is as follows:
 *   [mod/block]/<plugin_name>:<capabilityname>
 *
 * component_name should be the same as the directory name of the mod or block.
 *
 * Core moodle capabilities are defined thus:
 *    moodle/<capabilityclass>:<capabilityname>
 *
 * Examples: mod/forum:viewpost
 *           block/recent_activity:view
 *           moodle/site:deleteuser
 *
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

        $mform->addElement('html', html_writer::start_tag('div', array('class'=>'columns')));
        $context = CONTEXT_COURSE::instance($COURSE->id);
        $groupmode = $DB->get_field('course', 'groupmode', array('id'=>$COURSE->id), MUST_EXIST);
        // Groups filter
        if (empty($this->_customdata['hidegroups'])
            && $groupmode != NOGROUPS) {
            $groups = groups_get_all_groups($COURSE->id);
            $groupsel = $mform->createElement('select', 'groups', get_string('groups'));
            //$groupselects = array(get_string('all').' '.get_string('groups'));
            $groupsel->addOption(get_string('all').' '.get_string('groups'), 0);
            if(count($groups)) {
                list($grpssql, $grpsparams) = $DB->get_in_or_equal(array_keys($groups));
                $groupmembers = $DB->get_fieldset_sql("
                SELECT COUNT(DISTINCT userid)
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
            $users = $mform->addElement('select', 'users', get_string('users'), $userselects);
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
    }
}
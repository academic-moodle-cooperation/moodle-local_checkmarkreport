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
        // Groups filter
        if (empty($this->_customdata['hidegroups'])) {
            $groups = groups_get_all_groups($COURSE->id);
            $groupselects = array(get_string('all').' '.get_string('groups'));
            if(count($groups)) {
                foreach($groups as $group) {
                    $groupselects[$group->id] = $group->name;
                }
            }
            $groups = $mform->addElement('select', 'groups', get_string('groups'), $groupselects);
            $groups->setMultiple(true);
        }

        // User filter
        if (empty($this->_customdata['hideusers'])) {
            $mform->closeHeaderBefore('users');
            $userselects = array(get_string('all').' '.get_string('user'));
            $users = get_enrolled_users($context, '', 0, 'u.*', 'lastname ASC');
            foreach($users as $user) {
                $userselects[$user->id] = fullname($user);
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
        //$mform->closeHeaderBefore('addcolheader');
        $mform->closeHeaderBefore('grade');
        //$mform->addElement('static', 'addcolheader', html_writer::tag('div', get_string('additional_columns', 'local_checkmarkreport')));
        //grade
        $mform->addElement('advcheckbox', 'grade', get_string('additional_columns', 'local_checkmarkreport'), get_string('showgrade', 'local_checkmarkreport'));
        //x/y ex
        $mform->addElement('advcheckbox', 'sumabs', null, get_string('summary_abs', 'checkmark'));
        //% ex
        $mform->addElement('advcheckbox', 'sumrel', null, get_string('summary_rel', 'checkmark'));
        
        // Additional settings ?? don't need them...
        //$mform->closeHeaderBefore('addsettheader');
        $mform->closeHeaderBefore('showpoints');
        //$mform->addElement('static', 'addsettheader', html_writer::tag('div', get_string('additional_settings', 'local_checkmarkreport')));
        $mform->addElement('advcheckbox', 'showpoints', get_string('additional_settings', 'local_checkmarkreport'), get_string('showpoints', 'local_checkmarkreport'));

        $mform->addElement('html', html_writer::end_tag('div'));

        $mform->closeHeaderBefore('submitbutton');

        $mform->addElement('submit', 'submitbutton', get_string('filter', 'local_checkmarkreport'));
    }
}
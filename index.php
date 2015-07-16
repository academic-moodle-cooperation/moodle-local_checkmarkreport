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
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/checkmarkreport/lib.php');

$id = required_param('id', PARAM_INT);   // Course.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

$coursecontext = context_course::instance($course->id);

require_capability('local/checkmarkreport:view', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/local/checkmarkreport/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_course($course);

// Get Tabs according to capabilities!
$tabs = array();
$availabletabs = array();
if (has_capability('local/checkmarkreport:view_courseoverview', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE)) {
    $tabs[] = new tabobject('overview',
                           $CFG->wwwroot.'/local/checkmarkreport/index.php?id='.$id.
                           '&amp;tab=overview',
                           get_string('overview', 'local_checkmarkreport'),
                           get_string('overview_alt', 'local_checkmarkreport'),
                           false);
    $availabletabs[] = 'overview';
}

if (has_capability('local/checkmarkreport:view_students_overview', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE)) {
    $tabs[] = new tabobject('useroverview',
                           $CFG->wwwroot.'/local/checkmarkreport/index.php?id='.$id.
                           '&amp;tab=useroverview',
                           get_string('useroverview', 'local_checkmarkreport'),
                           get_string('useroverview_alt', 'local_checkmarkreport'),
                           false);
    $availabletabs[] = 'useroverview';
}

if (has_capability('local/checkmarkreport:view_own_overview', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE)) {
    $tabs[] = new tabobject('userview',
                           $CFG->wwwroot.'/local/checkmarkreport/index.php?id='.$id.
                           '&amp;tab=userview',
                           get_string('userview', 'local_checkmarkreport'),
                           get_string('userview_alt', 'local_checkmarkreport'),
                           false);
    $availabletabs[] = 'userview';
}

if (count($tabs) > 1) {
    $newtab = optional_param('tab', null, PARAM_ALPHAEXT);
    if (!empty($newtab)) {
        $tab = $newtab;
    } else if (!isset($tab)) {
        $tab = current($availabletabs);
    }
} else if (count($tabs) == 1) {
    $tab = current($availabletabs);
} else {
    $tab = 'noaccess';
}

$PAGE->set_url('/local/checkmarkreport/index.php', array('id' => $id, 'tab' => $tab));
$PAGE->navbar->add(get_string('pluginname', 'local_checkmarkreport'),
                   new moodle_url('/local/checkmarkreport/index.php',
                                  array('id' => $id)));
$PAGE->navbar->add(get_string($tab, 'local_checkmarkreport'),
                   new moodle_url('/local/checkmarkreport/index.php',
                                  array('id' => $id, 'tab' => $tab)));
$output = $PAGE->get_renderer('local_checkmarkreport');

echo $output->header();

echo $output->heading(get_string('pluginname', 'local_checkmarkreport'), 2);

if (count($tabs) > 1) {
    echo print_tabs(array($tabs), $tab, $tab, array(), true);
}

if (! $checkmarks = get_all_instances_in_course('checkmark', $course)) {
    notice(get_string('nocheckmarks', 'checkmark'), new moodle_url('/course/view.php',
                                                                   array('id' => $course->id)));
}

switch($tab) {
    case 'overview':
        $mform = new local_checkmarkreport_reportfilterform($PAGE->url, array('courseid'   => $id,
                                                        'hideusers' => true), 'get');
        if ($data = $mform->get_data()) {
            set_user_preference('checkmarkreport_showgrade', $data->grade);
            set_user_preference('checkmarkreport_sumabs', $data->sumabs);
            set_user_preference('checkmarkreport_sumrel', $data->sumrel);
            set_user_preference('checkmarkreport_showpoints', $data->showpoints);
            $groupings = empty($data->groupings) ? array(0) : $data->groupings;
            if (!is_array($groupings)) {
                $groupings = array($groupings);
            }
            $groups = empty($data->groups) ? array(0) : $data->groups;
            if (!is_array($groups)) {
                $groups = array($groups);
            }
            $instances = $data->instances;
        } else {
            $groupings = optional_param_array('groupings', array(0), PARAM_INT);
            $groups = optional_param_array('groups', array(0), PARAM_INT);
            $instances = optional_param_array('instances', array(0), PARAM_INT);
            $mform->set_data(array('groupings' => $groupings,
                                  'instances' => $instances));
        }
        $arrays = http_build_query(array('groupings'  => $groupings,
                                         'groups'     => $groups,
                                         'checkmarks' => $instances));
        $PAGE->set_url($PAGE->url.'&'.$arrays);
        $mform->display();
        $checkmarkreport = new local_checkmarkreport_overview($id, $groupings, $groups, $instances);
        // Trigger the event!
        \local_checkmarkreport\event\overview_viewed::overview($course)->trigger();
    break;
    case 'useroverview':
        $customdata = array('courseid'      => $id,
                            'hideinstances' => true,
                            'header'        => get_string('additional_information',
                                                          'local_checkmarkreport'));
        $mform = new local_checkmarkreport_reportfilterform($PAGE->url, $customdata, 'get');
        if ($data = $mform->get_data()) {
            set_user_preference('checkmarkreport_showgrade', $data->grade);
            set_user_preference('checkmarkreport_sumabs', $data->sumabs);
            set_user_preference('checkmarkreport_sumrel', $data->sumrel);
            set_user_preference('checkmarkreport_showpoints', $data->showpoints);
            $groupings = empty($data->groupings) ? array(0) : $data->groupings;
            if (!is_array($groupings)) {
                $groupings = array($groupings);
            }
            $groups = empty($data->groups) ? array(0) : $data->groups;
            if (!is_array($groups)) {
                $groups = array($groups);
            }
            $users = empty($data->users) ? array(0) : $data->users;
            if (!is_array($users)) {
                $users = array($users);
            }
        } else {
            $groupings = optional_param_array('groupings', array(0), PARAM_INT);
            $groups = optional_param_array('groups', array(0), PARAM_INT);
            $users = optional_param_array('users', array(0), PARAM_INT);
            $mform->set_data(array('groupings' => $groupings,
                                   'groups'    => $groups,
                                   'users'     => $users));
        }

        if (empty($groupings)) {
            $groupings = array(0);
        }

        if (empty($groups)) {
            $groups = array(0);
        }

        if (empty($users)) {
            $users = array(0);
        }

        $mform->display();

        $arrays = http_build_query(array('groupings' => $groupings,
                                         'groups'    => $groups,
                                         'users'     => $users));
        $PAGE->set_url($PAGE->url.'&'.$arrays);

        $checkmarkreport = new local_checkmarkreport_useroverview($id, $groupings, $groups, $users);
        // Trigger the event!
        \local_checkmarkreport\event\useroverview_viewed::useroverview($course)->trigger();
    break;
    case 'userview':
        $checkmarkreport = new local_checkmarkreport_userview($id);
        // Trigger the event!
        \local_checkmarkreport\event\userview_viewed::userview($course)->trigger();
    break;
    case 'noaccess':
        $notification = $output->notification(get_string('noaccess', 'local_checkmarkreport'), 'notifyproblem');
        echo $output->box($notification, 'generalbox centered');
        echo $output->footer();
        die;
    break;
    default:
        $notification = $output->notification(get_string('incorrect_tab', 'local_checkmarkreport'),
                                              'notifyproblem');
        echo $output->box($notification, 'generalbox centered');
        echo $output->footer();
        die;
}

echo $output->render($checkmarkreport);

echo $output->footer();

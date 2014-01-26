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
 * Prints a list of all checkmarkreport instances in the given course (via id)
 *
 * @package       local_checkmarkreport
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2013 onwards TSC TU Vienna
 * @since         Moodle 2.5.3
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once dirname(dirname(dirname(__FILE__))).'/config.php';
require_once $CFG->dirroot.'/local/checkmarkreport/lib.php';
require_once $CFG->dirroot.'/local/checkmarkreport/reportfilterform.class.php';

$id = required_param('id', PARAM_INT);   // Course.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('local/checkmarkreport:view', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE);

add_to_log($course->id, 'checkmarkreport', 'view', 'index.php?id='.$course->id, '');

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/local/checkmarkreport/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_course($course);

// Get Tabs according to capabilities
$tabs = array();
$available_tabs = array();
if (has_capability('local/checkmarkreport:view_courseoverview', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE)) {
    $tabs[] = new tabobject('overview',
                           $CFG->wwwroot.'/local/checkmarkreport/index.php?id='.$id.
                           '&amp;tab=overview',
                           get_string('overview', 'local_checkmarkreport'),
                           get_string('overview_alt', 'local_checkmarkreport'),
                           false);
    $available_tabs[] = 'overview';
}

if (has_capability('local/checkmarkreport:view_students_overview', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE)) {
    $tabs[] = new tabobject('useroverview',
                           $CFG->wwwroot.'/local/checkmarkreport/index.php?id='.$id.
                           '&amp;tab=useroverview',
                           get_string('useroverview', 'local_checkmarkreport'),
                           get_string('useroverview_alt', 'local_checkmarkreport'),
                           false);
    $available_tabs[] = 'useroverview';
}

if (has_capability('local/checkmarkreport:view_own_overview', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE)) {
    $tabs[] = new tabobject('userview',
                           $CFG->wwwroot.'/local/checkmarkreport/index.php?id='.$id.
                           '&amp;tab=userview',
                           get_string('userview', 'local_checkmarkreport'),
                           get_string('userview_alt', 'local_checkmarkreport'),
                           false);
    $available_tabs[] = 'userview';
}

if (count($tabs) > 1) {
    $new_tab = optional_param('tab', null, PARAM_ALPHAEXT);
    if (!empty($new_tab)) {
        $tab = $new_tab;
    } else if (!isset($tab)) {
        $tab = current($available_tabs);
    }
} else if (count($tabs) == 1) {
    $tab = current($available_tabs);
} else {
    $tab = 'noaccess';
}

$PAGE->set_url('/local/checkmarkreport/index.php', array('id' => $id, 'tab' => $tab));
$PAGE->navbar->add(get_string('pluginname', 'local_checkmarkreport'),
                   new moodle_url('/local/checkmarkreport/index.php',
                                  array('id'=>$id)));
$PAGE->navbar->add(get_string($tab, 'local_checkmarkreport'),
                   new moodle_url('/local/checkmarkreport/index.php',
                                  array('id'=>$id, 'tab'=>$tab)));
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
        $checkmarkreport = new checkmarkreport_overview($id);
        $mform = new reportfilterform($PAGE->url, array('courseid'   => $id,
                                                        'hidegroups' => true));
        if ($data = $mform->get_data()) {
            set_user_preference('checkmarkreport_showgrade', $data->grade);
            set_user_preference('checkmarkreport_sumabs', $data->sumabs);
            set_user_preference('checkmarkreport_sumrel', $data->sumrel);
            set_user_preference('checkmarkreport_showpoints', $data->showpoints);
        }
        $mform->display();
    break;
    case 'useroverview':
        $checkmarkreport = new checkmarkreport_useroverview($id);
        $mform = new reportfilterform($PAGE->url, array('courseid'   => $id,
                                                        'hideinstances' => true));
        if ($data = $mform->get_data()) {
            set_user_preference('checkmarkreport_showgrade', $data->grade);
            set_user_preference('checkmarkreport_sumabs', $data->sumabs);
            set_user_preference('checkmarkreport_sumrel', $data->sumrel);
            set_user_preference('checkmarkreport_showpoints', $data->showpoints);
        }
        $mform->display();
    break;
    case 'userview':
        $checkmarkreport = new checkmarkreport_userview($id);
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

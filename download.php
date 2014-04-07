<?php
// This file is part of local_checkmarkreport for Moodle - http://moodle.org/
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
 * Serves download-files
 *
 * @package       local_checkmarkreport
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2012 onwards TSC TU Vienna
 * @since         Moodle 2.5.3
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once($CFG->dirroot.'/local/checkmarkreport/lib.php');

$id = required_param('id', PARAM_INT);   // Course.

$groupings = optional_param_array('groupings', array(0), PARAM_INT);
$groups = optional_param_array('groups', array(0), PARAM_INT);
$users = optional_param_array('users', array(0), PARAM_INT);
$instances = optional_param_array('checkmarks', array(0), PARAM_INT);
$showgrade = optional_param('showgrade', true, PARAM_BOOL);
$showabs = optional_param('showabs', true, PARAM_BOOL);
$showrel = optional_param('showrel', true, PARAM_BOOL);
$showpoints = optional_param('showpoints', false, PARAM_BOOL);
$format = optional_param('format', checkmarkreport::FORMAT_XLSX, PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

$coursecontext = get_context_instance(CONTEXT_COURSE, $course->id);

require_capability('local/checkmarkreport:view', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE);

add_to_log($course->id, 'checkmarkreport', 'view', 'index.php?id='.$course->id, '');

$PAGE->set_pagelayout('popup');
$arrays = http_build_query(array('groups'    => $groups,
                                 'users'     => $users,
                                 'instances' => $instances));
$PAGE->set_url('/local/checkmarkreport/download.php?'.$arrays,
               array('id'         => $id,
                     'showgrade'  => $showgrade,
                     'showabs'    => $showabs,
                     'showrel'    => $showrel,
                     'showpoints' => $showpoints,
                     'format'     => $format));

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
$output = $PAGE->get_renderer('local_checkmarkreport');
switch($tab) {
    case 'overview':
        $report = new checkmarkreport_overview($id, $groupings, $groups, $instances);
    break;
    case 'useroverview':
        $report = new checkmarkreport_useroverview($id, $groupings, $groups, $users);
    break;
    case 'userview':
        $report = new checkmarkreport_userview($id);
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

switch($format) {
    case checkmarkreport::FORMAT_XML:
        $report->get_xml();
    break;
    case checkmarkreport::FORMAT_TXT:
        $report->get_txt();
    break;
    case checkmarkreport::FORMAT_ODS:
        $report->get_ods();
    break;
    case checkmarkreport::FORMAT_XLS:
        $report->get_xls();
    break;
    default:
    case checkmarkreport::FORMAT_XLSX:
        $report->get_xlsx();
    break;
}
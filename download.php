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
 * Serves download-files
 *
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

$groupings = optional_param_array('groupings', array(0), PARAM_INT);
$groups = optional_param_array('groups', array(0), PARAM_INT);
$users = optional_param_array('users', array(0), PARAM_INT);
$instances = optional_param_array('checkmarks', array(0), PARAM_INT);
$showgrade = optional_param('showgrade', true, PARAM_BOOL);
$showabs = optional_param('showabs', true, PARAM_BOOL);
$showrel = optional_param('showrel', true, PARAM_BOOL);
$showpoints = optional_param('showpoints', false, PARAM_BOOL);
$format = optional_param('format', local_checkmarkreport_base::FORMAT_XLSX, PARAM_INT);

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_course_login($course);

$coursecontext = context_course::instance($course->id);

require_capability('local/checkmarkreport:view', $coursecontext, $USER->id, CHECKMARKREPORT_GODMODE);

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

switch($format) {
    case local_checkmarkreport_base::FORMAT_XML:
        $formatreadable = 'XML';
    break;
    case local_checkmarkreport_base::FORMAT_TXT:
        $formatreadable = 'TXT';
    break;
    case local_checkmarkreport_base::FORMAT_ODS:
        $formatreadable = 'ODS';
    break;
    default:
    case local_checkmarkreport_base::FORMAT_XLSX:
        $formatreadable = 'XLSX';
    break;
}

switch($tab) {
    case 'overview':
        $report = new local_checkmarkreport_overview($id, $groupings, $groups, $instances);
        $event = \local_checkmarkreport\event\overview_exported::overview($course, $format, $formatreadable);
    break;
    case 'useroverview':
        $report = new local_checkmarkreport_useroverview($id, $groupings, $groups, $users);
        $event = \local_checkmarkreport\event\useroverview_exported::useroverview($course, $format, $formatreadable);
    break;
    case 'userview':
        $report = new local_checkmarkreport_userview($id);
        $event = \local_checkmarkreport\event\userview_exported::userview($course, $format, $formatreadable);
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

$event->trigger();

switch($format) {
    case local_checkmarkreport_base::FORMAT_XML:
        $report->get_xml();
    break;
    case local_checkmarkreport_base::FORMAT_TXT:
        $report->get_txt();
    break;
    case local_checkmarkreport_base::FORMAT_ODS:
        $report->get_ods();
    break;
    default:
    case local_checkmarkreport_base::FORMAT_XLSX:
        $report->get_xlsx();
    break;
}
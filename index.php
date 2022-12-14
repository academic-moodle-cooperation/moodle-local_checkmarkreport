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
 * Shows reports and filter forms
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\report_helper;

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->dirroot . '/local/checkmarkreport/lib.php');

$id = required_param('id', PARAM_INT);   // Course.

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);

require_course_login($course);

$coursecontext = context_course::instance($course->id);

require_capability('local/checkmarkreport:view', $coursecontext, $USER->id);

$PAGE->set_pagelayout('incourse');
$PAGE->set_url('/local/checkmarkreport/index.php', ['id' => $id]);
// We have to override the active node due to later $PAGE->set_url calls with included tab-parameter!
navigation_node::override_active_url(new moodle_url('/local/checkmarkreport/index.php', ['id' => $id]));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);
$PAGE->set_course($course);

// Get Tabs according to capabilities!
list($tabs, $availabletabs, $tab) = local_checkmarkreport_get_tabs($coursecontext, $id);

$PAGE->set_url('/local/checkmarkreport/index.php', ['id' => $id, 'tab' => $tab]);
$output = $PAGE->get_renderer('local_checkmarkreport');

echo $output->header();
$pluginname = get_string('pluginname', 'local_checkmarkreport');
report_helper::print_report_selector($pluginname);

if (count($tabs) > 1) {
    echo print_tabs([$tabs], $tab, $tab, [], true);
}

if (!$checkmarks = get_all_instances_in_course('checkmark', $course)) {
    notice(get_string('nocheckmarks', 'checkmark'), new moodle_url('/course/view.php', ['id' => $course->id]));
}
if ($tab == 'overview') {
    $customdata = [
            'courseid' => $id,
            'hideusers' => true
    ];
} else if ($tab == 'useroverview') {
    $customdata = [
            'courseid' => $id,
            'hideinstances' => true,
            'header' => get_string('additional_information', 'local_checkmarkreport')
    ];
}
if ($tab == 'overview' || $tab == 'useroverview') {
    $mform = new local_checkmarkreport_reportfilterform($PAGE->url, $customdata, 'get');
    if ($data = $mform->get_data()) {
        set_user_preference('checkmarkreport_showexamples', $data->showexamples);
        set_user_preference('checkmarkreport_showgrade', $data->grade);
        set_user_preference('checkmarkreport_sumabs', $data->sumabs);
        set_user_preference('checkmarkreport_sumrel', $data->sumrel);
        set_user_preference('checkmarkreport_showpoints', $data->showpoints);
        set_user_preference('checkmarkreport_showattendances', $data->showattendances);
        set_user_preference('checkmarkreport_showpresentationgrades', $data->showpresentationgrades);
        set_user_preference('checkmarkreport_showpresentationcount', $data->showpresentationsgraded);
        set_user_preference('checkmarkreport_signature', $data->signature);
        set_user_preference('checkmarkreport_seperatenamecolumns', $data->seperatenamecolumns);
        $groupings = empty($data->groupings) ? [0] : $data->groupings;
        if (!is_array($groupings)) {
            $groupings = [$groupings];
        }
        $groups = empty($data->groups) ? [0] : $data->groups;
        if (!is_array($groups)) {
            $groups = [$groups];
        }
        if ($tab == 'overview') {
            $instances = $data->instances;
        } else {
            $users = empty($data->users) ? [0] : $data->users;
            if (!is_array($users)) {
                $users = [$users];
            }
        }
        if (empty($groupings)) {
            $groupings = [0];
        }

        if (empty($groups)) {
            $groups = [0];
        }

        if (empty($users)) {
            $users = [0];
        }
    } else {
        $groupings = optional_param_array('groupings', [0], PARAM_INT);
        $groups = optional_param_array('groups', [0], PARAM_INT);
        if ($tab == 'overview') {
            $instances = optional_param_array('instances', [0], PARAM_INT);
            $mform->set_data([
                    'groupings' => $groupings,
                    'instances' => $instances
            ]);
        } else {
            $users = optional_param_array('users', [0], PARAM_INT);
            $mform->set_data([
                    'groupings' => $groupings,
                    'groups' => $groups,
                    'users' => $users
            ]);
        }
    }
    if ($tab == 'overview') {
        $arrays = http_build_query([
                'groupings' => $groupings,
                'groups' => $groups,
                'instances' => $instances
        ]);
    } else {
        $arrays = http_build_query([
                'groupings' => $groupings,
                'groups' => $groups,
                'users' => $users
        ]);
    }
    $PAGE->set_url($PAGE->url.'&'.$arrays);
    $mform->display();
}

switch ($tab) {
    case 'overview':
        $checkmarkreport = new local_checkmarkreport_overview($id, $groupings, $groups, $instances);
        // Trigger the event!
        \local_checkmarkreport\event\overview_viewed::overview($course)->trigger();
        break;
    case 'useroverview':
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

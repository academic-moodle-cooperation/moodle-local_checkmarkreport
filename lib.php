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
 * Library of interface functions and constants for module checkmarkreport
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 * All the checkmarkreport specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package   local_checkmarkreport
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Function injects navigation node linking to current courses checkmarkreport in navigation tree near grades node!
 *
 * @param global_navigation $nav Global navigation object
 *
 * @return void
 */
function local_checkmarkreport_extend_navigation(global_navigation $nav) {
    return;
}

/**
 * Funcction generates checkmarkreport entry in the main course navigation
 *
 * @param navigation_node $parentnode Global navigation object
 * @param stdClass $course Course object
 * @param context_course $context Course context
 * @return void
 * @throws coding_exception
 * @throws moodle_exception
 */
function local_checkmarkreport_extend_navigation_course(navigation_node $parentnode,
                                                        stdClass        $course, context_course $context) {
    // Find appropriate key where our link should come. Probably won't work, but at least try.
    global $PAGE, $USER;

    if (!$PAGE->course or $PAGE->course->id == SITEID) {
        return;
    }

    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('local/checkmarkreport:view', context_course::instance($PAGE->course->id), $USER->id)) {
        return;
    }

    // This is super fast!
    $modinfo = get_fast_modinfo($PAGE->course, -1);
    if (empty($modinfo->instances['checkmark'])) {
        return;
    }

    $keys = [
        'downloadcenter' => navigation_node::TYPE_SETTING,
        'competencies' => navigation_node::TYPE_CONTAINER,
        'unenrolself' => navigation_node::TYPE_SETTING,
        'fitlermanagement' => navigation_node::TYPE_SETTING
    ];
    $beforekey = null;
    foreach ($keys as $key => $type) {
        $list = $parentnode->children;
        if ($foundnode = $parentnode->find($key, $type)) {
            $beforekey = $key;
            break;
        }
    }

    // Prepare our node!
    $url = new moodle_url('/local/checkmarkreport/index.php', ['id' => $PAGE->course->id]);
    $icon = new pix_icon('i/report', get_string('pluginname', 'local_checkmarkreport'));
    $node = navigation_node::create(get_string('pluginname', 'local_checkmarkreport'),
        $url,
        navigation_node::TYPE_CUSTOM,
        get_string('pluginname', 'local_checkmarkreport'),
        'checkmarkreport' . $PAGE->course->id,
        $icon);

    if ($childnode = $parentnode->find('coursereports', \navigation_node::TYPE_CONTAINER)) {
        $node = $childnode->add_node($node);
    } else {
        $node = $parentnode->add_node($node, $beforekey);
    }
    $node->nodetype = navigation_node::TYPE_SETTING;
    $node->collapse = true;
    $node->add_class('checkmarkreportlink');

}

/**
 * Return the tab data for the checkmarkreport
 *
 * @param context_course $coursecontext
 * @param int $id course ID
 *
 * @return mixed[] array with all the tab data
 */
function local_checkmarkreport_get_tabs($coursecontext, $id) {
    global $USER, $CFG;

    $tabs = [];
    $availabletabs = [];
    if (has_capability('local/checkmarkreport:view_courseoverview', $coursecontext, $USER->id)) {
        $tabs[] = new tabobject('overview',
                $CFG->wwwroot . '/local/checkmarkreport/index.php?id=' . $id .
                '&amp;tab=overview',
                get_string('overview', 'local_checkmarkreport'),
                get_string('overview_alt', 'local_checkmarkreport'),
                false);
        $availabletabs[] = 'overview';
    }

    if (has_capability('local/checkmarkreport:view_students_overview', $coursecontext, $USER->id)) {
        $tabs[] = new tabobject('useroverview',
                $CFG->wwwroot . '/local/checkmarkreport/index.php?id=' . $id .
                '&amp;tab=useroverview',
                get_string('useroverview', 'local_checkmarkreport'),
                get_string('useroverview_alt', 'local_checkmarkreport'),
                false);
        $availabletabs[] = 'useroverview';
    }

    // Here we check for capability and enrolment, to prevent admins from getting empty views or errors!
    if (has_capability('local/checkmarkreport:view_own_overview', $coursecontext, $USER->id)
            && is_enrolled($coursecontext, null, 'local/checkmarkreport:view_own_overview', true)) {
        $tabs[] = new tabobject('userview',
                $CFG->wwwroot . '/local/checkmarkreport/index.php?id=' . $id .
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

    return [$tabs, $availabletabs, $tab];
}

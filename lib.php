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
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/** CHECKMARKREPORT_GODMODE is used to determine whether admins should have all capabilities by default **/
define('CHECKMARKREPORT_GODMODE', true);

/**
 * Inject new menu element in course administration linking to checkmark report!
 *
 * @param settings_navigation $setnav settins navigation object
 */
function local_checkmarkreport_extend_settings_navigation(settings_navigation $setnav) {
    global $PAGE, $USER;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == SITEID) {
        return;
    }

    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('local/checkmarkreport:view', context_course::instance($PAGE->course->id),
                        $USER->id, CHECKMARKREPORT_GODMODE)) {
        return;
    }
    $checkmarks = get_all_instances_in_course('checkmark', $PAGE->course);
    // Add link only if checkmarks are available in course!
    if (empty($checkmarks)) {
        return;
    }
    // Prepare our node!
    $url = new moodle_url('/local/checkmarkreport/index.php', array('id' => $PAGE->course->id));
    $icon = new pix_icon('i/report', get_string('pluginname', 'local_checkmarkreport'));
    $node = $setnav->create(get_string('pluginname', 'local_checkmarkreport'),
                                       $url,
                                       navigation_node::TYPE_CUSTOM,
                                       get_string('pluginname', 'local_checkmarkreport'),
                                       'checkmarkreport',
                                       $icon);
    // Find courseadmin!
    if ($courseadmin = $setnav->get('courseadmin')) {

        $iterator = $courseadmin->children->getIterator();

        // Find child grades!
        while ($iterator->valid() && ($iterator->current()->key != 'grades')) {
            $iterator->next();
        }
        $key = $iterator->current()->key;
        $iterator->next();
        if ($iterator->current() != null) {
            $key = $iterator->current()->key;
            // Add before!
            $courseadmin->children->add($node, $key);
        } else {
            // Add as last if there's no node after Grades!
            $courseadmin->children->add($node);
        }
    } // Otherwise there's no courseadmin menu here!

    return;
}

/**
 * Return the tab data for the checkmarkreport
 *
 * @return mixed[] array with all the tab data
 */
function local_checkmarkreport_get_tabs($coursecontext, $id) {
    global $USER, $CFG;

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

    return array($tabs, $availabletabs, $tab);
}
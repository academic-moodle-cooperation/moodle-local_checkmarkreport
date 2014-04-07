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

define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);   // Course.
$userid   = required_param('userid', PARAM_INT);
$groupings   = required_param_array('groupings', PARAM_INT);
$lang     = optional_param('lang', 'en', PARAM_LANG);

// We don't actually modify the session here as we have NO_MOODLE_COOKIES set.
$SESSION->lang = $lang;

// If session has expired and its an ajax request so we cant do a page redirect!
if (!isloggedin()) {
    $result = new stdClass();
    $result->error = get_string('sessionerroruser', 'error');
    echo json_encode($result);
    die();
}
require_course_login($courseid, true, null, true, true);

$context = get_context_instance(CONTEXT_COURSE, $courseid);

require_capability('local/checkmarkreport:view', $context, $userid);

$groupselects = array();
foreach ($groupings as $curgrpg) {
    $groups = groups_get_all_groups($courseid, 0, $curgrpg);
    foreach ($groups as $group) {
        $groupselects[$group->id] = $group;
    }
}
$allgroups = new stdClass();
$allgroups->name = get_string('all').' '.get_string('groups');
$allgroups->id = 0;
array_unshift($groupselects, $allgroups);
header('Content-type: application/json');
echo json_encode($groupselects);
die;

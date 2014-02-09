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

define('AJAX_SCRIPT', true);
require_once(dirname(__FILE__) . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);   // Course.
$userid   = required_param('userid', PARAM_INT);
$groups   = required_param_array('groups', PARAM_INT);
$lang     = optional_param('lang', 'en', PARAM_LANG);

// We don't actually modify the session here as we have NO_MOODLE_COOKIES set.
$SESSION->lang = $lang;

//if session has expired and its an ajax request so we cant do a page redirect
if( !isloggedin() ){
    $result = new stdClass();
    $result->error = get_string('sessionerroruser', 'error');
    echo json_encode($result);
    die();
}
require_course_login($courseid, true, NULL, true, true);

$context = get_context_instance(CONTEXT_COURSE, $courseid);

require_capability('local/checkmarkreport:view', $context, $userid);

$userselects = array();
foreach($groups as $curgrp) {
    $users = get_enrolled_users($context, '', $curgrp, 'u.*', 'lastname ASC');
    foreach($users as $user) {
        $userselects[$user->id] = new stdClass();
        $userselects[$user->id]->name = fullname($user);
        $userselects[$user->id]->id = $user->id;
    }
}
$allusers = new stdClass();
$allusers->name = get_string('all').' '.get_string('users');
$allusers->id = 0;
array_unshift($userselects, $allusers);
header('Content-type: application/json');
echo json_encode($userselects);
die;

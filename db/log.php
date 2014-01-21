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
 * Definition of log events
 *
 * NOTE: this is an example how to insert log event during installation/update.
 * It is not really essential to know about it, but these logs were created as example
 * in the previous 1.9.
 *
 * @package       mod_checkmarkreport
 * @author        Philipp Hager (e0803285@gmail.com)
 * @copyright     2012 onwards TSC TU Vienna
 * @since         Moodle 2.2.1+ (Build: 20120127)
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $DB;
/* It's not fully compatible with core this way so we should use standard style.
 * @todo using standard-action-pattern add/delete/update/view + additional info
 * If we don't, our filter-abilities in log-view are very restricted!
 */
$logs = array(
        array('module'   => 'checkmarkreport',
              'action'   => 'view',
              'mtable'   => 'course',
              'field'    => 'name'),
        array('module'   => 'checkmarkreport',
              'action'   => 'view overview',
              'mtable'   => 'course',
              'field'    => 'name'),
        array('module'   => 'checkmarkreport',
              'action'   => 'view useroverview',
              'mtable'   => 'course',
              'field'    => 'name'),
        array('module'   => 'checkmarkreport',
              'action'   => 'view userview',
              'mtable'   => 'course',
              'field'    => 'name'),
        array('module'   => 'checkmarkreport',
              'action'   => 'download overview',
              'mtable'   => 'course',
              'field'    => 'name'),
        array('module'   => 'checkmarkreport',
              'action'   => 'download useroverview',
              'mtable'   => 'course',
              'field'    => 'name'),
        array('module'   => 'checkmarkreport',
              'action'   => 'download userview',
              'mtable'   => 'course',
              'field'    => 'name'),
);


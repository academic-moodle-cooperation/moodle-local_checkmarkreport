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
 * Defines the version of checkmarkreport
 *
 * This code fragment is called by moodle_needs_upgrading() and
 * /admin/index.php
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2015071500;                                // The current module version (Date: YYYYMMDDXX).
$plugin->release = '2015-07-15 (00)';                           // User-friendly version number.
$plugin->maturity = MATURITY_STABLE;
$plugin->requires  = 2014041100;                                // Requires this Moodle version!
$plugin->component = 'local_checkmarkreport';                   // To check on upgrade, that module sits in correct place.
$plugin->dependencies = array('mod_checkmark' => 2015011400);   // FRANKENSTYLE + version number or ANY_VERSION.

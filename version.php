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
// If not, see <http://www.gnu.org/licenses/>.

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

$plugin->version   = 2014011004;              // The current module version (Date: YYYYMMDDXX).
$plugin->requires  = 2013111802.00;           // Requires this Moodle version!
                                              // 2.6.2 (Build: 20140310)
$plugin->cron      = 0;                       // Period for cron to check this module (secs).
$plugin->component = 'local_checkmarkreport'; // To check on upgrade, that module sits in correct place.

$plugin->maturity = MATURITY_ALPHA;
/*MATURITY_ALPHA, MATURITY_BETA, MATURITY_RC, MATURITY_STABLE*/
$plugin->release = 'TODO';
$plugin->dependencies = array('mod_checkmark' => 2013062000/*, 'mod_data' => ANY_VERSION*/);

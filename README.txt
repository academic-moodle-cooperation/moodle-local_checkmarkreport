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
 * README.txt
 * @version       2016-03-14
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Eva Karall (eva.maria.karall@univie.ac.at)
 * @author        Philipp Hager
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

# ---------------------------------------------------------------
# FOR Moodle 3.0+
# ---------------------------------------------------------------

Checkmarkreport plugin
===============

OVERVIEW
================================================================================


REQUIREMENTS
================================================================================
    Moodle 3.0 or later

INSTALLATION
================================================================================
    To install, extract the contents of the archive to the local/ folder in the moodle
    root folder, and all of the archive's contents will be properly placed into the
    folder structure. The module and all of its files is located in local_checkmarkreport
    folder and requires checkmark module (for Moodle 2.7) to be installed.

    The langfiles can be put into the folder local/checkmarkreport/lang normally.
    All languages should be encoded with utf-8.

    After it you have to run the admin-page of moodle (http://your-moodle-site/admin)
    in your browser. You have to be logged in as admin before.
    The installation process will be displayed on the screen. All the data necessary
    for a proper install is contained in the help files displayed on screen.

CHANGELOG
================================================================================
v 2016031401
-------------------------
*) Codechecker cleanup
*) Update to 3.0!

v 2016012001
-------------------------
*) fix another bug in calculation of gradesums

v 2016012000
-------------------------
*) prevent overview without selected instances
*) fix bug in calculation of gradesums in useroverview
*) remove auto-submit from filterform
*) changes due to split submissions and feedbacks tables in checkmark
*) fix divided by 0 warning for instances with empty grades

v 2015121700
-------------------------
*) prevent collapsed columns from being exported (just hidden in ODS and XLSX)
*) update for 2.9 compatibility

v 2015112700
-------------------------
*) fix multiple users with the same name corrupting ODS files
*) fix hard coded site ID 1 with constant SITEID
*) replace local_checkmarkreport_extends_settings_navigation with unified API
*) rewrite JS as AMD modules and use AMD-Modules/JQuery when possible
*) add a bunch of PHPDoc comments and improve code quality (codechecker)

v 2015071500
-------------------------
*) improve coding style (codechecker)
*) ensure css selectors are scoped to only affect checkmarkreport
*) remove empty and unused files
*) make use of automatic class loading - therefore refactoring some classes

v 2015061000
-------------------------
*) fixed typo in CSS destroying themes
*) update for 2.8 compatibility
*) remove XLS support and deprecate some strings

v 2015011400
-------------------------
*) Replace add_to_log with triggered events
*) Add support for PostgreSQL-DBs
*) Fix hidden columns not being restoreable
*) Strip HTML-tags from link titles in table
*) Implement gradebook-support and show locked/overwritten grades
*) Improve layout for small browser windows
*) Fixed some minor bugs

v 2014101900
-------------------------
    -) improve logging
    -) various bugs fixed

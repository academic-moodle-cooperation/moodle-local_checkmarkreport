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
 * local_checkmarkreport_useroverview class, handles checkmarkreport useroverview content
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

class local_checkmarkreport_userview extends local_checkmarkreport_useroverview implements renderable {

    protected $tableclass = 'table table-condensed table-hover table-striped userview';

    public function __construct($id) {
        global $USER;
        set_user_preference('checkmarkreport_showgrade', 1);
        set_user_preference('checkmarkreport_sumabs', 1);
        set_user_preference('checkmarkreport_sumrel', 1);
        parent::__construct($id, array(0), array(0), array($USER->id));
    }
}

<?php
// This file is part of mod_checkmark for Moodle - http://moodle.org/
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
 * File for custom behat steps used in local_checkmarkreport
 *
 * @package   mod_checkmark
 * @category  test
 * @author    Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

/**
 * local_checkmarkreport custom behat steps
 *
 * @package   mod_checkmark
 * @category  test
 * @author    Daniel Binder
 * @copyright 2020 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.


class behat_local_checkmarkreport extends behat_base {

    /**
     * Checks if the md5 checksums of two given files match
     *
     * @Then /^following "(?P<link_string>[^"]*)" should have matching checksums with the file "(?P<filepath_string>(?:[^"]|\\")*)"$/
     * @throws Exception
     * @param $originalfile
     * @param $testfile
     */
    public function checksums_of_two_files_match ($originalfile, $testfilelink) {
        $behatgeneralcontext = behat_context_helper::get('behat_general');
        $testfile = $behatgeneralcontext->download_file_from_link($testfilelink);
        var_dump($testfile);
        if (md5_file($originalfile) !== md5_file($testfile)) {
            throw new Exception("The md5 checksums of the given files do not match!");
        }
    }

}
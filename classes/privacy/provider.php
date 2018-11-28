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

/**
 * Privacy class for requesting user data.
 *
 * @package    mod_checkmarkreport
 * @copyright  2018 Academic Moodle Cooperation
 * @author     Philipp Hager <philipp.hager@tuwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_checkmarkreport\privacy;

defined('MOODLE_INTERNAL') || die();

use \core_privacy\local\metadata\provider as metadataprovider;
use \core_privacy\local\request\user_preference_provider as preference_provider;
use \core_privacy\local\metadata\collection;
use \core_privacy\local\request\writer;
use \core_privacy\local\request\transform;
use \coding_exception;
use \dml_exception;


/**
 * Privacy class for requesting user data.
 *
 * @package    mod_checkmarkreport
 * @copyright  2018 Academic Moodle Cooperation
 * @author     Philipp Hager <philipp.hager@tuwien.ac.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements metadataprovider, preference_provider {
    /**
     * @return array
     * @throws coding_exception
     */
    public static function get_preferences() {
        return [
            'checkmarkreport_showexamples' => [
                'string' => 'privacy:metadata:showexamples',
                'bool' => true
            ],
            'checkmarkreport_showgrade' => [
                'string' => 'privacy:metadata:showgrade',
                'bool' => true
            ],
            'checkmarkreport_sumabs' => [
                'string' => 'privacy:metadata:sumabs',
                'bool' => true
            ],
            'checkmarkreport_sumrel' => [
                'string' => 'privacy:metadata:sumrel',
                'bool' => true
            ],
            'checkmarkreport_showpoints' => [
                'string' => 'privacy:metadata:showpoints',
                'bool' => true
            ],
            'checkmarkreport_showattendances' => [
                'string' => 'privacy:metadata:showattendances',
                'bool' => true
            ],
            'checkmarkreport_showpresentationgrades' => [
                'string' => 'privacy:metadata:showpresentationgrades',
                'bool' => true
            ],
            'checkmarkreport_showpresentationcounts' => [
                'string' => 'privacy:metadata:showpresentationcounts',
                'bool' => true
            ],
            'checkmarkreport_signature' => [
                'string' => 'privacy:metadata:signature',
                'bool' => true
            ]
        ];
    }

    /**
     * Provides meta data that is stored about a user with local_checkmarkreport
     *
     * @param  collection $collection A collection of meta data items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection) : collection {

        $prefs = static::get_preferences();
        foreach ($prefs as $key => $pref) {
            $collection->add_user_preference($key, $pref['string']);
        }

        return $collection;
    }

    /**
     * Stores the user preferences related to local_checkmarkreport.
     *
     * @param int $userid The user ID that we want the preferences for.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function export_user_preferences(int $userid) {
        $context = \context_system::instance();

        foreach (static::get_preferences() as $key => $preference) {
            $value = get_user_preferences($key, null, $userid);
            if ($value === null) {
                // Export only stored preferences!
                continue;
            }
            if ($preference['bool']) {
                $value = transform::yesno($value);
            }
            if (isset($value)) {
                writer::with_context($context)->export_user_preference('local_checkmarkreport', $key, $value,
                        get_string($preference['string'], 'local_checkmarkreport'));
            }
        }
    }
}

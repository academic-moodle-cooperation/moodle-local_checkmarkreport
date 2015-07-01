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
 * The local_checkmarkreport_exported_base event.
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_checkmarkreport\event;
defined('MOODLE_INTERNAL') || die();

abstract class exported_base extends \core\event\base {
    /**
     * Init method.
     *
     * Please override this in extending class and specify objecttable.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' exported the checkmarkreport '".$this->data['other']['tab'].
               "' for the course with the "."id '$this->contextinstanceid' as '".$this->data['other']['format_readable']."'.";
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventexported', 'local_checkmarkreport');
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url("/local/checkmarkreport/download.php", array('id' => $this->contextinstanceid,
                                                                            'tab' => $this->data['other']['tab'],
                                                                            'format' => $this->data['other']['format']));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, $this->objecttable, 'export',
                     "download.php?id=".$this->contextinstanceid."&tab=".$this->data['other']['tab'].
                     "&format=".$this->data['other']['format'],
                     get_string($this->data['other']['tab'],
                     'local_checkmarkreport').' '.$this->data['other']['format_readable'], $this->contextinstanceid);
    }

    /**
     * Custom validation.
     *
     * @throws \coding_exception
     * @return void
     */
    protected function validate_data() {
        parent::validate_data();

        // Make sure the context level is set to module.
        if ($this->contextlevel != CONTEXT_COURSE) {
            throw new \coding_exception('Context level must be CONTEXT_COURSE.');
        }

        if (!key_exists('format', $this->data['other'])) {
            throw new \coding_exception('Format has to be specified in event!');
        }

        if (!key_exists('format_readable', $this->data['other'])) {
            throw new \coding_exception('Readable format has to be specified in event!');
        }

        if (!key_exists('tab', $this->data['other'])) {
            throw new \coding_exception('Tab has to be specified in event!');
        }
    }
}
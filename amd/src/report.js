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
 * report.js
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module local_checkmarkreport/report
  */
define(['jquery', 'jqueryui', 'core/str', 'core/log'], function($, jqueryui, str, log) {

    /**
     * @constructor
     * @alias module:local_checkmarkreport/report
     */
    var Report = function() {
        /**
         * @var object table object to use
         */
        this.table = {};
    };

    var instance = new Report();

    /**
     * Initialises the JavaScript for the checkmark report
     *
     *
     * @param {Object} config The configuration
     */
    instance.initializer = function(config) {
        this.table = $(config.id);

        log.info('Initialize report JS!', 'local_checkmarkreport');

        var tofetch = [{key: 'overwritten', component: 'local_checkmarkreport'},
                       {key: 'by', component: 'local_checkmarkreport'}];
        str.get_strings(tofetch).done(function(s) {

            log.info('Successfully acquired strings: ' + s, 'local_checkmarkreport');
            log.info('Register tooltips!', 'local_checkmarkreport');

            $( ".path-local-checkmarkreport #checkmarkreporttable" ).tooltip({
                items: ".current",
                track: true,
                content: function() {
                    var element = $( this );

                    var dategraded = element.data('dategraded');
                    var grader = element.data('grader');

                    var content = '<div class="checkmarkreportoverlay" role="tooltip" ';
                    content += 'aria-describedby="' + element.attr('id') + '">';
                    content += s[0]; // Is string 'overwritten' from 'local_checkmarkreport'!

                    if ( !element.is( "[data-username][data-grader][data-dategraded]" ) ) {
                        return content + '</div>';
                    }

                    if (grader !== '') {
                        content += '<div class="fullname">' + s[1] + // Is string 'by' from 'local_checkmarkreport']!
                                   '&nbsp;' + grader + '</div>';
                    }
                    if (dategraded !== '') {
                        content += '<div class="dategraded">' + dategraded + '</div>';
                    }

                    content += '</div>';

                    return content;
                }
            });
        }).fail(function(ex) {
            log.error("Error getting strings: " + ex, "local_checkmarkreport");
        });
    };

    return instance;
});
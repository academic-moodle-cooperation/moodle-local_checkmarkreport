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
 * filterform.js
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2015 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 /**
  * @module local_checkmarkreport/filterform
  */
define(['jquery', 'core/config', 'core/str', 'core/log'],
       function($, config, str, log) {

    /**
     * @constructor
     * @alias module:local_checkmarkreport/filterform
     */
    var Filterform = function() {
        /**
         * oldusers
         * @access public
         */
        this.oldusers = '';

        /**
         * ajaxloader
         * @access public
         */
        this.ajaxloader = false;

        /**
         * selectors
         * @access public
         */
        this.SELECTORS = {
            GROUPS : 'select#groups',
            GROUPINGS : 'select#groupings',
            MEMBERS : 'select#users',
        };
    };

    Filterform.prototype.get_members_ajaxurl = function (inst) {
        var ajaxurl = {
            url: config.wwwroot + '/local/checkmarkreport/getmembers.json.php',
            data: {
                courseid: $('input[name=id]').val(),
                userid: $('input[name=userid]').val(),
                groups: $(inst.SELECTORS.GROUPS).val()
            }
        };

        return ajaxurl;
    };

    Filterform.prototype.get_groups_ajaxurl = function (inst) {
        var ajaxurl = {
            url: config.wwwroot + '/local/checkmarkreport/getgroups.json.php',
            data: {
                courseid: $('input[name=id]').val(),
                userid: $('input[name=userid]').val(),
                groupings: $(inst.SELECTORS.GROUPINGS).val()
            }
        };

        return ajaxurl;
    };

    Filterform.prototype.update_members = function(e) {
        e.preventDefault();
        e.stopPropagation();

        var inst = e.data.inst;
        var strings = e.data.strings;
        var ajaxurl = inst.get_members_ajaxurl(inst);
        var membersselect = $(inst.SELECTORS.MEMBERS);

        if (!membersselect) {
            // Autosubmit form!
            this.closest('form')[0].submit();
            return;
        }

        // Get new groupsusers!
        var cfg = {
            method: "get",
            url: ajaxurl.url,
            data: ajaxurl.data,
            dataType: 'json',
            beforeSend: function () {
                // Log start and set loader image in users-select!
                log.info("Start members-request", "checkmarkreport");

                // Cache old inner HTML!
                this.oldusers = $(inst.SELECTORS.MEMBERS).children();
                //Delete old users
                var selectEl = $(inst.SELECTORS.MEMBERS);
                if (selectEl) {
                    $(inst.SELECTORS.MEMBERS).html('<option class=\"loading\" value=\"0\">' + strings.loading + '</option>');
                }
            },
            success: function (response, status) {
                //Insert them in users-select
                var members = "";
                for(var i = 0; i < response.length; i++) {
                    members += "<option value=\"" + response[i].id + "\">" + response[i].name + "</option>";
                }
                $(inst.SELECTORS.MEMBERS).html(members);
                log.info('Request success: ' + status, "checkmarkreport");
            },
            error: function (jqXHR, error) {
                log.error(error, "checkmarkreport");
                // Restore group-members and display error message?
                $(inst.SELECTORS.MEMBERS).html('<option value=\"0\">' + strings.all + ' ' + strings.members +
                                               strings.error + '</option>');
            }
        };

        log.info("Click detected; beginning io request users." + ajaxurl.url + ajaxurl.data, "checkmarkreport");
        $.ajax(cfg);
    };

    Filterform.prototype.update_groups = function(e) {
        e.preventDefault();
        e.stopPropagation();
        var inst = e.data.inst;
        var strings = e.data.strings;
        var groupsselect = $(inst.SELECTORS.GROUPS);
        var ajaxurl = inst.get_groups_ajaxurl(inst);

        if (!groupsselect) {
            // Autosubmit form!
            this.closest('form')[0].submit();
            return;
        }

        // Get new groupsusers!
        var cfg = {
            method: "GET",
            url: ajaxurl.url,
            data: ajaxurl.data,
            dataType: 'json',
            beforeSend: function () {
                // Log start and set loader image in groups-select!
                log.info("Start AJAX Call", "checkmarkreport");

                // Cache old inner HTML!
                inst.oldusers = $(inst.SELECTORS.GROUPS).get('children');
                // Delete old users!
                $(inst.SELECTORS.GROUPS).html('<option class=\"loading\" value=\"0\">' + strings.loading + '</option>');
            },
            success: function (data) {
                //Insert them in users-select
                var groups = "";
                for(var i = 0; i < data.length; i++) {
                    groups += "<option value=\"" + data[i].id + "\">" + data[i].name + "</option>";
                }
                $(inst.SELECTORS.GROUPS).html(groups);
            },
            error: function (error) {
                log.error(error, "example");
                // Restore group-members and display error message?
                $(inst.SELECTORS.GROUPS).html('<option value=\"0\">' + strings.all + ' ' + strings.groups + ' ' +
                                              strings.error + '</option>');
            }
        };

        log.info("Click detected; beginning io request groups." + ajaxurl.url + ajaxurl.data, "checkmarkreport");
        $.ajax(cfg);
    };

    var instance = new Filterform();

    instance.initializer = function(config) { //'config' contains the parameter values
        this.ajaxloader = config.ajaxloader;

        log.info('Initialize filterform JS', 'checkmarkreport');

        $(this.SELECTORS.GROUPS).on('change', null, this, function (e) {
            log.debug("Submit form!", "checkmarkreport");
            $(e.data.SELECTORS.GROUPS).closest('form')[0].submit();
        });
        if (this.AJAXLOADER) {
            // Get strings, remove other event handler and use the new one:
            str.get_strings([{key: 'loading', component: 'local_checkmarkreport'},
                             {key: 'all', component: ''},
                             {key: 'users', component: ''},
                             {key: 'error_retriefing_members', component: 'local_checkmarkreport'}]).done(function (s) {
                var tmp = {loading: s[0], all: s[1], members: s[2], error: s[3]};
                $(this.SELECTORS.GROUPS).on('change', null, {inst:this, strings:tmp}, this.update_members);
            }).fail(function (ex) {
                log.error("Error retrieving strings: " + ex, "checkmarkreport");
            });
        }

        $(this.SELECTORS.GROUPINGS).on('change', null, this, function (e) {
            $(e.data.SELECTORS.GROUPINGS).closest('form')[0].submit();
        });
        if (this.AJAXLOADER) {
            str.get_strings([{key: 'loading', component: 'local_checkmarkreport'},
                             {key: 'all', component: ''},
                             {key: 'groups', component: ''},
                             {key: 'error_retriefing_groups', component: 'local_checkmarkreport'}]).done(function (s) {
                var tmp = {loading: s[0], all: s[1], groups: s[2], error: s[3]};

                $(this.SELECTORS.GROUPINGS).on('change', null, {inst: this, strings:tmp}, this.update_groups);
            }).fail(function (ex) {
                log.error("Error retrieving strings: " + ex, "checkmarkreport");
            });
        }

        $(this.SELECTORS.MEMBERS).on('change', null, this, function (e) {
            $(e.data.SELECTORS.MEMBERS).closest('form')[0].submit();
        });
        if (this.AJAXLOADER) {
            $(this.SELECTORS.MEMBERS).on('change', null, this, this.update_data);
        }

        // Autosubmit form
        $('input[type=checkbox]').each(function (idx, taskNode) {
            $(taskNode).on('change', function () {
                $(taskNode).closest('form')[0].submit();
            });
        });

    };

    return instance;
});
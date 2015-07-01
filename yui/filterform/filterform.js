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
YUI.add('moodle-local_checkmarkreport-filterform', function(Y) {
    var MODULENAME = 'moodle-local_checkmarkreport-filterform';
    var oldusers = '';
    var filterform = function(Y) {
        filterform.superclass.constructor.apply(this, arguments);
    }

    var SELECTORS = {
            GROUPS : 'select#groups',
            GROUPINGS : 'select#groupings',
            MEMBERS : 'select#users',
        },
        CSS = {
        },
        WRAPPERS = {
        },
        ATTRS = {
            AJAXLOADER : false, // We reload the page by now, base for later ajax use is given.
        };

    Y.extend(filterform, Y.Base, {
        initializer : function(config) { //'config' contains the parameter values
            //gets called when it's going to be pluged in
            //add JS-Eventhandler for each tag
            if (Y.one(SELECTORS.GROUPS)) {
                if (ATTRS.AJAXLOADER) {
                    Y.one(SELECTORS.GROUPS).on('change', M.local_checkmarkreport.update_members);
                } else {
                    Y.one(SELECTORS.GROUPS).on('change', function () {
                        Y.one(SELECTORS.GROUPS).ancestor('form').submit();
                    });
                }
            }

            if (Y.one(SELECTORS.GROUPINGS)) {
                if (ATTRS.AJAXLOADER) {
                    Y.one(SELECTORS.GROUPINGS).on('change', M.local_checkmarkreport.update_groups);
                } else {
                    Y.one(SELECTORS.GROUPINGS).on('change', function () {
                        Y.one(SELECTORS.GROUPINGS).ancestor('form').submit();
                    });
                }
            }

            if (Y.one(SELECTORS.MEMBERS)) {
                if (ATTRS.AJAXLOADER) {
                    Y.one(SELECTORS.MEMBERS).on('change', M.local_checkmarkreport.update_data);
                } else {
                    Y.one(SELECTORS.MEMBERS).on('change', function () {
                        Y.one(SELECTORS.MEMBERS).ancestor('form').submit();
                    });
                }
            }

            var checkboxes = Y.all('input[type=checkbox]');
            if (checkboxes) {
                // Autosubmit form
                checkboxes.each(function (taskNode) {
                    taskNode.on('change', function (e) {taskNode.ancestor('form').submit()})}
                );
            }
        }

    }, {
        NAME : MODULENAME, //module name is something mandatory.
                                //It should be in lower case without space
                                //as YUI use it for name space sometimes.
        ATTRS : {
        } //Attributs are the parameters sent when the $PAGE->requires->yui_module calls the module.
          //Here you can declare default values or run functions on the parameter.
          //The param names must be the same as the ones declared
          //in the $PAGE->requires->yui_module call.

    });
    //this line use existing name path if it exists, ortherwise create a new one.
    //This is to avoid to overwrite previously loaded module with same name.
    M.local_checkmarkreport = M.local_checkmarkreport || {};

    M.local_checkmarkreport.memberssuccess = function (id, o, a) {
        //Parse data object
        //We use JSON.parse to sanitize the JSON (as opposed to simply performing an
        //JavaScript eval of the data):
        var data = Y.JSON.parse(o.responseText);
        //Insert them in users-select
        var members = "";
        for(var i = 0; i < data.length; i++) {
            members += "<option value=\"" + data[i].id + "\">" + data[i].name + "</option>";
        }
        Y.one(SELECTORS.MEMBERS).set('innerHTML', members);
    }

    M.local_checkmarkreport.groupssuccess = function (id, o, a) {
        //Parse data object
        //We use JSON.parse to sanitize the JSON (as opposed to simply performing an
        //JavaScript eval of the data):
        var data = Y.JSON.parse(o.responseText);
        //Insert them in users-select
        var groups = "";
        for(var i = 0; i < data.length; i++) {
            groups += "<option value=\"" + data[i].id + "\">" + data[i].name + "</option>";
        }
        Y.one(SELECTORS.GROUPS).set('innerHTML', groups);
    }

    M.local_checkmarkreport.get_members_ajaxurl = function () {
        var ajaxurl = M.cfg.wwwroot + '/local/checkmarkreport/getmembers.json.php?' +
                      'courseid=' + Y.one('input[name=id]').get('value') +
                      '&userid=' + Y.one('input[name=userid]').get('value');
        var groups = Y.one(SELECTORS.GROUPS);

        Y.one(SELECTORS.GROUPS).get("options").each( function() {
           // Here this refers option from the select!
           if (this.get('selected')) {
                ajaxurl += '&groups[]=' + this.get('value');
           }
           var text = this.get('text');
        });

        return ajaxurl;
    }

    M.local_checkmarkreport.get_groups_ajaxurl = function () {
        var ajaxurl = M.cfg.wwwroot + '/local/checkmarkreport/getgroups.json.php?' +
                      'courseid=' + Y.one('input[name=id]').get('value') +
                      '&userid=' + Y.one('input[name=userid]').get('value');
        var groupings = Y.one(SELECTORS.GROUPINGS);

        Y.one(SELECTORS.GROUPINGS).get("options").each( function() {
           // Here this refers option from the select!
           if (this.get('selected')) {
                ajaxurl += '&groupings[]=' + this.get('value');
           }
           var text = this.get('text');
        });

        return ajaxurl;
    }

    M.local_checkmarkreport.membersfailure = function (transaction, config) {
        Y.log("ERROR " + transaction + " " + config.statusText, "info", "example");
        // Restore group-members and display error message?
        Y.one(SELECTORS.MEMBERS).set('innerHTML', '<option value=\"0\">' +
                                         M.util.get_string('all') + ' ' + M.util.get_string('users') +
                                         M.util.get_string('error_retriefing_members', 'local_checkmarkreport') +
                                         '</option>');
    }

    M.local_checkmarkreport.membersstart = function (id, a) {
        // Log start and set loader image in users-select!
        Y.log("io:start firing.", "info", "example");

        // Cache old inner HTML!
        this.oldusers = Y.one(SELECTORS.MEMBERS).get('children');
        //Delete old users
        var selectEl = Y.one(SELECTORS.MEMBERS);
        if (selectEl) {
            while (selectEl.one('option')) {
                selectEl.removeChild(selectEl.one('option'));
            }
            Y.one(SELECTORS.MEMBERS).set('innerHTML',
                                         '<option class=\"loading\" value=\"0\">' +
                                         M.util.get_string('loading', 'local_checkmarkreport') +
                                         '</option>');
        }
    }

    M.local_checkmarkreport.update_members = function(e) {
        var membersselect = Y.one(SELECTORS.MEMBERS);
        e.preventDefault();
        e.stopPropagation();
        if (!membersselect) {
            // Autosubmit form!
            this.parent('form').submit();
            return;
        }

        // Get new groupsusers!
        var cfg = {
            method: "GET",
            xdr: {
                use:'native'
            },
            headers: { 'X-Transaction': 'GET Example'},
            on: {
                // Our event handlers previously defined.
                start: M.local_checkmarkreport.membersstart,
                success: M.local_checkmarkreport.memberssuccess,
                failure: M.local_checkmarkreport.membersfailure,
            }
        };

        Y.log("Click detected; beginning io request users." + M.local_checkmarkreport.get_members_ajaxurl(),
              "info", "Checkmarkreport FilterForm");
        Y.io(
            M.local_checkmarkreport.get_members_ajaxurl(),
            cfg
        );
    };

    M.local_checkmarkreport.groupsfailure = function (transaction, config) {
        Y.log("ERROR " + transaction + " " + config.statusText, "info", "example");
        // Restore group-members and display error message?
        Y.one(SELECTORS.GROUPS).set('innerHTML', '<option value=\"0\">' +
                                         M.util.get_string('all') + ' ' + M.util.get_string('groups') +
                                         M.util.get_string('error_retriefing_groups', 'local_checkmarkreport') +
                                         '</option>');
    }

    M.local_checkmarkreport.groupsstart = function (id, a) {
        // Log start and set loader image in groups-select!
        Y.log("io:start firing.", "info", "example");

        // Cache old inner HTML!
        this.oldusers = Y.one(SELECTORS.GROUPS).get('children');
        // Delete old users!
        var selectEl = Y.one(SELECTORS.GROUPS);
        if (selectEl) {
            while (selectEl.one('option')) {
                selectEl.removeChild(selectEl.one('option'));
            }
            Y.one(SELECTORS.GROUPS).set('innerHTML',
                                         '<option class=\"loading\" value=\"0\">' +
                                         M.util.get_string('loading', 'local_checkmarkreport') +
                                         '</option>');
        }
    }

    M.local_checkmarkreport.update_groups = function(e) {
        var groupsselect = Y.one(SELECTORS.GROUPS);
        e.preventDefault();
        e.stopPropagation();
        if (!groupsselect) {
            // Autosubmit form!
            this.parent('form').submit();
            return;
        }

        // Get new groupsusers!
        var cfg = {
            method: "GET",
            xdr: {
                use:'native'
            },
            headers: { 'X-Transaction': 'GET Example'},
            on: {
                // Our event handlers previously defined...
                start: M.local_checkmarkreport.groupsstart,
                success: M.local_checkmarkreport.groupssuccess,
                failure: M.local_checkmarkreport.groupsfailure,
            }
        };

        Y.log("Click detected; beginning io request groups." + M.local_checkmarkreport.get_groups_ajaxurl(),
              "info", "Checkmarkreport FilterForm");
        Y.io(
            M.local_checkmarkreport.get_groups_ajaxurl(),
            cfg
        );
    };

    // Here 'config' contains the parameter values...
    M.local_checkmarkreport.init_filterform = function(params) {
        return new filterform(params); // Here 'params' contains the parameter values...
    };
    // End of M.local_checkmark.init_filterform!

  }, '0.0.1', {
      requires:['base','node', 'event', 'io', 'json']
  });
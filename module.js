// This file is part of mod_grouptool for Moodle - http://moodle.org/
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
 * module.js
 *
 * @package       local_checkmarkreport
 * @author        Andreas Hruska (andreas.hruska@tuwien.ac.at)
 * @author        Katarzyna Potocka (katarzyna.potocka@tuwien.ac.at)
 * @author        Philipp Hager
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

M.local_checkmarkreport = {
    /**
     * @param {Array} reports An array of instantiated report objects
     */
    reports : [],
    /**
     * @namespace M.local_checkmarkreport
     * @param {Object} reports A collection of classes used by the checkmark report module
     */
    classes : {},
    /**
     * @param {Object} tooltip Null or a tooltip object
     */
    tooltip : null,
    /**
     * Instantiates a new checkmark report
     *
     * @function
     * @param {YUI} Y
     * @param {String} id The id attribute of the reports table
     * @param {Object} cfg A configuration object
     * @param {Array} An array of items in the report
     * @param {Array} An array of users on the report
     * @param {Array} An array of grade objects
     */
    init_report : function(Y, id, cfg, items, users, grade) {
        this.tooltip = this.tooltip || {
            overlay: null, // Y.Overlay instance
            /**
             * Attaches the tooltip event to the provided cell
             *
             * @function M.local_checkmarkreport.tooltip.attach
             * @this M.local_checkmarkreport
             * @param {Y.Node} td The cell to attach the tooltip event to
             */
            attach : function(td, report) {
                td.on('mouseenter', this.show, this, report);
            },
            /**
             * Shows the tooltip: Callback from @see M.local_checkmarkreport.tooltip#attach
             *
             * @function M.local_checkmarkreport.tooltip.show
             * @this {M.local_checkmarkreport.tooltip}
             * @param {Event} e
             * @param {M.local_checkmarkreport.classes.report} report
             */
            show : function(e, report) {
                e.halt();

                var properties = report.get_cell_info(e.target);
                if (!properties) {
                    return;
                }

                var content = '<div class="checkmarkreportoverlay" role="tooltip" aria-describedby="' + properties.id + '">';
                content += M.util.get_string('overwritten', 'local_checkmarkreport');

                if (properties.grader != '') {
                    content += '<div class="fullname">' + M.util.get_string('by', 'local_checkmarkreport') + '&nbsp;' +
                               properties.grader + '</div>';
                }
                if (properties.dategraded != '') {
                    content += '<div class="dategraded">' + properties.dategraded + '</div>';
                }
                content += '</div>';

                properties.cell.on('mouseleave', this.hide, this, properties.cell);
                properties.cell.addClass('tooltipactive');

                this.overlay = this.overlay || (function(){
                    var overlay = new Y.Overlay({
                        bodyContent : 'Loading',
                        visible : false,
                        zIndex : 2
                    });
                    overlay.render(report.table.ancestor('div'));
                    return overlay;
                })();
                this.overlay.set('xy', [e.target.getX() + (e.target.get('offsetWidth') / 2),
                                        e.target.getY() + e.target.get('offsetHeight') - 5]);
                this.overlay.set("bodyContent", content);
                this.overlay.show();
                this.overlay.get('boundingBox').setStyle('visibility', 'visible');
            },
            /**
             * Hides the tooltip
             *
             * @function M.local_checkmarkreport.tooltip.hide
             * @this {M.local_checkmarkreport.tooltip}
             * @param {Event} e
             * @param {Y.Node} cell
             */
            hide : function(e, cell) {
                cell.removeClass('tooltipactive');
                this.overlay.hide();
                this.overlay.get('boundingBox').setStyle('visibility', 'hidden');
            }
        };
        // Create the actual report
        this.reports[id] = new this.classes.report(Y, id, cfg, items, users, grade);
    }
};
/**
 * Initialises the JavaScript for the checkmark report
 *
 * The functions fall into 3 groups:
 * M.local_checkmarkreport.classes.ajax Used when editing is off and fields are dynamically added and removed
 * M.local_checkmarkreport.classes.existingfield Used when editing is on meaning all fields are already displayed
 * M.local_checkmarkreport.classes.report Common to both of the above
 *
 * @class report
 * @constructor
 * @this {M.local_checkmarkreport}
 * @param {YUI} Y
 * @param {int} id The id of the table to attach the report to
 * @param {Object} cfg Configuration variables
 * @param {Array} items An array containing grade items
 * @param {Array} users An array containing user information
 * @param {Array} feedback An array containing feedback information
 */
M.local_checkmarkreport.classes.report = function(Y, id, cfg, items, users, grade) {
    this.Y = Y;
    this.isediting = (cfg.isediting);
    this.ajaxenabled = (cfg.ajaxenabled);
    this.items = items;
    this.users = users;
    this.grade = grade;
    this.table = Y.one(id);

    // Alias this so that we can use the correct scope in the coming
    // node iteration
    this.table.all('tr').each(function(tr){
        // Highlight rows
        tr.all('th.cell').on('click', this.table_highlight_row, this, tr);
        // Display tooltips
        tr.all('.current.cell').each(function(cell){
            M.local_checkmarkreport.tooltip.attach(cell, this);
        }, this);
    }, this);

    // Highlight columns
    this.table.all('.highlightable').each(function(cell){
        cell.on('click', this.table_highlight_column, this, cell);
        cell.removeClass('highlightable');
    }, this);

    // If ajax is enabled then initialise the ajax component, currently deactivated
    if (0 &&this.ajaxenabled) {
        this.ajax = new M.local_checkmarkreport.classes.ajax(this, cfg);
    }
};
/**
 * Extend the report class with the following methods and properties
 */
M.local_checkmarkreport.classes.report.prototype.table = null;           // YUI Node for the reports main table
M.local_checkmarkreport.classes.report.prototype.items = [];             // Array containing grade items
M.local_checkmarkreport.classes.report.prototype.users = [];             // Array containing user information
M.local_checkmarkreport.classes.report.prototype.grade = [];             // Array containing data items
M.local_checkmarkreport.classes.report.prototype.ajaxenabled = false;    // True is AJAX is enabled for the report
M.local_checkmarkreport.classes.report.prototype.ajax = null;            // An instance of the ajax class or null
/**
 * Highlights a row in the report
 *
 * @function
 * @param {Event} e
 * @param {Y.Node} tr The table row to highlight
 */
M.local_checkmarkreport.classes.report.prototype.table_highlight_row = function (e, tr) {
    tr.all('.cell').toggleClass('hmarked');
};
/**
 * Highlights a column in the table
 *
 * @function
 * @param {Event} e
 * @param {Y.Node} cell
 */
M.local_checkmarkreport.classes.report.prototype.table_highlight_column = function(e, cell) {
    // Among cell classes find the one that matches pattern / i[\d]+ /
    var itemclass = (' ' + cell.getAttribute('class') + ' ').match(/ (i[\d]+) /);
    if (itemclass) {
        // Toggle class .vmarked for all cells in the table with the same class
        this.table.all('.cell.' + itemclass[1]).toggleClass('vmarked');
    }
};
/**
 * Builds an object containing information at the relevant cell given either
 * the cell to get information for or an array containing userid and itemid
 *
 * @function
 * @this {M.local_checkmarkreport}
 * @param {Y.Node|Array} arg Either a YUI Node instance or an array containing
 *                           the userid and itemid to reference
 * @return {Object}
 */
M.local_checkmarkreport.classes.report.prototype.get_cell_info = function(arg) {

    var userid = null;
    var itemid = null;
    var grader = ''; // Don't default feedback to null or string comparisons become error prone
    var dategraded = '';
    var cell = null;
    var i = null;

    if (arg instanceof this.Y.Node) {
        var regexp = /^u(\d+)i(\d+)_.$/;
        var parts = regexp.exec(arg.getAttribute('id'));
        userid = parts[1];
        itemid = parts[2];
        cell = arg;
    } else {
        userid = arg[0];
        itemid = arg[1];
        cell = this.Y.one('#u' + userid + 'i' + itemid);
    }

    if (!cell) {
        return null;
    }

    for (i in this.grade) {
        if (this.grade[i] && this.grade[i]['user'] == userid && this.grade[i]['item'] == itemid) {
            grader = this.grade[i]['grader'];
            dategraded = this.grade[i]['dategraded'];
            break;
        }
    }

    return {
        id : cell.getAttribute('id'),
        userid : userid,
        username : this.users[userid],
        itemid : itemid,
        grader : grader,
        dategraded : dategraded,
        cell : cell
    };
};
/**
 * Updates or creates the data JS structure for the given user/item
 *
 * @function
 * @this {M.local_checkmarkreport}
 * @param {Int} userid
 * @param {Int} itemid
 * @param {String} newfeedback
 * @return {Bool}
 */
M.local_checkmarkreport.classes.report.prototype.update_feedback = function(userid, itemid, grader, dategraded) {
    for (var i in this.grade) {
        if (this.grade[i].user == userid && this.grade[i].item == itemid) {
            this.grade[i].grader = grader;
            this.grade[i].dategraded = dategraded;
            return true;
        }
    }
    this.feedback.push({user:userid,item:itemid,grader:grader,dategraded:dategraded});
    return true;
};
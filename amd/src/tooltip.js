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
 * Handles overlays/tooltips in report tables!
 *
 * @author    Philipp Hager
 * @copyright 2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * @module local_checkmarkreport/tooltips
 */
define(['core/popper', 'core/log'], function(Popper, log) {

    /**
     * Find the tooltip referred to by this element and show it.
     *
     * @param {Event} e
     */
    function _showTooltip(e) {
        if (e.target.getAttribute('data-toggle') !== 'tooltip') {
            var triggerEl = e.target.closest('[data-toggle=tooltip]');
        } else {
            var triggerEl = e.target;
        }
        var tooltipid = 'tooltip_' + triggerEl.getAttribute('id');
        var tooltipEl = document.getElementById(tooltipid);

        if (tooltipEl === null) {
            var tooltipEl = document.createElement('div');
            tooltipEl.id = tooltipid;
            tooltipEl.classList.add('popover');
            tooltipEl.classList.add('bs-popover-auto');
            tooltipEl.setAttribute('role', 'tooltip');
            triggerEl.setAttribute('aria-describedby', tooltipEl.id);
            tooltipEl.innerHTML = '<div class="arrow"></div><h3 class="popover-header"></h3>' +
                    '<div class="popover-body">' + triggerEl.getAttribute('data-content') + '</div>';
            tooltipEl.style.opacity = '0';
            tooltipEl.setAttribute('aria-hidden', 'true');
            document.body.appendChild(tooltipEl);
        } else {
            tooltipEl.style.display = 'block';
        }

        new Popper(triggerEl, tooltipEl, {
            modifiers: {
                placement: 'right'/*,
                flip: {
                    behavior: 'clockwise'
                }*/
            }
        });
         tooltipEl.style.opacity = '1';
         tooltipEl.setAttribute('aria-hidden', 'false');
    }

    var _hide = function(toolTipEl) {
        return function() {
            toolTipEl.style.display = 'none';
            toolTipEl.setAttribute('aria-hidden', 'true');
        };
    };

    /**
     * Find the tooltip referred to by this element and hide it.
     *
     * @param {Event} e
     */
    function _hideTooltip(e) {
        if (e.target.getAttribute('data-toggle') !== 'tooltip') {
            var tooltipId = 'tooltip_' + e.target.closest('[data-toggle=tooltip]').getAttribute('id');
        } else {
            var tooltipId = 'tooltip_' + e.target.id;
        }
        var toolTipEl = document.getElementById(tooltipId);

        if (toolTipEl === null) {
            log.info('Tooltip element not found!', 'local_checkmarkreport');
        } else {
            toolTipEl.style.opacity = '0';
            window.setTimeout(_hide(toolTipEl), 200);
        }
    }

    /**
     * Listener for focus events.
     * @param {FocusEvent} e
     */
    function _handleFocus(e) {
        _showTooltip(e);
    }

    /**
     * Listener for keydown events.
     * @param {KeyboardEvent} e
     */
    function _handleKeyDown(e) {
        if (e.key === 'Escape') {
            _hideTooltip(e);
        }
    }

    /**
     * Listener for mouseover events.
     * @param {MouseEvent} e
     */
    function _handleMouseOver(e) {
        _showTooltip(e);
    }

    /**
     * Listener for mouseout events.
     * @param {MouseEvent} e
     */
    function _handleMouseOut(e) {
        // The mouseout-event's relatedTarget is the element which has been entered!
        if (e.relatedTarget !== undefined && e.relatedTarget !== null && e.relatedTarget.classList.contains('content')) {
            return;
        }

        _hideTooltip(e);
    }

    /**
     * Listener for blur events.
     * @param {FocusEvent} e
     */
    function _handleBlur(e) {
        _hideTooltip(e);
    }

    return {
        /**
         * Initialises the JavaScript for the checkmark report
         */
        initializer: function() {
            log.info('Register tooltips!', 'local_checkmarkreport');

            var tts = document.querySelectorAll('#checkmarkreporttable [data-toggle="tooltip"]');
            for (var i = 0; i < tts.length; i++) {
                tts[i].addEventListener('focus', _handleFocus.bind(this));
                tts[i].addEventListener('click', _handleFocus.bind(this));
                tts[i].addEventListener('mouseover', _handleMouseOver.bind(this));
                tts[i].addEventListener('mouseout', _handleMouseOut.bind(this));
                tts[i].addEventListener('blur', _handleBlur.bind(this));
                tts[i].addEventListener('keydown', _handleKeyDown.bind(this));
            }
        }
    };
});

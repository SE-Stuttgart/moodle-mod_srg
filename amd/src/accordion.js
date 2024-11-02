// This file is part of Moodle - http://moodle.org/
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
 * View HTML Accordion handling.
 *
 * @module     mod_srg/accordion
 * @copyright  2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Sets up the event listener for the accordion interaction.
 */
export function init() {
    var collapseButtons = document.getElementsByClassName("mod_srg-collapse-button");

    for (var i = 0; i < collapseButtons.length; i++) {
        var button = collapseButtons[i];
        button.addEventListener('click', handleAccordionClick);
    }
}

/**
 * Function to handle accordion interaction.
 * @param {*} event The input event catched by the listener.
 */
function handleAccordionClick(event) {
    var iconTarget = event.target.getAttribute('icon-target');
    var iconElement = document.querySelector(iconTarget);

    if (iconElement) {
        iconElement.classList.toggle('fa-chevron-down');
        iconElement.classList.toggle('fa-chevron-up');
    }
}
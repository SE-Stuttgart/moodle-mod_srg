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

import {
    initPageSelection
} from "mod_srg/page_selection";

/**
 * Create and support the data viewing in html.
 *
 * @module     mod_srg/file_selection
 * @copyright  2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Support for data_view.js, call only after rendering template/file_navigation.
 * Initialize the file-selection UI and delegate further html renders.
 * Set file index 0 as default starting file.
 */
export function initFileSelection() {
    initTabListeners();
    setActiveTab(0);
}

/**
 * Setup the Tab Listeners.
 */
function initTabListeners() {
    const tabs = document.querySelectorAll(".srg-tab-container .nav-link");

    tabs.forEach((tab, index) => {
        tab.addEventListener("click", (event) => {
            event.preventDefault();
            setActiveTab(index);
        });
    });
}

/**
 * Sets a new active tab and updates the tab content.
 * @param {int} nextTabIndex - Index of the new active tab.
 */
function setActiveTab(nextTabIndex) {
    const tabs = document.querySelectorAll(".srg-tab-container .nav-link");
    const dataElement = document.querySelector(".srg-page-container");
    const topPagination = document.querySelector(".srg-page-navigation-container-top");
    const tableContainer = document.querySelector(".srg-tab-content-container");
    const bottomPagination = document.querySelector(".srg-page-navigation-container-bottom");

    // Reset tab content.
    topPagination.innerHTML = "";
    tableContainer.innerHTML = "";
    bottomPagination.innerHTML = "";

    // Update the active file index information.
    dataElement.dataset.activeFileIndex = nextTabIndex;


    tabs.forEach((tab, index) => {
        const isActive = index === nextTabIndex;
        tab.classList.toggle("srg-tab-active", isActive);
        tab.setAttribute("aria-selected", isActive ? "true" : "false");

        if (isActive) {
            // Create an array (for the pagination template) with one object per page, starting at value 1;
            const pages = Array.from({ length: parseInt(tab.dataset.pageCount, 10) }, (_, index) => ({ index: index + 1 }));
            // Initiate page selection and content view if there are pages to display.
            if (pages.length > 0) {
                initPageSelection(pages);
            }
        }
    });
}
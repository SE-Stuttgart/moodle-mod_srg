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
    render
} from 'core/templates';

/**
 * Create and support the data viewing in html.
 *
 * @module     mod_srg/page_selection
 * @copyright  2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Support for file_selection.js, call only after rendering template/file_navigation.
 * Initialize the page-selection UI and render pagination and content.
 * Render the pagination template.
 * @param {array} pages - Array of pages to be used in the template pagination.
 */
export function initPageSelection(pages) {
    const topPagination = document.querySelector(".srg-page-navigation-container-top");
    const bottomPagination = document.querySelector(".srg-page-navigation-container-bottom");

    // Render the "page_navigation" Mustache template with the generated pages array
    const templateContext = { pages: pages };

    render('mod_srg/pagination', templateContext).then((html) => {
        // Render pagination HTML.
        topPagination.innerHTML = html;
        bottomPagination.innerHTML = html;

        // Setup pagination Listeners.
        initPaginationListeners(topPagination);
        initPaginationListeners(bottomPagination);

        // Set the first page as active by default.
        setActivePage(0);
        return undefined;
    }).catch(ex => {
        window.console.error('Template rendering failed: ', ex);
    });
}


/**
 * Setup the Pagination Listeners.
 * @param {Element} paginationContainerElement - The HTML element, that is parent to the pagination.
 */
function initPaginationListeners(paginationContainerElement) {
    // DataElement contains some data necessary for synchronous pagination (such as current page index).
    const dataElement = document.querySelector(".srg-page-container");

    const first = paginationContainerElement.querySelector(".srg-page-nav-first .page-link");
    const previous = paginationContainerElement.querySelector(".srg-page-nav-previous .page-link");
    const pages = paginationContainerElement.querySelectorAll(".srg-page-nav-page .page-link");
    const next = paginationContainerElement.querySelector(".srg-page-nav-next .page-link");
    const last = paginationContainerElement.querySelector(".srg-page-nav-last .page-link");


    // First Page.
    first.addEventListener("click", (event) => {
        event.preventDefault();
        // Get the index of the currently active Page.
        const activePageIndex = parseInt(dataElement.dataset.activePageIndex, 10);
        if (activePageIndex > 0) {
            setActivePage(0);
        }
    });

    // Previous Page.
    previous.addEventListener("click", (event) => {
        event.preventDefault();
        // Get the index of the currently active Page.
        const activePageIndex = parseInt(dataElement.dataset.activePageIndex, 10);
        if (activePageIndex > 0) {
            setActivePage(activePageIndex - 1);
        }
    });

    // Direct Pages.
    pages.forEach((page, index) => {
        page.addEventListener("click", (event) => {
            event.preventDefault();
            // Get the index of the currently active Page.
            const activePageIndex = parseInt(dataElement.dataset.activePageIndex, 10);
            if (activePageIndex !== index) {
                setActivePage(index);
            }
        });
    });

    // Next Page.
    next.addEventListener("click", (event) => {
        event.preventDefault();
        // Get the index of the currently active Page.
        const activePageIndex = parseInt(dataElement.dataset.activePageIndex, 10);
        if (activePageIndex < pages.length - 1) {
            setActivePage(activePageIndex + 1);
        }
    });

    // Last Page.
    last.addEventListener("click", (event) => {
        event.preventDefault();
        // Get the index of the currently active Page.
        const activePageIndex = parseInt(dataElement.dataset.activePageIndex, 10);
        if (activePageIndex < pages.length - 1) {
            setActivePage(pages.length - 1);
        }
    });
}

/**
 * Sets a new active tab and updates the tab content.
 * @param {int} nextPageIndex - Index of the new active tab.
 */
function setActivePage(nextPageIndex) {
    const dataElement = document.querySelector(".srg-page-container");
    const topPagination = document.querySelector(".srg-page-navigation-container-top");
    const bottomPagination = document.querySelector(".srg-page-navigation-container-bottom");

    // Update the active page index information.
    dataElement.dataset.activePageIndex = nextPageIndex;

    updatePaginationVisibilityStates(topPagination, nextPageIndex);
    updatePaginationVisibilityStates(bottomPagination, nextPageIndex);

    // Get the head and data for the currently active file/tab.
    const tabs = document.querySelectorAll(".srg-tab-container .nav-link");
    const activeTab = tabs[dataElement.dataset.activeFileIndex];
    const head = JSON.parse(atob(activeTab.getAttribute("data-head") || "e30="));
    const data = JSON.parse(atob(activeTab.getAttribute("data-content") || "e30="));

    // Get the content for the currently active page.
    const content = data[dataElement.dataset.activePageIndex].rows;

    renderTable(head, content);
}

/**
 * Disable/enable and hide/show pagination links.
 * @param {Element} paginationContainerElement - The HTML element, that is parent to the pagination.
 * @param {int} pageIndex - The active page index.
 */
function updatePaginationVisibilityStates(paginationContainerElement, pageIndex) {
    const first = paginationContainerElement.querySelector(".srg-page-nav-first");
    const previous = paginationContainerElement.querySelector(".srg-page-nav-previous");
    const leftEllipsis = paginationContainerElement.querySelector(".srg-page-nav-ellipsis-left");
    const pages = paginationContainerElement.querySelectorAll(".srg-page-nav-page");
    const rightEllipsis = paginationContainerElement.querySelector(".srg-page-nav-ellipsis-right");
    const next = paginationContainerElement.querySelector(".srg-page-nav-next");
    const last = paginationContainerElement.querySelector(".srg-page-nav-last");

    // We only want to show page numbers close to the current number.
    const offset = 5;

    const isFirst = pageIndex === 0;
    const isLast = pageIndex === pages.length - 1;
    const isFirstHidden = pageIndex - offset > 0;
    const isLastHidden = pageIndex + offset < pages.length - 1;

    first.classList.toggle("disabled", isFirst);
    previous.classList.toggle("disabled", isFirst);
    leftEllipsis.classList.toggle("srg-hidden", !isFirstHidden);
    rightEllipsis.classList.toggle("srg-hidden", !isLastHidden);
    next.classList.toggle("disabled", isLast);
    last.classList.toggle("disabled", isLast);

    pages.forEach((page, index) => {
        const isActive = index === pageIndex;
        page.classList.toggle("active", isActive);
        page.setAttribute("aria-selected", isActive ? "true" : "false");

        const isHidden = Math.abs(pageIndex - index) > offset;
        page.classList.toggle("srg-hidden", isHidden);
    });
}

/**
 * Render the content as a table.
 * @param {array} head - The column headers for the table.
 * @param {array} content - The content rows for the table.
 */
function renderTable(head, content) {
    const tableContainer = document.querySelector(".srg-tab-content-container");

    const templateContext = { head: head, rows: content };
    render('mod_srg/table', templateContext).then((html) => {
        tableContainer.innerHTML = html;
        return undefined;
    }).catch(ex => {
        window.console.error('Template rendering failed: ', ex);
    });
}
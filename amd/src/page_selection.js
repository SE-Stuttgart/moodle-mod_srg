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
    const templateContext = {
        pages: pages
    };

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
    const head = JSON.parse(atob(activeTab.getAttribute("data-head") || "W10="));
    const data = JSON.parse(atob(activeTab.getAttribute("data-content") || "W10="));

    renderTable(
        preprocessHead(head),
        preprocessData(data, parseInt(dataElement.dataset.pageLength, 10), parseInt(dataElement.dataset.activePageIndex, 10))
    );
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
 * @param {array} data - The content rows for the table.
 */
function renderTable(head, data) {
    const tableContainer = document.querySelector(".srg-tab-content-container");

    const templateContext = {
        head: head,
        rows: data
    };

    render('mod_srg/table', templateContext).then((html) => {
        tableContainer.innerHTML = html;
        return undefined;
    }).catch(ex => {
        window.console.error('Template rendering failed: ', ex);
    });
}

/**
 * Converts header values into HTML-safe objects with a `value` property.
 * @param {Array} head - Array of header values to process.
 * @returns {Array<Object>} Array of objects with each header as an HTML-safe string in `value`.
 */
function preprocessHead(head) {
    return head.map(headValue => ({
        value: makeHtmlSafe(headValue)
    }));
}

/**
 * Slices data to retrieve rows for the given page index, ensuring HTML safety for each cell value.
 * @param {Array<Array>} data - The data array where each element is a row array.
 * @param {number} pageLength - The number of rows per page.
 * @param {number} pageIndex - The index of the current page (0-based).
 * @returns {Array<Object>} Array of row objects, each with `columns` containing HTML-safe cell values.
 */
function preprocessData(data, pageLength, pageIndex) {
    const startIndex = pageIndex * pageLength;
    const endIndex = startIndex + pageLength;

    const page = data.slice(startIndex, endIndex);

    return page.map(row => ({
        columns: row.map(cellValue => ({
            value: makeHtmlSafe(cellValue)
        }))
    }));
}

/**
 * Converts a value to an HTML-safe string.
 * This function prevents HTML injection by escaping special characters.
 * @param {any} value - The value to make HTML-safe.
 * @returns {string} HTML-safe string representation of the value.
 */
function makeHtmlSafe(value) {
    const tempDiv = document.createElement('div');
    tempDiv.textContent = String(value);
    return tempDiv.innerHTML;
}
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
 * Create a pagination navigation to switch between the different data batches.
 *
 * @module     mod_srg/pagination
 * @copyright  2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Create a page navigation to switch between the different reports data batches.
 * Reload '/mod/srg/report_view.php' for each page and keep the active reportid.
 */
export function init() {
    initPaginationListeners();
    initPaginationVisibilityStates();
}

/**
 * Initializes click event listeners for pagination controls.
 * Handles navigation to the appropriate page based on the control clicked
 * (e.g., first, previous, specific page, next, or last).
 *
 * Assumes the presence of a container with the ID `#srg-pagination-container` in the DOM,
 * and that the container includes elements with specific classes:
 * - `.srg-page-nav-first .page-link`
 * - `.srg-page-nav-previous .page-link`
 * - `.srg-page-nav-page .page-link`
 * - `.srg-page-nav-next .page-link`
 * - `.srg-page-nav-last .page-link`
 */
function initPaginationListeners() {
    const paginationContainer = document.querySelector('#srg-pagination-container');

    const courseModuleId = parseInt(paginationContainer.dataset.coursemoduleid, 10);
    const activeTabIndex = parseInt(paginationContainer.dataset.activetabindex, 10);
    const activePageIndex = parseInt(paginationContainer.dataset.activepageindex, 10);


    const first = paginationContainer.querySelector(".srg-page-nav-first .page-link");
    const previous = paginationContainer.querySelector(".srg-page-nav-previous .page-link");
    const pages = paginationContainer.querySelectorAll(".srg-page-nav-page .page-link");
    const next = paginationContainer.querySelector(".srg-page-nav-next .page-link");
    const last = paginationContainer.querySelector(".srg-page-nav-last .page-link");

    // First Page navigation.
    first.addEventListener("click", (event) => {
        event.preventDefault();

        const newUrl = new URL('/mod/srg/report_view.php', window.location.origin);
        newUrl.searchParams.set('id', courseModuleId);
        newUrl.searchParams.set('report_id', activeTabIndex);
        newUrl.searchParams.set('page_index', 0);

        window.location.href = newUrl.toString();
    });

    // Previous Page navigation.
    previous.addEventListener("click", (event) => {
        event.preventDefault();

        const newUrl = new URL('/mod/srg/report_view.php', window.location.origin);
        newUrl.searchParams.set('id', courseModuleId);
        newUrl.searchParams.set('report_id', activeTabIndex);
        newUrl.searchParams.set('page_index', activePageIndex - 1);

        window.location.href = newUrl.toString();
    });

    // Specific Page navigation.
    pages.forEach((page, index) => {
        page.addEventListener("click", (event) => {
            event.preventDefault();

            const newUrl = new URL('/mod/srg/report_view.php', window.location.origin);
            newUrl.searchParams.set('id', courseModuleId);
            newUrl.searchParams.set('report_id', activeTabIndex);
            newUrl.searchParams.set('page_index', index);

            window.location.href = newUrl.toString();
        });
    });

    // Next Page navigation.
    next.addEventListener("click", (event) => {
        event.preventDefault();

        const newUrl = new URL('/mod/srg/report_view.php', window.location.origin);
        newUrl.searchParams.set('id', courseModuleId);
        newUrl.searchParams.set('report_id', activeTabIndex);
        newUrl.searchParams.set('page_index', activePageIndex + 1);

        window.location.href = newUrl.toString();
    });

    // Last Page navigation.
    last.addEventListener("click", (event) => {
        event.preventDefault();

        const newUrl = new URL('/mod/srg/report_view.php', window.location.origin);
        newUrl.searchParams.set('id', courseModuleId);
        newUrl.searchParams.set('report_id', activeTabIndex);
        newUrl.searchParams.set('page_index', pages.length - 1);

        window.location.href = newUrl.toString();
    });
}

/**
 * Updates the visibility and accessibility states of pagination controls.
 * Highlights the active page, disables navigation controls when appropriate,
 * and hides ellipses or page numbers outside the desired range.
 *
 * Assumes the presence of a container with the ID `#srg-pagination-container` in the DOM,
 * and that the container includes elements with specific classes:
 * - `.srg-page-nav-first`, `.srg-page-nav-previous`, `.srg-page-nav-next`, `.srg-page-nav-last`
 * - `.srg-page-nav-ellipsis-left`, `.srg-page-nav-ellipsis-right`
 * - `.srg-page-nav-page`
 *
 * Visibility logic:
 * - Disables "First" and "Previous" if on the first page.
 * - Disables "Last" and "Next" if on the last page.
 * - Hides page numbers outside the offset range from the current page.
 */
function initPaginationVisibilityStates() {
    const paginationContainer = document.querySelector('#srg-pagination-container');

    const activePageIndex = parseInt(paginationContainer.dataset.activepageindex, 10);

    const first = paginationContainer.querySelector(".srg-page-nav-first");
    const previous = paginationContainer.querySelector(".srg-page-nav-previous");
    const leftEllipsis = paginationContainer.querySelector(".srg-page-nav-ellipsis-left");
    const pages = paginationContainer.querySelectorAll(".srg-page-nav-page");
    const rightEllipsis = paginationContainer.querySelector(".srg-page-nav-ellipsis-right");
    const next = paginationContainer.querySelector(".srg-page-nav-next");
    const last = paginationContainer.querySelector(".srg-page-nav-last");

    // We only want to show page numbers close to the current number.
    const offset = 5;

    const isFirst = activePageIndex === 0;
    const isLast = activePageIndex === pages.length - 1;
    const isFirstHidden = activePageIndex - offset > 0;
    const isLastHidden = activePageIndex + offset < pages.length - 1;

    // Hide pagination if there are zero or one pages.
    const isAllHidden = pages.length < 2;

    first.classList.toggle("disabled", isFirst);
    previous.classList.toggle("disabled", isFirst);
    next.classList.toggle("disabled", isLast);
    last.classList.toggle("disabled", isLast);

    first.classList.toggle("srg-hidden", isAllHidden);
    previous.classList.toggle("srg-hidden", isAllHidden);
    next.classList.toggle("srg-hidden", isAllHidden);
    last.classList.toggle("srg-hidden", isAllHidden);

    leftEllipsis.classList.toggle("srg-hidden", !isFirstHidden);
    rightEllipsis.classList.toggle("srg-hidden", !isLastHidden);

    pages.forEach((page, index) => {
        const isActive = index === activePageIndex;
        page.classList.toggle("active", isActive);
        page.setAttribute("aria-selected", isActive.toString());

        const isHidden = Math.abs(activePageIndex - index) > offset;
        page.classList.toggle("srg-hidden", isHidden || isAllHidden);
    });

    // We want to start the container hidden for a more clean appearance.
    paginationContainer.classList.toggle("srg-hidden", false);
}
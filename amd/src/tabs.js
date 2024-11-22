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
 * Create a tab navigation to switch between the different reports.
 *
 * @module     mod_srg/tabs
 * @copyright  2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Create a tab navigation to switch between the different reports.
 * Reload '/mod/srg/report_view.php' for each tab and set the active page to 0.
 */
export function init() {
    initTabListeners();
    initTabVisibilityStates();
}

/**
 * Initializes click event listeners for tabs in the tab container.
 * When a tab is clicked, the page navigates to a new URL with parameters based on the clicked tab.
 *
 * Assumes there is a container with the ID `#srg-tab-container` in the DOM,
 * and that the tabs within it have the `nav-link` class and a `data-index` attribute.
 */
function initTabListeners() {
    const tabContainer = document.querySelector('#srg-tab-container');

    const baseUrl = tabContainer.dataset.baseurl;
    const courseModuleId = parseInt(tabContainer.dataset.coursemoduleid, 10);

    // Get all tab links within the container.
    const tabLinks = tabContainer.querySelectorAll('.nav-link');

    tabLinks.forEach(tab => {
        // Attach click listener.
        tab.addEventListener('click', (event) => {
            event.preventDefault();

            const reportId = tab.dataset.index;
            const newUrl = new URL(baseUrl, window.location.origin);
            newUrl.searchParams.set('id', courseModuleId);
            newUrl.searchParams.set('report_id', reportId);
            newUrl.searchParams.set('page_index', 0);

            window.location.href = newUrl.toString();
        });
    });
}

/**
 * Sets the visibility state and accessibility attributes for tabs in the tab container.
 * Highlights the active tab and sets the `aria-selected` attribute based on the current active index.
 *
 * Assumes there is a container with the ID `#srg-tab-container` in the DOM,
 * and that the tabs within it have the `nav-link` class and a `data-index` attribute.
 */
function initTabVisibilityStates() {
    const tabContainer = document.querySelector('#srg-tab-container');

    const activeTabIndex = parseInt(tabContainer.dataset.activetabindex, 10);

    // Get all tab links within the container.
    const tabLinks = tabContainer.querySelectorAll('.nav-link');

    tabLinks.forEach(tab => {
        const tabIndex = parseInt(tab.dataset.index, 10);
        const isActive = tabIndex === activeTabIndex;

        // Set aria attributes for the active tab.
        tab.setAttribute('aria-selected', isActive.toString());
        tab.classList.toggle('srg-tab-active', isActive);
    });

    // We want to start the container hidden for a more clean appearance.
    tabContainer.classList.toggle("srg-hidden", false);
}
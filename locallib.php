<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package     mod_srg
 * @copyright   2023 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_srg\local\report;
use mod_srg\local\report_generator;

use stdClass;

/**
 * Get the saved insctruction to be displayed on the view page.
 * @param int $id Id of the activity.
 * @return string Instruction
 */
function srg_get_instruction($id) {
    if (empty($id)) {
        return get_string('content_default', 'mod_srg');
    }

    global $DB;
    $record = $DB->get_record('srg', ['id' => $id]);
    return $record->instruction;
}

/**
 * Retrieves a list of available report identifiers for the SRG module.
 *
 * The function dynamically checks for the presence of optional plugins
 * like H5P (`mod_hvp`) and Chatbot (`block_chatbot`) to include their reports.
 *
 * @return array A list of report identifiers supported by the SRG module.
 */
function srg_get_report_list() {
    $reportlist = [];

    $reportlist[] = MOD_SRG_REPORT_COURSE_LOG;
    $reportlist[] = MOD_SRG_REPORT_COURSE_DEDICATION;
    $reportlist[] = MOD_SRG_REPORT_COURSE_MODULE_LOG;
    $reportlist[] = MOD_SRG_REPORT_COURSE_MODULE_DEDICATION;
    $reportlist[] = MOD_SRG_REPORT_GRADE_INSPECTION;
    $reportlist[] = MOD_SRG_REPORT_FORUM_ACTIVITY;
    if (core_plugin_manager::instance()->get_plugin_info('mod_hvp')) {
        $reportlist[] = MOD_SRG_REPORT_HVP;
    }
    $reportlist[] = MOD_SRG_REPORT_BADGES;
    if (core_plugin_manager::instance()->get_plugin_info('block_chatbot')) {
        $reportlist[] = MOD_SRG_REPORT_CHATBOT_HISTORY;
    }

    return $reportlist;
}

/**
 * Retrieves a specific report object based on the provided report ID, user, and course.
 *
 * The function maps the report ID to its corresponding report generation method in `report_generator`.
 * If the report ID is invalid, it returns null.
 *
 * @param int $reportid The ID of the report to retrieve.
 * @param stdClass $USER The user object for whom the report is being generated.
 * @param stdClass $course The course object associated with the report.
 * @return report|null The corresponding report object, or null if the ID is invalid.
 */
function srg_get_report(int $reportid, $USER, $course): ?report {
    switch ($reportid) {
        case MOD_SRG_REPORT_COURSE_LOG:
            return report_generator::get_course_report($USER, $course);
        case MOD_SRG_REPORT_COURSE_DEDICATION:
            return report_generator::get_course_dedication_report($USER, $course);
        case MOD_SRG_REPORT_COURSE_MODULE_LOG:
            return report_generator::get_course_module_log_report($USER, $course);
        case MOD_SRG_REPORT_COURSE_MODULE_DEDICATION:
            return report_generator::get_course_module_dedication_report($USER, $course);
        case MOD_SRG_REPORT_GRADE_INSPECTION:
            return report_generator::get_grading_interest_report($USER, $course);
        case MOD_SRG_REPORT_FORUM_ACTIVITY:
            return report_generator::get_forum_activity_report($USER, $course);
        case MOD_SRG_REPORT_HVP:
            return report_generator::get_hvp_report($USER, $course);
        case MOD_SRG_REPORT_BADGES:
            return report_generator::get_badges_report($USER, $course);
        case MOD_SRG_REPORT_CHATBOT_HISTORY:
            return report_generator::get_chatbot_history_report($USER, $course);
        default:
            return null;
    }
}


function srg_on_click_view_report($activityinstance, $context, $wwwroot, $cmid): moodle_url {
    // Trigger event\log_data_viewed.
    srg_log_data_view($activityinstance, $context);

    return new moodle_url($wwwroot . '/mod/srg/report_view.php', ['id' => $cmid, 'report_id' => 0, 'page_index' => 0]);
}

function srg_on_click_download_report($activityinstance, $context, $wwwroot, $cmid) {
    // Trigger event\log_data_downloaded.
    srg_log_data_download($activityinstance, $context);

    return new moodle_url($wwwroot . '/mod/srg/info.php', ['id' => $cmid]);
}

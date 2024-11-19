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

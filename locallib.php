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
defined('MOODLE_INTERNAL') || die();

use mod_srg\local\report_system;

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
 * Hardcoded Selected Logs Metadata
 * @param mixed $USER The current user.
 * @param stdClass $course The course of this activity.
 * @return array Array of log data packets. Each packet has a name an advised filename and the log as array.
 */
function srg_get_file_list($USER, $course) {
    $filelist = [];

    try {
        $reportsystem = new report_system();
    } catch (\Throwable $th) {
        debugging($th);
        return $filelist;
    }

    try {
        $filelist[] = [
            'name' => get_string('course_dedication_log', 'mod_srg'),
            'filename' => get_string('course_dedication_log_csv', 'mod_srg'),
            'content' => $reportsystem->get_course_dedication($USER, $course),
        ];
    } catch (\Throwable $th) {
        debugging($th);
    }

    try {
        $filelist[] = [
            'name' => get_string('course_module_log', 'mod_srg'),
            'filename' => get_string('course_module_log_csv', 'mod_srg'),
            'content' => $reportsystem->get_course_module_log($USER, $course),
        ];
    } catch (\Throwable $th) {
        debugging($th);
    }

    try {
        $filelist[] = [
            'name' => get_string('course_module_dedication', 'mod_srg'),
            'filename' => get_string('course_module_dedication_csv', 'mod_srg'),
            'content' => $reportsystem->get_course_module_dedication($USER, $course),
        ];
    } catch (\Throwable $th) {
        debugging($th);
    }

    try {
        $filelist[] = [
            'name' => get_string('grade_inspections', 'mod_srg'),
            'filename' => get_string('grade_inspections_csv', 'mod_srg'),
            'content' => $reportsystem->get_grading_interest($USER, $course),
        ];
    } catch (\Throwable $th) {
        debugging($th);
    }

    try {
        $filelist[] = [
            'name' => get_string('forum_activities', 'mod_srg'),
            'filename' => get_string('forum_activities_csv', 'mod_srg'),
            'content' => $reportsystem->get_forum_activity($USER, $course),
        ];
    } catch (\Throwable $th) {
        debugging($th);
    }

    if (core_plugin_manager::instance()->get_plugin_info('mod_hvp')) {
        try {
            $filelist[] = [
                'name' => get_string('hvp_scores', 'mod_srg'),
                'filename' => get_string('hvp_scores_csv', 'mod_srg'),
                'content' => $reportsystem->get_hvp($USER, $course),
            ];
        } catch (\Throwable $th) {
            debugging($th);
        }
    }

    try {
        $filelist[] = [
            'name' => get_string('badges', 'mod_srg'),
            'filename' => get_string('badges_csv', 'mod_srg'),
            'content' => $reportsystem->get_badges($USER, $course),
        ];
    } catch (\Throwable $th) {
        debugging($th);
    }

    if (core_plugin_manager::instance()->get_plugin_info('block_chatbot')) {
        try {
            $filelist[] = [
                'name' => get_string('chatbot_history', 'mod_srg'),
                'filename' => get_string('chatbot_history_csv', 'mod_srg'),
                'content' => $reportsystem->get_chatbot_history($USER, $course),
            ];
        } catch (\Throwable $th) {
            debugging($th);
        }
    }

    return $filelist;
}

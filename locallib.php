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
 * @copyright   2023 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use PhpXmlRpc\Helper\Logger;
use ScssPhp\ScssPhp\Formatter\Debug;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/sql.php');

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
 * Checks if a user is actively enrolled in a given course.
 * @param int $userid ID of the user
 * @param int $courseid ID of the course
 * @return bool True if the user with given id is enrolled in course with given id.
 */
function srg_enrolled_in($userid, $courseid) {
    global $DB;

    $params = array('userid' => $userid, 'courseid' => $courseid);
    $sql = "SELECT e.courseid FROM  {enrol} e
            JOIN {user_enrolments} ue ON e.id = ue.enrolid
            WHERE ue.status = 0 AND ue.userid = :userid AND e.courseid = :courseid";
    $enrolledcourses = $DB->get_records_sql($sql, $params);

    if (!$enrolledcourses) {
        return false;
    }
    return true;
}

/**
 * Hardcoded Selected Logs Metadata
 * @param mixed $USER The current user.
 * @param Course $course The course of this activity.
 * @return array Array of log data packets. Each packet has a name an advised filename and the log as array.
 */
function srg_get_file_list($USER, $course) {
    $filelist = array();

    try {
        $filelist[] = array(
            'name' => 'Course Dedication Report',
            'filename' => 'course_dedication.csv',
            'content' => srg_log::get_course_dedication($USER, $course)
        );
    } catch (\Throwable $th) {
        debugging($th);
    }

    try {
        $filelist[] = array(
            'name' => 'Course Module Log',
            'filename' => 'course_module_log.csv',
            'content' => srg_log::get_course_module_log($USER, $course)
        );
    } catch (\Throwable $th) {
        debugging($th);
    }

    try {
        $filelist[] = array(
            'name' => 'Course Module Dedication Report',
            'filename' => 'course_module_dedication.csv',
            'content' => srg_log::get_course_module_dedication($USER, $course)
        );
    } catch (\Throwable $th) {
        debugging($th);
    }

    try {
        $filelist[] = array(
            'name' => 'Grade Inspection Report',
            'filename' => 'grade_inspections.csv',
            'content' => srg_log::get_grading_interest($USER, $course)
        );
    } catch (\Throwable $th) {
        debugging($th);
    }

    try {
        $filelist[] = array(
            'name' => 'Forum Activity Report',
            'filename' => 'forum_activities.csv',
            'content' => srg_log::get_forum_activity($USER, $course)
        );
    } catch (\Throwable $th) {
        debugging($th);
    }

    if (core_plugin_manager::instance()->get_plugin_info('mod_hvp')) {
        try {
            $filelist[] = array(
                'name' => 'HVP Score Report',
                'filename' => 'hvp_scores.csv',
                'content' => srg_log::get_hvp($USER, $course)
            );
        } catch (\Throwable $th) {
            debugging($th);
        }
    }

    try {
        $filelist[] = array(
            'name' => 'User Earned Badges',
            'filename' => 'badges.csv',
            'content' => srg_log::get_badges($USER, $course)
        );
    } catch (\Throwable $th) {
        debugging($th);
    }

    return $filelist;
}

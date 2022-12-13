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
 * @copyright  2022 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/sql.php');
require_once(__DIR__ . '/db_conn.php');

/**
 * Checks if a user is actively enrolled in a given course.
 * @param int $user_id ID of the user
 * @param int $course_id ID of the course
 */
function srg_enrolled_in($user_id, $course_id)
{
    global $DB;

    $params = array('userid' => $user_id, 'courseid' => $course_id);
    $sql = "SELECT e.courseid FROM  {enrol} e
            JOIN {user_enrolments} ue ON e.id = ue.enrolid
            WHERE ue.status = 0 AND ue.userid = :userid AND e.courseid = :courseid";
    $enrolled_courses = $DB->get_records_sql($sql, $params);

    if (!$enrolled_courses) return false;
    return true;
}

// Hardcoded Selected Logs Metadata
function srg_get_file_list($USER, $course)
{
    $filelist = array();

    // $filelist[] = array(
    //     'name' => 'Detailed Course Log',
    //     'filename' => 'detailed_course_log.csv',
    //     'content' => srg_log::GetCourseLog($USER, $course)
    // );

    $filelist[] = array(
        'name' => 'Course Dedication Report',
        'filename' => 'course_dedication.csv',
        'content' => srg_log::GetCourseDedication($USER, $course)
    );

    $filelist[] = array(
        'name' => 'Course Module Log',
        'filename' => 'course_module_log.csv',
        'content' => srg_log::GetCourseModuleLog($USER, $course)
    );

    $filelist[] = array(
        'name' => 'Course Module Dedication Report',
        'filename' => 'course_module_dedication.csv',
        'content' => srg_log::GetCourseModuleDedication($USER, $course)
    );

    $filelist[] = array(
        'name' => 'Grade Inspection Report',
        'filename' => 'grade_inspections.csv',
        'content' => srg_log::GetGradingInterest($USER, $course)
    );

    $filelist[] = array(
        'name' => 'Forum Activity Report',
        'filename' => 'forum_activities.csv',
        'content' => srg_log::GetForumActivity($USER, $course)
    );

    if (core_plugin_manager::instance()->get_plugin_info('mod_hvp')) {
        $filelist[] = array(
            'name' => 'HVP Score Report',
            'filename' => 'hvp_scores.csv',
            'content' => srg_log::GETHVP($USER, $course)
        );
    }

    $filelist[] = array(
        'name' => 'User Earned Badges',
        'filename' => 'badges.csv',
        'content' => srg_log::GETBadges($USER, $course)
    );

    return $filelist;
}

/**
 * Class holding helper methods corresponding to .csv
 */
class srg_CSV
{
    // Transforms simple table (from db_conn) into .csv
    public static function simple_table_to_CSV($table)
    {
        $csv = '';

        if (!$table) return $csv;
        $first_row = array_shift($table);
        if (!$first_row) return $csv;
        $first_cell = array_shift($first_row);

        $csv .= '"' . preg_replace(array('/\n/', '/"/'), array('', '""'), $first_cell) . '"';

        foreach ($first_row as $cell) {
            $csv .= ",";
            $csv .= '"' . preg_replace(array('/\n/', '/"/'), array('', '""'), $cell) . '"';
        }

        foreach ($table as $row) {
            $csv .= "\n";

            if (!$row) return $csv;
            $first_cell = array_shift($row);

            $csv .= '"' . preg_replace(array('/\n/', '/"/'), array('', '""'), $first_cell) . '"';

            foreach ($row as $cell) {
                $csv .= ",";
                $csv .= '"' . preg_replace(array('/\n/', '/"/'), array('', '""'), $cell) . '"';
            }
        }

        return $csv;
    }
}

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
 * Factory class for creating various types of reports for Moodle courses.
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg\local;

use mod_srg\local\report;
use mod_srg\local\report_sql;

/**
 * The `report_generator` class provides static methods to generate different types of reports
 * related to Moodle courses and their associated data. Each method returns an instance of the
 * `report` class configured for a specific reporting context, such as course logs, module
 * activity, grading interests, or user engagement. It utilizes SQL builders and report-specific
 * configurations to streamline the creation of complex data reports.
 */
class report_generator {

    /**
     * Creates a course dedication report, including time and dedication fields.
     *
     * @param stdClass $USER The user object for the current user.
     * @param stdClass $course The course object for which the report is generated.
     * @return report Configured report object containing course dedication data.
     */
    public static function get_course_dedication_report($USER, $course): report {
        $report = new report(
            get_string('course_dedication_log', 'mod_srg'),
            get_string('course_dedication_log_csv', 'mod_srg'),
            new sql_builder(
                [report_sql::class, 'get_course_sql_and_params'],
                $USER->id,
                $course->id
            ),
            [
                "id" => get_string('id', 'mod_srg'),
                "timecreated" => get_string('timecreated', 'mod_srg'),
                "courseid" => get_string('courseid', 'mod_srg'),
            ],
            [],
            get_string('time', 'mod_srg'),
            get_string('dedication', 'mod_srg')
        );

        return $report;
    }

    /**
     * Creates a report of course module activity logs.
     *
     * @param stdClass $USER The user object for the current user.
     * @param stdClass $course The course object for which the report is generated.
     * @return report Configured report object containing module activity logs.
     */
    public static function get_course_module_log_report($USER, $course): report {
        $report = new report(
            get_string('course_module_log', 'mod_srg'),
            get_string('course_module_log_csv', 'mod_srg'),
            new sql_builder(
                [report_sql::class, 'get_course_module_log_sql_and_params'],
                $USER->id,
                $course->id
            ),
            [
                "id" => get_string('id', 'mod_srg'),
                "timecreated" => get_string('timecreated', 'mod_srg'),
                "eventname" => get_string('eventname', 'mod_srg'),
                "component" => get_string('component', 'mod_srg'),
                "action" => get_string('action', 'mod_srg'),
                "target" => get_string('target', 'mod_srg'),
                "objecttable" => get_string('objecttable', 'mod_srg'),
                "objectid" => get_string('objectid', 'mod_srg'),
                "contextid" => get_string('contextid', 'mod_srg'),
                "contextlevel" => get_string('contextlevel', 'mod_srg'),
                "contextinstanceid" => get_string('contextinstanceid', 'mod_srg'),
                "object_name" => get_string('object_name', 'mod_srg'),
            ],
            [
                get_string('course_shortname', 'mod_srg') => $course->shortname,
                get_string('course_fullname', 'mod_srg') => $course->fullname,
            ],
            get_string('time', 'mod_srg')
        );

        return $report;
    }

    /**
     * Creates a report of course module activity with dedication data.
     *
     * @param stdClass $USER The user object for the current user.
     * @param stdClass $course The course object for which the report is generated.
     * @return report Configured report object containing module activity and dedication data.
     */
    public static function get_course_module_dedication_report($USER, $course): report {
        $report = new report(
            get_string('course_module_dedication', 'mod_srg'),
            get_string('course_module_dedication_csv', 'mod_srg'),
            new sql_builder(
                [report_sql::class, 'get_course_module_log_sql_and_params'],
                $USER->id,
                $course->id
            ),
            [
                "id" => get_string('id', 'mod_srg'),
                "timecreated" => get_string('timecreated', 'mod_srg'),
                "eventname" => get_string('eventname', 'mod_srg'),
                "component" => get_string('component', 'mod_srg'),
                "action" => get_string('action', 'mod_srg'),
                "target" => get_string('target', 'mod_srg'),
                "objecttable" => get_string('objecttable', 'mod_srg'),
                "objectid" => get_string('objectid', 'mod_srg'),
                "contextid" => get_string('contextid', 'mod_srg'),
                "contextlevel" => get_string('contextlevel', 'mod_srg'),
                "contextinstanceid" => get_string('contextinstanceid', 'mod_srg'),
                "object_name" => get_string('object_name', 'mod_srg'),
            ],
            [
                get_string('course_shortname', 'mod_srg') => $course->shortname,
                get_string('course_fullname', 'mod_srg') => $course->fullname,
            ],
            get_string('time', 'mod_srg'),
            get_string('dedication', 'mod_srg'),
            'component'
        );

        return $report;
    }

    /**
     * Creates a grading interest report for a course.
     *
     * @param stdClass $USER The user object for the current user.
     * @param stdClass $course The course object for which the report is generated.
     * @return report Configured report object containing grading interest data.
     */
    public static function get_grading_interest_report($USER, $course): report {
        $report = new report(
            get_string('grade_inspections', 'mod_srg'),
            get_string('grade_inspections_csv', 'mod_srg'),
            new sql_builder(
                [report_sql::class, 'get_grading_interest_sql_and_params'],
                $USER->id,
                $course->id
            ),
            [
                "id" => get_string('id', 'mod_srg'),
                "timecreated" => get_string('timecreated', 'mod_srg'),
                "eventname" => get_string('eventname', 'mod_srg'),
            ],
            [
                get_string('course_shortname', 'mod_srg') => $course->shortname,
                get_string('course_fullname', 'mod_srg') => $course->fullname,
            ],
            get_string('time', 'mod_srg')
        );

        return $report;
    }

    /**
     * Creates a forum activity report for a course.
     *
     * @param stdClass $USER The user object for the current user.
     * @param stdClass $course The course object for which the report is generated.
     * @return report Configured report object containing forum activity logs.
     */
    public static function get_forum_activity_report($USER, $course): report {
        $report = new report(
            get_string('forum_activities', 'mod_srg'),
            get_string('forum_activities_csv', 'mod_srg'),
            new sql_builder(
                [report_sql::class, 'get_forum_activity_sql_and_params'],
                $USER->id,
                $course->id
            ),
            [
                "id" => get_string('id', 'mod_srg'),
                "timecreated" => get_string('timecreated', 'mod_srg'),
                "eventname" => get_string('eventname', 'mod_srg'),
                "component" => get_string('component', 'mod_srg'),
                "action" => get_string('action', 'mod_srg'),
                "target" => get_string('target', 'mod_srg'),
                "objecttable" => get_string('objecttable', 'mod_srg'),
                "objectid" => get_string('objectid', 'mod_srg'),
                "name" => get_string('name', 'mod_srg'),
            ],
            [],
            get_string('time', 'mod_srg')
        );

        return $report;
    }

    /**
     * Creates an H5P (interactive content) report for a course.
     *
     * @param stdClass $USER The user object for the current user.
     * @param stdClass $course The course object for which the report is generated.
     * @return report Configured report object containing H5P activity data.
     */
    public static function get_hvp_report($USER, $course): report {
        $report = new report(
            get_string('hvp', 'mod_srg'),
            get_string('hvp_csv', 'mod_srg'),
            new sql_builder(
                [report_sql::class, 'get_hvp_sql_and_params'],
                $USER->id,
                $course->id
            ),
            [
                "id" => get_string('id', 'mod_srg'),
                "content_id" => get_string('content_id', 'mod_srg'),
                "interaction_type" => get_string('interaction_type', 'mod_srg'),
                "raw_score" => get_string('raw_score', 'mod_srg'),
                "max_score" => get_string('max_score', 'mod_srg'),
                "courseid" => get_string('courseid', 'mod_srg'),
                "timecreated" => get_string('timecreated', 'mod_srg'),
                "object_name" => get_string('object_name', 'mod_srg'),
            ],
            [
                get_string('course_shortname', 'mod_srg') => $course->shortname,
                get_string('course_fullname', 'mod_srg') => $course->fullname,
            ],
            get_string('time', 'mod_srg')
        );

        return $report;
    }

    /**
     * Creates a badges report for a course.
     *
     * @param stdClass $USER The user object for the current user.
     * @param stdClass $course The course object for which the report is generated.
     * @return report Configured report object containing badge-related data.
     */
    public static function get_badges_report($USER, $course): report {
        $report = new report(
            get_string('badges', 'mod_srg'),
            get_string('badges_csv', 'mod_srg'),
            new sql_builder(
                [report_sql::class, 'get_badges_sql_and_params'],
                $USER->id,
                $course->id
            ),
            [
                "id" => get_string('id', 'mod_srg'),
                "badgeid" => get_string('badgeid', 'mod_srg'),
                "courseid" => get_string('courseid', 'mod_srg'),
                "object_name" => get_string('object_name', 'mod_srg'),
                "timecreated" => get_string('timecreated', 'mod_srg'),
            ],
            [
                get_string('course_shortname', 'mod_srg') => $course->shortname,
                get_string('course_fullname', 'mod_srg') => $course->fullname,
            ],
            get_string('time', 'mod_srg')
        );

        return $report;
    }

    /**
     * Creates a chatbot interaction history report for a course.
     *
     * @param stdClass $USER The user object for the current user.
     * @param stdClass $course The course object for which the report is generated.
     * @return report Configured report object containing chatbot conversation history.
     */
    public static function get_chatbot_history_report($USER, $course): report {
        $report = new report(
            get_string('chatbot_history', 'mod_srg'),
            get_string('chatbot_history_csv', 'mod_srg'),
            new sql_builder(
                [report_sql::class, 'get_chatbot_history_sql_and_params'],
                $USER->id,
                $course->id
            ),
            [
                "id" => get_string('id', 'mod_srg'),
                "timecreated" => get_string('timecreated', 'mod_srg'),
                "speaker" => get_string('speaker', 'mod_srg'),
                "message" => get_string('message', 'mod_srg'),
                "act" => get_string('act', 'mod_srg'),
            ],
            [
                get_string('course_shortname', 'mod_srg') => $course->shortname,
                get_string('course_fullname', 'mod_srg') => $course->fullname,
            ],
            get_string('time', 'mod_srg')
        );

        return $report;
    }
}

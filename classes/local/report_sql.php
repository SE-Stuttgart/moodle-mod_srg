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
 * TODO DESCRIPTION
 * 
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg\local;

use mod_srg\local\sql_generator;

class report_sql {

    public static function get_course_sql_and_params($userid, $courseid): array {
        // WHERE sql.
        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
        ];
        $where = "
            WHERE
                logstore_standard_log.userid = :userid
                AND logstore_standard_log.courseid = :courseid
                AND logstore_standard_log.id IS NOT NULL
                AND logstore_standard_log.timecreated IS NOT NULL
        ";

        // SELECT sql.
        $select = "
            SELECT
                logstore_standard_log.id,
                logstore_standard_log.timecreated,
                logstore_standard_log.courseid,
                logstore_standard_log.eventname,
                logstore_standard_log.component,
                logstore_standard_log.action,
                logstore_standard_log.target,
                logstore_standard_log.objecttable,
                logstore_standard_log.objectid,
                logstore_standard_log.contextid,
                logstore_standard_log.contextlevel,
                logstore_standard_log.contextinstanceid
        ";

        // FROM sql.
        $from = "
            FROM
                {logstore_standard_log} logstore_standard_log
        ";

        return [$select, $from, $where, $params];
    }

    public static function get_course_module_log_sql_and_params($userid, $courseid): array {
        global $DB;

        // WHERE sql.
        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
        ];
        list($targetconstraint, $targetconstraintparams) = $DB->get_in_or_equal([
            'course_module',
            'course_content',
            'course_bin_item',
            'h5p',
            'attempt',
            'chapter',
            'question',
        ], SQL_PARAMS_NAMED);
        list($actionconstraint, $actionconstraintparams) = $DB->get_in_or_equal([
            'viewed',
            'failed',
            'started',
            'submitted',
        ], SQL_PARAMS_NAMED);
        $params = array_merge($params, $targetconstraintparams, $actionconstraintparams);
        $where = "
            WHERE
                logstore_standard_log.userid = :userid
                AND logstore_standard_log.courseid = :courseid
                AND logstore_standard_log.id IS NOT NULL
                AND logstore_standard_log.objecttable IS NOT NULL
                    AND logstore_standard_log.objecttable <> ''
                AND logstore_standard_log.objectid IS NOT NULL
                AND logstore_standard_log.target {$targetconstraint}
                AND logstore_standard_log.action {$actionconstraint}
        ";


        // List of tables connected to logstore_standard_log, found in logstore_standard_log.objecttable.
        $defaultfields = sql_generator::get_default_tables_from_field(
            "SELECT DISTINCT logstore_standard_log.objecttable FROM {logstore_standard_log} logstore_standard_log {$where}",
            $params,
            "objecttable",
            // Tables with special custom behaviour of those found in logstore_standard_log.objecttable.
            [
                "book_chapters",
            ],
            // Required fields for the default behaviour.
            [
                "name",
            ]
        );

        // SELECT sql.
        $aliasobjectnamecase = sql_generator::get_switch_select_field(
            "logstore_standard_log",
            "objecttable",
            $defaultfields,
            "name",
            [
                "book_chapters" => "book_chapters_custom.title",
            ],
            "object_name"
        );
        $select = "
            SELECT
                logstore_standard_log.id,
                logstore_standard_log.timecreated,
                logstore_standard_log.eventname,
                logstore_standard_log.component,
                logstore_standard_log.action,
                logstore_standard_log.target,
                logstore_standard_log.objecttable,
                logstore_standard_log.objectid,
                logstore_standard_log.contextid,
                logstore_standard_log.contextlevel,
                logstore_standard_log.contextinstanceid,
                {$aliasobjectnamecase}
        ";

        // FROM sql.
        $defaultleftjoin = sql_generator::get_default_left_joins(
            "logstore_standard_log.objecttable",
            "logstore_standard_log.objectid",
            $defaultfields,
            "id"
        );
        $from = "
            FROM
                {logstore_standard_log} logstore_standard_log
            {$defaultleftjoin}
            LEFT JOIN
                {book_chapters} book_chapters_custom
                ON logstore_standard_log.objecttable = 'book_chapters'
                AND logstore_standard_log.objectid = book_chapters_custom.id
            
        ";

        return [$select, $from, $where, $params];
    }

    public static function get_grading_interest_sql_and_params($userid, $courseid): array {
        global $DB;

        // WHERE sql.
        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
        ];
        list($eventnameconstraint, $eventnameconstraintparams) = $DB->get_in_or_equal([
            '\mod_assign\event\grading_table_viewed',
            '\mod_assign\event\grading_form_viewed',
            '\gradereport_user\event\grade_report_viewed',
            '\gradereport_overview\event\grade_report_viewed',
            '\gradereport_grader\event\grade_report_viewed',
            '\gradereport_outcomes\event\grade_report_viewed',
            '\gradereport_singleview\event\grade_report_viewed',
        ], SQL_PARAMS_NAMED);
        $params = array_merge($params, $eventnameconstraintparams);
        $where = "
            WHERE
                logstore_standard_log.userid = :userid
                AND logstore_standard_log.courseid = :courseid
                AND logstore_standard_log.id IS NOT NULL
                AND logstore_standard_log.eventname {$eventnameconstraint}
        ";

        // SELECT sql.
        $select = "
            SELECT
                logstore_standard_log.id,
                logstore_standard_log.timecreated,
                logstore_standard_log.eventname
        ";

        // FROM sql.
        $from = "
            FROM
                {logstore_standard_log} logstore_standard_log
        ";

        return [$select, $from, $where, $params];
    }

    public static function get_forum_activity_sql_and_params($userid, $courseid): array {
        // WHERE sql.
        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
        ];
        $where = "
            WHERE
                logstore_standard_log.userid = :userid
                AND logstore_standard_log.courseid = :courseid
                AND logstore_standard_log.id IS NOT NULL
                AND logstore_standard_log.timecreated IS NOT NULL
                AND logstore_standard_log.component = 'mod_forum'
            ";

        // List of tables connected to logstore_standard_log, found in logstore_standard_log.objecttable.
        $defaultfields = sql_generator::get_default_tables_from_field(
            "SELECT DISTINCT logstore_standard_log.objecttable FROM {logstore_standard_log} logstore_standard_log {$where}",
            $params,
            "objecttable",
            // Tables with special custom behaviour of those found in logstore_standard_log.objecttable.
            [
                "forum_posts",
            ],
            // Required fields for the default behaviour.
            [
                "name",
            ]
        );

        // SELECT sql.
        $aliasnamecase = sql_generator::get_switch_select_field(
            "logstore_standard_log",
            "objecttable",
            $defaultfields,
            "name",
            [
                "forum_posts" => "forum_discussions_custom.name",
            ],
            "name"
        );
        $select = "
            SELECT
                logstore_standard_log.id,
                logstore_standard_log.timecreated,
                logstore_standard_log.eventname,
                logstore_standard_log.component,
                logstore_standard_log.action,
                logstore_standard_log.target,
                logstore_standard_log.objecttable,
                logstore_standard_log.objectid,
                {$aliasnamecase}
            ";

        // FROM sql.
        $defaultleftjoin = sql_generator::get_default_left_joins(
            "logstore_standard_log.objecttable",
            "logstore_standard_log.objectid",
            $defaultfields,
            "id"
        );
        $from = "
            FROM
                {logstore_standard_log} logstore_standard_log
            {$defaultleftjoin}
            LEFT JOIN
                {forum_posts} forum_posts_custom
                ON logstore_standard_log.objecttable = 'forum_posts'
                AND logstore_standard_log.objectid = forum_posts_custom.id
            LEFT JOIN
                {forum_discussions} forum_discussions_custom
                ON forum_posts_custom.discussion = forum_discussions_custom.id
            ";

        return [$select, $from, $where, $params];
    }

    public static function get_hvp_sql_and_params($userid, $courseid): array {
        // WHERE sql.
        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
        ];
        $where = "
            WHERE
                hvp_xapi_results.userid = :userid
                AND hvp_custom.course = :courseid
                AND hvp_xapi_results.id IS NOT NULL
                AND hvp_xapi_results.content_id IS NOT NULL
        ";

        // SELECT sql.
        $select = "
            SELECT
                hvp_xapi_results.id,
                hvp_xapi_results.content_id,
                hvp_xapi_results.interaction_type,
                hvp_xapi_results.raw_score,
                hvp_xapi_results.max_score,
                hvp_custom.course AS courseid,
                hvp_custom.timecreated,
                hvp_custom.name AS object_name
        ";

        // FROM sql.
        $from = "
            FROM
                {hvp_xapi_results} hvp_xapi_results
            LEFT JOIN
                {hvp} hvp_custom
                ON hvp_xapi_results.content_id = hvp_custom.id
        ";

        return [$select, $from, $where, $params];
    }

    public static function get_badges_sql_and_params($userid, $courseid): array {
        // WHERE sql.
        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
        ];
        $where = "
            WHERE
                badge_issued.userid = :userid
                AND badge_custom.courseid = :courseid
                AND badge_issued.id IS NOT NULL
                AND badge_issued.badgeid IS NOT NULL
        ";

        // SELECT sql.
        $select = "
            SELECT
                badge_issued.id,
                badge_issued.badgeid,
                badge_custom.courseid,
                badge_custom.timecreated,
                badge_custom.name AS object_name
        ";

        // FROM sql.
        $from = "
            FROM
                {badge_issued} badge_issued
            LEFT JOIN
                {badge} badge_custom
                ON badge_issued.badgeid = badge_custom.id
        ";

        return [$select, $from, $where, $params];
    }

    public static function get_chatbot_history_sql_and_params($userid, $courseid): array {
        // WHERE sql.
        $params = [
            'userid' => $userid,
            'courseid' => $courseid,
        ];
        $where = "
            WHERE
                chatbot_history.userid = :userid
                AND chatbot_history.courseid = :courseid
                AND chatbot_history.id IS NOT NULL
        ";

        // SELECT sql.
        $select = "
            SELECT
                chatbot_history.id,
                chatbot_history.timecreated,
                chatbot_history.speaker,
                chatbot_history.message,
                chatbot_history.act
        ";

        // FROM sql.
        $from = "
            FROM
                {chatbot_history} chatbot_history
        ";

        return [$select, $from, $where, $params];
    }
}

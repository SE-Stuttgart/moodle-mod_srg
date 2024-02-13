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
 * List of Hardcoded SQL Query Params
 *
 * @package     mod_srg
 * @copyright   2023 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/table_from_db.php');

/**
 * Class that has multiple static hard coded function, each creating and returning one set of log data.
 */
class db_sql {
    /**
     * This function returns all entries from the course log db table.
     *
     * @param mixed $USER The current user.
     * @param Course $course The course this activity belongs to.
     *
     * @return array Table containing set of log data.
     */
    public static function get_course_log($USER, $course) {
        return (new table_from_db(
            'logstore_standard_log',
            [
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
            ],
            [
                'eventname' => 'eventname',
                'component' => 'component',
                'action' => 'action',
                'target' => 'target',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid',
                'contextid' => 'contextid',
                'contextlevel' => 'contextlevel',
                'contextinstanceid' => 'contextinstanceid',
                'timecreated' => 'timecreated',
            ],
            [
                'id' => 'id',
                'timecreated' => 'timecreated',
            ]
        ))
            ->add_human_time('Time')
            ->add_constant_columns([
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ])
            ->get_table();
    }

    /**
     * This function returns all entries from the course log db table.
     * The entries are grouped by "dedication".
     * This means, entries that are timed close together get grouped together
     * and the time difference in this group is "dedication", how much time was spent on this group.
     *
     * @param mixed $USER The current user.
     * @param Course $course The course this activity belongs to.
     *
     * @return array Table containing set of log data.
     */
    public static function get_course_dedication($USER, $course) {
        return (new table_from_db(
            'logstore_standard_log',
            [
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
            ],
            [
                'courseid' => 'Course ID',
                'timecreated' => 'timecreated',
            ],
            [
                'id' => 'id',
                'timecreated' => 'timecreated',
            ]
        ))
            ->add_dedication('Dedication')
            ->add_human_time('Time')
            ->get_table();
    }

    /**
     * This function returns all entries from the course log db table that have selected targets and actions.
     * This data is expanded by information not found in the standard log db table.
     *
     * @param mixed $USER The current user.
     * @param Course $course The course this activity belongs to.
     *
     * @return array Table containing set of log data.
     */
    public static function get_course_module_log($USER, $course) {
        return (new table_from_db(
            'logstore_standard_log',
            [
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
                '(target="course_module" or target="course_content" or target="course_bin_item"'
                    . ' or target="h5p" or target="attempt" or target="chapter" or target="question")',
                '(action="viewed" or action="failed" or action="started" or action="submitted")',
            ],
            [
                'eventname' => 'eventname',
                'component' => 'component',
                'action' => 'action',
                'target' => 'target',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid',
                'contextid' => 'contextid',
                'contextlevel' => 'contextlevel',
                'contextinstanceid' => 'contextinstanceid',
                'courseid' => 'courseid',
                'timecreated' => 'timecreated',
            ],
            [
                'id' => 'id',
                'timecreated' => 'timecreated',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid',
            ]
        ))
            ->nest_query(
                false,
                'objecttable',
                'objectid',
                [],
                [table_from_db::DEFAULT => []],
                [
                    table_from_db::DEFAULT => ['name' => 'object_name'],
                    'book_chapters' => ['title' => 'object_name'],
                ],
                [table_from_db::DEFAULT => []]
            )
            ->add_human_time('Time')
            ->add_constant_columns([
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ])->rename_columns(['object_name' => 'Object Name'])
            ->get_table();
    }


    /**
     * This function returns all entries from the course log db table.
     * The entries are grouped by "dedication".
     * This means, entries that are timed close together and belonging to the same component get grouped together
     * and the time difference in this group is "dedication", how much time was spent on this group.
     * This data is expanded by information not found in the standard log db table.
     *
     * @param mixed $USER The current user.
     * @param Course $course The course this activity belongs to.
     *
     * @return array Table containing set of log data.
     */
    public static function get_course_module_dedication($USER, $course) {
        return (new table_from_db(
            'logstore_standard_log',
            [
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
                '(target="course_module" or target="course_content" or target="course_bin_item"'
                    . ' or target="h5p" or target="attempt" or target="chapter" or target="question")',
                '(action="viewed" or action="failed" or action="started" or action="submitted")',
            ],
            [
                'eventname' => 'eventname',
                'component' => 'component',
                'action' => 'action',
                'target' => 'target',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid',
                'contextid' => 'contextid',
                'contextlevel' => 'contextlevel',
                'contextinstanceid' => 'contextinstanceid',
                'courseid' => 'courseid',
                'timecreated' => 'timecreated',
            ],
            [
                'id' => 'id',
                'timecreated' => 'timecreated',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid',
            ]
        ))
            ->add_dedication(
                'Dedication',
                'component',
            )
            ->nest_query(
                false,
                'objecttable',
                'objectid',
                [],
                [table_from_db::DEFAULT => []],
                [
                    table_from_db::DEFAULT => ['name' => 'object_name'],
                    'book_chapters' => ['title' => 'object_name'],
                ],
                [table_from_db::DEFAULT => []]
            )
            ->add_human_time('Time')
            ->add_constant_columns([
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ])->rename_columns(['object_name' => 'Object Name'])
            ->get_table();
    }

    /**
     * This function returns all entries from the course log db table
     * that have information about the user accessing their grades.
     *
     * @param mixed $USER The current user.
     * @param Course $course The course this activity belongs to.
     *
     * @return array Table containing set of log data.
     */
    public static function get_grading_interest($USER, $course) {
        return (new table_from_db(
            'logstore_standard_log',
            [
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
                'eventname="\\\\mod_assign\\\\event\\\\grading_table_viewed"'
                    . ' or eventname="\\\\mod_assign\\\\event\\\\grading_form_viewed"'
                    . ' or eventname="\\\\gradereport_user\\\\event\\\\grade_report_viewed"'
                    . ' or eventname="\\\\gradereport_overview\\\\event\\\\grade_report_viewed"'
                    . ' or eventname="\\\\gradereport_grader\\\\event\\\\grade_report_viewed"'
                    . ' or eventname="\\\\gradereport_outcomes\\\\event\\\\grade_report_viewed"'
                    . ' or eventname="\\\\gradereport_singleview\\\\event\\\\grade_report_viewed"',
            ],
            [
                'eventname' => 'eventname',
                'timecreated' => 'timecreated',
            ],
            [
                'id' => 'id',
                'timecreated' => 'timecreated',
            ],
            'timecreated ASC'
        ))
            ->rename_columns(['eventname' => 'Eventname'])
            ->add_human_time('Time')
            ->add_constant_columns([
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ])
            ->get_table();
    }

    /**
     * This function returns all entries from the course log db table
     * that have information about the user using a forum.
     *
     * @param mixed $USER The current user.
     * @param Course $course The course this activity belongs to.
     *
     * @return array Table containing set of log data.
     */
    public static function get_forum_activity($USER, $course) {
        return (new table_from_db(
            'logstore_standard_log',
            [
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
                'component="mod_forum"',
            ],
            [
                'eventname' => 'eventname',
                'component' => 'component',
                'action' => 'action',
                'target' => 'target',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid',
                'timecreated' => 'timecreated',
            ],
            [
                'id' => 'id',
                'timecreated' => 'timecreated',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid',
            ]
        ))
            ->nest_query(
                false,
                'objecttable',
                'objectid',
                [],
                [table_from_db::DEFAULT => []],
                [
                    table_from_db::DEFAULT => ['name' => 'name'],
                    'forum_posts' => [],
                ],
                [
                    table_from_db::DEFAULT => [],
                    'forum_posts' => ['discussion' => 'discussion'],
                ]
            )
            ->nest_query(
                true,
                'forum_discussions',
                'discussion',
                ['objecttable' => 'forum_posts'],
                [table_from_db::DEFAULT => []],
                [table_from_db::DEFAULT => ['name' => 'name']],
                [table_from_db::DEFAULT => []]
            )
            ->rename_columns([])
            ->add_human_time('Time')
            ->add_constant_columns([])
            ->get_table();
    }

    /**
     * This function returns all entries from the course log db table
     * that have information about the users interaction with hvp content.
     *
     * @param mixed $USER The current user.
     * @param Course $course The course this activity belongs to.
     *
     * @return array Table containing set of log data.
     */
    public static function get_hvp($USER, $course) {
        return (new table_from_db(
            'hvp_xapi_results',
            ['user_id = ' . $USER->id],
            [
                'content_id' => 'content_id',
                'interaction_type' => 'interaction_type',
                'raw_score' => 'raw_score',
                'max_score' => 'max_score',
            ],
            [
                'id' => 'id',
                'content_id' => 'content_id',
            ]
        ))
            ->nest_query(
                true,
                'hvp',
                'content_id',
                [],
                [table_from_db::DEFAULT => ['course = ' . $course->id]],
                [
                    table_from_db::DEFAULT => ['name' => 'object_name'],
                    'book_chapters' => ['title' => 'object_name'],
                ],
                [table_from_db::DEFAULT => ['timecreated' => 'timecreated']]
            )
            ->add_human_time('Time')
            ->add_constant_columns([
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ])->rename_columns(['object_name' => 'Object Name'])
            ->get_table();
    }

    /**
     * This function returns all entries from the course log db table
     * that have information about the users badges.
     *
     * @param mixed $USER The current user.
     * @param Course $course The course this activity belongs to.
     *
     * @return array Table containing set of log data.
     */
    public static function get_badges($USER, $course) {
        return (new table_from_db(
            'badge_issued',
            ['userid = ' . $USER->id],
            ['badgeid' => 'badgeid'],
            [
                'id' => 'id',
                'badgeid' => 'badgeid',
            ]
        ))
            ->nest_query(
                true,
                'badge',
                'badgeid',
                [],
                [table_from_db::DEFAULT => ['course = ' . $course->id]],
                [table_from_db::DEFAULT => ['name' => 'name']],
                [table_from_db::DEFAULT => []]
            )
            ->add_human_time('Time')
            ->add_constant_columns([
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ])->rename_columns(['object_name' => 'Object Name'])
            ->get_table();
    }
}

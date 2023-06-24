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


defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/classes/db_conn/table_from_db.php');


class srg_log
{
    public static function GetCourseLog($USER, $course)
    {
        return (new table_from_db(
            'logstore_standard_log',
            array(
                'userid = ' . $USER->id,
                'courseid = ' . $course->id
            ),
            array(
                'eventname' => 'eventname',
                'component' => 'component',
                'action' => 'action',
                'target' => 'target',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid',
                'contextid' => 'contextid',
                'contextlevel' => 'contextlevel',
                'contextinstanceid' => 'contextinstanceid',
                'timecreated' => 'timecreated'
            ),
            array(
                'id' => 'id',
                'timecreated' => 'timecreated'
            )
        ))
            ->add_human_time('Time')
            ->add_constant_columns(array(
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ))
            ->get_table();
    }

    public static function GetCourseDedication($USER, $course)
    {
        return (new table_from_db(
            'logstore_standard_log',
            array(
                'userid = ' . $USER->id,
                'courseid = ' . $course->id
            ),
            array(
                'courseid' => 'Course ID',
                'timecreated' => 'timecreated'
            ),
            array(
                'id' => 'id',
                'timecreated' => 'timecreated'
            )
        ))
            ->add_dedication('Dedication')
            ->add_human_time('Time')
            ->get_table();
    }

    public static function GetCourseModuleLog($USER, $course)
    {
        return (new table_from_db(
            'logstore_standard_log',
            array(
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
                '(target="course_module" or target="course_content" or target="course_bin_item" or target="h5p" or target="attempt" or target="chapter" or target="question")',
                '(action="viewed" or action="failed" or action="started" or action="submitted")'
            ),
            array(
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
                'timecreated' => 'timecreated'
            ),
            array(
                'id' => 'id',
                'timecreated' => 'timecreated',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid'
            )
        ))
            ->nest_query(
                false,
                'objecttable',
                'objectid',
                array(),
                array(table_from_db::DEFAULT => array()),
                array(
                    table_from_db::DEFAULT => array(
                        'name' => 'object_name'
                    ),
                    'book_chapters' => array(
                        'title' => 'object_name'
                    )
                ),
                array(
                    table_from_db::DEFAULT => array()
                )
            )
            ->add_human_time('Time')
            ->add_constant_columns(array(
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ))->rename_columns(array(
                'object_name' => 'Object Name'
            ))
            ->get_table();
    }

    public static function GetCourseModuleDedication($USER, $course)
    {
        return (new table_from_db(
            'logstore_standard_log',
            array(
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
                '(target="course_module" or target="course_content" or target="course_bin_item" or target="h5p" or target="attempt" or target="chapter" or target="question")',
                '(action="viewed" or action="failed" or action="started" or action="submitted")'
            ),
            array(
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
                'timecreated' => 'timecreated'
            ),
            array(
                'id' => 'id',
                'timecreated' => 'timecreated',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid'
            )
        ))
            ->add_dedication(
                'Dedication',
                'component'
            )
            ->nest_query(
                false,
                'objecttable',
                'objectid',
                array(),
                array(table_from_db::DEFAULT => array()),
                array(
                    table_from_db::DEFAULT => array(
                        'name' => 'object_name'
                    ),
                    'book_chapters' => array(
                        'title' => 'object_name'
                    )
                ),
                array(
                    table_from_db::DEFAULT => array()
                )
            )
            ->add_human_time('Time')
            ->add_constant_columns(array(
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ))->rename_columns(array(
                'object_name' => 'Object Name'
            ))
            ->get_table();
    }

    public static function GetGradingInterest($USER, $course)
    {
        return (new table_from_db(
            'logstore_standard_log',
            array(
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
                'eventname="\\\\mod_assign\\\\event\\\\grading_table_viewed"'
                    . ' or eventname="\\\\mod_assign\\\\event\\\\grading_form_viewed"'
                    . ' or eventname="\\\\gradereport_user\\\\event\\\\grade_report_viewed"'
                    . ' or eventname="\\\\gradereport_overview\\\\event\\\\grade_report_viewed"'
                    . ' or eventname="\\\\gradereport_grader\\\\event\\\\grade_report_viewed"'
                    . ' or eventname="\\\\gradereport_outcomes\\\\event\\\\grade_report_viewed"'
                    . ' or eventname="\\\\gradereport_singleview\\\\event\\\\grade_report_viewed"'
            ),
            array(
                'eventname' => 'eventname',
                'timecreated' => 'timecreated'
            ),
            array(
                'id' => 'id',
                'timecreated' => 'timecreated'
            ),
            'timecreated ASC'
        ))
            ->rename_columns(array(
                'eventname' => 'Eventname',
            ))
            ->add_human_time('Time')
            ->add_constant_columns(array(
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ))
            ->get_table();
    }

    public static function GetForumActivity($USER, $course)
    {
        return (new table_from_db(
            'logstore_standard_log',
            array(
                'userid = ' . $USER->id,
                'courseid = ' . $course->id,
                'component="mod_forum"'
            ),
            array(
                'eventname' => 'eventname',
                'component' => 'component',
                'action' => 'action',
                'target' => 'target',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid',
                'timecreated' => 'timecreated'
            ),
            array(
                'id' => 'id',
                'timecreated' => 'timecreated',
                'objecttable' => 'objecttable',
                'objectid' => 'objectid'
            )
        ))
            ->nest_query(
                false,
                'objecttable',
                'objectid',
                array(),
                array(table_from_db::DEFAULT => array()),
                array(
                    table_from_db::DEFAULT => array(
                        'name' => 'name'
                    ),
                    'forum_posts' => array()
                ),
                array(
                    table_from_db::DEFAULT => array(),
                    'forum_posts' => array(
                        'discussion' => 'discussion'
                    )
                )
            )
            ->nest_query(
                true,
                'forum_discussions',
                'discussion',
                array('objecttable' => 'forum_posts'),
                array(table_from_db::DEFAULT => array()),
                array(
                    table_from_db::DEFAULT => array(
                        'name' => 'name'
                    )
                ),
                array(
                    table_from_db::DEFAULT => array()
                )
            )
            ->rename_columns(array())
            ->add_human_time('Time')
            ->add_constant_columns(array())
            ->get_table();
    }

    public static function GETHVP($USER, $course)
    {
        return (new table_from_db(
            'hvp_xapi_results',
            array(
                'userid = ' . $USER->id,
            ),
            array(
                'content_id' => 'content_id',
                'interaction_type' => 'interaction_type',
                'raw_score' => 'raw_score',
                'max_score' => 'max_score'
            ),
            array(
                'id' => 'id',
                'content_id' => 'content_id'
            )
        ))
            ->nest_query(
                true,
                'hvp',
                'content_id',
                array(),
                array(table_from_db::DEFAULT => array(
                    'course = ' . $course->id
                )),
                array(
                    table_from_db::DEFAULT => array(
                        'name' => 'object_name'
                    ),
                    'book_chapters' => array(
                        'title' => 'object_name'
                    )
                ),
                array(
                    table_from_db::DEFAULT => array()
                )
            )
            ->add_human_time('Time')
            ->add_constant_columns(array(
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ))->rename_columns(array(
                'object_name' => 'Object Name'
            ))
            ->get_table();
    }

    public static function GETBadges($USER, $course)
    {
        return (new table_from_db(
            'badge_issued',
            array(
                'userid = ' . $USER->id,
            ),
            array(
                'badgeid' => 'badgeid',
            ),
            array(
                'id' => 'id',
                'badgeid' => 'badgeid'
            )
        ))
            ->nest_query(
                true,
                'badge',
                'badgeid',
                array(),
                array(table_from_db::DEFAULT => array(
                    'course = ' . $course->id
                )),
                array(
                    table_from_db::DEFAULT => array(
                        'name' => 'name'
                    )
                ),
                array(
                    table_from_db::DEFAULT => array()
                )
            )
            ->add_human_time('Time')
            ->add_constant_columns(array(
                'course_shortname' => $course->shortname,
                'course_fullname' => $course->fullname,
            ))->rename_columns(array(
                'object_name' => 'Object Name'
            ))
            ->get_table();
    }
}

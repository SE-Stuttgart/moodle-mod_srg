<?php
// This file is part of the scientific work at the University of Stuttgart

/**
 * Version details
 *
 * @package    mod_srg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/db_conn.php');

/**
 * List of Hardcoded SQL Query Params
 */
class srg_log
{
    public static function GetCourseLog($USER, $course)
    {
        return srg_db_conn::build_simple_DB_table(new srg_db_query(
            $table = 'logstore_standard_log',
            $table_type = SRG_TABLE_STATIC,
            $select = 'userid = ? and courseid = ?',
            $params = array(
                'userid' => $USER->id,
                'courseid' => $course->id
            ),
            $columns = array(
                new srg_db_column('eventname', 'eventname'),
                new srg_db_column('component', 'component'),
                new srg_db_column('action', 'action'),
                new srg_db_column('target', 'target'),
                new srg_db_column('objecttable', 'objecttable'),
                new srg_db_column('objectid', 'objectid'),
                new srg_db_column('contextid', 'contextid'),
                new srg_db_column('contextlevel', 'contextlevel'),
                new srg_db_column('contextinstanceid', 'contextinstanceid'),
                new srg_db_column('courseid', 'courseid'),
                new srg_db_column('timecreated', 'timecreated'),
                new srg_db_column('course_shortname', $course->shortname, SRG_COLUMN_SOURCE_STATIC),
                new srg_db_column('course_fullname', $course->fullname, SRG_COLUMN_SOURCE_STATIC)
            ),
            $sort = 'timecreated ASC',
            $options = array(
                'human_time' => 'Time'
            ),
            $nested_queries_columns = array(),
            $nested_queries = array()
        ));
    }

    public static function GetCourseDedication($USER, $course)
    {
        return srg_db_conn::build_simple_DB_table(new srg_db_query(
            $table = 'logstore_standard_log',
            $table_type = SRG_TABLE_STATIC,
            $select = 'userid = ? and courseid = ?',
            $params = array(
                'userid' => $USER->id,
                'courseid' => $course->id
            ),
            $columns = array(
                new srg_db_column('courseid', 'courseid'),
                new srg_db_column('timecreated', 'timecreated'),
            ),
            $sort = 'timecreated ASC',
            $options = array(
                'human_time' => 'Time',
                'dedication' => 'Dedication'
            ),
            $nested_queries_columns = array(),
            $nested_queries = array()
        ));
    }

    public static function GetCourseModuleLog($USER, $course)
    {
        return srg_db_conn::build_simple_DB_table(new srg_db_query(
            $table = 'logstore_standard_log',
            $table_type = SRG_TABLE_STATIC,
            $select = 'userid = ? and courseid = ?
                    and (
                        action="viewed" or action="failed" or action="started" or action="submitted"
                        )
                    and (
                        target="course_module" or target="course_content" or target="course_bin_item" 
                        or target="h5p" or target="attempt" or target="chapter" or target="question"
                    )',
            $params = array(
                'userid' => $USER->id,
                'courseid' => $course->id
            ),
            $columns = array(
                new srg_db_column('eventname', 'eventname'),
                new srg_db_column('component', 'component'),
                new srg_db_column('action', 'action'),
                new srg_db_column('target', 'target'),
                new srg_db_column('objecttable', 'objecttable'),
                new srg_db_column('objectid', 'objectid'),
                new srg_db_column('contextid', 'contextid'),
                new srg_db_column('contextlevel', 'contextlevel'),
                new srg_db_column('contextinstanceid', 'contextinstanceid'),
                new srg_db_column('courseid', 'courseid'),
                new srg_db_column('timecreated', 'timecreated'),
                new srg_db_column('course_shortname', $course->shortname, SRG_COLUMN_SOURCE_STATIC),
                new srg_db_column('course_fullname', $course->fullname, SRG_COLUMN_SOURCE_STATIC)
            ),
            $sort = 'timecreated ASC',
            $options = array(
                'human_time' => 'Time'
            ),
            $nested_queries_columns = array(
                SRG_OPTIONAL => $object_name = 'object_name'
            ),
            $nested_queries = array(
                new srg_db_query(
                    $table = SRG_TABLE_DEFAULT,
                    $table_type = SRG_TABLE_VARIABLE,
                    $select = '',
                    $params = array(),
                    $columns = array(
                        new srg_db_column($object_name, 'name'),
                    ),
                    $sort = '',
                    $options = array(
                        'table_source' => 'objecttable',
                        'id_source' => 'objectid'
                    ),
                    $nested_queries_columns = array(),
                    $nested_queries = array()

                ), new srg_db_query(
                    $table = 'book_chapters',
                    $table_type = SRG_TABLE_VARIABLE,
                    $select = '',
                    $params = array(),
                    $columns = array(
                        new srg_db_column($object_name, 'title'),
                    ),
                    $sort = '',
                    $options = array(
                        'table_source' => 'objecttable',
                        'id_source' => 'objectid'
                    ),
                    $nested_queries_columns = array(),
                    $nested_queries = array()

                )
            )
        ));
    }

    public static function GetCourseModuleDedication($USER, $course)
    {
        return srg_db_conn::build_simple_DB_table(new srg_db_query(
            $table = 'logstore_standard_log',
            $table_type = SRG_TABLE_STATIC,
            $select = 'userid = ? and courseid = ?
                    and (
                        action="viewed" or action="failed" or action="started" or action="submitted"
                        )
                    and (
                        target="course_module" or target="course_content" or target="course_bin_item" 
                        or target="h5p" or target="attempt" or target="chapter" or target="question"
                    )',
            $params = array(
                'userid' => $USER->id,
                'courseid' => $course->id
            ),
            $columns = array(
                new srg_db_column('eventname', 'eventname'),
                new srg_db_column('component', 'component'),
                new srg_db_column('action', 'action'),
                new srg_db_column('target', 'target'),
                new srg_db_column('objecttable', 'objecttable'),
                new srg_db_column('objectid', 'objectid'),
                new srg_db_column('contextid', 'contextid'),
                new srg_db_column('contextlevel', 'contextlevel'),
                new srg_db_column('contextinstanceid', 'contextinstanceid'),
                new srg_db_column('courseid', 'courseid'),
                new srg_db_column('timecreated', 'timecreated'),
                new srg_db_column('course_shortname', $course->shortname, SRG_COLUMN_SOURCE_STATIC),
                new srg_db_column('course_fullname', $course->fullname, SRG_COLUMN_SOURCE_STATIC)
            ),
            $sort = 'timecreated ASC',
            $options = array(
                'human_time' => 'Time',
                'dedication' => 'Dedication',
                'dedication_target' => 'component'
            ),
            $nested_queries_columns = array(
                SRG_OPTIONAL => $object_name = 'object_name'
            ),
            $nested_queries = array(
                new srg_db_query(
                    $table = SRG_TABLE_DEFAULT,
                    $table_type = SRG_TABLE_VARIABLE,
                    $select = '',
                    $params = array(),
                    $columns = array(
                        new srg_db_column($object_name, 'name'),
                    ),
                    $sort = '',
                    $options = array(
                        'table_source' => 'objecttable',
                        'id_source' => 'objectid'
                    ),
                    $nested_queries_columns = array(),
                    $nested_queries = array()

                ), new srg_db_query(
                    $table = 'book_chapters',
                    $table_type = SRG_TABLE_VARIABLE,
                    $select = '',
                    $params = array(),
                    $columns = array(
                        new srg_db_column($object_name, 'title'),
                    ),
                    $sort = '',
                    $options = array(
                        'table_source' => 'objecttable',
                        'id_source' => 'objectid'
                    ),
                    $nested_queries_columns = array(),
                    $nested_queries = array()

                )
            )
        ));
    }

    public static function GetGradingInterest($USER, $course)
    {
        return srg_db_conn::build_simple_DB_table(new srg_db_query(
            $table = 'logstore_standard_log',
            $table_type = SRG_TABLE_STATIC,
            $select = 'userid = ? and courseid = ?
            and (
                eventname="\\\\mod_assign\\\\event\\\\grading_table_viewed" 
                or eventname="\\\\mod_assign\\\\event\\\\grading_form_viewed" 
                or eventname="\\\\gradereport_user\\\\event\\\\grade_report_viewed" 
                or eventname="\\\\gradereport_overview\\\\event\\\\grade_report_viewed" 
                or eventname="\\\\gradereport_grader\\\\event\\\\grade_report_viewed"
                or eventname="\\\\gradereport_outcomes\\\\event\\\\grade_report_viewed" 
                or eventname="\\\\gradereport_singleview\\\\event\\\\grade_report_viewed"
            )',
            $params = array(
                'userid' => $USER->id,
                'courseid' => $course->id
            ),
            $columns = array(
                new srg_db_column('eventname', 'eventname'),
                new srg_db_column('timecreated', 'timecreated'),
                new srg_db_column('course_shortname', $course->shortname, SRG_COLUMN_SOURCE_STATIC),
                new srg_db_column('course_fullname', $course->fullname, SRG_COLUMN_SOURCE_STATIC)
            ),
            $sort = 'timecreated ASC',
            $options = array(
                'human_time' => 'Time'
            ),
            $nested_queries_columns = array(),
            $nested_queries = array()
        ));
    }

    public static function GetForumActivity($USER, $course)
    {
        return srg_db_conn::build_simple_DB_table(new srg_db_query(
            $table = 'logstore_standard_log',
            $table_type = SRG_TABLE_STATIC,
            $select = 'userid = ? and courseid = ? 
            and component="mod_forum"',
            $params = array(
                'userid' => $USER->id,
                'courseid' => $course->id
            ),
            $columns = array(
                new srg_db_column('eventname', 'eventname'),
                new srg_db_column('component', 'component'),
                new srg_db_column('action', 'action'),
                new srg_db_column('target', 'target'),
                new srg_db_column('objecttable', 'objecttable'),
                new srg_db_column('objectid', 'objectid'),
                new srg_db_column('timecreated', 'timecreated')
            ),
            $sort = 'timecreated ASC',
            $options = array(
                'human_time' => 'Time'
            ),
            $nested_queries_columns = array(
                SRG_OPTIONAL => $name = 'name',
                SRG_TEMP => $discussion = 'discussion'
            ),
            $nested_queries = array(
                new srg_db_query(
                    $table = SRG_TABLE_DEFAULT,
                    $table_type = SRG_TABLE_VARIABLE,
                    $select = '',
                    $params = array(),
                    $columns = array(
                        new srg_db_column($name, 'name'),
                    ),
                    $sort = '',
                    $options = array(
                        'table_source' => 'objecttable',
                        'id_source' => 'objectid'
                    ),
                    $nested_queries_columns = array(),
                    $nested_queries = array()

                ),
                new srg_db_query(
                    $table = 'forum_posts',
                    $table_type = SRG_TABLE_VARIABLE,
                    $select = '',
                    $params = array(),
                    $columns = array(
                        new srg_db_column($discussion, 'discussion'),
                    ),
                    $sort = '',
                    $options = array(
                        'table_source' => 'objecttable',
                        'id_source' => 'objectid'
                    ),
                    $nested_queries_columns = array($name),
                    $nested_queries = array(
                        new srg_db_query(
                            $table = 'forum_discussions',
                            $table_type = SRG_TABLE_STATIC,
                            $select = '',
                            $params = array(),
                            $columns = array(
                                new srg_db_column($name, 'name'),
                            ),
                            $sort = '',
                            $options = array(
                                'id_source' => 'discussion'
                            ),
                            $nested_queries_columns = array(),
                            $nested_queries = array()

                        )
                    )

                )
            )
        ));
    }

    public static function GETHVP($USER, $course)
    {
        return srg_db_conn::build_simple_DB_table(new srg_db_query(
            $table = 'hvp_xapi_results',
            $table_type = SRG_TABLE_STATIC,
            $select = 'user_id = ?',
            $params = array(
                'user_id' => $USER->id
            ),
            $columns = array(
                new srg_db_column('content_id', 'content_id'),
                new srg_db_column('interaction_type', 'interaction_type'),
                new srg_db_column('raw_score', 'raw_score'),
                new srg_db_column('max_score', 'max_score')
            ),
            $sort = '',
            $options = array(),
            $nested_queries_columns = array(
                SRG_REQUIRED => $name = 'name'
            ),
            $nested_queries = array(new srg_db_query(
                $table = 'hvp',
                $table_type = SRG_TABLE_STATIC,
                $select = 'course = ?',
                $params = array(
                    'course' => $course->id
                ),
                $columns = array(
                    new srg_db_column($name, 'name'),
                ),
                $sort = '',
                $options = array(
                    'id_source' => 'content_id'
                ),
                $nested_queries_columns = array(),
                $nested_queries = array()

            ))
        ));
    }

    public static function GETBadges($USER, $course)
    {
        return srg_db_conn::build_simple_DB_table(new srg_db_query(
            $table = 'badge_issued',
            $table_type = SRG_TABLE_STATIC,
            $select = 'userid = ?',
            $params = array(
                'userid' => $USER->id
            ),
            $columns = array(
                new srg_db_column('badgeid', 'badgeid')
            ),
            $sort = '',
            $options = array(),
            $nested_queries_columns = array(
                SRG_REQUIRED => $name = 'name'
            ),
            $nested_queries = array(
                new srg_db_query(
                    $table = 'badge',
                    $table_type = SRG_TABLE_STATIC,
                    $select = 'courseid = ?',
                    $params = array(
                        'course' => $course->id
                    ),
                    $columns = array(
                        new srg_db_column($name, 'name'),
                    ),
                    $sort = '',
                    $options = array(
                        'id_source' => 'badgeid',
                    ),
                    $nested_queries_columns = array(),
                    $nested_queries = array()
                )
            )
        ));
    }
}

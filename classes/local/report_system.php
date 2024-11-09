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
 * List of Hardcoded DB query information.
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg\local;

use mod_srg\local\report_table;

use stdClass;

/**
 * Class that contains some preset reports based on existing database data.
 */
class report_system {
    /** @var array This Array holds all needed origin tables.*/
    private array $tables;

    /**
     * Create an object to easily access reports and reduce database queries.
     */
    public function __construct() {
        $this->tables = [];
    }

    /**
     * Load the data from the "logstore_standard_log" database table into a local representation as a `report_table` object.
     * This function queries the log data based on the current user's ID and the course ID, returning relevant event log information
     * for the given user and course. The data is filtered to exclude records with null or empty values for essential fields.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the log entry
     * - timecreated: The timestamp when the log entry was created
     * - userid: ID of the user who triggered the event
     * - courseid: ID of the course related to the event
     * - eventname: The name of the event
     * - component: The component that triggered the event
     * - action: The specific action performed in the event
     * - target: The target entity of the event
     * - objectThe table of the object involved in the event
     * - objectid: The ID of the object involved in the event
     * - contextid: The context ID of the event
     * - contextlevel: The level of context for the event
     * - contextinstanceid: The instance ID of the context for the event
     *
     * If the table data is not already loaded, the function instantiates a new `report_table` object, populates it with the
     * relevant data from the database, and caches it in the `$tables` array for future use.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object that contains the filtered log data for the given user and course.
     */
    private function get_logstore_standard_log_table($USER, $course): report_table {
        $tablename = "logstore_standard_log";

        // Check if the table data for the logstore has already been loaded.
        if (!isset($this->tables[$tablename])) {
            // Instantiate a new report_table with the appropriate headers.
            $table = new report_table(
                [
                    "id" => "id",
                    "timecreated" => "timecreated",
                    "userid" => "userid",
                    "courseid" => "courseid",
                    "eventname" => "eventname",
                    "component" => "component",
                    "action" => "action",
                    "target" => "target",
                    "objecttable" => "objecttable",
                    "objectid" => "objectid",
                    "contextid" => "contextid",
                    "contextlevel" => "contextlevel",
                    "contextinstanceid" => "contextinstanceid",
                ]
            );

            // Populate the report_table with log data from the database, applying conditions for user and course.
            $table->populate_from_database(
                $tablename,
                "userid = :userid"
                    . " AND courseid = :courseid"
                    . " AND id IS NOT NULL AND id <> ''"
                    . " AND timecreated IS NOT NULL AND timecreated <> ''",
                [
                    'userid' => $USER->id,
                    'courseid' => $course->id,
                ],
                implode(", ", [
                    "id",
                    "timecreated",
                    "userid",
                    "courseid",
                    "eventname",
                    "component",
                    "action",
                    "target",
                    "objecttable",
                    "objectid",
                    "contextid",
                    "contextlevel",
                    "contextinstanceid",
                ]),
                'id'
            );

            // Cache the populated table in the $tables array.
            $this->tables[$tablename] = $table;
        }

        // Return the cached or newly created report_table.
        return $this->tables[$tablename];
    }

    /**
     * Load the data from the "hvp_xapi_results" database table into a local representation as a `report_table` object.
     * This function queries the xAPI results data based on the current user's ID and filters out rows with null or empty values
     * in essential fields. It returns relevant data for the given user.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the xAPI result
     * - content_id: ID of the content involved in the xAPI result
     * - interaction_type: Type of interaction (e.g., multiple choice, true/false)
     * - raw_score: The raw score from the xAPI interaction
     * - max_score: The maximum possible score for the xAPI interaction
     *
     * If the table data is not already loaded, the function instantiates a new `report_table` object, populates it with the
     * relevant data from the database, and caches it in the `$tables` array for future use.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object that contains the filtered xAPI result data for the given user.
     */
    private function get_hvp_table($USER, $course): report_table {
        $tablename = "hvp_xapi_results";

        // Check if the table data for the xAPI results has already been loaded.
        if (!isset($this->tables[$tablename])) {
            // Instantiate a new report_table with the appropriate headers.
            $table = new report_table(
                [
                    "id" => "id",
                    "content_id" => "content_id",
                    "interaction_type" => "interaction_type",
                    "raw_score" => "raw_score",
                    "max_score" => "max_score",
                ]
            );

            // Populate the report_table with xAPI result data from the database, applying conditions for the user.
            $table->populate_from_database(
                $tablename,
                "userid = :userid"
                    . " AND id IS NOT NULL AND id <> ''"
                    . " AND content_id IS NOT NULL AND content_id <> ''",
                [
                    'userid' => $USER->id,
                ],
                implode(", ", [
                    "id",
                    "content_id",
                    "interaction_type",
                    "raw_score",
                    "max_score",
                ]),
                'id'
            );

            // Cache the populated table in the $tables array.
            $this->tables[$tablename] = $table;
        }

        // Return the cached or newly created report_table.
        return $this->tables[$tablename];
    }

    /**
     * Load the data from the "badge_issued" database table into a local representation as a `report_table` object.
     * This function queries the badge issuance data based on the current user's ID and filters out rows with null or empty values
     * in essential fields. It returns relevant badge data for the given user.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the badge issuance record
     * - badgeid: ID of the badge that was issued
     *
     * If the table data is not already loaded, the function instantiates a new `report_table` object, populates it with the
     * relevant data from the database, and caches it in the `$tables` array for future use.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object that contains the filtered badge issuance data for the given user.
     */
    private function get_badges_table($USER, $course): report_table {
        $tablename = "badge_issued";
        if (!isset($this->tables[$tablename])) {

            // Instantiate a new report_table with the appropriate headers.
            $table = new report_table(
                [
                    "id" => "id",
                    "badgeid" => "badgeid",
                ]
            );

            // Populate the report_table with badge issuance data from the database, applying conditions for the user.
            $table->populate_from_database(
                $tablename,
                "userid = :userid"
                    . " AND id IS NOT NULL AND id <> ''"
                    . " AND badgeid IS NOT NULL AND badgeid <> ''",
                [
                    'userid' => $USER->id,
                ],
                implode(", ", [
                    "id",
                    "badgeid",
                ]),
                'id'
            );

            // Cache the populated table in the $tables array.
            $this->tables[$tablename] = $table;
        }

        // Return the cached or newly created report_table.
        return $this->tables[$tablename];
    }

    /**
     * Load the data from the "chatbot_history" database table into a local representation as a `report_table` object.
     * This function queries the chatbot history data based on the current user's ID and the course ID, filtering out rows with
     * null or empty values in essential fields such as the message and timestamp. It returns the chatbot interaction data for the
     * given user and course.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the chatbot history entry
     * - timecreated: The timestamp when the chatbot message was created
     * - speaker: The speaker in the interaction (e.g., user or chatbot)
     * - message: The message text from the interaction
     * - act: The action associated with the chatbot message (e.g., response type)
     *
     * If the table data is not already loaded, the function instantiates a new `report_table` object, populates it with the
     * relevant data from the database, and caches it in the `$tables` array for future use.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object that contains the filtered chatbot history data for the given user and course.
     */
    private function get_chatbot_history_table($USER, $course): report_table {
        $tablename = "chatbot_history";
        if (!isset($this->tables[$tablename])) {

            // Instantiate a new report_table with the appropriate headers.
            $table = new report_table(
                [
                    "id" => "id",
                    "timecreated" => "timecreated",
                    "speaker" => "speaker",
                    "message" => "message",
                    "act" => "act",
                ]
            );

            // Populate the report_table with chatbot history data from the database, applying conditions for user and course.
            $table->populate_from_database(
                $tablename,
                "userid = :userid"
                    . " AND courseid = :courseid"
                    . " AND id IS NOT NULL AND id <> ''"
                    . " AND timecreated IS NOT NULL AND timecreated <> ''",
                [
                    'userid' => $USER->id,
                    'courseid' => $course->id,
                ],
                implode(", ", [
                    "id",
                    "timecreated",
                    "speaker",
                    "message",
                    "act",
                ]),
                'id'
            );

            // Cache the populated table in the $tables array.
            $this->tables[$tablename] = $table;
        }

        // Return the cached or newly created report_table.
        return $this->tables[$tablename];
    }

    /**
     * Retrieves and groups course log data entries by "dedication".
     * Entries that are time-close to each other are grouped together,
     * and the time difference between them is calculated as "dedication",
     * representing how much time was spent on that group of entries.
     * The function returns a table containing the log data for the course,
     * with additional columns for dedication time and human-readable timestamps.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for each log entry
     * - timecreated: The timestamp when the log entry was created
     * - courseid: ID of the course related to the log entry
     * - dedication: The calculated time spent on a particular group of log entries
     * - time: A human-readable format of the time the log entry was created
     *
     * The log entries are grouped by time and the resulting table is enhanced with additional columns:
     * - A column representing the total time spent on each group of entries (dedication)
     * - A column representing the human-readable time for each log entry
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object that contains the grouped and enhanced log data for the given user and course.
     */
    public function get_course_dedication($USER, $course): report_table {
        // Retrieve the course log data table.
        $origin = $this->get_logstore_standard_log_table($USER, $course);

        // Create a sub-table with additional headers and apply the dedication and human time columns.
        $table = $origin->create_and_get_sub_table(
            [
                "id" => "id",
                "timecreated" => "timecreated",
                "courseid" => "courseid",
            ]
        )
            ->add_dedication(get_string('dedication', 'mod_srg'))
            ->add_human_time(get_string('time', 'mod_srg'));

        // Return the enhanced table with dedication data.
        return $table;
    }


    /**
     * Retrieves and filters course log data entries based on specific targets and actions. The function expands the log data
     * by adding information that is not found in the standard log database table, such as details about the associated objects
     * and additional course information.
     *
     * The function selects log entries with specific targets and actions (e.g., course modules, H5P, attempts, etc.) and
     * adds relevant columns to the table. It also enriches the log data with human-readable timestamps and course details.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the log entry
     * - timecreated: The timestamp when the log entry was created
     * - eventname: The name of the event that triggered the log entry
     * - component: The component that generated the log entry
     * - action: The action performed in the event
     * - target: The target of the event (e.g., course module, chapter, question)
     * - objectThe table of the object involved in the event
     * - objectid: The ID of the object involved in the event
     * - contextid: The context ID of the event
     * - contextlevel: The level of the context for the event
     * - contextinstanceid: The instance ID of the context for the event
     * - object_name: The name of the object (enriched with additional information based on the objecttable and objectid)
     * - time: A human-readable format of the time the log entry was created
     * - course_shortname: The short name of the course
     * - course_fullname: The full name of the course
     *
     * Additionally, the function applies the following filters:
     * - Only log entries with specific targets (e.g., course modules, H5P, attempts) and actions (e.g., viewed, started, submitted)
     * - It joins the log data with additional tables (like `book_chapters` or `h5p`) to get more information about the objects
     * - It adds constant columns for course details (shortname, fullname) and the `object_name`
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object containing the filtered and enriched log data for the given user and course.
     */
    public function get_course_module_log($USER, $course): report_table {
        // Retrieve the course log data table.
        $origin = $this->get_logstore_standard_log_table($USER, $course);

        // Create a sub-table with specific headers and apply additional requirements and constraints.
        $table = $origin->create_and_get_sub_table(
            [
                "id" => "id",
                "timecreated" => "timecreated",
                "eventname" => "eventname",
                "component" => "component",
                "action" => "action",
                "target" => "target",
                "objecttable" => "objecttable",
                "objectid" => "objectid",
                "contextid" => "contextid",
                "contextlevel" => "contextlevel",
                "contextinstanceid" => "contextinstanceid",
            ]
        )
            // Additional requirements for objecttable and objectid.
            ->additional_requirements(
                [
                    "objecttable",
                    "objectid",
                ]
            )
            // Constraints on valid target values and action types.
            ->additional_constraints(
                [
                    "target" => [
                        "course_module",
                        "course_content",
                        "course_bin_item",
                        "h5p",
                        "attempt",
                        "chapter",
                        "question",
                    ],
                    "action" => [
                        "viewed",
                        "failed",
                        "started",
                        "submitted",
                    ],
                ]
            )
            // Add human-readable time and constant columns for course details.
            ->add_human_time(get_string('time', 'mod_srg'))
            ->add_constant_column("object_name", "")
            ->add_constant_column(get_string('course_shortname', 'mod_srg'), $course->shortname)
            ->add_constant_column(get_string('course_fullname', 'mod_srg'), $course->fullname)
            // Join additional tables to enrich the object data (e.g., book chapters, H5P).
            ->join_with_variable_table(
                "objecttable",
                "objectid",
                [
                    0 => [
                        "name" => "object_name",
                    ],
                    "book_chapters" => [
                        "title" => "object_name",
                    ],
                    "h5p" => [],
                ]
            )
            // Rename the "object_name" column to a user-friendly name.
            ->rename_column("object_name", get_string('object_name', 'mod_srg'));

        // Return the enhanced table with filtered and enriched log data.
        return $table;
    }

    /**
     * Retrieves and groups course log data entries based on "dedication." Entries that are timed closely together
     * and belong to the same component are grouped, with the time spent on the group representing the "dedication" value.
     * The function enriches the log data by adding additional information not available in the standard log database table.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the log entry
     * - timecreated: The timestamp when the log entry was created
     * - eventname: The name of the event that triggered the log entry
     * - component: The component that generated the log entry
     * - action: The action performed in the event
     * - target: The target of the event (e.g., course module, chapter, question)
     * - objectThe table of the object involved in the event
     * - objectid: The ID of the object involved in the event
     * - contextid: The context ID of the event
     * - contextlevel: The level of the context for the event
     * - contextinstanceid: The instance ID of the context for the event
     * - time: A human-readable format of the time the log entry was created
     * - object_name: The name of the object (enriched with additional information based on the objecttable and objectid)
     * - course_shortname: The short name of the course
     * - course_fullname: The full name of the course
     * - dedication: The amount of time spent on a group of log entries with closely timed events and the same component.
     *
     * The function processes log data by grouping entries that occur close together in time and belong to the same component.
     * The "dedication" column reflects the total time spent on these grouped entries. Additional details about the object involved
     * in the log events are added, and the log data is enriched with the course's short and full names.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object containing the grouped and enriched log data for the given user and course.
     */
    public function get_course_module_dedication($USER, $course): report_table {
        // Retrieve the filtered course module log data.
        $origin = $this->get_course_module_log($USER, $course);

        // Create a sub-table with specific headers, including the "dedication" column.
        $table = $origin->create_and_get_sub_table(
            [
                "id" => "id",
                "timecreated" => "timecreated",
                "eventname" => "eventname",
                "component" => "component",
                "action" => "action",
                "target" => "target",
                "objecttable" => "objecttable",
                "objectid" => "objectid",
                "contextid" => "contextid",
                "contextlevel" => "contextlevel",
                "contextinstanceid" => "contextinstanceid",
                get_string('time', 'mod_srg') => get_string('time', 'mod_srg'),
                "object_name" => get_string('object_name', 'mod_srg'),
                get_string('course_shortname', 'mod_srg') => get_string('course_shortname', 'mod_srg'),
                get_string('course_fullname', 'mod_srg') => get_string('course_fullname', 'mod_srg'),
            ]
        )
            // Add the "dedication" column, grouping by the "component" field.
            ->add_dedication(get_string('dedication', 'mod_srg'), "component");

        // Return the enhanced table with grouped and enriched log data.
        return $table;
    }

    /**
     * Retrieves log entries from the course log database table that are related to the user's interest in accessing grades.
     * These entries track user interactions with various grade reports and grading tables.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the log entry
     * - timecreated: The timestamp when the log entry was created
     * - eventname: The name of the event that triggered the log entry (related to grading and grade reports)
     * - time: A human-readable format of the time the log entry was created
     * - course_shortname: The short name of the course
     * - course_fullname: The full name of the course
     *
     * The function filters log entries based on specific grading-related events, such as viewing the grading table, grading form,
     * and grade reports. It also adds extra information about the course, including its short and full names.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object containing the filtered log entries related to grading activities.
     */
    public function get_grading_interest($USER, $course): report_table {
        // Retrieve the filtered course log data based on grading-related events.
        $origin = $this->get_logstore_standard_log_table($USER, $course);

        // Create a sub-table with specific headers and filter log entries related to grading activities.
        $table = $origin->create_and_get_sub_table(
            [
                "id" => "id",
                "timecreated" => "timecreated",
                "eventname" => "eventname",
            ]
        )
            // Apply conditions to filter log entries by event names related to grading activities.
            ->additional_constraints(
                [
                    "eventname" => [
                        '\mod_assign\event\grading_table_viewed',
                        '\mod_assign\event\grading_form_viewed',
                        '\gradereport_user\event\grade_report_viewed',
                        '\gradereport_overview\event\grade_report_viewed',
                        '\gradereport_grader\event\grade_report_viewed',
                        '\gradereport_outcomes\event\grade_report_viewed',
                        '\gradereport_singleview\event\grade_report_viewed',
                    ],
                ]
            )
            // Add additional columns for time and course details.
            ->add_human_time(get_string('time', 'mod_srg'))
            ->add_constant_column(get_string('course_shortname', 'mod_srg'), $course->shortname)
            ->add_constant_column(get_string('course_fullname', 'mod_srg'), $course->fullname)
            // Rename the "eventname" column to a more user-friendly label.
            ->rename_column("eventname", get_string('eventname', 'mod_srg'));

        // Return the table containing the filtered and enriched log data.
        return $table;
    }

    /**
     * Retrieves log entries from the course log database table related to user activity in a forum.
     * This includes actions such as viewing posts, making posts, or interacting with forum discussions.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the log entry
     * - timecreated: The timestamp when the log entry was created
     * - eventname: The name of the event that triggered the log entry (e.g., post viewed, post created)
     * - component: The component associated with the event (in this case, "mod_forum")
     * - action: The specific action taken (e.g., "viewed", "created")
     * - target: The target entity related to the action (e.g., a specific forum post or discussion)
     * - objectThe table of the object involved in the action (e.g., "forum_posts", "forum_discussions")
     * - objectid: The identifier of the object involved in the action (e.g., the ID of the forum post or discussion)
     * - time: A human-readable format of the time the log entry was created
     * - name: The name of the forum post or discussion (depending on the action)
     * - discussionid: The ID of the forum discussion associated with the log entry
     *
     * The function filters log entries to include only those related to the "mod_forum" component, ensuring that
     * the table contains only forum-related activities. It also adds additional information about the forum posts
     * and discussions, including their names and IDs, by joining with the relevant tables.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object containing the filtered log entries related to forum activity.
     */
    public function get_forum_activity($USER, $course): report_table {
        // Retrieve the filtered course log data related to forum activities.
        $origin = $this->get_logstore_standard_log_table($USER, $course);

        // Create a sub-table with specific headers and filter log entries related to the "mod_forum" component.
        $table = $origin->create_and_get_sub_table(
            [
                "id" => "id",
                "timecreated" => "timecreated",
                "eventname" => "eventname",
                "component" => "component",
                "action" => "action",
                "target" => "target",
                "objecttable" => "objecttable",
                "objectid" => "objectid",
            ]
        )
            // Add fields that are required for the join operations.
            ->additional_requirements(
                [
                    "objecttable",
                    "objectid",
                ]
            )
            // Apply conditions to filter log entries related to the "mod_forum" component.
            ->additional_constraints(
                [
                    "component" => [
                        "mod_forum",
                    ],
                ]
            )
            // Add additional columns for time and other forum-related information.
            ->add_human_time(get_string('time', 'mod_srg'))
            ->add_constant_column("name", "")
            ->add_constant_column("discussionid", "")
            // Join with the relevant variable tables to add details about the forum posts and discussions.
            ->join_with_variable_table(
                "objecttable",
                "objectid",
                [
                    0 => [
                        "name" => "name",
                    ],
                    "forum_posts" => [
                        "discussion" => "discussionid",
                    ],
                    "forum_discussion_subs" => [],
                ]
            )
            // Join with the fixed "forum_discussions" table to get the discussion name.
            ->join_with_fixed_table(
                "forum_discussions",
                "discussionid",
                [
                    "name" => "name",
                ]
            );

        // Return the table containing the filtered and enriched log data for forum activity.
        return $table;
    }

    /**
     * Retrieves log entries from the course log database table related to user interactions with H5P (HTML5 Package) content.
     * These log entries contain data such as the type of interaction, raw score, and max score for the H5P content.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the log entry
     * - content_id: The ID of the H5P content being interacted with
     * - interaction_type: The type of interaction the user had with the H5P content (e.g., viewed, completed)
     * - raw_score: The raw score achieved by the user in the H5P interaction
     * - max_score: The maximum score possible for the H5P interaction
     * - course: The course associated with the H5P content
     * - object_name: The name of the H5P content object
     * - timecreated: The timestamp when the H5P content was created
     * - time: A human-readable format of the time the log entry was created
     * - course_shortname: The short name of the course
     * - course_fullname: The full name of the course
     *
     * The function filters log entries to include only those related to the specified course. It then joins the
     * H5P content table to enrich the data with information such as the course ID, content name, and creation time.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object
     *                      containing the filtered log entries related to user interactions with H5P content.
     */
    public function get_hvp($USER, $course): report_table {
        // Retrieve the H5P-related log data for the specified user and course.
        $origin = $this->get_hvp_table($USER, $course);

        // Create a sub-table with the relevant headers for H5P interaction data.
        $table = $origin->create_and_get_sub_table(
            [
                "id" => "id",
                "content_id" => "content_id",
                "interaction_type" => "interaction_type",
                "raw_score" => "raw_score",
                "max_score" => "max_score",
            ]
        )
            // Add additional columns for course information, object name, and creation time.
            ->add_constant_column("course", "")
            ->add_constant_column("object_name", "")
            ->add_constant_column("timecreated", "")
            // Join the H5P table to enrich the data with the course, object name, and creation time.
            ->join_with_fixed_table(
                "hvp",
                "content_id",
                [
                    "course" => "courseid",
                    "name" => "object_name",
                    "timecreated" => "timecreated",
                ]
            )
            // Apply conditions to filter entries for the current course.
            ->additional_constraints(
                [
                    "courseid" => [
                        $course->id,
                    ],
                ]
            )
            // Add human-readable time column and constant columns for course short and full name.
            ->add_human_time(get_string('time', 'mod_srg'))
            ->add_constant_column(get_string('course_shortname', 'mod_srg'), $course->shortname)
            ->add_constant_column(get_string('course_fullname', 'mod_srg'), $course->fullname)
            // Rename the "object_name" column to a more user-friendly label.
            ->rename_column("object_name", get_string('object_name', 'mod_srg'));

        // Return the table containing the filtered and enriched log data for H5P content interaction.
        return $table();
    }

    /**
     * Retrieves log entries from the course log database table that contain information about user interactions
     * with badges, such as earning or viewing badges within the specified course.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the log entry.
     * - badgeid: The ID of the badge associated with the log entry.
     * - courseid: The ID of the course in which the badge was awarded or viewed.
     * - object_name: The name of the badge.
     * - timecreated: The timestamp when the badge was awarded or the action was logged.
     * - time: A human-readable format of the time the log entry was created.
     * - course_shortname: The short name of the course.
     * - course_fullname: The full name of the course.
     *
     * The function filters log entries to include only those related to the specified course. It then joins the
     * badge table to enrich the data with information about the badge, such as the course ID, badge name, and creation time.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object containing the filtered log entries related to user interactions with badges.
     */
    public function get_badges($USER, $course): report_table {
        // Retrieve the badge-related log data for the specified user and course.
        $origin = $this->get_badges_table($USER, $course);

        // Create a sub-table with the relevant headers for badge data.
        $table = $origin->create_and_get_sub_table(
            [
                "id" => "id",
                "badgeid" => "badgeid",
            ]
        )
            // Add additional columns for course information, object name, and creation time.
            ->add_constant_column("courseid", "")
            ->add_constant_column("object_name", "")
            ->add_constant_column("timecreated", "")
            // Join the badge table to enrich the data with the course ID, badge name, and creation time.
            ->join_with_fixed_table(
                "badge",
                "badgeid",
                [
                    "courseid" => "courseid",
                    "name" => "object_name",
                    "timecreated" => "timecreated",
                ]
            )
            // Apply conditions to filter entries for the current course.
            ->additional_constraints(
                [
                    "courseid" => [
                        $course->id,
                    ],
                ]
            )
            // Add a human-readable time column and constant columns for course short and full name.
            ->add_human_time(get_string('time', 'mod_srg'))
            ->add_constant_column(get_string('course_shortname', 'mod_srg'), $course->shortname)
            ->add_constant_column(get_string('course_fullname', 'mod_srg'), $course->fullname)
            // Rename the "object_name" column to a more user-friendly label.
            ->rename_column("object_name", get_string('object_name', 'mod_srg'));

        // Return the table containing the filtered and enriched log data for badge interactions.
        return $table;
    }

    /**
     * Retrieves log entries from the `chatbot_history` database table that contain information about user interactions
     * with the chatbot within the specified course. The entries include details such as the time of interaction,
     * the speaker (user or chatbot), the message exchanged, and the action performed.
     *
     * The resulting `report_table` object contains the following columns:
     * - id: Unique identifier for the log entry.
     * - timecreated: The timestamp when the interaction occurred.
     * - speaker: The entity (user or chatbot) that participated in the interaction.
     * - message: The message content exchanged during the interaction.
     * - act: The action that was performed during the interaction (e.g., user input, chatbot response).
     * - time: A human-readable format of the time the log entry was created.
     * - course_shortname: The short name of the course.
     * - course_fullname: The full name of the course.
     *
     * The function retrieves the relevant chatbot history log data for the current user and course and formats the
     * output with additional details such as the course name and a human-readable time format.
     *
     * @param mixed $USER The current user, typically an instance of `stdClass` representing the logged-in user.
     * @param stdClass $course The course object associated with the activity.
     *
     * @return report_table The `report_table` object containing the filtered log entries related to user interactions
     *         with the chatbot, enriched with additional course information and human-readable time formatting.
     */
    public function get_chatbot_history($USER, $course): report_table {
        // Retrieve the chatbot history data for the specified user and course.
        $origin = $this->get_chatbot_history_table($USER, $course);

        // Create a sub-table with the relevant headers for chatbot history data.
        $table = $origin->create_and_get_sub_table(
            [
                "id" => "id",
                "timecreated" => "timecreated",
                "speaker" => "speaker",
                "message" => "message",
                "act" => "act",
            ]
        )
            // Add a human-readable time column for when the interaction occurred.
            ->add_human_time(get_string('time', 'mod_srg'))
            // Add constant columns for the course's short name and full name.
            ->add_constant_column(get_string('course_shortname', 'mod_srg'), $course->shortname)
            ->add_constant_column(get_string('course_fullname', 'mod_srg'), $course->fullname);

        // Return the table containing the formatted chatbot history log data.
        return $table;
    }
}

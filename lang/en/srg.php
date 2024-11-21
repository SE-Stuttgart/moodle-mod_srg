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
 * Plugin strings are defined here.
 *
 * @package     mod_srg
 * @category    string
 * @copyright   2022 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// System text.
$string['pluginname'] = 'Student Report Generation';
$string['pluginadministration'] = 'Student Report Generation module administration';

$string['modulename'] = 'Student Report Generation';
$string['modulename_link'] = 'mod/srg/view';
$string['modulenameplural'] = 'Student Report Generations';

$string['no$srginstances'] = 'No Student Report Generation instances';

$string['privacy:metadata'] = 'The Student Report Generation Plugin only shows or downloads existing data from other Plugins. It does not store any data itself.';

$string['srg:addinstance'] = 'Add a new Student Report Generation activity';
$string['srg:view'] = 'View the Student Report Generation activity';

// Page Error.
$string['error_course_module_id'] = 'Course Module ID misconfigured.';
$string['error_course_not_found'] = 'Course is misconfigured';
$string['error_course_module'] = 'Course Module misconfigured.';
$string['error_course_access_denied'] = 'Access to course denied. You are not enrolled.';

// Report View Error.
$string['error_no_reports_found'] = 'No reports were created.';
$string['error_report_data_could_not_be_accessed'] = 'Failure in the report data collection.';

// Download Error.
$string['error_download_zipcreationfailed'] = 'Failed to create zipfile.';
$string['error_download_failed'] = 'The download system failed.';

// Report Error.
$string['error_report_generation_unknown_mode'] = 'Unknown access to the report system.';
$string['error_download_tempfilecreationfailed'] = 'Failed to create a temp file.';


// Form Text.
$string['report_view_title'] = 'Data Overview';
$string['report_view_heading'] = 'Data Overview';
$string['download_title'] = 'Data Overview';
$string['download_heading'] = 'Data Overview';
$string['content_title'] = 'Instructions';
$string['content_default'] = "Here, you can view part of your anonymized log data in this Moodle course or download it if needed.

Hint: The downloaded file is actually a file in the .zip format.
It is, however, downloaded with .kib3 as the extension to prevent automatic unpacking by your operating system.
You can use a zip programm like 7Zip to unpack it.";

// View Text.
$string['view_all_button_name'] = 'View log data';
$string['print_all_button_name'] = 'Download log data';

// Info UI text.
$string['page_navigation'] = 'Page navigation';
$string['first'] = 'first';
$string['previous'] = 'previous';
$string['next'] = 'next';
$string['last'] = 'last';
$string['page'] = 'page';

// Report strings.
$string['zipfilename'] = 'moodle-mod_srg';
// Report file names.
$string['course_dedication_log'] = 'Course Dedication Log';
$string['course_dedication_log_csv'] = 'course_dedication_log.csv';
$string['course_module_log'] = 'Course Module Log';
$string['course_module_log_csv'] = 'course_module_log.csv';
$string['course_module_dedication'] = 'Course Module Dedication Report';
$string['course_module_dedication_csv'] = 'course_module_dedication.csv';
$string['grade_inspections'] = 'Grade Inspection Report';
$string['grade_inspections_csv'] = 'grade_inspections.csv';
$string['forum_activities'] = 'Forum Activity Report';
$string['forum_activities_csv'] = 'forum_activities.csv';
$string['hvp_scores'] = 'HVP Score Report';
$string['hvp_scores_csv'] = 'hvp_scores.csv';
$string['badges'] = 'User Earned Badges';
$string['badges_csv'] = 'badges.csv';
$string['chatbot_history'] = 'Chatbot History';
$string['chatbot_history_csv'] = 'chatbot_history.csv';

// Report header names.
// General.
$string['id'] = 'ID';
$string['timecreated'] = 'Time created';
$string['courseid'] = 'Course ID';
// Special.
$string['object_name'] = 'Object Name';
$string['course_shortname'] = 'Course Shortname';
$string['course_fullname'] = 'Course Fullname';
$string['time'] = 'Time';
$string['dedication'] = 'Dedication';
// Table logstore_standard_log.
$string['eventname'] = 'Event name';
$string['component'] = 'Component';
$string['action'] = 'Action';
$string['target'] = 'Target';
$string['objecttable'] = 'Object table';
$string['objectid'] = 'Object ID';
$string['contextid'] = 'Context ID';
$string['contextlevel'] = 'Context level';
$string['contextinstanceid'] = 'Context instance ID';
// Table forum_activity.
$string['name'] = 'Name';
// Table hvp_report.
$string['content_id'] = 'Content ID';
$string['interaction_type'] = 'Interaction type';
$string['raw_score'] = 'Raw score';
$string['max_score'] = 'Max score';
// Table badge_report.
$string['badgeid'] = 'Badge ID';
// Table chatbot_history.
$string['speaker'] = 'Speaker';
$string['message'] = 'Message';
$string['act'] = 'Act';

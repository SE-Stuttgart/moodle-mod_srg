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

// Error.
$string['error_course_module_id'] = 'Course Module ID misconfigured.';
$string['error_course_module'] = 'Course Module misconfigured.';
$string['error_course_not_found'] = 'Course is misconfigured';
$string['error_course_access_denied'] = 'Access to course denied. You are not enrolled.';
$string['error_creating_csv_file'] = 'There was an error creating the .csv file.';

// Form Text.
$string['info_title'] = 'Data Overview';
$string['info_heading'] = 'Data Overview';
$string['content_title'] = 'Instructions';
$string['content_default'] = "Here, you can view part of your anonymized log data in this Moodle course or download it if needed.

Hint: The downloaded file is actually a file in the .zip format.
It is, however, downloaded with .kib3 as the extension to prevent automatic unpacking by your operating system.
You can use a zip programm like 7Zip to unpack it.";

// View Text.
$string['view_all_button_name'] = 'View log data';
$string['print_all_button_name'] = 'Download log data';

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
$string['time'] = 'Time';
$string['course_shortname'] = 'Course Shortname';
$string['course_fullname'] = 'Course Fullname';
$string['dedication'] = 'Dedication';
$string['object_name'] = 'Object Name';
$string['eventname'] = 'Eventname';

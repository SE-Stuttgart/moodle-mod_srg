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
 * @copyright   2022 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * System text
 */
$string['pluginname'] = 'Student Report Generation';
$string['pluginadministration'] = 'Student Report Generation module administration';

$string['modulename'] = 'Student Report Generation';
$string['modulename_link'] = 'mod/srg/view';
$string['modulenameplural'] = 'Student Report Generations';

$string['no$srginstances'] = 'No Student Report Generation instances';

/**
 * Error
 */
$string['error_course_module_id'] = 'Course Module ID misconfigured.';
$string['error_course_module'] = 'Course Module misconfigured.';
$string['error_course_not_found'] = 'Course is misconfigured';
$string['error_course_access_denied'] = 'Access to course denied. You are not enrolled.';
$string['error_creating_csv_file'] = 'There was an error creating the .csv file.';

/**
 * Form Text
 */
$string['info_title'] = 'Data Overview';
$string['info_heading'] = 'Data Overview';
$string['content_title'] = 'Instructions';
$string['content_default'] = 'Here you can view part of your anonymized log data in this Moodle course, or download it. <br><br>
Hint: the downloaded file is actually a file in .zip format.
It is however downloaded with .kib3 as extension, to prevent automatic unpacking by your operating system.
If you want to unpack and view it, please change the extension to .zip after downloading and then unpack it.';

/**
 * File Text
 */
$string['zipfilename'] = 'moodle_student_report';

/**
 * View Text
 */
$string['view_all_button_name'] = 'View log data';
$string['print_all_button_name'] = 'Download log data';



$string['srg:addinstance'] = 'Add a new Student Report Generation activity';
$string['srg:view'] = 'View the Student Report Generation activity';

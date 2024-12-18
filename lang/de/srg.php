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
$string['pluginname'] = 'Logdaten-Erstellung';
$string['pluginadministration'] = 'Logdaten-Erstellung: Modul Administration';

$string['modulename'] = 'Logdaten-Erstellung';
$string['modulename_link'] = 'mod/srg/view';
$string['modulenameplural'] = 'Logdaten-Erstellungen';

$string['no$srginstances'] = 'Keine Logdaten-Erstellung Instanzen';

$string['privacy:metadata'] = 'Das Logdaten-Erstellung Plugin zeigt oder lädt nur Daten herunter welche von anderen Plugins gesammelt werden. Es sammelt selbst keine Daten.';

$string['srg:addinstance'] = 'Füge ein neues Logdaten-Erstellungs-Modul hinzu';
$string['srg:view'] = 'Sehe die das Logdaten Modul an.';

// Error.
$string['error_course_module_id'] = 'Course Module ID misconfigured.';
$string['error_course_module'] = 'Course Module misconfigured.';
$string['error_course_not_found'] = 'Course is misconfigured';
$string['error_course_access_denied'] = 'Access to course denied. You are not enrolled.';
$string['error_creating_csv_file'] = 'There was an error creating the .csv file.';
$string['error_duplicate_primary_key'] = 'Duplicate value found for given primary key - keyfield ';
$string['error_accessing_database'] = 'There has been an error when accessing the following database table: ';

// Form Text.
$string['info_title'] = 'Übersicht über die Daten';
$string['info_heading'] = 'Übersicht';
$string['content_title'] = 'Anleitung';
$string['content_default'] = "Sie können hier einen Teil Ihrer Logdaten in diesem Moodle-Kurs in anonymisierter Form einsehen und bei Bedarf in einer Datei herunterladen.

Anmerkung: Die heruntergeladene Datei ist eigentlich eine .zip-Datei.
Sie hat aber die Endung .kib3, weil sie sonst unter manchen Betriebssystemen automatisch entpackt würde.
Sie können ein zip Programm wie 7Zip benutzen um die Datei zu entpacken.";

// View Text.
$string['view_all_button_name'] = 'Logdaten ansehen';
$string['print_all_button_name'] = 'Logdaten herunterladen';

// Info UI text.
$string['page_navigation'] = 'Seiten Navigation';
$string['first'] = 'Erste';
$string['previous'] = 'Vorherige';
$string['next'] = 'Nächste';
$string['last'] = 'Letzte';

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

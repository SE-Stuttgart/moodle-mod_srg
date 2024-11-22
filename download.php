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
 * Dummy page that is called and then killed. Calling this page downloads the report content as .csv in a .kib3 (.zip) folder.
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

global $CFG, $USER, $DB;

// Course module id form param.
$cmid = required_param('id', PARAM_INT);

// Course Module.
if (!$cm = get_coursemodule_from_id('srg', $cmid)) {
    throw new moodle_exception(get_string('error_course_module_id', 'mod_srg'));
}
// Course.
if (!$course = $DB->get_record('course', ['id' => $cm->course])) {
    throw new moodle_exception(get_string('error_course_not_found', 'mod_srg'));
}
// Activity.
if (!$srg = $DB->get_record('srg', ['id' => $cm->instance])) {
    throw new moodle_exception(get_string('error_course_module', 'mod_srg'));
}
// Does the user have access to the course?
if (!can_access_course($course)) {
    throw new moodle_exception(get_string('error_course_access_denied', 'mod_srg'));
}

$modulecontext = context_module::instance($cm->id);

require_login($course, true, $cm);
require_capability('mod/srg:view', $modulecontext);

$PAGE->set_url('/mod/srg/download.php',  ['id' => $cm->id]);
$PAGE->set_title(get_string('download_title', 'mod_srg'));
$PAGE->set_heading(get_string('download_heading', 'mod_srg'));
$PAGE->set_context($modulecontext);

// SQL Queries -> get Data.
$reportlist = [];
foreach (srg_get_report_list() as $reportid) {
    $report = srg_get_report($reportid, $USER, $course);

    if (!$report) {
        continue;
    }

    $reportlist[] = $report;
}


$zipfilename = get_string('zipfilename', 'mod_srg') . '.kib3';

// Create a temporary directory for files.
$exporttmpdir = make_request_directory();
$zippath = $exporttmpdir . DIRECTORY_SEPARATOR . $zipfilename;
$tempfiles = [];

try {
    // Iterate through each report.
    foreach ($reportlist as $index => $report) {
        $filename = $report->get_file_name();
        $tempfile = $exporttmpdir . DIRECTORY_SEPARATOR . $filename;

        // Write table rows to tempfile.
        $report->write_data_to_file($tempfile);

        // Store the file with its name as the key.
        $tempfiles[$filename] = $tempfile;
    }

    // Create a ZIP file with the temp files.
    $zippacker = new \zip_packer();

    if (!$zippacker->archive_to_pathname($tempfiles, $zippath)) {
        throw new moodle_exception(get_string('error_download_zipcreationfailed', 'mod_srg'));
    }

    // Send ZIP file for download.
    send_file($zippath, $zipfilename);
} catch (\Throwable $th) {
    debugging($th, DEBUG_DEVELOPER);
    throw new moodle_exception($th . "\n" . get_string('error_download_failed', 'mod_srg'));
} finally {
    // Cleanup temporary files and directory.
    foreach ($tempfiles as $file) {
        unlink($file);
    }
    if (file_exists($zippath)) {
        unlink($zippath);
    }
    rmdir($tempdir);
}

gc_collect_cycles();
die;

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
 * Version details and info
 *
 * @package     mod_srg
 * @copyright   2023 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use stdClass;

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

require_login($course, true, $cm);

$systemcontext = context_system::instance();
$modulecontext = context_module::instance($cm->id);
$usercontext = context_user::instance($USER->id);

$PAGE->set_url('/mod/srg/info.php',  ['id' => $cm->id]);
$PAGE->set_title(get_string('info_title', 'mod_srg'));
$PAGE->set_heading(get_string('info_heading', 'mod_srg'));
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

// Use Moodle 3.11 functionality.
if ($CFG->version >= 2021050000) {
    require_once($CFG->dirroot . '/files/classes/archive_writer.php');
    require_once($CFG->dirroot . '/files/classes/local/archive_writer/zip_writer.php');

    $zipwriter = \core_files\archive_writer::get_stream_writer($zipfilename, \core_files\archive_writer::ZIP_WRITER);
    if ($zipwriter instanceof \core_files\local\archive_writer\zip_writer) {
        // Stream the files into the zip.
        foreach ($reportlist as $report) {
            $zipwriter->add_file_from_string($report->get_file_name(), $report->get_as_csv_table(0, MOD_SRG_TARGET_TABLE_MAX_COUNT));
            unset($file);
        }

        // Finish the archive.
        $zipwriter->finish();
        gc_collect_cycles();
    } else {
        throw new Exception("Wrong Writer!");
    }
} else { // Use Moodle 3.10 functionality.
    require_once($CFG->dirroot . '/lib/filelib.php');

    $zip = new \zip_packer();
    $zipfiles = [];

    $exporttmpdir = make_request_directory();

    foreach ($reportlist as $report) {
        $csvfilepath = $exporttmpdir . DIRECTORY_SEPARATOR . $report->get_file_name();
        if (!file_put_contents($csvfilepath, $report->get_as_csv_table(0, MOD_SRG_TARGET_TABLE_MAX_COUNT))) {
            throw new moodle_exception(get_string('error_creating_csv_file', 'mod_srg'));
        }
        $zipfiles[$report->get_file_name()] = $csvfilepath;
        unset($report);
    }
    $zipfilepath = $exporttmpdir . DIRECTORY_SEPARATOR . $zipfilename;
    $zip->archive_to_pathname($zipfiles, $zipfilepath);
    send_temp_file($zipfilepath, $zipfilename);
    gc_collect_cycles();
}
gc_collect_cycles();
die; // Important!

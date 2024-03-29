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
 * @copyright   2023 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');
require_once(__DIR__ . '/classes/csv.php');

global $CFG, $USER, $DB;

// Course module id form param.
$cmid = required_param('id', PARAM_INT);
// Mode is view or print. Should the data be viewed or downloaded.
$mode = required_param('mode', PARAM_ALPHAEXT);

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

$PAGE->set_url('/mod/srg/info.php',  ['id' => $cm->id, 'mode' => $mode]);
$PAGE->set_title(get_string('info_title', 'mod_srg'));
$PAGE->set_heading(get_string('info_heading', 'mod_srg'));
$PAGE->set_context($modulecontext);

// SQL Queries -> get Data.
$filelist = srg_get_file_list($USER, $course);

if ($mode == 'print') { // Download data as CSV in .zip.
    // Trigger event\log_data_downloaded.
    srg_log_data_download($srg, $modulecontext);

    $zipfilename = get_string('zipfilename', 'mod_srg') . '.kib3';

    // Use Moodle 3.11 functionality.
    if ($CFG->version >= 2021050000) {
        require_once($CFG->dirroot . '/files/classes/archive_writer.php');
        require_once($CFG->dirroot . '/files/classes/local/archive_writer/zip_writer.php');

        $zipwriter = \core_files\archive_writer::get_stream_writer($zipfilename, \core_files\archive_writer::ZIP_WRITER);
        if ($zipwriter instanceof \core_files\local\archive_writer\zip_writer) {
            // Stream the files into the zip.
            foreach ($filelist as $file) {
                $zipwriter->add_file_from_string($file['filename'], mod_srg\srg_CSV::simple_table_to_csv($file['content']));
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

        foreach ($filelist as $file) {
            if (!$file['content']) {
                continue;
            }

            $csvfilepath = $exporttmpdir . DIRECTORY_SEPARATOR . $file['filename'];
            if (!file_put_contents($csvfilepath, mod_srg\srg_CSV::simple_table_to_csv($file['content']))) {
                throw new moodle_exception(get_string('error_creating_csv_file', 'mod_srg'));
            }
            $zipfiles[$file['filename']] = $csvfilepath;
            unset($file);
        }
        $zipfilepath = $exporttmpdir . DIRECTORY_SEPARATOR . $zipfilename;
        $zip->archive_to_pathname($zipfiles, $zipfilepath);
        send_temp_file($zipfilepath, $zipfilename);
        gc_collect_cycles();
    }
    die; // Important!
} else if ($mode == 'view') { // View data in browser.
    // Trigger event\log_data_viewed.
    srg_log_data_view($srg, $modulecontext);

    echo $OUTPUT->header();

    // View Content.
    echo html_writer::start_div('', ['id' => 'mod_srg-accordion']);
    foreach (array_values($filelist) as $i => $file) {
        $table = $file['content'];
        $t = new html_table();
        $t->head = array_shift($table);
        $t->data = $table;

        echo html_writer::start_div('card'); // Start Card (Accordion item).

        echo html_writer::tag(
            'button',
            ''
                . html_writer::tag(
                    'h5',
                    $file['name'],
                    ['class' => 'm-0']
                )
                . html_writer::tag(
                    'i',
                    '',
                    [
                        'class' => 'fa fa-chevron-down',
                        'id' => 'mod_srg-chevron-' . $i,
                        'aria-hidden' => 'true',
                    ]
                ),
            [
                'class' => 'mod_srg-collapse-button card-header collapsed'
                    . ' d-flex flex-row justify-content-between align-items-center',
                'id' => 'mod_srg-heading-' . $i,
                'data-toggle' => 'collapse',
                'data-target' => '#mod_srg-collapse-' . $i,
                'icon-target' => '#mod_srg-chevron-' . $i,
                'aria-expanded' => 'false',
                'aria-controls' => 'mod_srg-collapse-' . $i,
            ]
        );

        echo html_writer::div(
            html_writer::div(
                html_writer::table($t),
                'card-body p-0'
            ),
            'collapse',
            [
                'id' => 'mod_srg-collapse-' . $i,
                'aria-labelledby' => 'mod_srg-heading-' . $i,
                'data-parent' => '#mod_srg-accordion',
            ]
        );

        echo html_writer::end_div(); // End Card.
    }
    echo html_writer::end_div(); // End Accordion.



    echo $OUTPUT->footer();

    echo html_writer::script('', new moodle_url('/mod/srg/scripts/accordion.js'));

    gc_collect_cycles();
}

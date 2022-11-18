<?php
// This file is part of the scientific work at the University of Stuttgart

/**
 * Version details
 *
 * @package    mod_srg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

require_login();
$context = context_user::instance($USER->id);

global $CFG, $USER, $DB;

$mode = required_param('mode', PARAM_ALPHAEXT);         // Mode is view or print. Should the data be viewed or downloaded.
$course_id = required_param('course_id', PARAM_INT);    // Course ID of data source course

// Is course ID connected to a course.
if (!$course = $DB->get_record('course', array('id' => $course_id))) {
    print_error(get_string('error_course_not_found', 'mod_srg'));
}

// Does the user have access to the course?
if (!srg_enrolled_in($USER->id, $course->id)) {
    print_error(get_string('error_course_access_denied', 'mod_srg'));
}

$PAGE->set_url(new moodle_url($CFG->wwwroot . '/mod/srg/info.php', array('mode' => $mode, 'course_id' => $course_id)));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('info_title', 'mod_srg'));
$PAGE->set_heading(get_string('info_heading', 'mod_srg'));

// SQL Queries -> get Data;
$filelist = srg_get_file_list($USER, $course);


// Download Data as CSV in .zip
#region Create and Download filelist as .kib3 (zip) file.
if ($mode == 'print') {
    $zipfilename = get_string('zipfilename', 'mod_srg') . '_courseID' . '_' . $course_id . '.kib3';

    // Use Moodle 3.11 functionality
    if ($CFG->version >= 2021050000) {
        require_once($CFG->dirroot . '/files/classes/archive_writer.php');
        require_once($CFG->dirroot . '/files/classes/local/archive_writer/zip_writer.php');

        $zipwriter = \core_files\archive_writer::get_stream_writer($zipfilename, \core_files\archive_writer::ZIP_WRITER);
        if ($zipwriter instanceof \core_files\local\archive_writer\zip_writer) {
            // Stream the files into the zip.
            foreach ($filelist as $file) {
                $zipwriter->add_file_from_string($file['filename'], srg_CSV::simple_table_to_CSV($file['content']));
                unset($file);
            }

            // Finish the archive.
            $zipwriter->finish();
            gc_collect_cycles();
        } else {
            throw new Exception("Wrong Writer!");
        }
    }
    // Use Moodle 3.10 functionality
    else {
        require_once($CFG->dirroot . '/lib/filelib.php');

        $zip = new \zip_packer();
        $zipfiles = [];

        $exporttmpdir = make_request_directory();

        foreach ($filelist as $file) {
            if (!$file['content']) continue;

            $csvfilepath = $exporttmpdir . DIRECTORY_SEPARATOR . $file['filename'];
            if (!file_put_contents($csvfilepath, srg_CSV::simple_table_to_CSV($file['content']))) {
                print_error(get_string('error_creating_csv_file', 'mod_srg'));
            }
            $zipfiles[$file['filename']] = $csvfilepath;
            unset($file);
        }
        $zipfilepath = $exporttmpdir . DIRECTORY_SEPARATOR . $zipfilename;
        $zip->archive_to_pathname($zipfiles, $zipfilepath);
        send_temp_file($zipfilepath, $zipfilename);
        gc_collect_cycles();
    }
    die; //Important!
}
#endregion

#region Page Output
else if ($mode == 'view') {
    echo $OUTPUT->header();

    echo html_writer::tag('style', '
        .srg_collapsible {
            background-color: #777;
            color: white;
            cursor: pointer;
            padding: 18px;
            width: 100%;
            border: 5px;
            border-color: black;
            text-align: left;
            outline: none;
            font-size: 24px;
        }

        .active, .srg_collapsible:hover {
          background-color: #555;
        }
        
        .srg_collapsible:before {
          content: "\002B";
          color: white;
          font-weight: bold;
          float: right;
          margin-right: 5px;
        }
        
        .active:before {
          content: "\2212";
        }

        .srg_content {
            padding: 0 18px;
            max-height: 0;
            overflow-y: hidden;
            overflow-y: scroll;
            transition: max-height 0.2s ease-out;
            background-color: #f1f1f1;
        }
    ');

    foreach ($filelist as $file) {
        $table = $file['content'];
        $t = new html_table();
        $t->head = array_shift($table);
        $t->data = $table;

        echo html_writer::div(
            html_writer::tag(
                'h2',
                html_writer::span(
                    html_writer::tag('i', '', array('class' => 'srg_icon_dropdown', 'aria-hidden' => 'true')),
                    'media-left'
                )
                    . $file['name'],
                array('class' => 'srg_collapsible media')
            )
                . html_writer::div(
                    html_writer::table($t),
                    'srg_content'
                )
        );
        unset($file);
    }

    echo html_writer::script('
        var coll = document.getElementsByClassName("srg_collapsible");
        var i;

        for (i = 0; i < coll.length; i++) {
            coll[i].addEventListener("click", function() {
                this.classList.toggle("active");
                var srg_content = this.nextElementSibling;
                if (this.classList.contains("active")){
                    srg_content.style.maxHeight = srg_content.scrollHeight + "px";
                } else {
                    srg_content.style.maxHeight = null;
                } 
            });
        }
    ');

    echo $OUTPUT->footer();

    gc_collect_cycles();
}
#endregion

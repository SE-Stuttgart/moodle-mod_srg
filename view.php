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
 * Prints an instance of mod_srg.
 *
 * @package     mod_srg
 * @copyright  2022 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_once(__DIR__ . '/lib.php');

// Course module id form param
$cmid = required_param('id', PARAM_INT);


if (!$cm = get_coursemodule_from_id('srg', $cmid)) print_error(get_string('error_course_module_id', 'mod_srg'));               // Course Module
if (!$course = $DB->get_record('course', array('id' => $cm->course))) print_error(get_string('error_course_not_found', 'mod_srg'));   // Course
if (!$srg = $DB->get_record('srg', array('id' => $cm->instance))) print_error(get_string('error_course_module', 'mod_srg'));    // Activity

require_login($course, true, $cm);

$module_context = context_module::instance($cm->id);
$system_context = context_system::instance();

$PAGE->set_url('/mod/srg/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($srg->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($module_context);

echo $OUTPUT->header();

echo html_writer::start_div('', array('style' => 'flex-direction: row;align-items: center;text-align: center;justify-content: center;'));

// Display results on info page
echo $OUTPUT->single_button(
    new moodle_url($CFG->wwwroot . '/mod/srg/info.php', array('mode' => 'view', 'course_id' => $course->id)),
    get_string('view_all_button_name', 'mod_srg'),
    'get'
);

// Skip display and download results
echo $OUTPUT->single_button(
    new moodle_url($CFG->wwwroot . '/mod/srg/info.php', array('mode' => 'print', 'course_id' => $course->id)),
    get_string('print_all_button_name', 'mod_srg'),
    'get'
);

echo html_writer::end_div();

echo html_writer::tag('p', $srg->content);

echo $OUTPUT->footer();

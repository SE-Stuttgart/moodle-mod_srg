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
 * @copyright  2023 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

global $CFG, $USER, $DB;

#region Page setup

// Course module id form param
$cmid = required_param('id', PARAM_INT);

// Course Module
if (!$cm = get_coursemodule_from_id('srg', $cmid))
    throw new Exception(get_string('error_course_module_id', 'mod_srg'));
// Course
if (!$course = $DB->get_record('course', array('id' => $cm->course)))
    throw new Exception(get_string('error_course_not_found', 'mod_srg'));
// Activity
if (!$srg = $DB->get_record('srg', array('id' => $cm->instance)))
    throw new Exception(get_string('error_course_module', 'mod_srg'));
// Does the user have access to the course?
if (!srg_enrolled_in($USER->id, $course->id))
    throw new Exception(get_string('error_course_access_denied', 'mod_srg'));

require_login($course, true, $cm);

$system_context = context_system::instance();
$module_context = context_module::instance($cm->id);
$user_context = context_user::instance($USER->id);

$PAGE->set_url('/mod/srg/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($srg->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($module_context);

#endregion

// Trigger event\course_module_viewed
srg_view($srg, $module_context);

#region Output

echo $OUTPUT->header();

echo html_writer::start_div('', array('style' => 'flex-direction: row;align-items: center;text-align: center;justify-content: center;'));

// Display results on info page
echo $OUTPUT->single_button(
    new moodle_url($CFG->wwwroot . '/mod/srg/info.php', array('id' => $cm->id, 'mode' => 'view')),
    get_string('view_all_button_name', 'mod_srg'),
    'get'
);

// Skip display and download results
echo $OUTPUT->single_button(
    new moodle_url($CFG->wwwroot . '/mod/srg/info.php', array('id' => $cm->id, 'mode' => 'print')),
    get_string('print_all_button_name', 'mod_srg'),
    'get'
);

echo html_writer::end_div();

echo html_writer::tag('p', $srg->content);

echo $OUTPUT->footer();

#endregion

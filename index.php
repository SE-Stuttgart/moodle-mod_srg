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
 * Display information about all the mod_srg modules in the requested course.
 *
 * @package     mod_srg
 * @copyright  2022 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT); // Course ID.

// Ensure that the course specified is valid
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('Course ID is incorrect');
}

require_course_login($course, true);

// Get all required strings
$modulename_plural  = get_string('modulenameplural', 'mod_srg');
$modulename         = get_string('modulename', 'mod_srg');
$strname            = get_string('name');
$strintro           = get_string('moduleintro');
$strlastmodified    = get_string('lastmodified');

$PAGE->set_url('/mod/srg/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->shortname) . ':' . $modulename_plural);
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add($modulename);

echo $OUTPUT->header();

// Get all the appropriate data
if (!$srgs = get_all_instances_in_course('srg', $course)) {
    $srg = array();
}

$usesections = course_format_uses_sections($course->format);

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($usesections) {
    $strsectionname = get_string('sectionname', 'format_' . $course->format);
    $table->head  = array($strsectionname, $strname, $strintro);
    $table->align = array('center', 'left', 'left');
} else {
    $table->head  = array($strlastmodified, $strname, $strintro);
    $table->align = array('left', 'left', 'left');
}

$modinfo = get_fast_modinfo($course);
$currentsection = '';
foreach ($srgs as $srg) {
    $cm = $modinfo->get_cm($srg->coursemodule);
    if ($usesections) {
        $printsection = '';
        if ($srg->section !== $currentsection) {
            if ($srg->section) {
                $printsection = get_section_name($course, $srg->section);
            }
            if ($currentsection !== '') {
                $table->data[] = 'hr';
            }
            $currentsection = $srg->section;
        }
    } else {
        $printsection = html_writer::tag('span', userdate($srg->timemodified), array('class' => 'smallinfo'));
    }

    $class = $srg->visible ? null : array('class' => 'dimmed'); // hidden modules are dimmed

    $table->data[] = array(
        $printsection,
        html_writer::link(new moodle_url('view.php', array('id' => $cm->id)), format_string($srg->name), $class),
        format_module_intro('srg', $srg, $cm->id)
    );
}

echo html_writer::table($table);

echo $OUTPUT->footer();

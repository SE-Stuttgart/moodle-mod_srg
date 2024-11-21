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
 * A page to view the data collected by this activity.
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/locallib.php');

global $CFG, $USER, $DB;

// Course module id form param.
$cmid = required_param('id', PARAM_INT);

$reportid = required_param('report_id', PARAM_INT);
$pageindex = required_param('page_index', PARAM_INT);

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

$PAGE->set_url('/mod/srg/report_view.php', ['id' => $cm->id, 'report_id' => $reportid, 'page_index' => $pageindex]);
$PAGE->set_title(get_string('report_view_title', 'mod_srg'));
$PAGE->set_heading(get_string('report_view_heading', 'mod_srg'));
$PAGE->set_context($modulecontext);

// Gather reports.
$reportlist = [];
foreach (srg_get_report_list() as $reportindex) {
    $report = srg_get_report($reportindex, $USER, $course);

    if (!$report) {
        continue;
    }

    $reportlist[$reportindex] = $report;
}

// Handle the case, that there are no reports.
if (empty($reportlist)) {
    throw new moodle_exception(get_string('error_no_reports_found', 'mod_srg'));
}

// If there is an unknown reportid, we default to the first report.
if (!in_array($reportid, array_keys($reportlist))) {
    $reportid = array_key_first($reportlist);
    $pageindex = 0;
    $PAGE->set_url('/mod/srg/report_view.php', ['id' => $cm->id, 'report_id' => $reportid, 'page_index' => $pageindex]);
}

$data = [];
$headers = [];
$pagecount = 0;
try {
    list($data, $headers, $pagecount) = $reportlist[$reportid]->get_template_table_data($pageindex, MOD_SRG_TARGET_TABLE_MAX_COUNT);
} catch (\Throwable $th) {
    debugging($th, DEBUG_DEVELOPER);
    throw new moodle_exception(get_string('error_report_data_could_not_be_accessed', 'mod_srg'));
}

// Create tab environment.
$tabdata = [];
foreach ($reportlist as $index => $report) {
    $tab = new stdClass();
    $tab->index = format_text(strval($index), FORMAT_HTML);
    $tab->name = format_text(strval($report->get_report_name()), FORMAT_HTML);
    $tabdata[] = $tab;
}
$tabtemplatedata = [
    'coursemoduleid' => $cm->id,
    'activetabindex' => $reportid,
    'tabdata' => $tabdata,
];

// Create pagination environment.
$pagesdata = [];
for ($i = 0; $i < $pagecount; $i++) {
    $page = new stdClass();
    $page->index = $i;
    $page->viewindex = $i + 1;
    $pagesdata[] = $page;
}
$paginationtemplatedata = [
    'coursemoduleid' => $cm->id,
    'activetabindex' => $reportid,
    'activepageindex' => $pageindex,
    'pages' => $pagesdata,
];

// Create table display.
$tabletemplatedata = [
    'headers' => $headers,
    'rows' => $data,
];

// Register the stylesheet.
$PAGE->requires->css('/mod/srg/styles.css');

echo $OUTPUT->header();

echo $OUTPUT->render_from_template('mod_srg/tabs', $tabtemplatedata);

echo $OUTPUT->render_from_template('mod_srg/pagination', $paginationtemplatedata);

echo $OUTPUT->render_from_template('mod_srg/table', $tabletemplatedata);

echo $OUTPUT->footer();

gc_collect_cycles();

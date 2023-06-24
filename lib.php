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
 * Library of interface functions and constants.
 *
 * @package     mod_srg
 * @copyright  2023 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

#region activity requirements

/**
 * Supported features
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function srg_supports($feature)
{
    switch ($feature) {
        case FEATURE_MOD_ARCHETYPE:
            return MOD_ARCHETYPE_RESOURCE;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;

        default:
            return null;
    }
}


/**
 * Saves a new instance of the mod_srg into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $data An object from the form.
 * @return int The id of the newly inserted record.
 */
function srg_add_instance($data)
{
    global $DB;

    $data->timemodified = $data->timecreated = time();

    $data->content       = $data->instruction['text'];
    $data->contentformat = $data->instruction['format'];

    // Create and add instance of srg
    $id = $DB->insert_record('srg', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'srg', $id, $completiontimeexpected);

    return $id;
}

/**
 * Updates an instance of the mod_srg in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $data An object from the form in mod_form.php.
 * @param mod_srg_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function srg_update_instance($data)
{
    global $DB;

    $data->timemodified = time();
    $data->id = $data->instance;

    $data->content       = $data->instruction['text'];
    $data->contentformat = $data->instruction['format'];

    $DB->update_record('srg', $data);

    $completiontimeexpected = !empty($data->completionexpected) ? $data->completionexpected : null;
    \core_completion\api::update_completion_date_event($data->coursemodule, 'srg', $data->id, $completiontimeexpected);

    return true;
}

/**
 * Removes an instance of the mod_srg from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function srg_delete_instance($id)
{
    global $DB;

    $exists = $DB->get_record('srg', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $cm = get_coursemodule_from_instance('srg', $id);
    \core_completion\api::update_completion_date_event($cm->id, 'srg', $id, null);

    $DB->delete_records('srg', array('id' => $id));

    return true;
}

#endregion

#region Events

/**
 * Trigger the course_module_viewed event.
 *
 * @param  stdClass $srg     srg object
 * @param  stdClass $course  course object
 * @param  stdClass $cm      course module object
 * @param  stdClass $context context object
 */
function srg_view($srg, $context)
{
    $params = array(
        'context' => $context,
        'objectid' => $srg->id
    );

    $event = \mod_srg\event\course_module_viewed::create($params);
    $event->add_record_snapshot('srg', $srg);
    $event->trigger();
}

/**
 * Trigger the log data viewed event
 *
 * @param  stdClass $srg     srg object
 * @param  stdClass $course  course object
 * @param  stdClass $cm      course module object
 * @param  stdClass $context context object
 */
function srg_log_data_view($srg, $context)
{
    $params = array(
        'context' => $context,
        'objectid' => $srg->id
    );

    $event = \mod_srg\event\log_data_viewed::create($params);
    $event->add_record_snapshot('srg', $srg);
    $event->trigger();
}

/**
 * Trigger the log data downloaded event
 *
 * @param  stdClass $srg     srg object
 * @param  stdClass $course  course object
 * @param  stdClass $cm      course module object
 * @param  stdClass $context context object
 */
function srg_log_data_download($srg, $context)
{
    $params = array(
        'context' => $context,
        'objectid' => $srg->id
    );

    $event = \mod_srg\event\log_data_downloaded::create($params);
    $event->add_record_snapshot('srg', $srg);
    $event->trigger();
}

#endregion

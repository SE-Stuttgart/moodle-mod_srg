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
 * The task that provides all the steps to perform a complete backup is defined here.
 *
 * @package     mod_srg
 * @category    backup
 * @copyright  2022 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// More information about the backup process: {@link https://docs.moodle.org/dev/Backup_API}.
// More information about the restore process: {@link https://docs.moodle.org/dev/Restore_API}.

require_once($CFG->dirroot . '/mod/srg/backup/moodle2/backup_srg_stepslib.php'); // Because it exists (must)
require_once($CFG->dirroot . '/mod/srg/backup/moodle2/backup_srg_settingslib.php'); // Because it exists (optional)

/**
 * Provides all the settings and steps to perform a complete backup of mod_srg.
 */
class backup_srg_activity_task extends backup_activity_task
{
    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings()
    {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps()
    {
        $this->add_step(new backup_srg_activity_structure_step('srg_structure', 'srg.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content)
    {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');

        // Link to the list of choices.
        $search = "/(" . $base . "\/mod\/srg\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SRGINDEX*$2@$', $content);

        // Link to srg view by moduleid.
        $search = "/(" . $base . "\/mod\/srg\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SRGVIEWBYID*$2@$', $content);

        return $content;
    }
}

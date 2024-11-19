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
 * TODO DESCRIPTION
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg\local;

class sql_generator {

    /**
     * Retrieves a list of default tables from a field in a given table.
     *
     * @param string $table The name of the table to query.
     * @param string $field The column name containing table references.
     * @param array $customfields A list of fields that are considered "custom" and should be excluded.
     * @param array $requiredfields A list of fields that must exist in a table for it to be considered "default."
     * @return array A list of default table names that meet the required criteria.
     */
    public static function get_default_tables_from_field(
        string $sql,
        array $params,
        string $tablefield,
        array $customfields,
        array $requiredfields
    ): array {
        global $DB;
        $dbmanager = $DB->get_manager();

        // Initialize the result list for default tables.
        $defaulttablelist = [];

        // Query the database for distinct values in the specified field.
        $recordset = $DB->get_recordset_sql(
            $sql,
            $params,
            0,
            MOD_SRG_TARGET_TABLE_MAX_COUNT // Maximum record limit for safety.
        );

        foreach ($recordset as $record) {
            // Extract the table name from the current record.
            $tablename = $record->$tablefield;

            // Skip if the table is a custom field.
            if (in_array($tablename, $customfields)) {
                continue;
            }

            // Skip if the table does not exist in the database.
            if (!$dbmanager->table_exists($tablename)) {
                continue;
            }

            // Verify that all required fields exist in the table.
            foreach ($requiredfields as $requiredfield) {
                if (!$dbmanager->field_exists($tablename, $requiredfield)) {
                    continue 2; // Skip to the next table if a field is missing.
                }
            }

            // Add the table to the default list if it meets all criteria.
            $defaulttablelist[] = $tablename;
        }

        // Close the recordset to free up resources.
        $recordset->close();

        return $defaulttablelist;
    }

    /**
     * Generate a SQL CASE statement for a SELECT field with an alias.
     *
     * @param string $sourcetable Name of the table containing the source field.
     * @param string $sourcefield Name of the source field to evaluate.
     * @param array $defaulttables List of default tables to check (table names as strings).
     * @param string $defaultfield Default field to retrieve from the default tables.
     * @param array $customtables Associative array of custom tables and specific SQL expressions.
     *              Example: ['forum_posts' => 'forum_discussions.name']
     * @param string $alias Alias for the CASE statement in the SELECT.
     * @return string SQL CASE statement for a SELECT field with an alias, or an empty string if both tables are empty.
     */
    public static function get_switch_select_field(
        string $sourcetable,
        string $sourcefield,
        array $defaulttables,
        string $defaultfield,
        array $customtables,
        string $alias
    ): string {
        // Return an empty string if both defaulttables and customtables are empty.
        if (empty($defaulttables) && empty($customtables)) {
            return "";
        }

        $sql = "CASE";

        // Build the CASE conditions for default tables.
        foreach ($defaulttables as $table) {
            $sql .= " WHEN {$sourcetable}.{$sourcefield} = '{$table}'"
                . " THEN {$table}_default.{$defaultfield}";
        }

        // Build the CASE conditions for custom tables.
        foreach ($customtables as $table => $select) {
            $sql .= " WHEN {$sourcetable}.{$sourcefield} = '{$table}'"
                . " THEN {$select}"; // Do not escape $select; it must be a valid SQL expression.
        }

        $sql .= " END AS {$alias}";

        return $sql;
    }


    public static function get_default_left_joins(
        string $tablesource,
        string $idsource,
        array $defaulttables,
        string $defaultfield
    ) {
        $sql = "";

        foreach ($defaulttables as $table) {
            $sql .= " LEFT JOIN {{$table}} {$table}_default ON {$tablesource} = '{$table}'"
                . " AND {$idsource} = {$table}_default.{$defaultfield}";
        }

        return $sql;
    }
}

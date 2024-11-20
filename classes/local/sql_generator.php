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
 * Provides utility methods for generating SQL queries, including CASE statements, left joins, and default table selection.
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg\local;

/**
 * The sql_generator class offers a set of static methods to assist in constructing dynamic SQL queries for Moodle plugins.
 * It includes tools for selecting default tables based on specific criteria, generating SQL CASE statements for field mapping,
 * and creating LEFT JOIN clauses for relational queries.
 * These utilities help manage database interactions efficiently and flexibly.
 */
class sql_generator {

    /**
     * Retrieves a list of default tables from a field in a given table based on specified criteria.
     *
     * This function queries a database field for table names and filters them against a list of custom fields,
     * checks for table existence, and ensures required fields are present in each table before including it
     * in the default table list.
     *
     * @param string $sql SQL query to fetch table names.
     * @param array $params Parameters for the SQL query.
     * @param string $tablefield The field containing table names.
     * @param array $customfields Fields that represent custom tables to exclude.
     * @param array $requiredfields Fields that must exist in a table to be included.
     * @return array List of default table names that meet all criteria.
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
     * Generates a SQL CASE statement for dynamically mapping field values to corresponding table fields or custom expressions.
     *
     * @param string $sourcetable The table containing the source field.
     * @param string $sourcefield The field to evaluate in the CASE statement.
     * @param array $defaulttables List of default tables to include in the CASE mapping.
     * @param string $defaultfield Field to retrieve from default tables in the CASE mapping.
     * @param array $customtables Custom tables and specific SQL expressions for the CASE mapping.
     *              Example: ['forum_posts' => 'forum_discussions.name']
     * @param string $alias Alias for the generated CASE field.
     * @return string SQL CASE statement with the specified alias, or an empty string if no tables are provided.
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

    /**
     * Generates SQL LEFT JOIN clauses for default tables based on a source table and ID mapping.
     *
     * This function creates LEFT JOIN statements for each default table, linking them to the source table using
     * the specified ID mapping and ensuring data consistency.
     *
     * @param string $tablesource The source table field to join on.
     * @param string $idsource The ID field to match between the source table and default tables.
     * @param array $defaulttables List of default tables to join.
     * @param string $defaultfield The field in the default table to use for joining.
     * @return string SQL LEFT JOIN clauses for the provided default tables.
     */
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

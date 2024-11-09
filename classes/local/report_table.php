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
 * This class is a data object to hold table data.
 * It provides functions for reading data from the database and organizing it into headers and rows.
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg\local;

use moodle_exception;
use stdClass;

/**
 * This class is a data object to hold table data.
 * It provides functions for reading data from the database and organizing it into headers and rows.
 */
class report_table {

    /** 
     * @var array $headers - Array defining the table's columns and their display names.
     */
    private array $headers;

    /** 
     * @var array $data - Array holding all the table data in the format [id => row].
     * Each row corresponds to a unique identifier based on the specified keyfield.
     */
    private array $data;

    /**
     * Constructor to initialize the table with headers and optionally rows.
     *
     * @param array $headers - An array defining column names and display headers.
     * @param array $rows - An optional array containing table data, with unique keyfield values as keys.
     */
    public function __construct(array $headers, array $rows = []) {
        $this->headers = $headers;
        $this->data = $rows;
    }

    /**
     * Retrieve the headers of the table.
     *
     * This function returns an array containing only the display names (titles) of the table's columns, 
     * which can be useful for displaying the table headers without the internal column keys.
     *
     * @return array An array of header titles for the table's columns.
     */
    public function get_headers(): array {
        return array_values($this->headers);
    }

    /**
     * Retrieve the content rows of the table.
     *
     * This function returns all data rows in the table as a two-dimensional array, where each inner array
     * represents a row of data with only the values (no keys), preserving their order in each row.
     *
     * @return array A two-dimensional array where each inner array contains the values of a row in the table.
     */
    public function get_data(): array {
        $data = [];

        // Iterate through each row, extracting only the values and appending them to the data array
        foreach ($this->data as $row) {
            $data[] = array_values($row);
        }

        return $data;
    }

    /**
     * Populate the table from a database table using specified conditions.
     * Ensures that each entry has a unique identifier based on the given keyfield.
     * If a duplicate keyfield value is encountered, it throws an exception.
     *
     * @param string $table - The database table to query.
     * @param string $conditions - The SQL conditions (WHERE clause) for the query.
     * @param array $params - An array of parameters for safe SQL injection handling.
     * @param string $fields - Comma-separated list of fields to retrieve from the database.
     * @param string $keyfield - Field that uniquely identifies each row; must be unique in the result set.
     * 
     * @throws Exception - Throws an exception if duplicate values are found in the keyfield.
     * @return report_table - The current report_table object with populated rows.
     */
    public function populate_from_database(
        string $table,
        string $conditions,
        array $params,
        string $fields,
        string $keyfield
    ): report_table {
        $this->data = [];  // Clear existing rows before populating.

        global $DB;

        // Retrieve recordset from the database with specified conditions and fields.
        $recordset = $DB->get_recordset_select(
            table: $table,
            select: $conditions,
            params: $params,
            fields: $fields
        );

        foreach ($recordset as $record) {
            // Ensure keyfield is unique by checking if it already exists in rows.
            if (isset($this->data[$record->$keyfield])) {
                $msg = get_string('error_duplicate_primary_key', 'mod_srg') . "'$keyfield': " . $record->$keyfield;
                throw new moodle_exception($msg);
            }

            // Populate each row based on the headers.
            $row = [];
            foreach ($this->headers as $key => $value) {
                $row[$key] = $record->$key ?? "";
            }
            $this->data[$record->$keyfield] = $row;
        }

        // Close the recordset to free resources.
        $recordset->close();

        return $this;
    }

    /**
     * Create a sub-table containing a subset of headers and corresponding rows.
     *
     * @param array $newheaders - The new headers for the sub-table, based on the original table's headers.
     * 
     * @return report_table - A new report_table object containing only the specified headers and filtered rows.
     */
    public function create_and_get_sub_table(array $newheaders): report_table {
        $newrows = [];

        // Iterate over each row in the current table, using the existing row key.
        foreach ($this->data as $key => $row) {
            $newrow = [];
            foreach ($newheaders as $headerKey => $headerValue) {
                // Populate the new row with the specified headers; default to empty if not found.
                $newrow[$headerKey] = $row[$headerKey] ?? "";
            }
            // Use the existing row key from the original table for each new row.
            $newrows[$key] = $newrow;
        }
        return new report_table($newheaders, $newrows);
    }

    /**
     * Converts the report_table instance into a CSV string.
     *
     * @return string - A CSV-formatted string with headers as the first line and data rows following.
     *                  This format is ideal for exporting or saving the table data as a CSV file.
     */
    public function get_as_csv_table(): string {
        $headers = $this->get_headers();  // Retrieve headers from report_table.
        $data = $this->get_data();     // Retrieve data rows from report_table.

        $rows = [];

        // Add headers to CSV as the first row.
        $cells = [];
        foreach ($headers as $value) {
            // Escape double quotes and format each header as a quoted string.
            $cells[] = '"' . preg_replace(['/\n/', '/"/'], ['', '""'], $value) . '"';
        }
        $rows[] = implode(",", $cells);

        // Add each data row to CSV.
        foreach ($data as $row) {
            $cells = [];
            foreach ($row as $value) {
                // Escape double quotes and format each cell value as a quoted string.
                $cells[] = '"' . preg_replace(['/\n/', '/"/'], ['', '""'], $value) . '"';
            }
            $rows[] = implode(",", $cells);
        }

        // Combine all rows into a single CSV string, separated by newline characters.
        $csv = implode("\n", $rows);

        return $csv;
    }

    /**
     * Adds a human-readable timestamp column to the table, based on each row's 'timecreated' field.
     *
     * This function creates a new column with a formatted date-time string (Y-m-d H:i:s)
     * for each row in the table, making the 'timecreated' value easier to understand.
     *
     * @param string $name The title of the new column to be added for the human-readable timestamp.
     *
     * @return report_table This object with the new human-readable timestamp column.
     */
    public function add_human_time(string $name): report_table {
        // Set the new column header with the provided name
        $this->headers[$name] = $name;

        // Iterate through each row, converting 'timecreated' to a formatted timestamp
        foreach ($this->data as $id => $row) {
            $this->data[$id][$name] = date("Y-m-d H:i:s", $this->get_time($id));
        }

        return $this;
    }

    /**
     * Adds a column with a constant value across all rows.
     *
     * This function creates a new column with a specified value applied uniformly
     * across all rows in the table.
     *
     * @param string $name The name of the new column to be added.
     * @param mixed $value The constant value to be applied to each row in the new column.
     *
     * @return report_table This object with the newly added constant-value column.
     */
    public function add_constant_column(string $name, mixed $value): report_table {
        // Add the new column header with the specified name
        $this->headers[$name] = $name;

        // Set the specified constant value for each row in the new column
        foreach ($this->data as $id => $row) {
            $this->data[$id][$name] = $value;
        }

        return $this;
    }

    /**
     * Renames the display title of an existing column without changing the internal column name.
     *
     * This function updates the column header display name, allowing it to be customized
     * for presentation purposes, while the internal reference remains unchanged.
     *
     * @param string $column The internal name of the column to rename.
     * @param string $title The new display title for the column header.
     *
     * @return report_table This object with the updated column header title.
     */
    public function rename_column(string $column, string $title): report_table {
        // Update the header display title for the specified column
        $this->headers[$column] = $title;
        return $this;
    }

    /**
     * Calculate and add a 'dedication' column representing active time spent on a target task, based on session rules.
     * Inspired by the Block_Dedication plugin.
     *
     * This function collapses table rows based on the $dedicationtarget and each entry’s 'timecreated' value.
     * A new column named $name is added, containing the calculated time difference for each collapsed entry,
     * representing the active time spent on each task. 
     * The minimum active session time ($dedicationmintime) and maximum time between entries ($dedicationmaxtime)
     * can be specified to account for pauses and task transitions.
     *
     * @param string $name Name of the new column to add for dedication time.
     * @param string $dedicationtarget [default=''] Column name representing a single task or target for grouping.
     *        If empty, all entries are treated as the same task.
     * @param int $dedicationmintime [default=60] Minimum session duration in seconds for a task to count.
     * @param int $dedicationmaxtime [default=900] Maximum time in seconds between entries to treat as a single session.
     *
     * @return report_table This object with updated dedication column.
     */
    public function add_dedication(
        string $name,
        string $dedicationtarget = '',
        int $dedicationmintime = 60,
        int $dedicationmaxtime = 900
    ): report_table {
        // Add the new header for dedication time
        $this->headers[$name] = $name;

        // If there is no data to process, return early
        if (empty($this->data)) {
            return $this;
        }

        // Initialize session variables with the first row
        $sessionstartid = array_key_first($this->data); // Start ID for the first session
        $sessionstarttime = $this->get_time($sessionstartid); // Time of the first session entry
        $idstodelete = []; // To hold IDs of rows to delete as subsessions

        // Iterate over each row to determine session boundaries and calculate durations
        foreach ($this->data as $id => $row) {
            $currenttime = $this->get_time($id); // Get the timestamp for the current row

            // Determine if a new session should start
            $newsession = $id === $sessionstartid ||
                ($currenttime - $sessionstarttime > $dedicationmaxtime) ||
                (!empty($dedicationtarget) && $row[$dedicationtarget] !== $this->data[$sessionstartid][$dedicationtarget]);

            if ($newsession) {
                // Check if the previous session duration is valid, else mark it for deletion
                if ($sessionstartid !== $id && ($this->data[$sessionstartid][$name] ?? 0) < $dedicationmintime) {
                    $idstodelete[] = $sessionstartid;
                }

                // Begin a new session at the current row
                $sessionstartid = $id;
                $sessionstarttime = $currenttime;
                $this->data[$sessionstartid][$name] = 0; // Initialize the session time for this entry
                continue;
            }

            // Update ongoing session time and mark the current row as a subsession to delete
            $this->data[$sessionstartid][$name] = $currenttime - $sessionstarttime;
            $idstodelete[] = $id; // Current row is part of the session and can be removed
        }

        // Final check: If the last session duration is too short, mark it for deletion
        if (($this->data[$sessionstartid][$name] ?? 0) < $dedicationmintime) {
            $idstodelete[] = $sessionstartid;
        }

        // Remove rows marked for deletion, retaining only primary session entries
        foreach ($idstodelete as $id) {
            unset($this->data[$id]);
        }

        // Format dedication times for the retained rows to make them user-readable
        foreach ($this->data as &$row) {
            $row[$name] = $this->format_time($row[$name]);
        }

        return $this;
    }


    /**
     * Filter the current table by retaining only rows that have non-empty values in specified columns.
     * 
     * This function updates the table by iterating through each row and checking if the specified fields
     * contain values. If a row has any field missing or empty, it will be excluded from the table.
     * 
     * @param array $fields An array of column names that must have values for a row to be retained.
     * @return report_table This object with filtered data, allowing for method chaining.
     */
    public function additional_requirements(array $fields): report_table {
        $data = [];

        // Iterate through each row of data by its unique ID.
        foreach ($this->data as $id => $row) {
            // Check if each required field has a value in the current row.
            foreach ($fields as $value) {
                if (!isset($row[$value]) || $row[$value] === "") {
                    // If any required field is missing or empty, skip this row.
                    continue 2; // Exit both loops to check the next row.
                }
            }

            // Add the row to the filtered data array if all required fields have values.
            $data[$id] = $row;
        }

        // Update the table's data with only the rows that met the field requirements.
        $this->data = $data;

        return $this;
    }

    /**
     * Constrain the current table by retaining only rows where specified columns contain one of the allowed values.
     * 
     * This function filters the table data based on multiple field-value conditions. Only rows where each specified
     * field contains one of the allowed values will be retained. Rows failing to meet any condition are excluded.
     * 
     * @param array $conditions An associative array where each key is a column name and its value is an array of
     *                          allowed values for that column. Example: ['column1' => ['value1', 'value2'], 'column2' => ['value3']]
     * @return report_table This object with filtered data, allowing for method chaining.
     */
    public function additional_constraints(array $conditions): report_table {
        $data = [];

        // Iterate over each row of data by its unique ID.
        foreach ($this->data as $id => $row) {
            // Check each specified condition for this row.
            foreach ($conditions as $field => $constraints) {
                // If the field does not exist in the row or its value is not allowed, skip this row.
                if (!isset($row[$field]) || !in_array($row[$field], $constraints, true)) {
                    continue 2; // Skip to the next row if a constraint is not met.
                }
            }

            // Add the row to the filtered data if all constraints are satisfied.
            $data[$id] = $row;
        }

        // Update the table's data with only the rows that met all conditions.
        $this->data = $data;

        return $this;
    }

    /**
     * Joins data from a specified Moodle database table to this table's rows based on matching ID columns.
     *
     * This function prepares and calls a join operation between the current data and a specified target table. 
     * It constructs an array of IDs to join on, mapping each ID in the target table to its corresponding 
     * local row IDs in this table, then invokes a join operation to populate specified columns.
     *
     * @param string $table The name of the target table from which data is joined.
     * @param string $idsource The column name in the current table that stores IDs for matching records in the target table.
     * @param array $joinfields An associative array mapping columns to retrieve from the target table (`targetcolumnname`) 
     *                          to the columns where these values should be stored in this table (`tablecolumnname`).
     *                          Example: [ 'source_column' => 'target_column', ... ]
     * 
     * @return report_table This table object, with data from the joined table added to matching rows.
     */
    public function join_with_fixed_table(
        string $table,
        string $idsource,
        array $joinfields
    ): report_table {
        $joinids = [];

        // Build an associative array of join IDs, mapping each source ID to local row IDs in this table.
        foreach ($this->data as $localid => $row) {
            if (!in_array($row[$idsource], array_keys($joinids))) {
                $joinids[$row[$idsource]] = [];
            }
            $joinids[$row[$idsource]][] = $localid;
        }

        try {
            // Call the join function, passing the join parameters to link target table data with local rows.
            $this->join_with_table(
                table: $table,
                joinids: $joinids,
                joinfields: $joinfields
            );
        } catch (\Throwable $th) {
            // Log an error if the database access fails, including the table name and exception details.
            $msg = get_string('error_accessing_database', 'mod_srg') . $table . "\n" . $th;
            debugging($msg, DEBUG_DEVELOPER);
        }

        return $this;
    }

    /**
     * Join data from multiple database tables to this table's rows based on variable target tables.
     *
     * This method dynamically joins data from different target tables based on the values in specific columns of the current data.
     * It groups rows by their associated target table and performs a join operation with the appropriate columns for each table.
     *
     * @param string $tablesource Column in this table specifying the target table name for each row.
     * @param string $idsource Column in this table specifying the ID for joining rows in the target tables.
     * @param array $joinfieldsets Defines the fields to join from each target table. This can include:
     *      - `joinfieldsets[0]`: Default columns to select if the target table has no custom configuration.
     *      - `joinfieldsets['custom_table_name']`: A set of fields to use for a specific target table.
     *      Format: [ targetcolumnname => tablecolumnname ]
     * 
     * @return report_table This table object, with data from the joined tables added to matching rows.
     */
    public function join_with_variable_table(
        string $tablesource,
        string $idsource,
        array $joinfieldsets
    ): report_table {
        // Prepare an associative array for target tables with IDs to join on
        $targettables = [];
        foreach ($this->data as $localid => $row) {
            // Initialize the structure for the target table if it doesn't exist
            if (!in_array($row[$tablesource], array_keys($targettables))) {
                $targettables[$row[$tablesource]] = [];
            }
            // Initialize the structure for the remote ID if it doesn't exist
            if (!in_array($row[$idsource], array_keys($targettables[$row[$tablesource]]))) {
                $targettables[$row[$tablesource]][$row[$idsource]] = [];
            }
            // Append the local row ID to the target table and ID
            $targettables[$row[$tablesource]][$row[$idsource]][] = $localid;
        }

        // Perform the join for each target table based on the constructed ID sets
        foreach ($targettables as $table => $joinids) {
            // Select custom join fields if defined for this table; use defaults otherwise
            $joinfields = in_array($table, array_keys($joinfieldsets))
                ? $joinfieldsets[$table]
                : $joinfieldsets[0];

            try {
                // Join the data from the target table with the current table's rows
                $this->join_with_table(
                    table: $table,
                    joinids: $joinids,
                    joinfields: $joinfields
                );
            } catch (\Throwable $th) {
                // Log a debugging message if the join fails
                $msg = get_string('error_accessing_database', 'mod_srg') . $table . "\n" . $th;
                debugging($msg, DEBUG_DEVELOPER);
            }
        }
        return $this;
    }

    /**
     * Joins data from an external table to the current table by mapping specific fields and IDs.
     *
     * This function fetches rows from a specified table and adds selected fields to the current data based on matching IDs.
     * Fields from the joined table are mapped to the target columns in the current data structure.
     *
     * @param string $table The name of the table to join.
     * @param array $joinids An associative array where keys are IDs in the join table, and each value is an array of IDs 
     *                       that match rows in the current table. Used to map joined data to rows in this table.
     *                       Example: [ 'join_id' => [ 'local_id1', 'local_id2' ], ... ]
     * @param array $joinfields An associative array defining the fields to join. The key is the source field in the join table,
     *                          and the value is the target field to store the result in the current table. 
     *                          Example: [ 'source_field' => 'target_field', ... ]
     * 
     * @return void
     */
    private function join_with_table(string $table, array $joinids, array $joinfields) {
        global $DB;

        // Prepare the list of fields to retrieve from the join table, ensuring 'id' is included.
        $fields = "";
        if (!in_array('id', array_keys($joinfields))) {
            $fields = 'id, ';
        }
        if (!array_keys($joinfields)) {
            return; // Exit if no join fields are provided.
        }
        $fields .= implode(", ", array_keys($joinfields));

        // Fetch records from the join table, matching only rows with IDs in $joinids.
        $recordset = $DB->get_recordset_list(
            table: $table,
            field: 'id',
            values: array_keys($joinids),
            fields: $fields
        );

        // Map retrieved records to the target fields in this table's data.
        foreach ($joinfields as $source => $target) {
            foreach ($recordset as $record) {
                // For each matched join ID, apply the join data to the corresponding local IDs in this table.
                foreach ($joinids[$record->{'id'}] as $localid) {
                    $this->data[$localid][$target] = $record->$source;
                }
            }
        }

        // Close the recordset after processing.
        $recordset->close();
    }

    /**
     * Retrieve the 'timecreated' timestamp for a specified row.
     *
     * @param int $id Row ID for which to retrieve the 'timecreated' timestamp.
     * @return int Timestamp in seconds indicating when the row was created.
     */
    private function get_time(int $id): int {
        return (int)($this->data[$id]["timecreated"]);
    }

    /**
     * Convert a duration in seconds to a human-readable string.
     *
     * Converts an integer representing a duration in seconds to a string format, such as:
     * "x hours y minutes z seconds". This format is influenced by the localization strings
     * for "hour," "minute," and "second," and will adjust for singular and plural terms.
     *
     * @param int $seconds Duration in seconds to convert.
     * @return string Formatted duration string (e.g., "2 hours 15 minutes").
     */
    private function format_time(int $seconds): string {
        $totalsecs = abs($seconds);

        // Fetch singular and plural labels for hours, minutes, and seconds.
        $str = new stdClass();
        $str->hour = get_string('hour');
        $str->hours = get_string('hours');
        $str->min = get_string('min');
        $str->mins = get_string('mins');
        $str->sec = get_string('sec');
        $str->secs = get_string('secs');

        // Calculate hours, minutes, and seconds.
        $hours = floor($totalsecs / HOURSECS);
        $remainder = $totalsecs - ($hours * HOURSECS);
        $mins = floor($remainder / MINSECS);
        $secs = round($remainder - ($mins * MINSECS), 2);

        // Determine singular or plural terms based on values.
        $ss = ($secs == 1) ? $str->sec : $str->secs;
        $sm = ($mins == 1) ? $str->min : $str->mins;
        $sh = ($hours == 1) ? $str->hour : $str->hours;

        // Format each time component if it has a non-zero value.
        $ohours = $hours ? $hours . ' ' . $sh : '';
        $omins = $mins ? $mins . ' ' . $sm : '';
        $osecs = $secs ? $secs . ' ' . $ss : '';

        // Construct the final output based on available components.
        if ($hours) {
            return trim($ohours . ' ' . $omins);
        }
        if ($mins) {
            return trim($omins . ' ' . $osecs);
        }
        if ($secs) {
            return $osecs;
        }
        return get_string('none'); // Return "none" if duration is zero.
    }
}

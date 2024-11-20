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
 * This class represents a report system that retrieves, processes, and formats data from the Moodle Database.
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg\local;

use mod_srg\local\sql_builder;
use moodle_exception;
use stdClass;

/**
 * Class report
 *
 * This class represents a report system that retrieves, processes, and formats data
 * from a database using an SQL builder. The data can be retrieved with headers,
 * paginated, or formatted as a CSV table. Optionally, it can process data based
 * on "dedication" sessions to calculate time-related statistics.
 */
class report {

    /** @var string The name of the report. */
    private string $reportname;

    /** @var string The filename for the report (e.g., for CSV export). */
    private string $filename;

    /** @var sql_builder The SQL builder responsible for generating SQL queries. */
    private sql_builder $sqlbuilder;

    /** @var array Field-to-header mapping for the report. */
    private array $headers;

    /** @var array Constant key-value pairs to include in each row of the report. */
    private array $constants;

    /** @var string Optional column name for displaying human-readable timestamps. */
    private string $humantime;

    /** @var string Optional column name for dedication time calculations. */
    private string $dedication;

    /** @var string Optional column name for the dedication target (e.g., session grouping). */
    private string $dedicationtarget;

    /**
     * report constructor.
     *
     * Initializes the report with a name, file name, SQL builder, headers, and optional constants.
     *
     * @param string $reportname The name of the report.
     * @param string $filename The filename for export purposes.
     * @param sql_builder $sqlbuilder The SQL builder instance for query generation.
     * @param array $headers An associative array mapping fields to column headers.
     * @param array $constants Optional key-value constants to include in every row.
     * @param string $humantime Optional column for human-readable timestamps.
     * @param string $dedication Optional column for dedication time calculations.
     * @param string $dedicationtarget Optional column for dedication grouping (e.g., sessions).
     */
    public function __construct(
        string $reportname,
        string $filename,
        sql_builder $sqlbuilder,
        array $headers,
        array $constants = [],
        string $humantime = '',
        string $dedication = '',
        string $dedicationtarget = ''
    ) {
        $this->reportname = $reportname;
        $this->filename = $filename;
        $this->sqlbuilder = $sqlbuilder;
        $this->headers = $headers;
        $this->constants = $constants;
        $this->humantime = $humantime;
        $this->dedication = $dedication;
        $this->dedicationtarget = $dedicationtarget;
    }

    /**
     * Gets the name of the report.
     *
     * @return string The report name.
     */
    public function get_report_name(): string {
        return $this->reportname;
    }

    /**
     * Gets the filename associated with the report.
     *
     * @return string The file name.
     */
    public function get_file_name(): string {
        return $this->filename;
    }

    /**
     * Retrieves a batch of data preprocessed for display in a template.
     *
     * This function returns an indexed array with three elements, designed to be unpacked using `list()`. 
     * The array includes:
     *
     * - **[0] data**: A nested array containing rows of data, where each row is represented as 
     *   `["columns" => [["value" => value]]]`.
     * - **[1] headers**: An array of column headers, each represented as `["value" => value]`.
     * - **[2] pagecount**: An integer representing the total number of pages available.
     *
     * Example return structure:
     * ```php
     * [
     *     [
     *         ["columns" => [["value" => "value1"], ["value" => "value2"]]],
     *         ...
     *     ],
     *     [["value" => "Header1"], ["value" => "Header2"]],
     *     10
     * ]
     * ```
     *
     * @param int $batchindex The index of the current data batch to retrieve.
     * @param int $limitnum The maximum number of records per batch.
     * @return array An indexed array `[data, headers, pagecount]` for use in templates.
     */
    public function get_template_table_data(int $batchindex, int $limitnum): array {
        return $this->iterate_over_data(
            MOD_SRG_DATA_VIEW,
            !empty($this->dedication),
            '',
            $batchindex,
            $limitnum
        );
    }

    /**
     * Writes the entire dataset to a CSV file.
     *
     * This function processes the complete dataset and writes it to the specified filename
     * in CSV format. Useful for exporting large amounts of data for offline analysis or sharing.
     *
     * @param string $filename The path to the file where the data will be written.
     * @return void
     */
    public function write_data_to_file(string $filename): void {
        $this->iterate_over_data(
            MOD_SRG_DATA_DOWNLOAD,
            !empty($this->dedication),
            $filename,
            0,
            0
        );
    }

    /**
     * Processes data for template display or file download, handling both normal and dedication modes.
     *
     * Depending on the mode, this function either:
     * - Prepares a batch of data for display in a template (`MOD_SRG_DATA_VIEW`).
     * - Writes all processed data to a CSV file (`MOD_SRG_DATA_DOWNLOAD`).
     *
     * In dedication mode, it groups data into sessions, finalizes them, and processes only complete sessions.
     *
     * @param int $mode One of `MOD_SRG_DATA_VIEW` or `MOD_SRG_DATA_DOWNLOAD`, determining the processing mode.
     * @param bool $isdedication Whether to process data in dedication mode (grouped by sessions).
     * @param string $filename The file path where CSV data will be written (only for `MOD_SRG_DATA_DOWNLOAD`).
     * @param int $batchindex The batch index for paginated data retrieval (only for `MOD_SRG_DATA_VIEW`).
     * @param int $limitnum The maximum number of records per batch (only for `MOD_SRG_DATA_VIEW`).
     * @return array For `MOD_SRG_DATA_VIEW`: An indexed array `[rows, headers, batchcount]`.
     *               For `MOD_SRG_DATA_DOWNLOAD`: An empty array (returns void implicitly).
     * @throws moodle_exception If the mode is invalid or file creation fails.
     */
    private function iterate_over_data(
        int $mode,
        bool $isdedication,
        string $filename,
        int $batchindex,
        int $limitnum
    ): array {
        // Validate the mode to ensure it is supported.
        if ($mode !== MOD_SRG_DATA_VIEW && $mode !== MOD_SRG_DATA_DOWNLOAD) {
            throw new moodle_exception(get_string('error_report_generation_unknown_mode', 'mod_srg'));
        }

        global $DB;

        // Calculate the offset for batched data retrieval.
        $limitfrom = $batchindex * $limitnum;

        // Initialize variables for `MOD_SRG_DATA_VIEW`.
        $rows = []; // Stores rows for the current batch.
        $headers = []; // Stores column headers for the template.
        $batchcount = 0; // Tracks the total number of available batches/pages.

        if ($mode === MOD_SRG_DATA_VIEW) {
            // Prepare column headers for template display.
            foreach ($this->get_headers() as $header) {
                $headers[] = ["value" => $header];
            }
        }

        // Initialize CSV file handling for `MOD_SRG_DATA_DOWNLOAD`.
        $handle = null;
        if ($mode === MOD_SRG_DATA_DOWNLOAD) {
            $handle = fopen($filename, 'w');
            if (!$handle) {
                throw new moodle_exception(get_string('error_download_tempfilecreationfailed', 'mod_srg'));
            }
            // Write headers to the CSV file.
            fputcsv($handle, $this->headers);
        }

        // Variables for dedication mode processing.
        $sessionindex = 0; // Counter for finalized sessions.
        $session = null;  // Tracks the current session being processed.

        // Fetch records from the database using SQL and parameters from the SQL builder.
        $recordset = $DB->get_recordset_sql(
            $this->sqlbuilder->get_select_sql(),
            $this->sqlbuilder->get_params(),
            $isdedication ? 0 : $limitfrom,
            $isdedication ? 0 : $limitnum
        );

        // Process each record in the recordset.
        foreach ($recordset as $record) {
            // Handle the record, finalize the session if applicable, and process the row.
            list($row, $sessionfinished, $session) = $this->handle_record($record, $isdedication, $session);

            if ($isdedication && !$sessionfinished) {
                continue; // Skip incomplete sessions in dedication mode.
            }

            // Handle rows for template display.
            if ($mode === MOD_SRG_DATA_VIEW) {
                if (!$isdedication) {
                    $rows[] = $this->template_process($row);
                    continue;
                }
                // Add finalized session rows within the current batch range.
                if ($sessionindex >= $limitfrom && count($rows) < $limitnum) {
                    $rows[] = $this->template_process($row);
                }
                $sessionindex++; // Increment the session counter.
                continue;
            }

            // Handle rows for file download.
            if ($mode === MOD_SRG_DATA_DOWNLOAD) {
                fputcsv($handle, $row);
            }
        }

        // Finalize and process the last session if applicable.
        if ($isdedication && $session !== null && $this->should_keep_session($session)) {
            $row = $this->finalize_row($session);

            // Add the last session row to template data.
            if ($mode === MOD_SRG_DATA_VIEW) {
                if ($sessionindex >= $limitfrom && count($rows) < $limitnum) {
                    $rows[] = $this->template_process($row);
                }
                $sessionindex++;
            }

            // Write the last session row to the CSV file.
            if ($mode === MOD_SRG_DATA_DOWNLOAD) {
                fputcsv($handle, $row);
            }
        }

        // Close the recordset to free up resources.
        $recordset->close();

        // Calculate the total number of pages for `MOD_SRG_DATA_VIEW`.
        if ($mode === MOD_SRG_DATA_VIEW) {
            $batchcount = $isdedication
                ? (int)ceil($sessionindex / $limitnum)
                : (int)ceil($this->get_row_count() / $limitnum);
        }

        // Close the CSV file handle for `MOD_SRG_DATA_DOWNLOAD`.
        if ($mode === MOD_SRG_DATA_DOWNLOAD) {
            fclose($handle);
        }

        // Return results for template display.
        return [$rows, $headers, $batchcount];
    }

    /**
     * Retrieves the headers for the report.
     *
     * Combines field headers, constant headers, optional human-readable time header, 
     * and dedication column (if set).
     *
     * @return array The report headers.
     */
    private function get_headers(): array {
        $fieldheaders = array_values($this->headers);
        $constantheaders = !empty($this->constants) ? array_keys($this->constants) : [];
        $humantimeheader = $this->humantime !== '' ? [$this->humantime] : [];
        $dedication = $this->dedication !== '' ? [$this->dedication] : [];

        // Combine all header sources into one array.
        $reportheaders = array_merge($fieldheaders, $constantheaders, $humantimeheader, $dedication);

        return $reportheaders;
    }

    /**
     * Processes a database record and updates or finalizes a session for dedication mode.
     *
     * Depending on whether dedication mode is enabled, this function:
     * - Initializes and updates rows for regular processing.
     * - Manages session state for dedication mode, including finalizing sessions and starting new ones.
     *
     * @param stdClass $record A database record to process. Must contain the necessary fields for row/session handling.
     * @param bool $isdedication Whether dedication mode is enabled (grouping records into sessions).
     * @param array|null $session The current session being processed (only used in dedication mode).
     *                             Null if no session is currently active.
     * @return array An indexed array containing:
     *               - array $row: The processed row data. Empty if no row is finalized or for unusable records.
     *               - bool $sessionfinished: Whether the session was finalized in this call.
     *               - array|null $session: The updated or newly initialized session, or null if no session is active.
     */
    private function handle_record(stdClass $record, bool $isdedication, ?array $session) {
        $row = []; // Initializes an empty row for the current record.

        // For regular (non-dedication) mode: process the record directly into a row.
        if (!$isdedication) {
            $row = $this->init_row($record); // Converts the record into a row format.
            return [$row, false, null]; // No session management in regular mode.
        }

        $sessionfinished = false; // Tracks whether the session was finalized.

        // Skip records that are missing required fields or otherwise unusable.
        if ($this->unusable($record)) {
            return [$row, $sessionfinished, $session]; // Return with no changes.
        }

        // Start a new session if no active session exists.
        if ($session === null) {
            $session = $this->initialize_session($record); // Initialize a new session.
            return [$row, $sessionfinished, $session];
        }

        // Check if the current record belongs to the active session.
        if ($this->is_same_session($session, $record)) {
            $session = $this->update_session($session, $record); // Update the session with the record.
            return [$row, $sessionfinished, $session];
        }

        // Finalize the current session if it meets the criteria for saving.
        if ($this->should_keep_session($session)) {
            $row = $this->finalize_row($session); // Convert the session to a finalized row.
            $sessionfinished = true; // Mark the session as finalized.
        }

        // Start a new session after finalizing the previous one.
        $session = $this->initialize_session($record);

        return [$row, $sessionfinished, $session]; // Return the processed row, session status, and the new session.
    }

    /**
     * Transforms a row of data into a format suitable for rendering in a template.
     *
     * Each cell value in the input is wrapped in an associative array with a "value" key,
     * and the resulting row is structured as an array of "columns."
     *
     * @param array $temprow An indexed array representing a row of raw data values.
     * @return array An associative array with a "columns" key, containing an array of processed cells.
     *               Each cell is formatted as ["value" => $cellvalue].
     */
    private function template_process(array $temprow) {
        $row = []; // Initializes an empty array for the processed row.

        // Wrap each cell value in the input row in an associative array with a "value" key.
        foreach ($temprow as $cellvalue) {
            $row[] = ["value" => $cellvalue];
        }

        // Return the processed row as an associative array with a "columns" key.
        return ["columns" => $row];
    }

    /**
     * Initializes a row of data from a database record by mapping fields to headers.
     *
     * Each field in the record is matched to the corresponding header, and constants
     * or additional fields (e.g., human-readable timestamps) are appended as extra columns.
     *
     * @param stdClass $record A database record containing the data to be transformed into a row.
     *                         The record's fields should match the keys in $this->headers.
     * @return array An indexed array representing the fully initialized row, ready for further processing.
     */
    private function init_row(stdClass $record) {
        $row = []; // Initialize an empty array to hold the processed row data.

        // Map each database field to its corresponding header.
        foreach ($this->headers as $field => $header) {
            $row[$header] = $record->$field ?? ""; // Use the record's field value or an empty string if missing.
        }

        // Append constant values as additional columns.
        foreach ($this->constants as $header => $value) {
            $row[$header] = $value;
        }

        // Optionally add a human-readable timestamp if the "humantime" property is defined.
        if (!empty($this->humantime)) {
            $row[$this->humantime] = isset($record->timecreated)
                ? date("Y-m-d H:i:s", $record->timecreated) // Format the "timecreated" field as a readable date-time string.
                : ""; // Use an empty string if "timecreated" is not available.
        }

        // Convert the associative row to an indexed array for consistent output.
        return array_values($row);
    }

    /**
     * Gets the total row count for the report query.
     *
     * @return int The total number of rows.
     */
    private function get_row_count(): int {
        global $DB;
        return $DB->count_records_sql($this->sqlbuilder->get_count_sql(), $this->sqlbuilder->get_params());
    }

    /**
     * Checks if a record is unusable for session processing.
     *
     * A record is considered unusable if it lacks the `timecreated` field or,
     * if a specific `dedicationtarget` is set, does not include that target field.
     *
     * @param mixed $record The record to check.
     * @return bool True if the record is unusable, false otherwise.
     */
    private function unusable(mixed $record): bool {
        // Records are unusable if the `timecreated` field is missing or if the target
        // dedication field is required and not present in the record.
        return !isset($record->timecreated) ||
            ($this->dedicationtarget !== '' && !isset($record->{$this->dedicationtarget}));
    }

    /**
     * Determines if a record belongs to the same session as the current session.
     *
     * A record is considered part of the same session if:
     * - The time gap between the record and the current session does not exceed $dedicationmaxtime.
     * - The `dedicationtarget` is either empty (no target) or matches between the record and session.
     *
     * @param array $session The current session data.
     * @param mixed $record The record to compare.
     * @return bool True if the record belongs to the same session, false otherwise.
     */
    private function is_same_session(array $session, mixed $record): bool {
        $currenttime = $record->timecreated;
        $sessiontime = $session['lastactivitytime'];

        // Sessions are not the same if the time gap exceeds the max allowed time.
        if ($currenttime - $sessiontime > MOD_SRG_DEDICATION_MAX_TIME) {
            return false;
        }

        // If no specific target, any record within the time range belongs to the session.
        if ($this->dedicationtarget === '') {
            return true;
        }

        // If a specific target is set, only match records with the same target value.
        return $session[$this->dedicationtarget] === $record->{$this->dedicationtarget};
    }

    /**
     * Determines if a session should be kept based on its dedication time.
     *
     * A session is kept if the difference between `lastactivitytime` and `sessionstarttime`
     * meets or exceeds the minimum dedication time.
     *
     * @param array $session The session data.
     * @return bool True if the session should be kept, false otherwise.
     */
    private function should_keep_session(array $session): bool {
        // A session is worth keeping if its duration meets or exceeds the minimum dedication time.
        $dedicationtime = $session['lastactivitytime'] - $session['sessionstarttime'];
        return $dedicationtime >= MOD_SRG_DEDICATION_MIN_TIME;
    }

    /**
     * Initializes a new session based on a record.
     *
     * The session includes data from the record, predefined headers, constants,
     * and tracks the start and last activity times.
     *
     * @param mixed $record The record used to initialize the session.
     * @return array The initialized session array.
     */
    private function initialize_session(mixed $record): array {
        // Initialize a session with record data and constants.
        $session = [];
        foreach ($this->headers as $field => $header) {
            $session[$field] = $record->$field ?? "";
        }
        foreach ($this->constants as $header => $value) {
            $session[$header] = $value;
        }

        // Add a human-readable time field if configured.
        if (!empty($this->humantime)) {
            $session[$this->humantime] = date("Y-m-d H:i:s", $record->timecreated);
        }

        // Track session start and last activity times.
        $session['sessionstarttime'] = $record->timecreated;
        $session['lastactivitytime'] = $record->timecreated;

        return $session;
    }

    /**
     * Updates an existing session with data from a new record.
     *
     * Updates the `lastactivitytime` to reflect the latest activity in the session.
     *
     * @param array $session The current session data.
     * @param mixed $record The record used to update the session.
     * @return array The updated session array.
     */
    private function update_session(array $session, mixed $record): array {
        // Update the last activity time for the session.
        $session['lastactivitytime'] = $record->timecreated;
        return $session;
    }

    /**
     * Finalizes a session for output.
     *
     * Calculates the total dedication time, formats it, and removes internal tracking fields.
     *
     * @param array $session The session to finalize.
     * @return array The finalized session array ready for output.
     */
    private function finalize_row(array $session): array {
        // Calculate the total dedication time for the session.
        $dedicationtime = $session['lastactivitytime'] - $session['sessionstarttime'];
        $session[$this->dedication] = $this->format_time($dedicationtime);

        // Remove internal tracking fields.
        unset($session['sessionstarttime'], $session['lastactivitytime']);

        return array_values($session);
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

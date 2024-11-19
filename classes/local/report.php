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
    private sql_builder $sql_builder;

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
     * @param sql_builder $sql_builder The SQL builder instance for query generation.
     * @param array $headers An associative array mapping fields to column headers.
     * @param array $constants Optional key-value constants to include in every row.
     * @param string $humantime Optional column for human-readable timestamps.
     * @param string $dedication Optional column for dedication time calculations.
     * @param string $dedicationtarget Optional column for dedication grouping (e.g., sessions).
     */
    public function __construct(
        string $reportname,
        string $filename,
        sql_builder $sql_builder,
        array $headers,
        array $constants = [],
        string $humantime = '',
        string $dedication = '',
        string $dedicationtarget = ''
    ) {
        $this->reportname = $reportname;
        $this->filename = $filename;
        $this->sql_builder = $sql_builder;
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
     * Retrieves the headers for the report.
     *
     * Combines field headers, constant headers, optional human-readable time header, 
     * and dedication column (if set).
     *
     * @return array The report headers.
     */
    public function get_headers(): array {
        $fieldheaders = array_values($this->headers);
        $constantheaders = !empty($this->constants) ? array_keys($this->constants) : [];
        $humantimeheader = $this->humantime !== '' ? [$this->humantime] : [];
        $dedication = $this->dedication !== '' ? [$this->dedication] : [];

        // Combine all header sources into one array.
        $reportheaders = array_merge($fieldheaders, $constantheaders, $humantimeheader, $dedication);

        return $reportheaders;
    }

    /**
     * Retrieves paginated data for the report.
     *
     * Depending on the presence of a dedication column, retrieves either direct data 
     * or dedication-processed data, along with batch count for pagination.
     *
     * @param int $batchindex The index of the batch (page) to retrieve.
     * @param int $limitnum The number of rows to retrieve.
     * @return array An array containing:
     *               - rows: The data rows for the current page.
     *               - batchcount: The total number of pages/batches.
     */
    public function get_data(int $batchindex, int $limitnum): array {
        $rows = [];
        $batchcount = 0;

        if ($this->dedication === '') {
            // Direct data mode.
            $rows = $this->get_direct_data($batchindex, $limitnum);
            $batchcount = (int)ceil($this->get_row_count() / $limitnum);

            return [$rows, $batchcount];
        }

        // Dedication processing mode.
        list($rows, $batchcount) = $this->get_dedication_data($batchindex, $limitnum);
        return [$rows, $batchcount];
    }

    /**
     * Formats report data as a CSV table.
     *
     * @param int $batchindex The index of the batch (page) to retrieve.
     * @param int $limitnum The number of rows to retrieve.
     * @return string The formatted CSV table.
     */
    public function get_as_csv_table(int $batchindex, int $limitnum): string {
        $headers = $this->get_headers();
        list($data, $batchcount) = $this->get_data($batchindex, $limitnum);

        $rows = [];

        // Add headers to the CSV as the first row.
        $cells = [];
        foreach ($headers as $value) {
            // Escape newlines and double quotes in the headers.
            $cells[] = '"' . preg_replace(['/\n/', '/"/'], ['', '""'], $value) . '"';
        }
        $rows[] = implode(",", $cells);

        // Add each data row to the CSV.
        foreach ($data as $row) {
            $cells = [];
            foreach ($row as $value) {
                // Escape newlines and double quotes in the data.
                $cells[] = '"' . preg_replace(['/\n/', '/"/'], ['', '""'], $value) . '"';
            }
            $rows[] = implode(",", $cells);
        }

        // Combine all rows into a single CSV string with newline separators.
        $csv = implode("\n", $rows);

        return $csv;
    }

    /**
     * Gets the total row count for the report query.
     *
     * @return int The total number of rows.
     */
    private function get_row_count(): int {
        global $DB;
        return $DB->count_records_sql($this->sql_builder->get_count_sql(), $this->sql_builder->get_params());
    }

    /**
     * Retrieves data directly without dedication calculations.
     *
     * @param int $batchindex The index of the batch (page) to retrieve.
     * @param int $limitnum The number of rows to retrieve.
     * @return array The data rows.
     */
    private function get_direct_data(int $batchindex, int $limitnum): array {
        global $DB;

        // Execute the SQL query with pagination.
        $recordset = $DB->get_recordset_sql(
            $this->sql_builder->get_select_sql(),
            $this->sql_builder->get_params(),
            $batchindex * $limitnum,
            $limitnum
        );

        $rows = [];
        foreach ($recordset as $record) {
            $row = [];
            // Map each field to its header.
            foreach ($this->headers as $field => $header) {
                $row[$header] = $record->$field ?? "";
            }
            // Add constants as additional columns.
            foreach ($this->constants as $header => $value) {
                $row[$header] = $value;
            }
            // Optionally add human-readable time.
            if (!empty($this->humantime)) {
                $row[$this->humantime] = isset($record->timecreated) ? date("Y-m-d H:i:s", $record->timecreated) : "";
            }
            $rows[] = array_values($row);
        }

        $recordset->close();

        return $rows;
    }

    /**
     * Retrieves dedication data for a specific batch and calculates total pages for pagination.
     *
     * @param int $batchindex The index of the batch (page) to retrieve.
     * @param int $limitnum The number of rows per batch.
     * @param int $dedicationmintime Minimum dedication time required to keep a session (in seconds).
     * @param int $dedicationmaxtime Maximum time allowed between activities to consider the same session (in seconds).
     * @return array An array containing:
     *               - rows: The rows for the requested batch.
     *               - batchcount: The total number of available batches/pages.
     */
    private function get_dedication_data(
        int $batchindex,
        int $limitnum,
        int $dedicationmintime = 60,
        int $dedicationmaxtime = 900
    ): array {
        global $DB;

        // Calculate the starting point for the current batch.
        $limitfrom = $batchindex * $limitnum;
        $sessionindex = 0; // Tracks the total number of finalized sessions processed.

        $rows = [];       // Stores the rows for the current batch.
        $batchcount = 0;  // Tracks the total number of batches/pages available.

        $session = null;  // Tracks the current session being processed.

        // Fetch all records to determine total sessions for pagination.
        $recordset = $DB->get_recordset_sql(
            $this->sql_builder->get_select_sql(),
            $this->sql_builder->get_params()
        );

        foreach ($recordset as $record) {
            // Skip unusable records based on required fields.
            if ($this->unusable($record)) {
                continue;
            }

            // Start a new session if none exists.
            if ($session === null) {
                $session = $this->initialize_session($record);
                continue;
            }

            // Check if the current record belongs to the same session.
            if ($this->is_same_session($session, $record, $dedicationmaxtime)) {
                $session = $this->update_session($session, $record);
                continue;
            }

            // Finalize the current session if it meets the minimum dedication time.
            if ($this->should_keep_session($session, $dedicationmintime)) {
                if ($sessionindex >= $limitfrom && count($rows) < $limitnum) {
                    $rows[] = $this->finalize_row($session);
                }
                $sessionindex++; // Increment the session counter.
            }

            // Start a new session after finalizing the previous one.
            $session = $this->initialize_session($record);
        }

        $recordset->close();

        // Finalize any remaining session after the loop ends.
        if ($session !== null && $this->should_keep_session($session, $dedicationmintime)) {
            if ($sessionindex >= $limitfrom && count($rows) < $limitnum) {
                $rows[] = $this->finalize_row($session);
            }
            $sessionindex++; // Increment the session counter for the final session.
        }

        // Calculate the total number of batches/pages based on session count.
        $batchcount = (int)ceil($sessionindex / $limitnum);

        // Return both the rows for the current batch and the total number of pages.
        return [$rows, $batchcount];
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
     * @param int $dedicationmaxtime Maximum allowed time between activities to consider the same session (in seconds).
     * @return bool True if the record belongs to the same session, false otherwise.
     */
    private function is_same_session(array $session, mixed $record, int $dedicationmaxtime): bool {
        $currenttime = $record->timecreated;
        $sessiontime = $session['lastactivitytime'];

        // Sessions are not the same if the time gap exceeds the max allowed time.
        if ($currenttime - $sessiontime > $dedicationmaxtime) {
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
     * @param int $dedicationmintime Minimum dedication time required to keep a session (in seconds).
     * @return bool True if the session should be kept, false otherwise.
     */
    private function should_keep_session(array $session, int $dedicationmintime): bool {
        // A session is worth keeping if its duration meets or exceeds the minimum dedication time.
        $dedicationtime = $session['lastactivitytime'] - $session['sessionstarttime'];
        return $dedicationtime >= $dedicationmintime;
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

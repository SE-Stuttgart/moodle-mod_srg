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
 * This class helps in predefining a report and gathering the necessary data from the DB.
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg\local;

use moodle_exception;
use stdClass;

class report {

    private string $reportname;
    private string $filename;
    private sql_builder $sql_builder;
    private array $headers;
    private array $constants;
    private string $humantime;
    private string $dedication;
    private string $dedicationtarget;

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

    public function get_report_name(): string {
        return $this->reportname;
    }

    public function get_file_name(): string {
        return $this->filename;
    }

    public function get_headers(): array {
        $fieldheaders = array_values($this->headers);
        $constantheaders = !empty($this->constants) ? array_keys($this->constants) : [];
        $humantimeheader = $this->humantime !== '' ? [$this->humantime] : [];
        $dedication = $this->dedication !== '' ? [$this->dedication] : [];

        $reportheaders = array_merge($fieldheaders, $constantheaders, $humantimeheader, $dedication);

        return $reportheaders;
    }

    public function get_row_count(): int {
        global $DB;
        return $DB->count_records_sql($this->sql_builder->get_count_sql(), $this->sql_builder->get_params());
    }

    public function get_data(int $limitfrom, int $limitnum): array {
        $rows = [];
        $newlimitfrom = 0;
        if ($this->dedication === '') {
            $rows = $this->get_direct_data($limitfrom, $limitnum);
            $newlimitfrom = $limitfrom + $limitnum;
            return [$rows, $newlimitfrom];
        }
        list($rows, $newlimitfrom) = $this->get_dedication_data($limitfrom, $limitnum);
        return [$rows, $newlimitfrom];
    }


    public function get_as_csv_table(int $limitfrom, int $limitnum): string {
        $headers = $this->get_headers();
        list($data, $newlimitfrom) = $this->get_data($limitfrom, $limitnum);

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



    private function get_direct_data(int $limitfrom, int $limitnum): array {
        global $DB;

        $recordset = $DB->get_recordset_sql(
            $this->sql_builder->get_select_sql(),
            $this->sql_builder->get_params(),
            $limitfrom,
            $limitnum
        );

        $rows = [];
        foreach ($recordset as $record) {
            $row = [];
            foreach ($this->headers as $field => $header) {
                $row[$header] = $record->$field ?? "";
            }
            foreach ($this->constants as $header => $value) {
                $row[$header] = $value;
            }
            if (!empty($this->humantime)) {
                $row[$this->humantime] = isset($record->timecreated) ? date("Y-m-d H:i:s", $record->timecreated) : "";
            }
            $rows[] = array_values($row);
        }

        $recordset->close();

        return $rows;
    }
    private function get_dedication_data(
        int $limitfrom,
        int $limitnum,
        int $dedicationmintime = 60,
        int $dedicationmaxtime = 900
    ): array {
        $rows = [];
        $processedcount = 0;
        // Explicitly initialize as null. Null meand either first or last session.
        $session = null;

        while ($processedcount < $limitnum && $limitfrom < $this->get_row_count() - 1) {
            global $DB;

            // Fetch records in batches.
            $recordset = $DB->get_recordset_sql(
                $this->sql_builder->get_select_sql(),
                $this->sql_builder->get_params(),
                $limitfrom,
                MOD_SRG_DEDICATION_BATCH_SIZE
            );

            foreach ($recordset as $record) {
                // Iterate recordcounter.
                $limitfrom++;

                // Skip unusable records.
                $recordunusable = !isset($record->timecreated) || (
                    !empty($dedicationtarget) &&
                    !isset($record->$dedicationtarget)
                );
                if ($recordunusable) {
                    continue;
                }

                // Is this the first session?
                if ($session === null) {
                    $session = $this->initialize_session($record);
                    continue;
                }

                // Update the session with the current row record.
                if ($this->is_same_session($session, $record, $dedicationmaxtime)) {
                    $session = $this->update_session($session, $record);
                    continue;
                }

                if ($this->should_keep_session($session, $dedicationmintime)) {
                    $rows[] = $this->finalize_row($session);
                    $processedcount++;

                    // Reset $session to null after processing the finalized row.
                    $session = null;

                    if ($processedcount >= $limitnum) {
                        break;
                    }
                }

                $session = $this->initialize_session($record);
            }

            // The recordset has no entries.
            if (!$recordset->valid()) {
                $recordset->close();
                break;
            }

            $recordset->close();
        }

        // We ran out of records before the total processedcount was >= limitnum.
        if ($session !== null && $this->should_keep_session($session, $dedicationmintime)) {
            $rows[] = $this->finalize_row($session);
            $processedcount++;
            $session = null;
        }

        return [$rows, $limitfrom + 1];
    }

    private function is_same_session(array $session, mixed $record, int $dedicationmaxtime): bool {
        $currenttime = $record->timecreated;
        $sessiontime = $session['lastactivitytime'];

        // The time difference between the session and this record is too large, > $dedicationmaxtime.
        if ($currenttime - $sessiontime > $dedicationmaxtime) {
            return false;
        }

        // We do not care about a component.
        if ($this->dedicationtarget === '') {
            return true;
        }

        // We care about same component, it is the same session only if the component matches.
        return $session[$this->dedicationtarget] === $record->{$this->dedicationtarget};
    }
    private function should_keep_session(array $session, int $dedicationmintime): bool {
        $dedicationtime = $session['lastactivitytime'] - $session['sessionstarttime'];
        return $dedicationtime >= $dedicationmintime;
    }

    private function initialize_session($record): array {
        $session = [];
        foreach ($this->headers as $field => $header) {
            $session[$field] = $record->$field ?? "";
        }
        foreach ($this->constants as $header => $value) {
            $session[$header] = $value;
        }
        if (!empty($this->humantime)) {
            $session[$this->humantime] = date("Y-m-d H:i:s", $record->timecreated);
        }
        $session['sessionstarttime'] = $record->timecreated;
        $session['lastactivitytime'] = $record->timecreated;
        return $session;
    }

    private function update_session(array $session, $record): array {
        $session['lastactivitytime'] = $record->timecreated;
        return $session;
    }

    private function finalize_row(array $session): array {
        $dedicationtime = $session['lastactivitytime'] - $session['sessionstarttime'];
        $session[$this->dedication] = $this->format_time($dedicationtime);
        unset($session['sessionstarttime'], $session['lastactivitytime']); // Remove internal tracking field.

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

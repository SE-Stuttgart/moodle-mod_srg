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

/**
 * This class is used to create an array based on the data of a DB query result.
 * The query will be performed by the constructor using constructor parameters.
 * This class has multiple function working with and altering the array.
 */
class table_from_db
{
    public const DEFAULT = 0;
    public const PUBLIC = 1;
    public const HIDDEN = 2;

    private array $table_heading;
    private array $table;

    private array $hidden_table_headings;
    private array $hidden_table;

    /**
     * Create an Object of table_from_db which makes a call to the DB based on given parameters and holds the acquired data in an array.
     * Class has multiple function working with and altering the array.
     * @param string $from name of the table to be queried.
     * @param array $where Array of where clauses, will be connected via and.
     * @param array $public_columns Array ('internal_column_name' => 'public_column_name') of columns we want to have in our return table.
     * Public columns are always optional. If you want a required public column, have the column in BOTH public and hidden columns.
     * @param array $hidden_columns Array ('internal_column_name' => 'internal_column_name') of columns we need, but do not want returned.
     * (i.e. 'id' 'timecreated' should be included). Hidden columns are always required.
     * @param string $sort [default="id ADC"] the sort by clause for the query.
     */
    public function __construct(
        string  $from,
        array   $where,
        array   $public_columns,
        array   $hidden_columns,
        string  $sort = 'id ASC'
    ) {
        if (!isset($hidden_columns['id'])) $hidden_columns['id'] = 'id';


        global $DB;
        $this->table_heading = $public_columns;
        $this->table = array();
        $this->hidden_table_headings = $hidden_columns;
        $this->hidden_table = array();

        // Prepare $db_where
        $db_where = '';
        if (!empty($where))
            $db_where .= "(" . implode(' and ', $where) . ")";
        if (!empty($where) && !empty($hidden_columns))
            $db_where .= " and ";
        if (!empty($hidden_columns))
            $db_where .= "(" . implode(" <> '' and ", array_keys($hidden_columns)) . " <> '')";

        // Get DB records
        try {
            $records = $DB->get_records_select(
                $from,
                $db_where
            );
        } catch (\Throwable $th) {
            throw $th;
        }

        // Transform DB returned records into desired data structure
        foreach ($records as $record) {
            $row = array();
            foreach ($this->table_heading as $internal_heading => $external_heading) {
                if (isset($record->$internal_heading))
                    $row[$internal_heading] = $record->$internal_heading;
            }
            if (isset($record->{'id'}))
                $this->table[$record->{'id'}] = $row;

            $row = array();
            foreach ($this->hidden_table_headings as $internal_heading) {
                if (isset($record->$internal_heading))
                    $row[$internal_heading] = $record->$internal_heading;
            }
            if (isset($record->{'id'}))
                $this->hidden_table[$record->{'id'}] = $row;
        }
    }


    /**
     * Make a query using information from the first query and combine the it into the table.
     * @param bool $is_const True if parameter $table_source is a constant. False if parameter $table_source is a column in the table.
     * @param string $table_source Option 1: Table name of table to query. Option 2: Column name of table where to find the table name (Hidden Table Only).
     * @param string $id_source Column name where to find the IDs corresponding to the new table. (Hidden Table Only).
     * @param array $params Parameter needed for DB query. 
     * 
     * Array(table_name => array(table_from_db::PUBLIC => array(internal_source_name => internal_table_name), table_from_db::HIDDEN => array (internal_source_name => internal_table_name)))
     * 
     * For having a different internal than external name, use rename_columns(). 
     * 
     * Auto hidden array element, do not add: id => id_source
     * 
     * [table_name = table_from_db::DEFAULT] for general behaviour independant of table. Useful for $is_const == true. 
     * Default should be avoided for $is_const == false as it assumes similar DB structure of multiple tables.
     * @param array $requirements [default = array()] set requirements that the table row has to have to be able to count for this nest_query.
     * 
     * array(column => value)
     * @return table_from_db object.
     */
    public function nest_query(
        bool $is_const,
        string $table_source,
        string $id_source,
        array $requirements,
        array $where,
        array $public_columns,
        array $hidden_columns
    ) {
        global $DB;
        $table_to_query = true; // const
        $query_to_table = false; // const

        // Create an array of ID matching arrays for every table to be queried.
        $sub_queries = array();
        foreach ($this->hidden_table as $id => $row) {
            // Check if row is necessary for this query
            if (!isset($row[$id_source])) continue;
            foreach ($requirements as $column => $value) {
                if (!isset($row[$column]) || $row[$column] != $value) continue;
            }

            // Set target table to query
            if ($is_const) $target_table = $table_source;
            else $target_table = $row[$table_source];

            // Create two arrays, with ids referencing each other.
            $sub_queries[$target_table][$table_to_query][$id] = $row[$id_source];
            $sub_queries[$target_table][$query_to_table][$row[$id_source]][] = $id;
        }

        // loop over the tables, that need to be searched
        foreach ($sub_queries as $table => $ids) {
            // Missing information
            if (
                (!isset($where[$table]) && !isset($where[self::DEFAULT]))
                || (!isset($public_columns[$table]) && !isset($public_columns[self::DEFAULT]))
                || (!isset($hidden_columns[$table]) && !isset($hidden_columns[self::DEFAULT]))
            ) continue;

            // Check which parameters should be used for this query.
            if (!isset($where[$table])) $this_where = array('id in (' . implode(",", $ids[$table_to_query]) . ')') + $where[self::DEFAULT];
            else $this_where = array('id' => $ids[$table_to_query]) + $where[$table];
            if (!isset($public_columns[$table])) $this_public_columns = $public_columns[self::DEFAULT];
            else $this_public_columns = $public_columns[$table];
            if (!isset($hidden_columns[$table])) $this_hidden_columns = array('id' => $id_source) + $hidden_columns[self::DEFAULT];
            else $this_hidden_columns = array('id' => $id_source) + $hidden_columns[$table];

            // Create table columns (headers)
            foreach ($this_public_columns as $internal_table_heading)
                $this->table_heading[$internal_table_heading] = $internal_table_heading;
            foreach ($this_hidden_columns as $internal_table_heading)
                $this->hidden_table_headings[$internal_table_heading] = $internal_table_heading;

            // Prepare $db_where
            $db_where = '';
            if (!empty($this_where))
                $db_where .= "(" . implode(' and ', $this_where) . ")";
            if (!empty($this_where) && !empty($this_hidden_columns))
                $db_where .= " and ";
            if (!empty($this_hidden_columns))
                $db_where .= "(" . implode(" <> '' and ", array_keys($this_hidden_columns)) . " <> '')";

            // Get DB records
            try {
                $records = $DB->get_records_select(
                    $table,
                    $db_where
                );
            } catch (\Throwable $th) {
                throw $th;
            }

            // Fill table rows with new data
            foreach ($records as $record) {
                // Get IDs in table which shall be filled with this record
                $table_ids = $ids[$query_to_table][$record->{'id'}];
                foreach ($table_ids as $table_id) {
                    foreach ($this_public_columns as $internal_source_heading => $internal_table_heading) {
                        if (isset($record->$internal_source_heading))
                            $this->table[$table_id][$internal_table_heading] = $record->$internal_source_heading;
                    }
                    foreach ($this_hidden_columns as $internal_source_heading => $internal_table_heading) {
                        if (isset($record->$internal_source_heading))
                            $this->hidden_table[$table_id][$internal_table_heading] = $record->$internal_source_heading;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Collapse a the table based on $dedication_targen and the timecreated of the DB entries.
     * Create a new column $name which holds the timedifference of each collapsed entry.
     * This timedifference represents the the time actively spent on each $dedication_target.
     * To consider the closing of the website or the pause of working, a min time (of total dedication) and max time (of time between entries) is required.
     * @param string $name Name of the new column
     * @param string $dedication_target [default =''] Name of the column to collapse into. This column represents what represents a single task. 
     * If empty, all entries count as the same task.
     * @param int $dedication_min_time [default = 60] Minimal time in seconds spend on a task to count as working on it.
     * @param int $dedication_max_time [default = 60 * 15] Maximal time in seconds between entries to spot work pauses.
     * @return table_from_db object.
     */
    public function add_dedication(string $name, string $dedication_target = '', int $dedication_min_time = 60, int $dedication_max_time = 60 * 15)
    {
        $this->table_heading[$name] = $name;

        $first_session = true;
        $session_id = array_key_first($this->table);
        $last_id = array_key_last($this->table);
        $previous_time = $this->get_time($session_id);

        $ids_to_delete = array();
        foreach ($this->table as $id => $row) {

            // If new session do update session_id
            if (
                $first_session
                || $this->get_time($id) - $previous_time > $dedication_max_time
                || !empty($dedication_target) && $this->table[$id][$dedication_target] != $this->table[$session_id][$dedication_target]
            ) {
                // Dedication is under our min_time
                if (!$first_session && $this->table[$session_id][$name] < $dedication_min_time)
                    $ids_to_delete[] = $session_id;

                // Next Session
                $first_session = false;
                $session_id = $id;
                $previous_time = $this->get_time($id);
                $this->table[$session_id][$name] = $previous_time - $this->get_time($session_id);

                // Session can't start on last id
                if ($session_id == $last_id)
                    $ids_to_delete[] = $id;
                continue;
            }

            // Else update session edication and delete subsession entry
            $previous_time = $this->get_time($id);
            $this->table[$session_id][$name] = $previous_time - $this->get_time($session_id);
            $ids_to_delete[] = $id;

            // Last session may be too short
            if ($session_id == $last_id && $this->table[$session_id][$name] < $dedication_min_time)
                $ids_to_delete[] = $session_id;
        }

        // Delete IDs of subsession entries.
        foreach ($ids_to_delete as $id) {
            unset($this->table[$id]);
            unset($this->hidden_table[$id]);
        }

        foreach ($this->table as &$row) {
            $row[$name] = $this->format_time($row[$name]);
        }

        return $this;
    }

    /**
     * Add a new column to the table with a given constant value for all rows.
     * @param array of column heading and value pairs.
     * @return table_from_db object.
     */
    public function add_constant_columns(array $name_data_pairs)
    {
        foreach ($name_data_pairs as $name => $data) {
            $this->table_heading[$name] = $name;
            foreach ($this->table as $id => $row) {
                $this->table[$id][$name] = $data;
            }
        }

        return $this;
    }

    /**
     * Change the first row value representing the column headings.
     * This does not change the keys. If keys are required for other functions, still use the old key.
     * @param array of column heading key and new column heading value.
     * @return table_from_db object.
     */
    public function rename_columns(array $key_value_pairs)
    {
        foreach ($key_value_pairs as $key => $value) {
            $this->table_heading[$key] = $value;
        }

        return $this;
    }

    /**
     * Function to add a Human understandable Timestamp based on the timecreated of the log to the Table.
     * @param string $name is the column heading for the new column.
     * @return table_from_db object. 
     */
    public function add_human_time(string $name)
    {
        $this->table_heading[$name] = $name;
        foreach ($this->table as $id => $row) {
            $this->table[$id][$name] = date("Y-m-d H:i:s", $this->get_time($id));
        }

        return $this;
    }

    /**
     * Function removes rows in table that do not have a value in at least one of the given columns.
     * @param array $columns Array of table columns to check
     * @return table_from_db object. 
     */
    public function prune_table(array $columns)
    {
        $delete_ids = array();
        foreach ($this->table as $id => $row) {
            foreach ($columns as $column) {
                if (!isset($row[$column]) || empty($row[$column])) {
                    $delete_ids[] = $id;
                    break;
                }
            }
        }
        foreach ($delete_ids as $id) {
            unset($this->table[$id]);
            unset($this->hidden_table[$id]);
        }

        return $this;
    }

    /**
     * @return array of rows representing the table. First row and column keys are the column headings.
     */
    public function get_table()
    {
        return array(0 => $this->table_heading) + $this->table;
    }

    /**
     * Turns an int of seconds into a string: x hours y minutes z seconds
     * @param int $time_in_seconds
     * @return string x hours y minutes z seconds
     */
    private function format_time(int $time_in_seconds)
    {

        $totalsecs = abs($time_in_seconds);

        $str = new stdClass();
        $str->hour = get_string('hour');
        $str->hours = get_string('hours');
        $str->min = get_string('min');
        $str->mins = get_string('mins');
        $str->sec = get_string('sec');
        $str->secs = get_string('secs');

        $hours = floor($totalsecs / HOURSECS);
        $remainder = $totalsecs - ($hours * HOURSECS);
        $mins = floor($remainder / MINSECS);
        $secs = round($remainder - ($mins * MINSECS), 2);

        $ss = ($secs == 1) ? $str->sec : $str->secs;
        $sm = ($mins == 1) ? $str->min : $str->mins;
        $sh = ($hours == 1) ? $str->hour : $str->hours;

        $ohours = '';
        $omins = '';
        $osecs = '';

        if ($hours) {
            $ohours = $hours . ' ' . $sh;
        }
        if ($mins) {
            $omins = $mins . ' ' . $sm;
        }
        if ($secs) {
            $osecs = $secs . ' ' . $ss;
        }

        if ($hours) {
            return trim($ohours . ' ' . $omins);
        }
        if ($mins) {
            return trim($omins . ' ' . $osecs);
        }
        if ($secs) {
            return $osecs;
        }
        return get_string('none');
    }

    /**
     * Get the timecreated for the given id saved in the hidden_table
     * @param int $id row id
     * @return int timecreated
     */
    private function get_time(int $id)
    {
        return (int)($this->hidden_table[$id]['timecreated']);
    }
}

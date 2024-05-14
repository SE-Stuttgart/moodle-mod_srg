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
 * Object Class of a local db, with connection to moodle db and data manipulation fuinctions.
 *
 * @package     mod_srg
 * @copyright   2024 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg;

use stdClass;

/**
 * This class is a connection to the db, a representation and data and has data manipulation capabilities.
 */
class table {

    /** @var array $columns this array holds information on which columns are represented and how the column is named.*/
    private array $columns;
    /** @var array $data this array holds all the data of the table [id => [column => value]]. */
    private array $data;

    /**
     * Create a table object, that can request data from the db and can manipulate the existing data.
     * @param array $columns , this array holds information on which columns are represented and how the column is named.
     * @param array $data , this array holds all the data of the table [id => [column => value]].
     */
    public function __construct(array $columns, array $data) {
        $this->columns = $columns;
        $this->data = $data;
    }


    /**
     * This function accessess the moodle db and loads information into this table object.
     * @param string $tablename name of the target table to load data from.
     * @param array $conditions conditions [column => [value]] where row is skipped if the column data is not part of values.
     * @param array $fields a list of columns that select which fields should appear in the resulting table.
     * @return table this object
     */
    public function get_db_records(string $tablename, array $conditions, array $fields): table {
        global $DB;

        $data = [];

        // Get all records of given table. Then filter ist based on our conditions.
        $rs = $DB->get_recordset($tablename);
        foreach ($rs as $record) {

            // Check if the result is relevant for us.
            $continue = false;
            foreach ($conditions as $field => $values) {
                if (isset($record->$field) && !in_array($record->$field, $values)) {
                    $continue = true;
                    break;
                }
            }
            if ($continue || !isset($record->{"id"})) {
                continue;
            }

            // Transform DB returned records into desired data structure.
            $row = [];
            foreach ($fields as $column) {
                if (isset($record->$column)) {
                    $row[$column] = $record->$column;
                }
            }
            $data[$record->{"id"}] = $row;
        }
        $rs->close();

        // Object containing all necessary information we need of a single table.
        $this->columns = $fields;
        $this->data = $data;

        return $this;
    }


    /**
     * Create a new table based on this table and some more constraints.
     * @param array $columns a list of columns [column_name => title] to select which columns the sub-table has.
     * @return table new sub-table object
     */
    public function create_and_get_sub_table(array $columns): table {
        $subdata = [];

        foreach ($this->data as $id => $data) {
            $row = [];
            foreach ($columns as $column => $title) {
                if (isset($data[$column])) {
                    $row[$column] = $data[$column];
                } else {
                    $row[$column] = "";
                }
            }
            $subdata[$id] = $row;
        }
        return new table($columns, $subdata);
    }


    /**
     * Constrain the current table by deleting all rows where there is no value in the given column.
     * @param string $column the constrained column.
     * @return table this object
     */
    public function additional_requirement(string $column): table {
        $data = [];
        foreach ($this->data as $id => $row) {
            if (isset($row[$column])) {
                $data[$id] = $row;
            }
        }
        $this->data = $data;
        return $this;
    }


    /**
     * Contrain the current table by deleting all rows where there is not one of the given values in the given columns.
     * @param string $column the constrained column.
     * @param array $values the list of allowed values.
     * @return table this object
     */
    public function additional_constraint(string $column, array $values): table {
        $data = [];
        foreach ($this->data as $id => $row) {
            if (isset($row[$column]) && in_array($row[$column], $values)) {
                $data[$id] = $row;
            }
        }
        $this->data = $data;
        return $this;
    }


    /**
     * The way how to calculate the dedication was inspired by the Block_Dedication plug-in.
     *
     * Collapse a the table based on $dedication_targen and the timecreated of the DB entries.
     * Create a new column $name which holds the timedifference of each collapsed entry.
     * This timedifference represents the the time actively spent on each $dedicationtarget.
     * To consider the closing of the website or the pause of working,
     * a min time (of total dedication) and max time (of time between entries) is required.
     * @param string $name Name of the new column
     * @param string $dedicationtarget [default =''] Name of the column to collapse into.
     * This column represents what represents a single task.
     * If empty, all entries count as the same task.
     * @param int $dedicationmintime [default = 60] Minimal time in seconds spend on a task to count as working on it.
     * @param int $dedicationmaxtime [default = 60 * 15] Maximal time in seconds between entries to spot work pauses.
     * @return table this object
     */
    public function add_dedication(
        string $name,
        string $dedicationtarget = '',
        int $dedicationmintime = 60,
        int $dedicationmaxtime = 60 * 15
    ) {
        $this->columns[$name] = $name;

        if (empty($this->data)) {
            return $this;
        }

        $firstsession = true;
        $sessionid = array_key_first($this->data);
        $lastid = array_key_last($this->data);
        $previoustime = $this->get_time($sessionid);

        $idstodelete = [];
        foreach ($this->data as $id => $row) {

            // If new session do update sessionid.
            if (
                $firstsession
                || $this->get_time($id) - $previoustime > $dedicationmaxtime
                || !empty($dedicationtarget) && $this->data[$id][$dedicationtarget] != $this->data[$sessionid][$dedicationtarget]
            ) {
                // Dedication is under our min_time.
                if (!$firstsession && $this->data[$sessionid][$name] < $dedicationmintime) {
                    $idstodelete[] = $sessionid;
                }

                // Next Session.
                $firstsession = false;
                $sessionid = $id;
                $previoustime = $this->get_time($id);
                $this->data[$sessionid][$name] = $previoustime - $this->get_time($sessionid);

                // Session can't start on last id.
                if ($sessionid == $lastid) {
                    $idstodelete[] = $id;
                }
                continue;
            }

            // Else update session edication and delete subsession entry.
            $previoustime = $this->get_time($id);
            $this->data[$sessionid][$name] = $previoustime - $this->get_time($sessionid);
            $idstodelete[] = $id;

            // Last session may be too short.
            if ($sessionid == $lastid && $this->data[$sessionid][$name] < $dedicationmintime) {
                $idstodelete[] = $sessionid;
            }
        }

        // Delete IDs of subsession entries.
        foreach ($idstodelete as $id) {
            unset($this->data[$id]);
        }

        foreach ($this->data as &$row) {
            $row[$name] = $this->format_time($row[$name]);
        }

        return $this;
    }


    /**
     * Add a new column to the table with a given constant value for all rows.
     * @param string $name the name of the new column.
     * @param mixed $value the value of the new param.
     * @return table this object
     */
    public function add_constant_column(string $name, mixed $value): table {
        $this->columns[$name] = $name;
        foreach ($this->data as $id => $row) {
            $this->data[$id][$name] = $value;
        }
        return $this;
    }


    /**
     * Rename the title of a column (internal column_name is unchanged).
     * @param string $column the name of the column to be renamed.
     * @param string $title the new title.
     * @return table this object
     */
    public function rename_column(string $column, string $title): table {
        $this->columns[$column] = $title;
        return $this;
    }


    /**
     * Function to add a Human understandable Timestamp based on the timecreated of the log to the Table.
     * @param string $name Name is the column title for the new column.
     * @return table this object
     */
    public function add_human_time(string $name): table {
        $this->columns[$name] = $name;
        foreach ($this->data as $id => $row) {
            $this->data[$id][$name] = date("Y-m-d H:i:s", $this->get_time($id));
        }

        return $this;
    }


    /**
     * Returns the table column and data as an array.
     * Array keys of row items are column_name, value of first row is title.
     * @return array Array of rows representing the table.
     */
    public function get_table(): array {
        return [0 => $this->columns] + $this->data;
    }


    /**
     * Call the Moodle DB and join data of one other Moodle DB with this table.
     * @param string $targettable name of the target table.
     * @param string $targetidcolumn where in this table can be the join id be found.
     * @param array $joindata what data (columns) do we want from the joined table [targetcolumnname => tablecolumnname].
     * @return table this object
     */
    public function join_with_fixed_table(
        string $targettable,
        string $targetidcolumn,
        array $joindata
    ): table {
        $idmatches = [];
        foreach ($this->data as $id => $row) {
            // Check if this row can be subqueried.
            if (isset($row[$targetidcolumn])) {
                // Save the row ids for later join operations. idmatches[JoinID => [MainID]].
                if (!isset($idmatches[$row[$targetidcolumn]])) {
                    $idmatches[$row[$targetidcolumn]] = [];
                }
                $idmatches[$row[$targetidcolumn]][] = $id;
            }
        }
        $this->join($targettable, $idmatches, $joindata);
        return $this;
    }


    /**
     * Call the Moodle DB and join data of other Moodle DBs with this table.
     * The target DBs are based on values in the this table.
     * @param string $targettablecolumn where in this table can the target DB be found.
     * @param string $targetidcolumn where in this table can be the join id be found.
     * @param array $defaultjoindata what data (columns) do we want from the joined table [targetcolumnname => tablecolumnname].
     * @param array $customjoindata similar to $defaultjoindata but it defines different data columns selected based on target DB.
     * @return table this object
     */
    public function join_with_variable_table(
        string $targettablecolumn,
        string $targetidcolumn,
        array $defaultjoindata,
        array $customjoindata
    ): table {
        $idmatches = [];
        foreach ($this->data as $id => $row) {
            // Check if this row can be subqueried.
            if (isset($row[$targettablecolumn]) && isset($row[$targetidcolumn])) {
                // Check if this table is already registered.
                if (!isset($idmatches[$row[$targettablecolumn]])) {
                    $idmatches[$row[$targettablecolumn]] = [];
                }
                // Save the row ids for later join operations. idmatches[JoinTable => [JoinID => [MainID]]].
                if (!isset($idmatches[$row[$targettablecolumn]][$row[$targetidcolumn]])) {
                    $idmatches[$row[$targettablecolumn]][$row[$targetidcolumn]] = [];
                }
                $idmatches[$row[$targettablecolumn]][$row[$targetidcolumn]][] = $id;
            }
        }

        foreach ($idmatches as $tablename => $tableidmatches) {
            if (isset($customjoindata[$tablename])) {
                $this->join($tablename, $tableidmatches, $customjoindata[$tablename]);
            } else {
                $this->join($tablename, $tableidmatches, $defaultjoindata);
            }
        }
        return $this;
    }


    /**
     * This function calls upon a given Moodle DB table and joins its data based on the selected joindata.
     * @param string $targettable name of the target Moodle DB table.
     * @param array $idmatches ID matching [JoinID => [MainID]] to quickly find where found data should be joined to.
     * @param array $joindata what data (columns) do we want from the joined table [targetcolumnname => tablecolumnname].
     */
    private function join(string $targettable, array $idmatches, array $joindata) {
        global $DB;

        // Get all records of given table. Then filter ist based on our conditions.
        $rs = $DB->get_recordset($targettable);
        foreach ($rs as $record) {
            if (!isset($record->{'id'}) || !array_key_exists($record->{"id"}, $idmatches)) {
                continue;
            }
            foreach ($joindata as $sourcecolumn => $targetcolumn) {
                if (!isset($record->$sourcecolumn)) {
                    continue;
                }
                foreach ($idmatches[$record->{'id'}] as $id) {
                    $this->data[$id][$targetcolumn] = $record->$sourcecolumn;
                }
            }
        }
        $rs->close();
    }


    /**
     * This method was copied as part from the dedication from the Block_Dedication plug-in.
     *
     * Turns an int of seconds into a string: x hours y minutes z seconds.
     * @param int $seconds time in seconds.
     * @return string x hours y minutes z seconds.
     */
    private function format_time(int $seconds) {

        $totalsecs = abs($seconds);

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
     * Get the timecreated for the given id.
     * @param int $id row id.
     * @return int timecreated.
     */
    private function get_time(int $id): int {
        return (int)($this->data[$id]["timecreated"]);
    }
}

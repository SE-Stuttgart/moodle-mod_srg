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
 * This class is used to create a table based on the data of a DB query result and further manipulations.
 */
class table_from_db {
    // This const marks default behaviour, which is accessed if there is no specified behaviour.
    public const DEFAULT = 0;

    // This array holds the internal => public names for the heading of the public table.
    private array $tableheading;
    // This array holds the id => row data of the public table. Values are optional.
    private array $table;

    // This array holds the internal => internal names for the heading of the hidden table.
    private array $hiddentableheadings;
    // This array holds the id => row data of the hidden table. Values are required. Base of many further expansions.
    private array $hiddentable;

    /**
     * Create an Object of table_from_db which makes a call to the DB based on given parameters
     * and holds the acquired data in an array.
     * Class has multiple function working with and altering the array.
     * @param string $from name of the table to be queried.
     * @param array $where Array of where clauses, will be connected via and.
     * @param array $publiccolumns Array of columns we want to have in our return table.
     * Array('internal_column_name' => 'public_column_name')
     * Public columns are always optional.
     * If you want a required public column, have the column in BOTH public and hidden columns.
     * @param array $hiddencolumns of columns we need, but do not want returned.
     * ArrayArray ('internal_column_name' => 'internal_column_name')
     * (i.e. 'id' 'timecreated' should be included). Hidden columns are always required.
     * @param string $sort [default="id ADC"] the sort by clause for the query.
     */
    public function __construct(
        string  $from,
        array   $where,
        array   $publiccolumns,
        array   $hiddencolumns,
        string  $sort = 'id ASC'
    ) {
        if (!isset($hiddencolumns['id'])) {
            $hiddencolumns['id'] = 'id';
        }

        global $DB;
        $this->tableheading = $publiccolumns;
        $this->table = array();
        $this->hiddentableheadings = $hiddencolumns;
        $this->hiddentable = array();

        // Prepare $dbwhere.
        $dbwhere = '';
        if (!empty($where)) {
            $dbwhere .= "(" . implode(' and ', $where) . ")";
        }
        if (!empty($where) && !empty($hiddencolumns)) {
            $dbwhere .= " and ";
        }
        if (!empty($hiddencolumns)) {
            $dbwhere .= "(" . implode(" <> '' and ", array_keys($hiddencolumns)) . " <> '')";
        }

        // Get DB records.
        try {
            $records = $DB->get_records_select(
                $from,
                $dbwhere
            );
        } catch (\Throwable $th) {
            throw $th;
        }

        // Transform DB returned records into desired data structure.
        foreach ($records as $record) {
            $row = array();
            foreach ($this->tableheading as $internalheading => $externalheading) {
                if (isset($record->$internalheading)) {
                    $row[$internalheading] = $record->$internalheading;
                }
            }
            if (isset($record->{'id'})) {
                $this->table[$record->{'id'}] = $row;
            }

            $row = array();
            foreach ($this->hiddentableheadings as $internalheading) {
                if (isset($record->$internalheading)) {
                    $row[$internalheading] = $record->$internalheading;
                }
            }
            if (isset($record->{'id'})) {
                $this->hiddentable[$record->{'id'}] = $row;
            }
        }
    }


    /**
     * Make a query using information from the first query and combine the it into the table.
     * @param bool $isconst True if parameter $tablesource is a constant.
     * False if parameter $tablesource is a column in the table.
     * @param string $tablesource Option 1: Table name of table to query.
     * Option 2: Column name of table where to find the table name (Hidden Table Only).
     * @param string $idsource Column name where to find the IDs corresponding to the new table. (Hidden Table Only).
     * @param array $requirements set requirements that the table row has to have to be able to count for this nest_query.
     * Array(column => value)
     * @param array $where Array of where clauses, will be connected via and.
     * @param array $publiccolumns Array of Array of columns we want to add top the local table.
     * Array ('tablesource' / table_from_db::DEFAULT => Array('sourcecolumn' => 'internaltablecolumn'))
     * Public columns are always optional.
     * If you want a required public column, have the column in BOTH public and hidden columns.
     * @param array $hiddencolumns of columns we need, but do not want returned.
     * Array ('tablesource' / table_from_db::DEFAULT => Array('sourcecolumn' => 'internaltablecolumn'))
     * Hidden columns are always required.
     * @return table_from_db object.
     */
    public function nest_query(
        bool $isconst,
        string $tablesource,
        string $idsource,
        array $requirements,
        array $where,
        array $publiccolumns,
        array $hiddencolumns
    ) {
        global $DB;
        $tabletoquery = true; // Const.
        $querytotable = false; // Const.

        // Create an array of ID matching arrays for every table to be queried.
        $subqueries = array();
        foreach ($this->hiddentable as $id => $row) {
            // Check if row is necessary for this query.
            if (!isset($row[$idsource])) {
                continue;
            }
            foreach ($requirements as $column => $value) {
                if (!isset($row[$column]) || $row[$column] != $value) {
                    continue;
                }
            }

            // Set target table to query.
            if ($isconst) {
                $targettable = $tablesource;
            } else {
                $targettable = $row[$tablesource];
            }

            // Create two arrays, with ids referencing each other.
            $subqueries[$targettable][$tabletoquery][$id] = $row[$idsource];
            $subqueries[$targettable][$querytotable][$row[$idsource]][] = $id;
        }

        // Loop over the tables, that need to be searched.
        foreach ($subqueries as $table => $ids) {
            // Missing information.
            if (
                (!isset($where[$table]) && !isset($where[self::DEFAULT]))
                || (!isset($publiccolumns[$table]) && !isset($publiccolumns[self::DEFAULT]))
                || (!isset($hiddencolumns[$table]) && !isset($hiddencolumns[self::DEFAULT]))
            ) {
                continue;
            }

            // Check which parameters should be used for this query.
            if (!isset($where[$table])) {
                $thiswhere = array('id in (' . implode(",", $ids[$tabletoquery]) . ')') + $where[self::DEFAULT];
            } else {
                $thiswhere = array('id' => $ids[$tabletoquery]) + $where[$table];
            }
            if (!isset($publiccolumns[$table])) {
                $thispubliccolumns = $publiccolumns[self::DEFAULT];
            } else {
                $thispubliccolumns = $publiccolumns[$table];
            }
            if (!isset($hiddencolumns[$table])) {
                $thishiddencolumns = array('id' => $idsource) + $hiddencolumns[self::DEFAULT];
            } else {
                $thishiddencolumns = array('id' => $idsource) + $hiddencolumns[$table];
            }

            // Create table columns (headers).
            foreach ($thispubliccolumns as $internaltableheading) {
                $this->tableheading[$internaltableheading] = $internaltableheading;
            }
            foreach ($thishiddencolumns as $internaltableheading) {
                $this->hiddentableheadings[$internaltableheading] = $internaltableheading;
            }

            // Prepare $dbwhere.
            $dbwhere = '';
            if (!empty($thiswhere)) {
                $dbwhere .= "(" . implode(' and ', $thiswhere) . ")";
            }
            if (!empty($thiswhere) && !empty($thishiddencolumns)) {
                $dbwhere .= " and ";
            }
            if (!empty($thishiddencolumns)) {
                $dbwhere .= "(" . implode(" <> '' and ", array_keys($thishiddencolumns)) . " <> '')";
            }

            // Get DB records.
            try {
                $records = $DB->get_records_select(
                    $table,
                    $dbwhere
                );
            } catch (\Throwable $th) {
                throw $th;
            }

            // Fill table rows with new data.
            foreach ($records as $record) {
                // Get IDs in table which shall be filled with this record.
                $tableids = $ids[$querytotable][$record->{'id'}];
                foreach ($tableids as $tableid) {
                    foreach ($thispubliccolumns as $internalsourceheading => $internaltableheading) {
                        if (isset($record->$internalsourceheading)) {
                            $this->table[$tableid][$internaltableheading] = $record->$internalsourceheading;
                        }
                    }
                    foreach ($thishiddencolumns as $internalsourceheading => $internaltableheading) {
                        if (isset($record->$internalsourceheading)) {
                            $this->hiddentable[$tableid][$internaltableheading] = $record->$internalsourceheading;
                        }
                    }
                }
            }
        }

        return $this;
    }

    /**
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
     * @return table_from_db object.
     */
    public function add_dedication(
        string $name,
        string $dedicationtarget = '',
        int $dedicationmintime = 60,
        int $dedicationmaxtime = 60 * 15
    ) {
        $this->tableheading[$name] = $name;

        $firstsession = true;
        $sessionid = array_key_first($this->table);
        $lastid = array_key_last($this->table);
        $previoustime = $this->get_time($sessionid);

        $idstodelete = array();
        foreach ($this->table as $id => $row) {

            // If new session do update sessionid.
            if (
                $firstsession
                || $this->get_time($id) - $previoustime > $dedicationmaxtime
                || !empty($dedicationtarget) && $this->table[$id][$dedicationtarget] != $this->table[$sessionid][$dedicationtarget]
            ) {
                // Dedication is under our min_time.
                if (!$firstsession && $this->table[$sessionid][$name] < $dedicationmintime) {
                    $idstodelete[] = $sessionid;
                }

                // Next Session.
                $firstsession = false;
                $sessionid = $id;
                $previoustime = $this->get_time($id);
                $this->table[$sessionid][$name] = $previoustime - $this->get_time($sessionid);

                // Session can't start on last id.
                if ($sessionid == $lastid) {
                    $idstodelete[] = $id;
                }
                continue;
            }

            // Else update session edication and delete subsession entry.
            $previoustime = $this->get_time($id);
            $this->table[$sessionid][$name] = $previoustime - $this->get_time($sessionid);
            $idstodelete[] = $id;

            // Last session may be too short.
            if ($sessionid == $lastid && $this->table[$sessionid][$name] < $dedicationmintime) {
                $idstodelete[] = $sessionid;
            }
        }

        // Delete IDs of subsession entries.
        foreach ($idstodelete as $id) {
            unset($this->table[$id]);
            unset($this->hiddentable[$id]);
        }

        foreach ($this->table as &$row) {
            $row[$name] = $this->format_time($row[$name]);
        }

        return $this;
    }

    /**
     * Add a new column to the table with a given constant value for all rows.
     * @param array $namedatapairs Array of column heading => value pairs.
     * @return table_from_db object.
     */
    public function add_constant_columns(array $namedatapairs) {
        foreach ($namedatapairs as $name => $data) {
            $this->tableheading[$name] = $name;
            foreach ($this->table as $id => $row) {
                $this->table[$id][$name] = $data;
            }
        }

        return $this;
    }

    /**
     * Change the first row value representing the column headings.
     * This does not change the keys. If keys are required for other functions, still use the old key.
     * @param array $keyvaluepair Array of column heading key and new column heading value.
     * @return table_from_db object.
     */
    public function rename_columns(array $keyvaluepair) {
        foreach ($keyvaluepair as $key => $value) {
            $this->tableheading[$key] = $value;
        }

        return $this;
    }

    /**
     * Function to add a Human understandable Timestamp based on the timecreated of the log to the Table.
     * @param string $name Name is the column heading for the new column.
     * @return table_from_db object.
     */
    public function add_human_time(string $name) {
        $this->tableheading[$name] = $name;
        foreach ($this->table as $id => $row) {
            $this->table[$id][$name] = date("Y-m-d H:i:s", $this->get_time($id));
        }

        return $this;
    }

    /**
     * Function removes rows in table that do not have a value in at least one of the given columns.
     * @param array $columns Array of table columns to check.
     * @return table_from_db object.
     */
    public function prune_table(array $columns) {
        $deleteids = array();
        foreach ($this->table as $id => $row) {
            foreach ($columns as $column) {
                if (!isset($row[$column]) || empty($row[$column])) {
                    $deleteids[] = $id;
                    break;
                }
            }
        }
        foreach ($deleteids as $id) {
            unset($this->table[$id]);
            unset($this->hiddentable[$id]);
        }

        return $this;
    }

    /**
     * Returns the public table. Array keys of row items are internal header, value of first row is public header.
     * @return array Array of rows representing the table. First row and column keys are the column headings.
     */
    public function get_table() {
        return array(0 => $this->tableheading) + $this->table;
    }

    /**
     * Turns an int of seconds into a string: x hours y minutes z seconds
     * @param int $seconds
     * @return string x hours y minutes z seconds
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
     * Get the timecreated for the given id saved in the hiddentable
     * @param int $id row id
     * @return int timecreated
     */
    private function get_time(int $id) {
        return (int)($this->hiddentable[$id]['timecreated']);
    }
}

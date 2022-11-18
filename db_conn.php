<?php
// This file is part of the scientific work at the University of Stuttgart

/**
 * Version details
 *
 * This class was inspired be the Block_Dedication
 * @package    mod_srg
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../config.php');

class srg_db_conn
{
    private const debug = false;

    public static function build_simple_DB_table(srg_db_query $query): array
    {
        return self::simple_table(self::build_DB_table($query));
    }

    public static function build_DB_table(srg_db_query $main_query): array
    {
        global $DB;

        try {
            // Get DB records
            $records = $DB->get_records_select(
                $main_query->table,
                $main_query->select,
                $main_query->params,
                $main_query->sort,
                self::get_fields($main_query)
            );
        } catch (\Throwable $th) {
            if (self::debug) throw $th;
        }

        $table = array();

        foreach ($records as $record) {
            $row = array();
            $row = self::handle_columns($row, $record, $main_query);

            unset($record);

            if (is_null($row)) continue;

            // Get Data from nested queries
            foreach ($main_query->nested_queries as $nq) {
                $row = self::update_row_using_nested_query($row, $nq);
                if (is_null($row)) continue;
            }

            foreach ($main_query->nested_queries_columns as $requirement => $column) {
                switch ($requirement) {
                    case SRG_REQUIRED:
                        if (!isset($row[$column]) || $row[$column] == '') {
                            unset($row);
                            continue 3;
                        }
                        break;
                    case SRG_TEMP:
                        unset($row[$column]);
                        break;
                    case SRG_OPTIONAL:
                        if (!isset($row[$column])) {
                            $row[$column] = '';
                        }
                        break;
                    default:
                        if (!isset($row[$column])) {
                            $row[$column] = '';
                        }
                }
            }

            // Add row to result
            if (!is_null($row))
                $table[] = $row;
            unset($row);
        }

        if ($table && $main_query->options['dedication']) {
            $table = self::get_dedication(
                $table,
                $main_query->options['dedication'],
                $main_query->options['dedication_min_time'],
                $main_query->options['dedication_max_time'],
                $main_query->options['dedication_target']
            );
        }

        // Handle empty Query/Result
        if (!$table) {
            $row = array();
            // Get Static and variable Columns
            foreach ($main_query->columns as $column)
                $row[$column->target_name] = '';
            // Get Optional Columns
            if ($main_query->options['human_time'])
                $row[$main_query->options['human_time']] = '';
            if ($main_query->options['dedication'])
                $row[$main_query->options['dedication']] = '';
            // Get Nested Query Columns
            foreach ($main_query->nested_queries_columns as $requirement => $column)
                if ($requirement != SRG_TEMP) $row[$column] = '';
            // Add Empty row to result
            $table[] = $row;
            unset($row);
        }

        // Clear memory
        unset($records);

        return $table;
    }

    private static function update_row_using_nested_query(array $row, srg_db_query $nq)
    {
        global $DB;

        if (is_null($row)) return $row;

        try {
            // Check of necessary data for nested query search is present
            if (!$row[$nq->options['id_source']]) return $row;
            switch ($nq->table_type) {
                case SRG_TABLE_STATIC:
                    $table = $nq->table;
                    break;
                case SRG_TABLE_VARIABLE:
                    if ($nq->table == SRG_TABLE_DEFAULT) {
                        $table = $row[$nq->options['table_source']];
                        break;
                    } else if ($nq->table != $row[$nq->options['table_source']]) {
                        return $row;
                    } else {
                        $table = $nq->table;
                    }
                    break;
                default:
                    $table = $row[$nq->options['table_source']];
                    break;
            }
        } catch (\Throwable $th) {
            if (self::debug) throw $th;
            return $row;
        }

        // Add the id param to select where
        $select = $nq->select ? $nq->select . ' and id = ?' : 'id = ?';

        // Add the id param to $params
        $params = $nq->params;
        $params['id'] = $row[$nq->options['id_source']];

        try {
            $record = $DB->get_record_select($table, $select, $params, self::get_fields($nq));
        } catch (\Throwable $th) {
            if (self::debug) throw $th;
            return $row;
        }

        // Get Desired Columns
        $row = self::handle_columns($row, $record, $nq);

        // Clear memory
        unset($record);

        if (is_null($row)) return $row;

        foreach ($nq->nested_queries as $_nq) {
            $row = self::update_row_using_nested_query($row, $_nq);
            if (is_null($row)) return $row;
        }

        return $row;
    }

    private static function get_fields(srg_db_query $query): string
    {
        // We need the primary key as field
        $fields = array('id' => 'id');

        // We need all our Source Columns as fields
        foreach ($query->columns as $column) {
            if ($column->source_type == SRG_COLUMN_SOURCE_DB_QUERY)
                $fields[$column->source_name] = $column->source_name;
        }

        // For our options we need the timecreated column
        if ($query->options['human_time'] || $query->options['dedication']) {
            $fields['timecreated'] = 'timecreated';
        }
        $fields = implode(',', $fields);

        return $fields;
    }

    private static function handle_columns(array $row, $record, srg_db_query $query): array
    {
        foreach ($query->columns as $column) {

            // Get static Columns
            if ($column->source_type == SRG_COLUMN_SOURCE_STATIC) {
                $row[$column->target_name] = $column->source_name;
            }

            // Get Variable Columns
            if ($column->source_type == SRG_COLUMN_SOURCE_DB_QUERY) {
                try {
                    if ($record && property_exists($record, $column->source_name))
                        $row[$column->target_name] = $record->{$column->source_name};
                    else
                        $row[$column->target_name] = '';
                } catch (\Throwable $th) {
                    if (self::debug) throw $th;
                    $row[$column->target_name] = '';
                } finally {
                    if ($column->requirement == SRG_REQUIRED && $row[$column->target_name] == '') {
                        return null;
                    }
                }
            }

            // Get Options Columns
            try {
                if ($query->options['human_time'] && $record && property_exists($record, 'timecreated'))
                    $row[$query->options['human_time']] = date("Y-m-d H:i:s", $record->timecreated);
                if ($query->options['dedication'] && $record && property_exists($record, 'timecreated'))
                    $row[$query->options['dedication']] = $record->timecreated;
            } catch (\Throwable $th) {
                if (self::debug) throw $th;
            }
        }
        return $row;
    }

    private static function get_dedication(array $logs, string $dedication_header, int $dedication_min_time, int $dedication_max_time, string $dedication_target)
    {
        $new_logs = array();
        if (!$logs) return $new_logs;

        // First Session
        $sessionlog = $previouslog = array_shift($logs);

        foreach ($logs as $log) {

            if (
                $log[$dedication_header] - $previouslog[$dedication_header] > $dedication_max_time
                || ($dedication_target && $sessionlog[$dedication_target] != $log[$dedication_target])
            ) {
                $dedication = $previouslog[$dedication_header] - $sessionlog[$dedication_header];

                // Ignore sessions with really short duration
                if ($dedication > $dedication_min_time) {
                    // Return Session as Log with dedication
                    $sessionlog[$dedication_header] = self::format_dedication($dedication);
                    $new_logs[] = $sessionlog;
                }

                // New Session
                $sessionlog = $log;
            }
            // Next Log
            $previouslog = $log;
        }

        $dedication = $previouslog[$dedication_header] - $sessionlog[$dedication_header];

        // Ignore sessions with a really short duration.
        if ($dedication > $dedication_min_time) {
            // Return Last Session as Log with dedication
            $sessionlog[$dedication_header] = self::format_dedication($dedication);
            $new_logs[] = $sessionlog;
        }

        return $new_logs;
    }

    private static function simple_table(array $table): array
    {
        $s_table = array();

        if ($table) {
            $s_row0 = array();
            $s_row1 = array();

            $row1 = array_shift($table);
            foreach ($row1 as $header => $value) {
                $s_row0[] = $header;
                $s_row1[] = $value;
            }
            $s_table[] = $s_row0;
            $s_table[] = $s_row1;

            foreach ($table as $row) {
                $s_row = array();
                foreach ($row as $header => $value) {
                    $s_row[] = $value;
                }
                $s_table[] = $s_row;
            }
        }

        return $s_table;
    }


    /**
     * Formats time based in Moodle function format_time($totalsecs).
     * @param int $totalsecs
     * @return string
     */
    private static function format_dedication($totalsecs)
    {
        $totalsecs = abs($totalsecs);

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
}

define('SRG_TEMP', -1);
define('SRG_OPTIONAL', -5);
define('SRG_REQUIRED', -10);

define('SRG_COLUMN_SOURCE_STATIC', -1);
define('SRG_COLUMN_SOURCE_DB_QUERY', -10);


class srg_db_column
{
    public string   $target_name;
    public string   $source_name;
    public int      $source_type;
    public int      $requirement;

    public function __construct(
        string $target_name,
        string $source_name,
        int $source_type = SRG_COLUMN_SOURCE_DB_QUERY,
        int $requirement = SRG_OPTIONAL
    ) {
        $this->target_name = $target_name;
        $this->source_name = $source_name;
        $this->source_type = $source_type;
        $this->requirement = $requirement;
    }
}

define('SRG_TABLE_STATIC', -1);
define('SRG_TABLE_VARIABLE', -10);

define('SRG_TABLE_DEFAULT', -1);

class srg_db_query
{
    public string   $table;
    public int      $table_type;
    public string   $select;
    public array    $params;
    public array    $columns;
    public string   $sort;

    public array    $options;
    public array    $nested_queries_columns;
    public array    $nested_queries;

    public function __construct(
        string  $table,
        int     $table_type,
        string  $select,
        array   $params,
        array   $columns,
        string  $sort,
        array   $options,
        array   $nested_queries_columns,
        array   $nested_queries
    ) {
        $this->table                            = $table;
        $this->table_type                       = $table_type;
        $this->select                           = $select;
        $this->params                           = $params;
        $this->columns                          = $columns;
        $this->sort                             = $sort;

        $this->options                          = $options;
        $this->nested_queries_columns           = $nested_queries_columns;
        $this->nested_queries                   = $nested_queries;

        // Main Query options
        $this->options['human_time']            = isset($this->options['human_time'])           ? $this->options['human_time']              : '';
        $this->options['dedication']            = isset($this->options['dedication'])           ? $this->options['dedication']              : '';
        $this->options['dedication_min_time']   = isset($this->options['dedication_min_time'])  ? $this->options['dedication_min_time']     : 59;
        $this->options['dedication_max_time']   = isset($this->options['dedication_max_time'])  ? $this->options['dedication_max_time']     : 60 * 15;
        $this->options['dedication_target']     = isset($this->options['dedication_target'])    ? $this->options['dedication_target']       : '';

        // Nested Query Options
        $this->options['table_source']          = isset($this->options['table_source'])         ? $this->options['table_source']            : '';
        $this->options['id_source']             = isset($this->options['id_source'])            ? $this->options['id_source']               : '';
    }
}

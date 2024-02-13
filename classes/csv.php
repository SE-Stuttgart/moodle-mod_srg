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
 * @copyright  2022 Universtity of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg;

/**
 * Class holding helper methods corresponding to .csv
 */
class srg_CSV {
    /**
     * Transforms simple table (from db_conn) into .csv.
     * @param array $table Array of rows (first is header) to be turned into an csv file.
     * @return string CSV type string.
     */
    public static function simple_table_to_csv(array $table) {
        $csv = '';

        if (!$table) {
            return $csv;
        }
        $firstrow = array_shift($table);
        if (!$firstrow) {
            return $csv;
        }
        $firstcell = array_shift($firstrow);

        $csv .= '"' . preg_replace(['/\n/', '/"/'], ['', '""'], $firstcell) . '"';

        foreach ($firstrow as $cell) {
            $csv .= ",";
            $csv .= '"' . preg_replace(['/\n/', '/"/'], ['', '""'], $cell) . '"';
        }

        foreach ($table as $row) {
            $csv .= "\n";

            if (!$row) {
                return $csv;
            }
            $firstcell = array_shift($row);

            $csv .= '"' . preg_replace(['/\n/', '/"/'], ['', '""'], $firstcell) . '"';

            foreach ($row as $cell) {
                $csv .= ",";
                $csv .= '"' . preg_replace(['/\n/', '/"/'], ['', '""'], $cell) . '"';
            }
        }

        return $csv;
    }
}

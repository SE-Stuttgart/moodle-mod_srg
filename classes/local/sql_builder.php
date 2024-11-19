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
 * TODO DESCRIPTION
 *
 * @package     mod_srg
 * @copyright   2024 University of Stuttgart <kasra.habib@iste.uni-stuttgart.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_srg\local;

use moodle_exception;

class sql_builder {
    /**
     * @var callable|null Function that generates SQL components dynamically.
     * We use a callable to only load the sql at need.
     * Because some sql generation requires one or multiple DataBase calls-
     */
    private $sqlgenerationcallable;

    /**
     * @var array Argument (the current users id) passed to the callable.
     */
    private int $userid;

    /**
     * @var array Argument (the course this activity is in) passed to the callable.
     */
    private int $courseid;

    /**
     * @var string The SELECT part of the SQL query.
     */
    private string $select;

    /**
     * @var string The FROM part of the SQL query.
     */
    private string $from;

    /**
     * @var string The WHERE part of the SQL query.
     */
    private string $where;

    /**
     * @var array Parameters for the SQL query.
     */
    private array $params;

    /**
     * @var bool Whether the SQL query components have been initialized.
     */
    private bool $initialized;

    /**
     * Constructor.
     *
     * @param callable $sqlgenerationcallable Function that generates SQL components.
     * @param array $callableargs Arguments to pass to the callable.
     */
    public function __construct(callable $sqlgenerationcallable, int $userid, int $courseid) {
        $this->sqlgenerationcallable = $sqlgenerationcallable;
        $this->userid = $userid;
        $this->courseid = $courseid;

        $this->select = '';
        $this->from = '';
        $this->where = '';
        $this->params = [];

        $this->initialized = false;
    }

    /**
     * Initializes the SQL query components by calling the provided callable.
     *
     * @throws moodle_exception If the callable is invalid or not set.
     */
    private function init(): void {
        if ($this->initialized) {
            return;
        }

        if (!is_callable($this->sqlgenerationcallable)) {
            throw new moodle_exception('SQL generation callable is not set or invalid.');
        }

        // Call the callable and expect it to return [select, from, where, params].
        list($select, $from, $where, $params) = call_user_func(
            $this->sqlgenerationcallable,
            $this->userid,
            $this->courseid
        );

        // Validate returned data.
        if (!is_string($select) || !is_string($from) || !is_string($where) || !is_array($params)) {
            throw new moodle_exception('SQL generation callable must return [string, string, string, array].');
        }

        $this->select = $select;
        $this->from = $from;
        $this->where = $where;
        $this->params = $params;

        $this->initialized = true;
    }

    /**
     * Returns the SQL query to count rows.
     *
     * @return string The count SQL query.
     */
    public function get_count_sql(): string {
        $this->init();
        return "SELECT COUNT(*) {$this->from} {$this->where}";
    }

    /**
     * Returns the SQL query for selecting rows.
     *
     * @return string The select SQL query.
     */
    public function get_select_sql(): string {
        $this->init();
        return "{$this->select} {$this->from} {$this->where}";
    }

    /**
     * Returns the parameters for the SQL query.
     *
     * @return array The SQL query parameters.
     */
    public function get_params(): array {
        $this->init();
        return $this->params;
    }
}

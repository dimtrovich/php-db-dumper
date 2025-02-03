<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\DbDumper\Adapters;

use Dimtrovich\DbDumper\Exceptions\Exception;

class SqliteAdapter extends Factory
{
	// Numerical SQLITE types
    public $sqliteTypes = [
        'numerical' => [
			'INT',
			'INTEGER',
			'TINYINT',
			'SMALLINT',
			'MEDIUMINT',
			'BIGINT',
			'UNSIGNED BIG INT',
			'INT2',
			'INT8',
			'REAL',
			'DOUBLE',
			'DOUBLE PRECISION',
			'FLOAT',
            'NUMERIC',
        ],
        'blob' => [
            'BLOB',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function showCreateTable(string $tableName): string
    {
        return "SELECT tbl_name as 'Table', sql as 'Create Table' " .
            'FROM sqlite_master ' .
            "WHERE type='table' AND tbl_name='{$tableName}'";
    }

	/**
     * {@inheritDoc}
     */
    public function createTable(array $row): string
    {
        if (! isset($row['Create Table'])) {
            throw new Exception('Error getting table code, unknown output');
        }

        $createTable = $row['Create Table'];

        if ($this->option->reset_auto_increment) {
            $createTable = 'DELETE FROM sqlite_sequence WHERE name=\'' . $row['Table'] .'\'' . PHP_EOL . $createTable;
        }

        if ($this->option->if_not_exists) {
            $createTable = preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $createTable);
        }

        return $createTable . ';' . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function showCreateView(string $viewName): string
    {
        return "SELECT tbl_name as 'View', sql as 'Create View' " .
            'FROM sqlite_master ' .
            "WHERE type='view' AND tbl_name='{$viewName}'";
    }

    /**
     * {@inheritDoc}
     */
    public function showTables(): string
    {
        return "SELECT tbl_name FROM sqlite_master WHERE type='table' AND tbl_name NOT LIKE 'sqlite_%'";
    }

    /**
     * {@inheritDoc}
     */
    public function showViews(): string
    {
        return "SELECT tbl_name FROM sqlite_master WHERE type='view' AND tbl_name NOT LIKE 'sqlite_%'";
    }

    /**
     * {@inheritDoc}
     */
    public function showTriggers(): string
    {
        return "SELECT name FROM sqlite_master WHERE type='trigger' AND tbl_name NOT LIKE 'sqlite_%'";
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     */
    public function showColumns(): string
    {
        if (func_num_args() !== 1) {
            return '';
        }

        $table = func_get_arg(0);

        return "pragma table_info({$table})";
    }

    /**
     * {@inheritDoc}
     */
    public function startTransaction(): string
    {
        return 'BEGIN EXCLUSIVE';
    }

    /**
     * {@inheritDoc}
     */
    public function commitTransaction(): string
    {
        return 'COMMIT';
    }

    /**
     * {@inheritDoc}
     */
    public function parseColumnType(array $colType): array
    {
        $colInfo = parent::_parseColumnType($colType, $this->sqliteTypes);
        // for virtual columns that are of type 'Extra', column type
        // could by "STORED GENERATED" or "VIRTUAL GENERATED"
        // MySQL reference: https://dev.mysql.com/doc/refman/5.7/en/create-table-generated-columns.html
        // $colInfo['is_virtual'] = str_contains($colType['Extra'], 'VIRTUAL GENERATED') || str_contains($colType['Extra'], 'STORED GENERATED');

        return $colInfo;
    }
}

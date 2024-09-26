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

class SqliteAdapter extends Factory
{
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
        return "SELECT tbl_name FROM sqlite_master WHERE type='table'";
    }

    /**
     * {@inheritDoc}
     */
    public function showViews(): string
    {
        return "SELECT tbl_name FROM sqlite_master WHERE type='view'";
    }

    /**
     * {@inheritDoc}
     */
    public function showTriggers(): string
    {
        return "SELECT name FROM sqlite_master WHERE type='trigger'";
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
}

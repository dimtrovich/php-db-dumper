<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\DbDumper;

use Dimtrovich\DbDumper\Exceptions\Exception;
use PDO;

/**
 * @method void onTableExport(callable(string $tableName, int $rowCount) $callback)
 */
class Exporter
{
    use Dumper;

    /**
     * List of registered tables
     */
    private array $tables = [];

    /**
     * List of columns types for tables [$tableName => [$column => $type]]
     */
    private array $tableColumnTypes = [];

    /**
     * List of registered views
     */
    private array $views = [];

    /**
     * List of registered triggers
     */
    private array $triggers = [];

    /**
     * List of registered procedures
     */
    private array $procedures = [];

    /**
     * List of registered functions
     */
    private array $functions = [];

    /**
     * List of registered events
     */
    private array $events = [];

    /**
     * @var callable
     */
    private $transformTableRowCallable;

    /**
     * Keyed on table name, with the value as the conditions.
     * e.g. - 'users' => 'date_registered > NOW() - INTERVAL 6 MONTH'
     */
    private array $tableWheres = [];

    private array $tableLimits = [];

    /**
     * Primary function, triggers dumping.
     *
     * @param string $filename Name of file to write sql dump to
     */
    public function process(string $filename = 'php://stdout')
    {
        // Create output file
        $this->compressor->open($filename);

        // Write some basic info to output file
        $this->compressor->write($this->getDumpFileHeader());

        // initiate a transaction at global level to create a consistent snapshot
        if ($this->option->single_transaction) {
            $this->pdo->exec($this->adapter->setupTransaction());
            $this->pdo->exec($this->adapter->startTransaction());
        }

        // Store server settings and use sanner defaults to dump
        $this->compressor->write($this->adapter->backupParameters());

        if ($this->option->databases) {
            $this->compressor->write($this->adapter->getDatabaseHeader($this->database));

            if ($this->option->add_drop_database) {
                $this->compressor->write($this->adapter->addDropDatabase($this->database));
            }
        }

        // Get table, view, trigger, procedures, functions and events structures from database.
        $this->getDatabaseStructureTables();
        $this->getDatabaseStructureViews();
        $this->getDatabaseStructureTriggers();
        $this->getDatabaseStructureProcedures();
        $this->getDatabaseStructureFunctions();
        $this->getDatabaseStructureEvents();

        if ($this->option->databases) {
            $this->compressor->write($this->adapter->databases($this->database));
        }

        // If there still are some tables/views in include-tables array,
        // that means that some tables or views weren't found.
        // Give proper error and exit.
        // This check will be removed once include-tables supports regexps.
        if ($this->option->include_tables !== []) {
            $name = implode(',', $this->option->include_tables);

            throw Exception::tableNotFound($name);
        }

        $this->exportTables();
        $this->exportTriggers();
        $this->exportFunctions();
        $this->exportProcedures();
        $this->exportViews();
        $this->exportEvents();

        // Restore saved parameters.
        $this->compressor->write($this->adapter->restoreParameters());

        // end transaction
        if ($this->option->single_transaction) {
            $this->pdo->exec($this->adapter->commitTransaction());
        }

        // Write some stats to output file.
        $this->compressor->write($this->getDumpFileFooter());

        // Close output file.
        $this->compressor->close();
    }

    /**
     * Keyed by table name, with the value as the conditions:
     * e.g. 'users' => 'date_registered > NOW() - INTERVAL 6 MONTH AND deleted=0'
     */
    public function setTableWheres(array $tableWheres)
    {
        $this->tableWheres = $tableWheres;
    }

    /**
     * @return bool|mixed
     */
    public function getTableWhere(string $tableName)
    {
        if (! empty($this->tableWheres[$tableName])) {
            return $this->tableWheres[$tableName];
        }
        if ($this->option->where !== '') {
            return $this->option->where;
        }

        return false;
    }

    /**
     * Keyed by table name, with the value as the numeric limit:
     * e.g. 'users' => 3000
     */
    public function setTableLimits(array $tableLimits)
    {
        $this->tableLimits = $tableLimits;
    }

    /**
     * Returns the LIMIT for the table.
     * Must be numeric to be returned.
     *
     * @param mixed $tableName
     *
     * @return false|int
     */
    public function getTableLimit($tableName)
    {
        if (! isset($this->tableLimits[$tableName])) {
            return false;
        }

        $limit = $this->tableLimits[$tableName];

        if (! is_numeric($limit)) {
            return false;
        }

        return $limit;
    }

    /**
     * Returns header for dump file.
     */
    private function getDumpFileHeader(): string
    {
        $header = '';

        if (! $this->option->skip_comments) {
            // Some info about software, source and time
            $header = '-- Database Backup Manager' . PHP_EOL .
                    '-- This backup was created automatically by the Dimtrovich Db-Dumper. A simplest PHP Database Backup Manager' . PHP_EOL .
                    '-- © ' . date('Y') . ' Dimitri Sitchet Tomkeu' . PHP_EOL .
                    '-- https://github.com/dimtrovich/php-db-dumper' . PHP_EOL .
                    '-- ' . PHP_EOL .
                    '-- Host: ' . $this->pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS) . PHP_EOL .
                    "-- Database: {$this->database}" . PHP_EOL .
                    '-- Server version: ' . $this->pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . ' Driver: ' . $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . PHP_EOL .
                    '-- ' . PHP_EOL .
                    '-- Generated on: ' . date('r') . PHP_EOL .
            		'-- ' . str_repeat('----------------------------------------------------', 2) . PHP_EOL . PHP_EOL;
        }

        return $header;
    }

    /**
     * Returns footer for dump file.
     */
    private function getDumpFileFooter()
    {
        $footer = '';

        if (! $this->option->skip_comments) {
            $footer .= '-- Dump completed';

            if (! $this->option->skip_dump_date) {
                $footer .= ' on: ' . date('r');
            }

            $footer .= PHP_EOL;
        }

        return $footer;
    }

    /**
     * Reads table names from database.
     * Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureTables()
    {
        $tables = $this->pdo->query($this->adapter->showTables($this->database));

        // Listing all tables from database
        if ($this->option->include_tables === []) {
            // include all tables for now, blacklisting happens later
            foreach ($tables as $row) {
                $this->tables[] = current($row);
            }
        } else {
            // include only the tables mentioned in include-tables
            foreach ($tables as $row) {
                if (in_array(current($row), $this->option->include_tables, true)) {
                    $this->tables[] = current($row);
                    $elem           = array_search(current($row), $this->option->include_tables, true);
                    unset($this->option->include_tables[$elem]);
                }
            }
        }
    }

    /**
     * Reads view names from database.
     * Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureViews()
    {
        $views = $this->pdo->query($this->adapter->showViews($this->database));

        // Listing all views from database
        if ($this->option->include_views === []) {
            // include all views for now, blacklisting happens later
            foreach ($views as $row) {
                $this->views[] = current($row);
            }
        } else {
            // include only the tables mentioned in include-tables
            foreach ($views as $row) {
                if (in_array(current($row), $this->option->include_views, true)) {
                    $this->views[] = current($row);
                    $elem          = array_search(current($row), $this->option->include_views, true);
                    unset($this->option->include_views[$elem]);
                }
            }
        }
    }

    /**
     * Reads trigger names from database.
     * Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureTriggers()
    {
        // Listing all triggers from database
        if ($this->option->skip_triggers) {
            foreach ($this->pdo->query($this->adapter->showTriggers($this->database)) as $row) {
                $this->triggers[] = $row['Trigger'];
            }
        }
    }

    /**
     * Reads procedure names from database.
     * Fills $this->tables array so they will be dumped later.
     *
     * @return null
     */
    private function getDatabaseStructureProcedures()
    {
        // Listing all procedures from database
        if ($this->option->routines) {
            foreach ($this->pdo->query($this->adapter->showProcedures($this->database)) as $row) {
                $this->procedures[] = $row['procedure_name'];
            }
        }
    }

    /**
     * Reads functions names from database.
     * Fills $this->tables array so they will be dumped later.
     *
     * @return null
     */
    private function getDatabaseStructureFunctions()
    {
        // Listing all functions from database
        if ($this->option->routines) {
            foreach ($this->pdo->query($this->adapter->showFunctions($this->database)) as $row) {
                $this->functions[] = $row['function_name'];
            }
        }
    }

    /**
     * Reads event names from database.
     * Fills $this->tables array so they will be dumped later.
     */
    private function getDatabaseStructureEvents()
    {
        // Listing all events from database
        if ($this->option->events) {
            foreach ($this->pdo->query($this->adapter->showEvents($this->database)) as $row) {
                $this->events[] = $row['event_name'];
            }
        }
    }

    /**
     * Compare if $table name matches with a definition inside $arr
     *
     * @param $arr array with strings or patterns
     */
    private function matches(string $table, array $arr): bool
    {
        $match = false;

        foreach ($arr as $pattern) {
            if ('/' !== $pattern[0]) {
                continue;
            }
            if (1 === preg_match($pattern, $table)) {
                $match = true;
            }
        }

        return in_array($table, $arr, true) || $match;
    }

    /**
     * Exports all the tables selected from database
     */
    private function exportTables()
    {
        // Exporting tables one by one
        foreach ($this->tables as $table) {
            if ($this->matches($table, $this->option->exclude_tables)) {
                continue;
            }

            $this->getTableStructure($table);

            if (false === $this->option->no_data) { // don't break compatibility with old trigger
                $this->listValues($table);
            } elseif (true === $this->option->no_data || $this->matches($table, $this->option->no_data)) {
                continue;
            } else {
                $this->listValues($table);
            }
        }
    }

    /**
     * Exports all the views found in database
     */
    private function exportViews()
    {
        if (false === $this->option->no_create_info) {
            // Exporting views one by one
            foreach ($this->views as $view) {
                if ($this->matches($view, $this->option->exclude_tables)) {
                    continue;
                }

                $this->tableColumnTypes[$view] = $this->getTableColumnTypes($view);
                $this->getViewStructureTable($view);
            }

            foreach ($this->views as $view) {
                if ($this->matches($view, $this->option->exclude_tables)) {
                    continue;
                }

                $this->getViewStructureView($view);
            }
        }
    }

    /**
     * Exports all the triggers found in database
     */
    private function exportTriggers()
    {
        // Exporting triggers one by one
        foreach ($this->triggers as $trigger) {
            $this->getTriggerStructure($trigger);
        }
    }

    /**
     * Exports all the procedures found in database
     */
    private function exportProcedures()
    {
        // Exporting triggers one by one
        foreach ($this->procedures as $procedure) {
            $this->getProcedureStructure($procedure);
        }
    }

    /**
     * Exports all the functions found in database
     */
    private function exportFunctions()
    {
        // Exporting triggers one by one
        foreach ($this->functions as $function) {
            $this->getFunctionStructure($function);
        }
    }

    /**
     * Exports all the events found in database
     */
    private function exportEvents()
    {
        // Exporting triggers one by one
        foreach ($this->events as $event) {
            $this->getEventStructure($event);
        }
    }

    /**
     * Table structure extractor
     */
    private function getTableStructure(string $tableName)
    {
        if (! $this->option->no_create_info) {
            $ret = '';

            if (! $this->option->skip_comments) {
                $ret = '--' . PHP_EOL .
                    "-- Table structure for table `{$tableName}`" . PHP_EOL .
                    '--' . PHP_EOL . PHP_EOL;
            }

            $stmt = $this->adapter->showCreateTable($tableName);

            foreach ($this->pdo->query($stmt) as $r) {
                $this->compressor->write($ret);

                if ($this->option->add_drop_table) {
                    $this->compressor->write($this->adapter->dropTable($tableName));
                }

                $this->compressor->write($this->adapter->createTable($r));

                break;
            }
        }

        $this->tableColumnTypes[$tableName] = $this->getTableColumnTypes($tableName);
    }

    /**
     * Store column types to create data dumps and for Stand-In tables
     *
     * @return array type column types detailed
     */
    private function getTableColumnTypes(string $tableName): array
    {
        $columnTypes = [];

        $columns = $this->pdo->query(
            $this->adapter->showColumns($tableName)
        );
        $columns->setFetchMode(PDO::FETCH_ASSOC);

        foreach ($columns as $key => $col) {
            $types                      = $this->adapter->parseColumnType($col);
            $columnTypes[$col['Field']] = [
                'is_numeric' => $types['is_numeric'],
                'is_blob'    => $types['is_blob'],
                'type'       => $types['type'],
                'type_sql'   => $col['Type'],
                'is_virtual' => $types['is_virtual'],
            ];
        }

        return $columnTypes;
    }

    /**
     * View structure extractor, create table (avoids cyclic references)
     */
    private function getViewStructureTable(string $viewName)
    {
        if (! $this->option->skip_comments) {
            $ret = '--' . PHP_EOL .
                "-- Stand-In structure for view `{$viewName}`" . PHP_EOL .
                '--' . PHP_EOL . PHP_EOL;

            $this->compressor->write($ret);
        }

        $stmt = $this->adapter->showCreateView($viewName);

        // create views as tables, to resolve dependencies
        foreach ($this->pdo->query($stmt) as $r) {
            if ($this->option->add_drop_table) {
                $this->compressor->write($this->adapter->dropView($viewName));
            }

            $this->compressor->write($this->createStandInTable($viewName));
            break;
        }
    }

    /**
     * Write a create table statement for the table Stand-In, show create
     * table would return a create algorithm when used on a view
     */
    public function createStandInTable(string $viewName): string
    {
        $ret = [];

        foreach ($this->tableColumnTypes[$viewName] as $k => $v) {
            $ret[] = "`{$k}` {$v['type_sql']}";
        }

        $ret = implode(PHP_EOL . ',', $ret);

        return "CREATE TABLE IF NOT EXISTS `{$viewName}` (" .
            PHP_EOL . $ret . PHP_EOL . ');' . PHP_EOL;
    }

    /**
     * View structure extractor, create view
     */
    private function getViewStructureView(string $viewName)
    {
        if (! $this->option->skip_comments) {
            $ret = '--' . PHP_EOL .
                "-- View structure for view `{$viewName}`" . PHP_EOL .
                '--' . PHP_EOL . PHP_EOL;
            $this->compressor->write($ret);
        }

        $stmt = $this->adapter->showCreateView($viewName);

        // create views, to resolve dependencies
        // replacing tables with views
        foreach ($this->pdo->query($stmt) as $r) {
            // because we must replace table with view, we should delete it
            $this->compressor->write($this->adapter->dropView($viewName));
            $this->compressor->write($this->adapter->createView($r));

            break;
        }
    }

    /**
     * Trigger structure extractor
     */
    private function getTriggerStructure(string $triggerName)
    {
        $stmt = $this->adapter->showCreateTrigger($triggerName);

        foreach ($this->pdo->query($stmt) as $r) {
            if ($this->option->add_drop_trigger) {
                $this->compressor->write($this->adapter->addDropTrigger($triggerName));
            }

            $this->compressor->write($this->adapter->createTrigger($r));

            return;
        }
    }

    /**
     * Procedure structure extractor
     */
    private function getProcedureStructure(string $procedureName)
    {
        if (! $this->option->skip_comments) {
            $ret = '--' . PHP_EOL .
                "-- Dumping routines for database '" . $this->database . "'" . PHP_EOL .
                '--' . PHP_EOL . PHP_EOL;
            $this->compressor->write($ret);
        }

        $stmt = $this->adapter->showCreateProcedure($procedureName);

        foreach ($this->pdo->query($stmt) as $r) {
            $this->compressor->write($this->adapter->createProcedure($r));

            return;
        }
    }

    /**
     * Function structure extractor
     */
    private function getFunctionStructure(string $functionName)
    {
        if (! $this->option->skip_comments) {
            $ret = '--' . PHP_EOL .
                "-- Dumping routines for database '" . $this->database . "'" . PHP_EOL .
                '--' . PHP_EOL . PHP_EOL;

            $this->compressor->write($ret);
        }

        $stmt = $this->adapter->showCreateFunction($functionName);

        foreach ($this->pdo->query($stmt) as $r) {
            $this->compressor->write($this->adapter->createFunction($r));

            return;
        }
    }

    /**
     * Event structure extractor
     */
    private function getEventStructure(string $eventName)
    {
        if (! $this->option->skip_comments) {
            $ret = '--' . PHP_EOL .
                "-- Dumping events for database '" . $this->database . "'" . PHP_EOL .
                '--' . PHP_EOL . PHP_EOL;

            $this->compressor->write($ret);
        }

        $stmt = $this->adapter->showCreateEvent($eventName);

        foreach ($this->pdo->query($stmt) as $r) {
            $this->compressor->write($this->adapter->createEvent($r));

            return;
        }
    }

    /**
     * Prepare values for output
     *
     * @param array $row Associative array of column names and values to be quoted
     */
    private function prepareColumnValues(string $tableName, array $row): array
    {
        $ret         = [];
        $columnTypes = $this->tableColumnTypes[$tableName];

        if ($this->transformTableRowCallable) {
            $row = ($this->transformTableRowCallable)($tableName, $row);
        }

        foreach ($row as $colName => $colValue) {
            $ret[] = $this->escape($colValue, $columnTypes[$colName]);
        }

        return $ret;
    }

    /**
     * Escape values with quotes when needed
     *
     * @param mixed $colValue
     * @param mixed $colType
     */
    private function escape($colValue, $colType)
    {
        if (null === $colValue) {
            return 'NULL';
        }
        if ($this->option->hex_blob && $colType['is_blob']) {
            if ($colType['type'] === 'bit' || ! empty($colValue)) {
                return "0x{$colValue}";
            }

            return "''";
        }
        if ($colType['is_numeric']) {
            return $colValue;
        }

        return $this->pdo->quote($colValue);
    }

    /**
     * Set a callable that will be used to transform table rows
     */
    public function transformTableRow(callable $callable)
    {
        $this->transformTableRowCallable = $callable;
    }

    /**
     * Table rows extractor
     */
    private function listValues(string $tableName)
    {
        $this->prepareListValues($tableName);

        $onlyOnce = true;

        // colStmt is used to form a query to obtain row values
        $colStmt = $this->getColumnStmt($tableName);

        // colNames is used to get the name of the columns when using complete-insert
        if ($this->option->complete_insert) {
            $colNames = $this->getColumnNames($tableName);
        }

        $stmt = 'SELECT ' . implode(',', $colStmt) . " FROM `{$tableName}`";

        // Table specific conditions override the default 'where'
        $condition = $this->getTableWhere($tableName);

        if ($condition) {
            $stmt .= " WHERE {$condition}";
        }

        $limit = $this->getTableLimit($tableName);

        if ($limit !== false) {
            $stmt .= " LIMIT {$limit}";
        }

        $resultSet = $this->pdo->query($stmt);
        $resultSet->setFetchMode(PDO::FETCH_ASSOC);

        $ignore = $this->option->insert_ignore ? '  IGNORE' : '';

        $count = 0;
        $line  = '';

        foreach ($resultSet as $row) {
            $count++;
            $vals = $this->prepareColumnValues($tableName, $row);
            if ($onlyOnce || ! $this->option->extended_insert) {
                if ($this->option->complete_insert) {
                    $line .= "INSERT{$ignore} INTO `{$tableName}` (" .
                        implode(', ', $colNames) .
                        ') VALUES (' . implode(',', $vals) . ')';
                } else {
                    $line .= "INSERT{$ignore} INTO `{$tableName}` VALUES (" . implode(',', $vals) . ')';
                }
                $onlyOnce = false;
            } else {
                $line .= ',(' . implode(',', $vals) . ')';
            }

            if ((strlen($line) > $this->option->net_buffer_length)
                    || ! $this->option->extended_insert) {
                $onlyOnce = true;
                $this->compressor->write($line . ';' . PHP_EOL);
                $line = '';
            }
        }

        $resultSet->closeCursor();

        if ('' !== $line) {
            $this->compressor->write($line . ';' . PHP_EOL);
        }

        $this->endListValues($tableName, $count);

        $this->event->emit('table.export', $tableName, $count);
    }

    /**
     * Table rows extractor, append information prior to dump
     */
    public function prepareListValues(string $tableName)
    {
        if (! $this->option->skip_comments) {
            $this->compressor->write(
                '--' . PHP_EOL .
                "-- Dumping data for table `{$tableName}`" . PHP_EOL .
                '--' . PHP_EOL . PHP_EOL
            );
        }

        if ($this->option->lock_tables && ! $this->option->single_transaction) {
            $this->adapter->lockTable($tableName);
        }

        if ($this->option->add_locks) {
            $this->compressor->write(
                $this->adapter->startAddLockTable($tableName)
            );
        }

        if ($this->option->disable_keys) {
            $this->compressor->write(
                $this->adapter->startAddDisableKeys($tableName)
            );
        }

        // Disable autocommit for faster reload
        if ($this->option->no_autocommit) {
            $this->compressor->write(
                $this->adapter->startDisableAutocommit()
            );
        }
    }

    /**
     * Table rows extractor, close locks and commits after dump
     *
     * @param int $count Number of rows inserted.
     */
    public function endListValues(string $tableName, int $count = 0)
    {
        if ($this->option->disable_keys) {
            $this->compressor->write(
                $this->adapter->endAddDisableKeys($tableName)
            );
        }

        if ($this->option->add_locks) {
            $this->compressor->write($this->adapter->endAddLockTable($tableName));
        }

        if ($this->option->lock_tables && ! $this->option->single_transaction) {
            $this->adapter->unlockTable($tableName);
        }

        // Commit to enable autocommit
        if ($this->option->no_autocommit) {
            $this->compressor->write(
                $this->adapter->endDisableAutocommit()
            );
        }

        $this->compressor->write(PHP_EOL);

        if (! $this->option->skip_comments) {
            $this->compressor->write(
                '-- Dumped table `' . $tableName . "` with {$count} row(s)" . PHP_EOL .
                '--' . PHP_EOL . PHP_EOL
            );
        }
    }

    /**
     * Build SQL List of all columns on current table which will be used for selecting
     *
     * @return array SQL sentence with columns for select
     */
    public function getColumnStmt(string $tableName): array
    {
        $colStmt = [];

        foreach ($this->tableColumnTypes[$tableName] as $colName => $colType) {
            if ($colType['is_virtual']) {
                $this->option->complete_insert = true;

                continue;
            }
            if ($colType['type'] === 'bit' && $this->option->hex_blob) {
                $colStmt[] = "LPAD(HEX(`{$colName}`),2,'0') AS `{$colName}`";
            } elseif ($colType['type'] === 'double' && PHP_VERSION_ID > 80100) {
                $colStmt[] = sprintf('CONCAT(`%s`) AS `%s`', $colName, $colName);
            } elseif ($colType['is_blob'] && $this->option->hex_blob) {
                $colStmt[] = "HEX(`{$colName}`) AS `{$colName}`";
            } else {
                $colStmt[] = "`{$colName}`";
            }
        }

        return $colStmt;
    }

    /**
     * Build SQL List of all columns on current table which will be used for inserting
     *
     * @return array columns for sql sentence for insert
     */
    public function getColumnNames(string $tableName): array
    {
        $colNames = [];

        foreach ($this->tableColumnTypes[$tableName] as $colName => $colType) {
            if ($colType['is_virtual']) {
                $this->option->complete_insert = true;

                continue;
            }
            $colNames[] = "`{$colName}`";
        }

        return $colNames;
    }
}

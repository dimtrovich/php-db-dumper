<?php

namespace Dimtrovich\DbDumper\Adapters;

use Dimtrovich\DbDumper\Exceptions\Exception;
use Dimtrovich\DbDumper\Option;
use PDO;

abstract class Factory
{
	public function __construct(protected PDO $pdo, protected Option $option)
    {
    }

	/**
	 * Create an instance of compressor
	 *
	 * @internal
	 */
	public static function create(PDO $pdo, Option $option): static
	{
		$type = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

		$class = __NAMESPACE__ . '\\' . ucfirst(strtolower($type)) . 'Adapter';

        if (!class_exists($class) || $class === self::class) {
			throw Exception::invalidAdapter($type);
		}

        return new $class($pdo, $option);
	}

	/**
	 * Get information about a current database
	 */
	public function getDatabaseHeader(): string
    {
        $args = func_get_args();

		if (!isset($args[0])) {
			return '';
		}

		return "--".PHP_EOL.
            "-- Current Database: `{$args[0]}`".PHP_EOL.
            "--".PHP_EOL.PHP_EOL;
    }

	/**
     * Add sql to create and use database
     */
    public function databases(): string
    {
        return "";
    }

	/**
     * Get table creation code from database
     */
    abstract public function showCreateTable(string $tableName): string;

    /**
     * Modify table creation code to add something according options
     */
    public function createTable(array $row): string
    {
        return "";
    }

	/**
     * Get view creation code from database
     */
    abstract public function showCreateView(string $viewName): string;

    /**
     * Modify view creation code to add something according options
     */
    public function createView(array $row): string
    {
        return "";
    }

    /**
     * Get trigger creation code from database
     */
    public function showCreateTrigger(string $triggerName): string
    {
        return "";
    }

    /**
     * Modify trigger creation code, delimiters, etc
     */
    public function createTrigger(array $row): string
    {
        return "";
    }

	/**
     * Get procedure creation code from database
     */
	public function showCreateProcedure(string $procedureName): string
    {
        return "";
    }

    /**
     * Modify procedure creation code, add delimiters, etc
     */
    public function createProcedure(array $row): string
    {
        return "";
    }

	/**
     * Get function creation code from database
     */
    public function showCreateFunction(string $functionName): string
    {
        return "";
    }

    /**
     * Modify function creation code, add delimiters, etc
     */
    public function createFunction(array $row): string
    {
        return "";
    }

	/**
     * Get event creation code from database
     */
    public function showCreateEvent(string $eventName): string
    {
        return "";
    }

	/**
     * Modify event creation code to add something according options
     */
	public function createEvent(array $row): string
    {
		return "";
	}

	/**
	 * Get code to list tables of database
	 */
    abstract public function showTables(): string;

	/**
	 * Get code to list views of database
	 */
    abstract public function showViews(): string;

	/**
	 * Get code to list triggers of database
	 */
    abstract public function showTriggers(): string;

	/**
	 * Get code to list columns of table
	 */
    abstract public function showColumns(): string;

	/**
	 * Get code to list procedures of database
	 */
    public function showProcedures(): string
    {
        return "";
    }

	/**
	 * Get code to list functions of database
	 */
    public function showFunctions(): string
    {
        return "";
    }

	/**
	 * Get code to list events of database
	 */
    public function showEvents(): string
    {
        return "";
    }

	/**
	 * Get code to setup database transaction
	 */
    public function setupTransaction(): string
    {
        return "";
    }

	/**
	 * Get code to start database transaction
	 */
    abstract public function startTransaction(): string;

	/**
	 * Get code to commit transaction
	 */
    abstract public function commitTransaction(): string;

	/**
	 * Perform lock table
	 */
    public function lockTable(): int|false
    {
        return false;
    }

	/**
	 * Perform unlock table
	 */
    public function unlockTable(): int|false
    {
        return false;
    }

	/**
	 * Get code to start lock table operation
	 */
    public function startAddLockTable(): string
    {
        return PHP_EOL;
    }

	/**
	 * Get code to finish lock table operation
	 */
    public function endAddLockTable(): string
    {
        return PHP_EOL;
    }

	/**
	 * Get code to start disabled keys operation
	 */
    public function startAddDisableKeys(): string
    {
        return PHP_EOL;
    }

	/**
	 * Get code to finish disabled keys operation
	 */
    public function endAddDisableKeys(): string
    {
        return PHP_EOL;
    }

    /**
	 * Get code to start disabled foreign keys operation
	 */
    public function startDisableForeignKeysCheck(): string
    {
        return PHP_EOL;
    }

    /**
	 * Get code to finish disabled foreign keys operation
	 */
    public function endDisableForeignKeysCheck(): string
    {
        return PHP_EOL;
    }

	/**
	 * Get code to start disabled autocommit operation
	 */
    public function startDisableAutocommit(): string
    {
        return PHP_EOL;
    }

	/**
	 * Get code to finish disabled autocommit operation
	 */
    public function endDisableAutocommit(): string
    {
        return PHP_EOL;
    }

    /**
	 * Get code to drop database
	 */
    public function addDropDatabase(): string
    {
        return PHP_EOL;
    }

    /**
	 * Get code to drop trigger
	 */
    public function addDropTrigger(): string
    {
        return PHP_EOL;
    }

    /**
	 * Get code to drop table
	 */
    public function dropTable(): string
    {
        return PHP_EOL;
    }

    /**
	 * Get code to drop view
	 */
    public function dropView(): string
    {
        return PHP_EOL;
    }

    /**
     * Decode column metadata and fill info structure.
     * type, is_numeric and is_blob will always be available.
     *
     * @param array $colType Array returned from "SHOW COLUMNS FROM tableName"
     */
    public function parseColumnType(array $colType): array
    {
        return [];
    }

    /**
	 * Get code backup database parameters
	 */
    public function backupParameters(): string
    {
        return PHP_EOL;
    }

    /**
	 * Get code restore database parameters
	 */
    public function restoreParameters(): string
    {
        return PHP_EOL;
    }
}

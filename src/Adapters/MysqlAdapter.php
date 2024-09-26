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

class MysqlAdapter extends Factory
{
    public const DEFINER_RE = 'DEFINER=`(?:[^`]|``)*`@`(?:[^`]|``)*`';

    // Numerical Mysql types
    public $mysqlTypes = [
        'numerical' => [
            'bit',
            'tinyint',
            'smallint',
            'mediumint',
            'int',
            'integer',
            'bigint',
            'real',
            'double',
            'float',
            'decimal',
            'numeric',
        ],
        'blob' => [
            'tinyblob',
            'blob',
            'mediumblob',
            'longblob',
            'binary',
            'varbinary',
            'bit',
            'geometry', // http://bugs.mysql.com/bug.php?id=43544
            'point',
            'linestring',
            'polygon',
            'multipoint',
            'multilinestring',
            'multipolygon',
            'geometrycollection',
        ],
    ];

    /**
     * {@inheritDoc}
     */
    public function databases(): string
    {
        if ($this->option->no_create_db) {
            return '';
        }

        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $args         = func_get_args();
        $databaseName = $args[0];

        $resultSet    = $this->pdo->query("SHOW VARIABLES LIKE 'character_set_database';");
        $characterSet = $resultSet->fetchColumn(1);
        $resultSet->closeCursor();

        $resultSet   = $this->pdo->query("SHOW VARIABLES LIKE 'collation_database';");
        $collationDb = $resultSet->fetchColumn(1);
        $resultSet->closeCursor();

        return "CREATE DATABASE /*!32312 IF NOT EXISTS*/ `{$databaseName}`" .
            " /*!40100 DEFAULT CHARACTER SET {$characterSet} " .
            " COLLATE {$collationDb} */;" . PHP_EOL . PHP_EOL .
            "USE `{$databaseName}`;" . PHP_EOL . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function showCreateTable(string $tableName): string
    {
        return "SHOW CREATE TABLE `{$tableName}`";
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
            $match       = '/AUTO_INCREMENT=[0-9]+/s';
            $replace     = '';
            $createTable = preg_replace($match, $replace, $createTable);
        }

        if ($this->option->if_not_exists) {
            $createTable = preg_replace('/^CREATE TABLE/', 'CREATE TABLE IF NOT EXISTS', $createTable);
        }

        return '/*!40101 SET @saved_cs_client     = @@character_set_client */;' . PHP_EOL .
            '/*!40101 SET character_set_client = ' . $this->option->default_character_set . ' */;' . PHP_EOL .
            $createTable . ';' . PHP_EOL .
            '/*!40101 SET character_set_client = @saved_cs_client */;' . PHP_EOL .
            PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function showCreateView(string $viewName): string
    {
        return "SHOW CREATE VIEW `{$viewName}`";
    }

    /**
     * {@inheritDoc}
     */
    public function showCreateTrigger(string $triggerName): string
    {
        return "SHOW CREATE TRIGGER `{$triggerName}`";
    }

    /**
     * {@inheritDoc}
     */
    public function showCreateProcedure(string $procedureName): string
    {
        return "SHOW CREATE PROCEDURE `{$procedureName}`";
    }

    /**
     * {@inheritDoc}
     */
    public function showCreateFunction(string $functionName): string
    {
        return "SHOW CREATE FUNCTION `{$functionName}`";
    }

    /**
     * {@inheritDoc}
     */
    public function showCreateEvent(string $eventName): string
    {
        return "SHOW CREATE EVENT `{$eventName}`";
    }

    /**
     * {@inheritDoc}
     */
    public function createView(array $row): string
    {
        if (! isset($row['Create View'])) {
            throw new Exception('Error getting view structure, unknown output');
        }

        $viewStmt = $row['Create View'];

        $definerStr = $this->option->skip_definer ? '' : '/*!50013 \2 */' . PHP_EOL;

        if ($viewStmtReplaced = preg_replace(
            '/^(CREATE(?:\s+ALGORITHM=(?:UNDEFINED|MERGE|TEMPTABLE))?)\s+('
            . self::DEFINER_RE . '(?:\s+SQL SECURITY (?:DEFINER|INVOKER))?)?\s+(VIEW .+)$/',
            '/*!50001 \1 */' . PHP_EOL . $definerStr . '/*!50001 \3 */',
            $viewStmt,
            1
        )) {
            $viewStmt = $viewStmtReplaced;
        }

        return $viewStmt . ';' . PHP_EOL . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function createTrigger(array $row): string
    {
        if (! isset($row['SQL Original Statement'])) {
            throw new Exception('Error getting trigger code, unknown output');
        }

        $triggerStmt = $row['SQL Original Statement'];
        $definerStr  = $this->option->skip_definer ? '' : '/*!50017 \2*/ ';
        if ($triggerStmtReplaced = preg_replace(
            '/^(CREATE)\s+(' . self::DEFINER_RE . ')?\s+(TRIGGER\s.*)$/s',
            '/*!50003 \1*/ ' . $definerStr . '/*!50003 \3 */',
            $triggerStmt,
            1
        )) {
            $triggerStmt = $triggerStmtReplaced;
        }

        return 'DELIMITER ;;' . PHP_EOL .
            $triggerStmt . ';;' . PHP_EOL .
            'DELIMITER ;' . PHP_EOL . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function createProcedure(array $row): string
    {
        if (! isset($row['Create Procedure'])) {
            throw new Exception('Error getting procedure code, unknown output. ' .
                "Please check 'https://bugs.mysql.com/bug.php?id=14564'");
        }

        $procedureStmt = $row['Create Procedure'];

        if ($this->option->skip_definer) {
            if ($procedureStmtReplaced = preg_replace(
                '/^(CREATE)\s+(' . self::DEFINER_RE . ')?\s+(PROCEDURE\s.*)$/s',
                '\1 \3',
                $procedureStmt,
                1
            )) {
                $procedureStmt = $procedureStmtReplaced;
            }
        }

        return '/*!50003 DROP PROCEDURE IF EXISTS `' .
            $row['Procedure'] . '` */;' . PHP_EOL .
            '/*!40101 SET @saved_cs_client     = @@character_set_client */;' . PHP_EOL .
            '/*!40101 SET character_set_client = ' . $this->option->default_character_set . ' */;' . PHP_EOL .
            'DELIMITER ;;' . PHP_EOL .
            $procedureStmt . ' ;;' . PHP_EOL .
            'DELIMITER ;' . PHP_EOL .
            '/*!40101 SET character_set_client = @saved_cs_client */;' . PHP_EOL . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function createFunction(array $row): string
    {
        if (! isset($row['Create Function'])) {
            throw new Exception('Error getting function code, unknown output. ' .
                "Please check 'https://bugs.mysql.com/bug.php?id=14564'");
        }

        $functionStmt        = $row['Create Function'];
        $characterSetClient  = $row['character_set_client'];
        $collationConnection = $row['collation_connection'];
        $sqlMode             = $row['sql_mode'];

        if ($this->option->skip_definer) {
            if ($functionStmtReplaced = preg_replace(
                '/^(CREATE)\s+(' . self::DEFINER_RE . ')?\s+(FUNCTION\s.*)$/s',
                '\1 \3',
                $functionStmt,
                1
            )) {
                $functionStmt = $functionStmtReplaced;
            }
        }

        return '/*!50003 DROP FUNCTION IF EXISTS `' .
            $row['Function'] . '` */;' . PHP_EOL .
            '/*!40101 SET @saved_cs_client     = @@character_set_client */;' . PHP_EOL .
            '/*!50003 SET @saved_cs_results     = @@character_set_results */ ;' . PHP_EOL .
            '/*!50003 SET @saved_col_connection = @@collation_connection */ ;' . PHP_EOL .
            '/*!40101 SET character_set_client = ' . $characterSetClient . ' */;' . PHP_EOL .
            '/*!40101 SET character_set_results = ' . $characterSetClient . ' */;' . PHP_EOL .
            '/*!50003 SET collation_connection  = ' . $collationConnection . ' */ ;' . PHP_EOL .
            '/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;' . PHP_EOL .
            "/*!50003 SET sql_mode              = '" . $sqlMode . "' */ ;;" . PHP_EOL .
            '/*!50003 SET @saved_time_zone      = @@time_zone */ ;;' . PHP_EOL .
            "/*!50003 SET time_zone             = 'SYSTEM' */ ;;" . PHP_EOL .
            'DELIMITER ;;' . PHP_EOL .
            $functionStmt . ' ;;' . PHP_EOL .
            'DELIMITER ;' . PHP_EOL .
            '/*!50003 SET sql_mode              = @saved_sql_mode */ ;' . PHP_EOL .
            '/*!50003 SET character_set_client  = @saved_cs_client */ ;' . PHP_EOL .
            '/*!50003 SET character_set_results = @saved_cs_results */ ;' . PHP_EOL .
            '/*!50003 SET collation_connection  = @saved_col_connection */ ;' . PHP_EOL .
            '/*!50106 SET TIME_ZONE= @saved_time_zone */ ;' . PHP_EOL . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function createEvent(array $row): string
    {
        if (! isset($row['Create Event'])) {
            throw new Exception('Error getting event code, unknown output. ' .
                "Please check 'http://stackoverflow.com/questions/10853826/mysql-5-5-create-event-gives-syntax-error'");
        }

        $eventName  = $row['Event'];
        $eventStmt  = $row['Create Event'];
        $sqlMode    = $row['sql_mode'];
        $definerStr = $this->option->skip_definer ? '' : '/*!50117 \2*/ ';

        if ($eventStmtReplaced = preg_replace(
            '/^(CREATE)\s+(' . self::DEFINER_RE . ')?\s+(EVENT\s.*)$/s',
            '/*!50106 \1*/ ' . $definerStr . '/*!50106 \3 */',
            $eventStmt,
            1
        )) {
            $eventStmt = $eventStmtReplaced;
        }

        return '/*!50106 SET @save_time_zone= @@TIME_ZONE */ ;' . PHP_EOL .
            '/*!50106 DROP EVENT IF EXISTS `' . $eventName . '` */;' . PHP_EOL .
            'DELIMITER ;;' . PHP_EOL .
            '/*!50003 SET @saved_cs_client      = @@character_set_client */ ;;' . PHP_EOL .
            '/*!50003 SET @saved_cs_results     = @@character_set_results */ ;;' . PHP_EOL .
            '/*!50003 SET @saved_col_connection = @@collation_connection */ ;;' . PHP_EOL .
            '/*!50003 SET character_set_client  = utf8 */ ;;' . PHP_EOL .
            '/*!50003 SET character_set_results = utf8 */ ;;' . PHP_EOL .
            '/*!50003 SET collation_connection  = utf8_general_ci */ ;;' . PHP_EOL .
            '/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;;' . PHP_EOL .
            "/*!50003 SET sql_mode              = '" . $sqlMode . "' */ ;;" . PHP_EOL .
            '/*!50003 SET @saved_time_zone      = @@time_zone */ ;;' . PHP_EOL .
            "/*!50003 SET time_zone             = 'SYSTEM' */ ;;" . PHP_EOL .
            $eventStmt . ' ;;' . PHP_EOL .
            '/*!50003 SET time_zone             = @saved_time_zone */ ;;' . PHP_EOL .
            '/*!50003 SET sql_mode              = @saved_sql_mode */ ;;' . PHP_EOL .
            '/*!50003 SET character_set_client  = @saved_cs_client */ ;;' . PHP_EOL .
            '/*!50003 SET character_set_results = @saved_cs_results */ ;;' . PHP_EOL .
            '/*!50003 SET collation_connection  = @saved_col_connection */ ;;' . PHP_EOL .
            'DELIMITER ;' . PHP_EOL .
            '/*!50106 SET TIME_ZONE= @save_time_zone */ ;' . PHP_EOL . PHP_EOL;
        // Commented because we are doing this in restore_parameters()
        // "/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;" . PHP_EOL . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $database
     */
    public function showTables(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $database = func_get_arg(0);

        return 'SELECT TABLE_NAME AS tbl_name ' .
            'FROM INFORMATION_SCHEMA.TABLES ' .
            "WHERE TABLE_TYPE='BASE TABLE' AND TABLE_SCHEMA='{$database}' " .
            'ORDER BY TABLE_NAME';
    }

    /**
     * {@inheritDoc}
     *
     * @param string $database
     */
    public function showViews(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $database = func_get_arg(0);

        return 'SELECT TABLE_NAME AS tbl_name ' .
            'FROM INFORMATION_SCHEMA.TABLES ' .
            "WHERE TABLE_TYPE='VIEW' AND TABLE_SCHEMA='{$database}' " .
            'ORDER BY TABLE_NAME';
    }

    /**
     * {@inheritDoc}
     *
     * @param string $database
     */
    public function showTriggers(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $database = func_get_arg(0);

        return "SHOW TRIGGERS FROM `{$database}`;";
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     */
    public function showColumns(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $table = func_get_arg(0);

        return "SHOW COLUMNS FROM `{$table}`;";
    }

    /**
     * {@inheritDoc}
     *
     * @param string $database
     */
    public function showProcedures(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $database = func_get_arg(0);

        return 'SELECT SPECIFIC_NAME AS procedure_name ' .
            'FROM INFORMATION_SCHEMA.ROUTINES ' .
            "WHERE ROUTINE_TYPE='PROCEDURE' AND ROUTINE_SCHEMA='{$database}'";
    }

    /**
     * {@inheritDoc}
     *
     * @param string $database
     */
    public function showFunctions(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $database = func_get_arg(0);

        return 'SELECT SPECIFIC_NAME AS function_name ' .
            'FROM INFORMATION_SCHEMA.ROUTINES ' .
            "WHERE ROUTINE_TYPE='FUNCTION' AND ROUTINE_SCHEMA='{$database}'";
    }

    /**
     * {@inheritDoc}
     *
     * @param string $database
     */
    public function showEvents(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $database = func_get_arg(0);

        return 'SELECT EVENT_NAME AS event_name ' .
            'FROM INFORMATION_SCHEMA.EVENTS ' .
            "WHERE EVENT_SCHEMA='{$database}'";
    }

    /**
     * {@inheritDoc}
     */
    public function setupTransaction(): string
    {
        return 'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ';
    }

    /**
     * {@inheritDoc}
     */
    public function startTransaction(): string
    {
        return 'START TRANSACTION ' .
            '/*!40100 WITH CONSISTENT SNAPSHOT */';
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
     *
     * @param string $table
     */
    public function lockTable(): false|int
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $table = func_get_arg(0);

        return $this->pdo->exec("LOCK TABLES `{$table}` READ LOCAL");
    }

    /**
     * {@inheritDoc}
     */
    public function unlockTable(): false|int
    {
        return $this->pdo->exec('UNLOCK TABLES');
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     */
    public function startAddLockTable(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $table = func_get_arg(0);

        return "LOCK TABLES `{$table}` WRITE;" . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function endAddLockTable(): string
    {
        return 'UNLOCK TABLES;' . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     */
    public function startAddDisableKeys(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);

        $table = func_get_arg(0);

        return "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;" .
            PHP_EOL;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     */
    public function endAddDisableKeys(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $table = func_get_arg(0);

        return "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;" .
            PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function startDisableAutocommit(): string
    {
        return 'SET autocommit=0;' . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function endDisableAutocommit(): string
    {
        return 'COMMIT;' . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $database
     */
    public function addDropDatabase(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $database = func_get_arg(0);

        return "/*!40000 DROP DATABASE IF EXISTS `{$database}`*/;" .
            PHP_EOL . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $trigger
     */
    public function addDropTrigger(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $trigger = func_get_arg(0);

        return "DROP TRIGGER IF EXISTS `{$trigger}`;" . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $table
     */
    public function dropTable(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $table = func_get_arg(0);

        return "DROP TABLE IF EXISTS `{$table}`;" . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     *
     * @param string $view
     */
    public function dropView(): string
    {
        $this->checkParameters(func_num_args(), $expected_num_args = 1, __METHOD__);
        $view = func_get_arg(0);

        return "DROP TABLE IF EXISTS `{$view}`;" . PHP_EOL .
                "/*!50001 DROP VIEW IF EXISTS `{$view}`*/;" . PHP_EOL;
    }

    /**
     * {@inheritDoc}
     */
    public function parseColumnType(array $colType): array
    {
        $colInfo  = [];
        $colParts = explode(' ', $colType['Type']);

        if ($fparen = strpos($colParts[0], '(')) {
            $colInfo['type']       = substr($colParts[0], 0, $fparen);
            $colInfo['length']     = str_replace(')', '', substr($colParts[0], $fparen + 1));
            $colInfo['attributes'] = $colParts[1] ?? null;
        } else {
            $colInfo['type'] = $colParts[0];
        }
        $colInfo['is_numeric'] = in_array($colInfo['type'], $this->mysqlTypes['numerical'], true);
        $colInfo['is_blob']    = in_array($colInfo['type'], $this->mysqlTypes['blob'], true);
        // for virtual columns that are of type 'Extra', column type
        // could by "STORED GENERATED" or "VIRTUAL GENERATED"
        // MySQL reference: https://dev.mysql.com/doc/refman/5.7/en/create-table-generated-columns.html
        $colInfo['is_virtual'] = str_contains($colType['Extra'], 'VIRTUAL GENERATED') || str_contains($colType['Extra'], 'STORED GENERATED');

        return $colInfo;
    }

    /**
     * {@inheritDoc}
     */
    public function backupParameters(): string
    {
        $ret = '/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;' . PHP_EOL .
            '/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;' . PHP_EOL .
            '/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;' . PHP_EOL .
            '/*!40101 SET NAMES ' . $this->option->default_character_set . ' */;' . PHP_EOL;

        if (false === $this->option->skip_tz_utc) {
            $ret .= '/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;' . PHP_EOL .
                "/*!40103 SET TIME_ZONE='+00:00' */;" . PHP_EOL;
        }

        if ($this->option->no_autocommit) {
            $ret .= '/*!40101 SET @OLD_AUTOCOMMIT=@@AUTOCOMMIT */;' . PHP_EOL;
        }

        $ret .= '/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;' . PHP_EOL .
            '/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;' . PHP_EOL .
            "/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;" . PHP_EOL .
            '/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;' . PHP_EOL . PHP_EOL;

        return $ret;
    }

    /**
     * {@inheritDoc}
     */
    public function restoreParameters(): string
    {
        $ret = '';

        if (false === $this->option->skip_tz_utc) {
            $ret .= '/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;' . PHP_EOL;
        }

        if ($this->option->no_autocommit) {
            $ret .= '/*!40101 SET AUTOCOMMIT=@OLD_AUTOCOMMIT */;' . PHP_EOL;
        }

        $ret .= '/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;' . PHP_EOL .
            '/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;' . PHP_EOL .
            '/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;' . PHP_EOL .
            '/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;' . PHP_EOL .
            '/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;' . PHP_EOL .
            '/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;' . PHP_EOL .
            '/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;' . PHP_EOL . PHP_EOL;

        return $ret;
    }

    /**
     * Check number of parameters passed to function, useful when inheriting.
     * Raise exception if unexpected.
     */
    private function checkParameters(int $num_args, int $expected_num_args, string $method_name)
    {
        if ($num_args !== $expected_num_args) {
            throw new Exception("Unexpected parameter passed to {$method_name}");
        }
    }
}

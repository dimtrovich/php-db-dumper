EN | [FR](README-fr.md)

# Backup and restore database content

[![Tests](https://github.com/dimtrovich/php-db-dumper/actions/workflows/run-tests.yml/badge.svg)](https://github.com/dimtrovich/php-db-dumper/actions/workflows/run-tests.yml)
[![Code Coverage](https://scrutinizer-ci.com/g/dimtrovich/php-db-dumper/badges/coverage.png?b=main)](https://scrutinizer-ci.com/g/dimtrovich/php-db-dumper/?branch=main)
[![Coding Standards](https://github.com/dimtrovich/php-db-dumper/actions/workflows/test-coding-standards.yml/badge.svg)](https://github.com/dimtrovich/php-db-dumper/actions/workflows/test-coding-standards.yml)
[![Build Status](https://scrutinizer-ci.com/g/dimtrovich/php-db-dumper/badges/build.png?b=main)](https://scrutinizer-ci.com/g/dimtrovich/php-db-dumper/build-status/main)
[![Code Intelligence Status](https://scrutinizer-ci.com/g/dimtrovich/php-db-dumper/badges/code-intelligence.svg?b=main)](https://scrutinizer-ci.com/code-intelligence)
[![Quality Score](https://img.shields.io/scrutinizer/g/dimtrovich/php-db-dumper.svg?style=flat-square)](https://scrutinizer-ci.com/g/dimtrovich/php-db-dumper)
[![PHPStan](https://github.com/dimtrovich/php-db-dumper/actions/workflows/test-phpstan.yml/badge.svg)](https://github.com/dimtrovich/php-db-dumper/actions/workflows/test-phpstan.yml)
[![PHPStan level](https://img.shields.io/badge/PHPStan-level%206-brightgreen)](phpstan.neon.dist)
[![Total Downloads](https://poser.pugx.org/dimtrovich/db-dumper/downloads)](https://packagist.org/packages/dimtrovich/db-dumper)
[![Latest Version](https://img.shields.io/packagist/v/dimtrovich/db-dumper.svg?style=flat-square)](https://packagist.org/packages/dimtrovich/db-dumper)
![PHP](https://img.shields.io/badge/PHP->=7.4-blue)
[![Software License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

<br>

**Db Dumper** is a tool that offers you a simple and efficient way to **export** and **import** your database in PHP. It is somewhat of a PHP version of the command-line tool `mysqldump` that comes with MySQL, without dependencies, with output compression and reasonable default parameters.

**Db Dumper** supports backing up table structures, the data itself, views, triggers, and events.

## Features

Db Dumper supports:
* outputting binary blobs in hexadecimal form.
* resolving view dependencies (using substitute tables).
* backing up stored routines (functions and procedures).
* backing up events.
* extended and/or complete insertion.
* MySQL 5.7 virtual columns.
* `insert-ignore`, like a `REPLACE` but ignoring errors if a duplicate key exists.
* modifying database data on the fly during backup, using `hooks`.
* direct backup to Google Cloud storage via a compressed stream wrapper (GZIPSTREAM).

Db Dumper is designed to work with the main current database management systems. The list below outlines their support:
* MySQL (supported)
* SQLite (supported)
* PostgreSQL (in progress)
* Oracle (not supported)

## Prerequisites

- PHP 7.4+
- *MySQL 5+
- *SQLite 3+
- [PDO](https://secure.php.net/pdo)

## Installation

Using [Composer](https://getcomposer.org/):

```
$ composer require dimtrovich/db-dumper
```

After installing this package, you must first ensure that you have access to a `PDO` instance as the export and import systems need it.

```php
use PDO;

$pdo = new PDO('mysql:host=localhost;port=3307;dbname=database', 'username', 'password');
```

## Data Export (backup)

Data export is the main functionality of this package. **Db Dumper** offers you a simple API to backup your database with the same options offered by native MySQL or PostgreSQL commands (`mysqldump` / `pgrestore`)

```php
use Dimtrovich\DbDumper\Exporter;
use Exception;

try {
    $exporter = new Exporter($pdo, 'database');
    
    $exporter->process('storage/work/dump.sql');
} catch (Exception $e) {
    echo 'db-dumper error: ' . $e->getMessage();
}
```

### Modifying values during export

You can register a callable that will be used to transform values during export. A typical use case is removing sensitive data from database backups:

```php
$exporter = new Exporter($pdo, 'database');
    
$exporter->transformTableRow(function (string $tableName, array $row) {
    if ($tableName === 'customers') {
        $row['social_security_number'] = (string) rand(1000000, 9999999);
    }

    return $row;
});

$exporter->process('storage/work/dump.sql');
```

### Getting information about table export

You can register a callable that will be used to report the progress of the backup:

```php
$exporter->onTableExport(function($tableName, $rowCount) {
    echo "Exporting table $tableName with $rowCount rows\n";
});
```

### Table-specific export conditions

You can define table-specific `WHERE` clauses to limit the data of tables that can be exported. These clauses override the default `where` parameter:

```php
$exporter->setTableWheres([
    'users' => 'date_registered > NOW() - INTERVAL 3 MONTH AND deleted=0',
    'logs' => 'date_logged > NOW() - INTERVAL 1 DAY',
    'posts' => 'active=1'
]);
```

### Table-specific export limits

You can also define table-specific limits to limit the number of records that will be saved for each table:

```php
$exporter->setTableLimits([
    'users' => 300,
    'logs' => 50,
    'posts' => 10
]);
```

### Exporter configuration options

The constructor of the `Exporter` class accepts a third parameter which is an array designating the data export options.

<div class="overflow-auto">

Option  | Type | Default | Description
------- | ------- | ------- | -------
`include-tables` | `array` | `[]` | Include only these tables (array of table names), include all if empty.
`exclude-tables` | `array` | `[]` | Exclude these tables (array of table names), include all if empty, supports regular expressions.
`include-views` | `array` | `[]` | Include only these views (array of view names), include all if empty. By default, all views named in the `include-tables` array are included.
`compress` | `Gzip`, `Bzip2`, `None`, `GzipStream` | `None` |   
`init_commands` | `array` | `[]` |   
`no-data` | `array` | `[]` | Do not save data for these tables (array of table names), supports regular expressions.
`if-not-exists` | `bool` | `false` | Create a new table only if a table of the same name does not already exist. No error message is generated if the table already exists.
`reset-auto-increment` | `bool` | `false` | Removes the `AUTO_INCREMENT` option from the database definition. Useful when used with `no-data`, so that when the database is recreated, it starts at 1 instead of using an old value.
`add-drop-database` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-database)
`add-drop-table` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-table)
`add-drop-trigger` | `bool` | `true` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-drop-trigger)
`add-locks` | `bool` | `true` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_add-locks)
`complete-insert` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_complete-insert)
`databases` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_databases)
`default-character-set` | `utf8`, `utf8mb4`, `binary` | `utf8` |   
`disable-keys` | `bool` | `true` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_disable-keys)
`extended-insert` | `bool` | `true` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_extended-insert)
`events` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_events)
`hex-blob` | `bool` | `true` | (Faster than escaped content). [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_hex-blob)
`insert-ignore` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_insert-ignore)
`lock-tables` | `bool` | `true` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_lock-tables)
`net_buffer_length` | `int` | `1000000` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.7/en/mysqldump.html#option_mysqldump_net_buffer_length)
`no-autocommit` | `bool` | `true` | Option to disable autocommit (faster inserts, no problems with index keys). [MySQL Documentation](https://dev.mysql.com/doc/refman/4.1/en/commit.html)
`no-create-db` | `bool` | `false` | Option to disable backing up database creation instructions. [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-create-db)
`no-create-info` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_no-create-info)
`routines` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_routines)
`single-transaction` | `bool` | `true` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_single-transaction)
`skip-triggers` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_triggers)
`skip-tz-utc` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_tz-utc)
`skip-comments` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_comments)
`skip-dump-date` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_dump-date)
`skip-definer` | `bool` | `false` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.7/en/mysqlpump.html#option_mysqlpump_skip-definer)
`where` | `string` | `''` | [MySQL Documentation](https://dev.mysql.com/doc/refman/5.1/en/mysqldump.html#option_mysqldump_where)
</div>

## Data Import (Restore)

Just like with data export, **Db Dumper** provides you with a simple API to restore your database from a backup file (`.sql`, `.gz`, `.gzip`, `.bz2`, `.bzip2`).

```php
use Dimtrovich\DbDumper\Importer;
use Exception;

try {
    $importer = new Importer($pdo, 'database');
    
    $importer->process('storage/work/dump.sql');
} catch (Exception $e) {
    echo 'db-dumper error: ' . $e->getMessage();
}
```

The file extension of the restore file determines the type of compression to use:
- `.sql` No compression, it's a plain SQL file.
- `.gz`, `.gzip` GZIP compression, the importer will decompress the file before proceeding with the database restoration. **Make sure your PHP installation has the `Zlib` extension enabled before using such a dump.**
- `.bz2`, `.bzip2` BZIP2 compression, the importer will decompress the file before proceeding with the database restoration. **Make sure your PHP installation has the `Bzlib2` extension enabled before using such a dump.**

### Get Information on Table Import

You can register a callable that will be used to report the progress of the restoration:

```php
$importer->onTableCreate(function($tableName) {
    echo "Creating table $tableName\n";
});
$importer->onTableInsert(function($tableName, $rowCount) {
    echo "Inserting $rowCount rows into table $tableName\n";
});
```

## Errors

To back up a database, you need the following privileges:

- **SELECT**
  - To back up table structures and data.
- **SHOW VIEW**
  - If a database contains views, otherwise you will get an error.
- **TRIGGER**
  - If a table contains one or more triggers.
- **LOCK TABLES**
  - If the "lock tables" option is enabled.

Use **SHOW GRANTS FOR user@host;** to check the user's privileges. See the following link for more information:

[What are the minimum privileges required to get a backup of a MySQL database schema?](https://dba.stackexchange.com/questions/55546/which-are-the-minimum-privileges-required-to-get-a-backup-of-a-mysql-database-sc/55572#55572)

To restore a database, you need the following privileges:

- **ALTER**
  - Required if your backup file contains table alteration instructions.
- **CREATE**
  - Required if your backup file contains table creation instructions.
- **CREATE ROUTINE**
  - Required if your backup file contains routine creation instructions.
- **CREATE VIEW**
  - Required if your backup file contains view creation instructions.
- **DELETE**
  - Required if your backup file contains data deletion instructions.
- **DROP**
  - Required if your backup file contains table or view deletion instructions.
- **INSERT**
  - Required if your backup file contains data insertion instructions into tables.
- **UPDATE**
  - Required if your backup file contains data modification instructions in tables.

## Tests

The unit tests for this package are written using the Kahlan library. The tests cover SQLite, but tests for MySQL have not been written, although tests have been conducted in a real environment. PRs in this direction are welcome.

## Todo

- Write more tests, also test with MariaDB.
- Support for other database drivers (PostgreSQL, Oracle, MS Server, MongoDB).

## Contribution

Please see [CONTRIBUTING](CONTRIBUTING.md) for more details.

## License

This project is open-source software licensed under the [MIT](https://opensource.org/license/MIT) license. Please see the [License File](LICENSE.md) for more information.

## Credits

Although significantly modified, the code for **DB Dumper**'s exporter was inspired by [MySQLDump - PHP](https://github.com/ifsnop/mysqldump-php) maintained by [Diego Torres](https://github.com/ifsnop). We would like to thank him.

That said, note that this package was created by [Dimitri Sitchet Tomkeu](https://github.com/dimtrovich) and is maintained by [All Contributors](../../contributors).

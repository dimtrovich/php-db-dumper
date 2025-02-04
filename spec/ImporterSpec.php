<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\DbDumper\Adapters\SqliteAdapter;
use Dimtrovich\DbDumper\Importer;

use function Kahlan\expect;

describe('Importer', function () {
    it('should be instantiable', function () {
        $importer = new Importer('database', pdo());
        expect($importer)->toBeAnInstanceOf(Importer::class);
    });

    it('should process importation', function () {
        $importer = new Importer('database', $pdo = pdo());
        $adapter  = new SqliteAdapter($pdo, $importer->getOption());

        $tables = $pdo->query($adapter->showTables())->fetchAll(PDO::FETCH_NUM);
        $tables = array_map(fn ($table) => $table[0], $tables);

        expect($tables)->not->toContain('countries');

        $importer->process(__DIR__ . '/import.sql');

        $tables = $pdo->query($adapter->showTables())->fetchAll(PDO::FETCH_NUM);
        $tables = array_map(fn ($table) => $table[0], $tables);

        expect($tables)->toContain('countries');

        $name = $pdo->query('SELECT name FROM countries WHERE id = 1')->fetch(PDO::FETCH_NUM);
        expect($name)->toBe(['Turkey']);

        $pdo->exec($adapter->dropTable('countries'));

        $tables = $pdo->query($adapter->showTables())->fetchAll(PDO::FETCH_NUM);
        $tables = array_map(fn ($table) => $table[0], $tables);

        expect($tables)->not->toContain('countries');
    });

    it('should process importation and register hooks', function () {
        $importer = new Importer('database', $pdo = pdo());
        $adapter  = new SqliteAdapter($pdo, $importer->getOption());

        $importer->onTableCreate(function ($table) {
            echo "Creating table {$table}\n";
        });
        $importer->onTableInsert(function ($table, $rowCount) {
            echo "Inserted {$rowCount} rows into table {$table}\n";
        });

        expect(fn () => $importer->process(__DIR__ . '/import.sql'))
            ->toEcho("Creating table countries\nInserted 5 rows into table countries\n");

        $pdo->exec($adapter->dropTable('countries'));
    });
});

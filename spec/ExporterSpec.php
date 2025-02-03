<?php

use Dimtrovich\DbDumper\Exceptions\Exception;
use Dimtrovich\DbDumper\Exporter;
use Dimtrovich\DbDumper\Spec\ReflectionHelper;

use function Kahlan\expect;

describe('Exporter', function() {
	it('should be instantiable', function() {
		$exporter = new Exporter('database', pdo());
		expect($exporter)->toBeAnInstanceOf(Exporter::class);
	});

	it('should specify table where conditions', function() {
		$exporter = new Exporter('database', pdo());
        $exporter->setTableWheres(['users' => 'date_registered > NOW() - INTERVAL 6 MONTH AND deleted=0']);
        expect($exporter->getTableWhere('users'))->toBe('date_registered > NOW() - INTERVAL 6 MONTH AND deleted=0');
        expect($exporter->getTableWhere('undefined_table'))->toBeFalsy();
	});

	it('should specify single table where conditions', function() {
		$exporter = new Exporter('database', pdo());
        $exporter->where('users', 'date_registered > NOW() - INTERVAL 6 MONTH AND deleted=0');
        expect($exporter->getTableWhere('users'))->toBe('date_registered > NOW() - INTERVAL 6 MONTH AND deleted=0');
	});

	it('should specify global where conditions', function() {
		$exporter = new Exporter('database', pdo(), ['where' => 'created_at < NOW()']);
        expect($exporter->getTableWhere('users'))->toBe('created_at < NOW()');
	});

	it('should specify table limits', function() {
		$exporter = new Exporter('database', pdo());
        $exporter->setTableLimits(['users' => 3000, 'bad_limit' => 'foo']);
		expect($exporter->getTableLimit('users'))->toBe(3000);
		expect($exporter->getTableLimit('undefined_table'))->toBeFalsy();
		expect($exporter->getTableLimit('bad_limit'))->toBeFalsy();
	});

	it('should specify single table limits', function() {
		$exporter = new Exporter('database', pdo());
        $exporter->limit('users', 3000);
		expect($exporter->getTableLimit('users'))->toBe(3000);
	});

	it('should get dump file header', function() {
		$exporter          = new Exporter('database', $pdo = pdo());
		$getDumpFileHeader = ReflectionHelper::getPrivateMethodInvoker($exporter, 'getDumpFileHeader');
  		$header            = $getDumpFileHeader();
        expect($header)->toContain('-- Database Backup Manager');
        expect($header)->toContain('-- Server version: ' . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . ' Driver: ' . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME));
	});

	it('should get dump file footer', function() {
		$exporter          = new Exporter('database', pdo());
		$getDumpFileFooter = ReflectionHelper::getPrivateMethodInvoker($exporter, 'getDumpFileFooter');
  		$footer            = $getDumpFileFooter();
        expect($footer)->toContain('-- Dump completed');
	});

	it('should process exportation', function() {
		$exporter  = new Exporter('database', pdo());

		expect(file_exists($database = __DIR__ . '/database.sql'))->toBeFalsy();
		$exporter->process($database);
		expect(file_exists($database))->toBeTruthy();

		$content = file_get_contents($database);

		expect(str_contains($content, 'CREATE TABLE `users`'))->toBeTruthy();

		expect(
			str_contains($content, "INSERT INTO `users` VALUES (1,'Shellie Wolfe','(275) 245-4857','Turkey','scelerisque.lorem@outlook.couk')")
		)->toBeTruthy();

		unlink($database);
	});

	it('should process exportation and register hooks', function() {
		$exporter  = new Exporter('database', pdo());

		$exporter->onTableExport(function($table, $rowCount) {
			echo "Exporting table $table with $rowCount rows\n";
		});
		$database = __DIR__ . '/database.sql';

		expect(fn() => $exporter->process($database))->toEcho("Exporting table users with 5 rows\n");

		unlink($database);
	});
});

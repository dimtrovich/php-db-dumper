<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	// identifier: return.type
	'message' => '#^Method Dimtrovich\\\\DbDumper\\\\Adapters\\\\Factory\\:\\:create\\(\\) should return static\\(Dimtrovich\\\\DbDumper\\\\Adapters\\\\Factory\\) but returns object\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Adapters/Factory.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @method has invalid value \\(void onTableExport\\(callable\\(string \\$tableName, int \\$rowCount\\) \\$callback\\)\\)\\: Unexpected token "\\(", expected variable at offset 42$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Exporter.php',
];
$ignoreErrors[] = [
	// identifier: booleanOr.alwaysTrue
	'message' => '#^Result of \\|\\| is always true\\.$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Exporter.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @method has invalid value \\(void onTableCreate\\(callable\\(string \\$tableName\\) \\$callback\\)\\)\\: Unexpected token "\\(", expected variable at offset 42$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Importer.php',
];
$ignoreErrors[] = [
	// identifier: phpDoc.parseError
	'message' => '#^PHPDoc tag @method has invalid value \\(void onTableInsert\\(callable\\(string \\$tableName, int \\$rowCount\\) \\$callback\\)\\)\\: Unexpected token "\\(", expected variable at offset 111$#',
	'count' => 1,
	'path' => __DIR__ . '/src/Importer.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];

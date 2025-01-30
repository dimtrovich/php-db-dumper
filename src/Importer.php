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

use Dimtrovich\DbDumper\Compressor\Factory as CompressorFactory;
use Dimtrovich\DbDumper\Exceptions\Exception;
use PDOException;

/**
 * @method void onTableCreate(callable(string $tableName) $callback)
 * @method void onTableInsert(callable(string $tableName, int $rowCount) $callback)
 */
class Importer
{
    use Dumper;

    /**
     * Primary function, triggers restoration.
     *
     * @param string $filename Name of file to read sql dump to
     */
    public function process(string $filename)
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        $this->compressor = match ($extension) {
            'gz' , 'gzip' => CompressorFactory::create(Option::COMPRESSION_GZIP),
            'bz2', 'bzip2' => CompressorFactory::create(Option::COMPRESSION_BZIP2),
            'sql'   => CompressorFactory::create(Option::COMPRESSION_NONE),
            default => throw Exception::unavailableDriverForcompression($extension),
        };

        $filename = $this->getFile($filename);

        if ($this->option->disable_foreign_keys_check) {
            $this->pdo->exec('SET foreign_key_checks = 0');
        }

        /**
         * Read backup file line by line
         */
        $handle = fopen($filename, 'rb');

        if (! $handle) {
            throw Exception::failledToRead($filename);
        }

        $buffer = '';

        try {
            while (! feof($handle)) {
                $line = fgets($handle);

                if (substr($line, 0, 2) === '--' || ! $line) {
                    continue; // skip comments
                }

                $buffer .= $line;

                // if it has a semicolon at the end, it's the end of the query
                if (';' === substr(rtrim($line), -1, 1)) {
                    if (false !== $affectedRows = $this->pdo->exec($buffer)) {
                        if (preg_match('/^CREATE TABLE `([^`]+)`/i', $buffer, $tableName)) {
                            $this->event->emit('table.create', $tableName[1]);
                        }
                        if (preg_match('/^INSERT INTO `([^`]+)`/i', $buffer, $tableName)) {
                            $this->event->emit('table.insert', $tableName[1], $affectedRows);
                        }
                    }

                    $buffer = '';
                }
            }
        } catch (PDOException $e) {
            throw Exception::pdoException($e->getMessage(), $buffer);
        } finally {
            fclose($handle);
            unlink($filename);
        }

        if ($this->option->disable_foreign_keys_check) {
            $this->pdo->exec('SET foreign_key_checks = 1');
        }
    }

    /**
     * Return unzipped file
     */
    private function getFile(string $source): string
    {
        $pathinfo = pathinfo($source);

        $dest = $pathinfo['dirname'] . '/' . date('Ymd_His', time()) . '_' . $pathinfo['filename'];

        // Remove $dest file if exists
        if (file_exists($dest)) {
            if (! unlink($dest)) {
                return false;
            }
        }

        // Open gzipped and destination files in binary mode
        $this->compressor->open($source, 'rb');
        if (! $dstFile = fopen($dest, 'wb')) {
            return false;
        }

        if (! fwrite($dstFile, $this->compressor->read())) {
            return false;
        }

        fclose($dstFile);
        $this->compressor->close();

        return $dest;
    }
}

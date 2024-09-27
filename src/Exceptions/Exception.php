<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\DbDumper\Exceptions;

use Exception as BaseException;

class Exception extends BaseException
{
    public const COMPRESSOR_DRIVER_MISSING     = 301;
    public const COMPRESSOR_INVALID            = 302;
    public const COMPRESSOR_DRIVER_UNAVAILABLE = 303;
    public const DATABASE_INVALID_ADAPTER      = 401;
    public const DATABASE_TABLE_NOT_FOUND      = 402;
    public const FILE_FAILL_TO_READ            = 501;
    public const FILE_FAILL_TO_WRITE           = 502;
    public const FILE_NOT_WRITABLE             = 503;
    public const PDO_EXCEPTION                 = 1601;

    /**
     * Error meta informations
     */
    private array $meta = [];

    /**
     * Get exception meta informations
     */
    public function getMeta(): array
    {
        return $this->meta;
    }

    public static function invalidCompressor(string $compressor): self
    {
        return new self(sprintf('Compression method "%s" is not defined yet', $compressor), self::COMPRESSOR_INVALID);
    }

    public static function compressionDriverMissing(string $driver): self
    {
        return new self(sprintf('Compression is enabled, but "%s" lib is not installed or configured properly', $driver), self::COMPRESSOR_DRIVER_MISSING);
    }

    public static function unavailableDriverForcompression(string $extension): self
    {
        return new self(sprintf('No driver found for "%s" extension', $extension), self::COMPRESSOR_DRIVER_UNAVAILABLE);
    }

    public static function fileNotWritable(string $filename): self
    {
        return new self(sprintf('File "%s" is not writable.', $filename), self::FILE_NOT_WRITABLE);
    }

    public static function failledToWrite(): self
    {
        return new self('Writting to file failed! Probably, there is no more free space left?', self::FILE_FAILL_TO_WRITE);
    }

    public static function failledToRead($filename): self
    {
        return new self(sprintf('Couldn\'t open backup file %s', $filename), self::FILE_FAILL_TO_READ);
    }

    public static function tableNotFound(string $table): self
    {
        return new self(sprintf('Table "%s not found in database', $table), self::DATABASE_TABLE_NOT_FOUND);
    }

    public static function invalidAdapter(string $adapter): self
    {
        return new self(sprintf('Database type support for "%s" not yet available', $adapter), self::DATABASE_INVALID_ADAPTER);
    }

    public static function pdoException(string $message, string $sql): self
    {
        $message = trim($message);
        $sql     = trim($sql);

        $exception       = new self(sprintf('Error during request "%s" execution. Error: "%s"', $sql, $message), self::PDO_EXCEPTION);
        $exception->meta = compact('message', 'sql');

        return $exception;
    }
}

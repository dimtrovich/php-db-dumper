<?php

namespace Dimtrovich\DbDumper\Exceptions;

use Exception as BaseException;

class Exception extends BaseException
{
	const COMPRESSOR_DRIVER_MISSING     = 301;
	const COMPRESSOR_INVALID            = 302;
	const COMPRESSOR_DRIVER_UNAVAILABLE = 303;
	const DATABASE_INVALID_ADAPTER      = 401;
	const DATABASE_TABLE_NOT_FOUND      = 402;
	const FILE_FAILL_TO_READ            = 501;
	const FILE_FAILL_TO_WRITE           = 502;
	const FILE_NOT_WRITABLE             = 503;

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
}

<?php

namespace Dimtrovich\DbDumper\Compressor;

use Dimtrovich\DbDumper\Exceptions\Exception;

class GzipCompressor extends Factory
{
	/**
	 * @var resource|false
	 */
	private $handler = null;

	public function __construct()
    {
        if (!function_exists("gzopen")) {
			throw Exception::compressionDriverMissing('gzip');
        }
    }

    /**
	 * {@inheritDoc}
	 */
    public function open(string $filename, string $mode = 'wb'): bool
    {
    	$this->handler = gzopen($filename, $mode);
		if (false === $this->handler) {
			throw Exception::fileNotWritable($filename);
		}

		return true;
    }

	/**
	 * {@inheritDoc}
	 */
    public function write(string $data): int
    {
		$bytesWritten = gzwrite($this->handler, $data);

		if (false === $bytesWritten) {
			throw Exception::failledToWrite();
		}

    	return $bytesWritten;
    }

	/**
	 * {@inheritDoc}
	 */
	public function read(): string
	{
		$content = '';

		while (!gzeof($this->handler)) {
            // Read buffer-size bytes
            $content .= gzread($this->handler, 4096); // read 4kb at a time
        }

		return $content;
	}

	/**
	 * {@inheritDoc}
	 */
    public function close(): bool
    {
    	return gzclose($this->handler);
    }
}

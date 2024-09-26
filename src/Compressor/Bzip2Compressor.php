<?php

namespace Dimtrovich\DbDumper\Compressor;

use Dimtrovich\DbDumper\Exceptions\Exception;

class Bzip2Compressor extends Factory
{
	/**
	 * @var resource|false
	 */
	private $handler = null;

	public function __construct()
    {
		if (!function_exists("bzopen")) {
			throw Exception::compressionDriverMissing('bzip2');
        }
    }

    /**
	 * {@inheritDoc}
	 */
    public function open(string $filename, string $mode = 'w'): bool
    {
    	$this->handler = bzopen($filename, $mode);
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
		$bytesWritten = bzwrite($this->handler, $data);

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

		/* while (!bzeof($this->handler)) {
            // Read buffer-size bytes
            $content .= bzread($this->handler, 4096); // read 4kb at a time
        } */

		return $content;
	}

	/**
	 * {@inheritDoc}
	 */
    public function close(): bool
    {
    	return bzclose($this->handler);
    }
}

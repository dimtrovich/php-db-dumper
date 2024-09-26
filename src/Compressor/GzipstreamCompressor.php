<?php

namespace Dimtrovich\DbDumper\Compressor;

use Dimtrovich\DbDumper\Exceptions\Exception;
use DeflateContext;

class GzipstreamCompressor extends Factory
{
	/**
	 * @var resource|false
	 */
	private $handler = null;

    private DeflateContext $context;

    /**
	 * {@inheritDoc}
	 */
    public function open(string $filename, string $mode = 'wb'): bool
    {
    	$this->handler = fopen($filename, $mode);
		if (false === $this->handler) {
			throw Exception::fileNotWritable($filename);
		}

    	$this->context = deflate_init(ZLIB_ENCODING_GZIP, array('level' => 9));

		return true;
    }

	/**
	 * {@inheritDoc}
	 */
    public function write(string $data): int
    {
		$bytesWritten = fwrite($this->handler, deflate_add($this->context, $data, ZLIB_NO_FLUSH));

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

		while (!feof($this->handler)) {
            // Read buffer-size bytes
            $content .= fread($this->handler, 4096); // read 4kb at a time
        }

		return $content;
	}

	/**
	 * {@inheritDoc}
	 */
    public function close(): bool
    {
    	fwrite($this->handler, deflate_add($this->context, '', ZLIB_FINISH));

		return fclose($this->handler);
    }
}

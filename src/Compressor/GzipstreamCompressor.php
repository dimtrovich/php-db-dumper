<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Dimtrovich\DbDumper\Compressor;

use DeflateContext;
use Dimtrovich\DbDumper\Exceptions\Exception;

class GzipstreamCompressor extends Factory
{
    /**
     * @var false|resource
     */
    private $handler;

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

        $this->context = deflate_init(ZLIB_ENCODING_GZIP, ['level' => 9]);

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

        while (! feof($this->handler)) {
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

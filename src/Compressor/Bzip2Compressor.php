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

use Dimtrovich\DbDumper\Exceptions\Exception;

class Bzip2Compressor extends Factory
{
    /**
     * @var false|resource
     */
    private $handler;

    public function __construct()
    {
        if (! function_exists('bzopen')) {
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
        return '';
        /* while (!bzeof($this->handler)) {
            // Read buffer-size bytes
            $content .= bzread($this->handler, 4096); // read 4kb at a time
        } */
    }

    /**
     * {@inheritDoc}
     */
    public function close(): bool
    {
        return bzclose($this->handler);
    }
}

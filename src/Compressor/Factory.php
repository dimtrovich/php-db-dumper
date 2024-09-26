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

abstract class Factory
{
    /**
     * Init instance of compressor
     *
     * @internal
     */
    public static function create(string $compressor): self
    {
        $class = __NAMESPACE__ . '\\' . ucfirst(strtolower($compressor)) . 'Compressor';

        if (! class_exists($class) || $class === self::class) {
            throw Exception::invalidCompressor($compressor);
        }

        return new $class();
    }

    /**
     * Open compression buffer
     */
    abstract public function open(string $filename, string $mode = 'wb'): bool;

    /**
     * Write data on compression buffer
     */
    abstract public function write(string $data): int;

    /**
     * Read data on compression buffer
     */
    abstract public function read(): string;

    /**
     * Close compression buffer
     */
    abstract public function close(): bool;
}

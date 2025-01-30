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

use BadMethodCallException;
use Dimtrovich\DbDumper\Adapters\Factory as AdapterFactory;
use Dimtrovich\DbDumper\Compressor\Factory as CompressorFactory;
use PDO;

trait Dumper
{
    /**
     * Configuration options
     */
    private Option $option;

    /**
     * Compression manager
     */
    private CompressorFactory $compressor;

    /**
     * Database adapter
     */
    private AdapterFactory $adapter;

    /**
     * Event manager
     */
    private Event $event;

    /**
     * The cache of dot-cased words.
     */
    protected static $dotCache = [];

    public function __construct(private string $database, private PDO $pdo, array $options = [])
    {
        $this->option     = new Option($options);
        $this->event      = new Event();
        $this->compressor = CompressorFactory::create($this->option->compress);
        $this->adapter    = AdapterFactory::create($pdo, $this->option);

        if ($pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            // This drops MYSQL dependency, only use the constant if it's defined.
            $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
        }

        // Execute init commands once connected
        foreach ($this->option->init_commands as $stmt) {
            $pdo->exec($stmt);
        }

        $pdo->setAttribute(PDO::ATTR_ORACLE_NULLS, PDO::NULL_NATURAL);
    }

    /**
     * Get Dumper configurations option
     */
    public function getOption(): Option
    {
        return $this->option;
    }

    /**
     * Set Dumper configurations option
     */
    public function setOption(array|Option $option): static
    {
        if ($option instanceof Option) {
            $this->option = $option;
        } else {
            $this->option->setOptions($option);
        }

        return $this;
    }

    public function __call($name, $args)
    {
        if (str_starts_with($name, 'on')) {
            $name = static::toDot(substr($name, 2));

            $this->event->on($name, array_shift($args));

            return;
        }

        throw new BadMethodCallException(sprintf('Method "%s" is not allowed to be called on "%s"', $name, static::class));
    }

    public static function toDot(string $value): string
    {
        $key = $value;

        if (isset(static::$dotCache[$key])) {
            return static::$dotCache[$key];
        }

        if (! ctype_lower($value)) {
            $value = preg_replace('/\s+/u', '', ucwords($value));

            $value = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $value));
        }

        return static::$dotCache[$key] = str_replace('_', '.', $value);
    }
}

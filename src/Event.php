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

class Event
{
    /**
     * @var array<string, callable[]>
     */
    private array $listeners = [];

    /**
     * Register a listener for event
     */
    public function on(string $event, callable $callable): void
    {
        if (! array_key_exists($event, $this->listeners)) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $callable;
    }

    /**
     * Trigger event
     */
    public function emit(string $event, ...$args)
    {
        $listeners = $this->listeners[$event] ?? [];

        foreach ($listeners as $callable) {
            $callable(...$args);
        }
    }
}

<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\DbDumper\Event;

use function Kahlan\expect;

describe('Event', function () {
    it('should be instantiable', function () {
        $event = new Event();
        expect($event)->toBeAnInstanceOf(Event::class);
    });

    it('should register an event', function () {
        $event = new Event();
        $event->on('test', function () {});

        $reflection = new ReflectionClass($event);
        $listeners  = $reflection->getProperty('listeners');
        $listeners->setAccessible(true);
        expect($listeners->getValue($event))->toBeA('array');
        expect($listeners->getValue($event))->toContainKey('test');
    });

    it('should emit an event', function () {
        $event = new Event();
        $event->on('test', function () {
            echo 'hello world';
        });

        expect(fn () => $event->emit('test'))->toEcho('hello world');
    });
});

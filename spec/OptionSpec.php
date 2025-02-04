<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\DbDumper\Option;

use function Kahlan\expect;

describe('Option', function () {
    it('should be instantiable', function () {
        $option = new Option([]);
        expect($option)->toBeAnInstanceOf(Option::class);
    });

    it('should set an existant option', function () {
        $option = new Option(['skip_tz_utc' => true]);
        expect($option->skip_tz_utc)->toBe(true);
    });

    it('should set an undefined option ', function () {
        $option                      = new Option([]);
        $option->non_existent_option = 'test';
        expect($option->non_existent_option)->toBe('test');
    });

    it('should ignore numeric key as option ', function () {
        $option = new Option(['non_existent_option']);
        expect($option->non_existent_option)->toBeNull();
    });
});

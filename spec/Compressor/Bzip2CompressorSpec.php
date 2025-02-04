<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\DbDumper\Compressor\Bzip2Compressor;
use Dimtrovich\DbDumper\Exceptions\Exception;

use function Kahlan\expect;

describe('Compressor\Bzip2Compressor', function () {
    it('should be instantiable', function () {
        if (! function_exists('bzopen')) {
            expect(fn () => new Bzip2Compressor())
                ->toThrow(Exception::compressionDriverMissing('bzip2'));
        } else {
            $bzip2Compressor = new Bzip2Compressor();
            expect($bzip2Compressor)->toBeAnInstanceOf(Bzip2Compressor::class);
        }
    });

    // Add more specific tests for Bzip2Compressor methods here
});

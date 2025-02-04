<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\DbDumper\Compressor\GzipCompressor;
use Dimtrovich\DbDumper\Exceptions\Exception;

use function Kahlan\expect;

describe('Compressor\GzipCompressor', function () {
    it('should be instantiable', function () {
        if (! function_exists('gzopen')) {
            expect(fn () => new GzipCompressor())
                ->toThrow(Exception::compressionDriverMissing('gzip'));
        } else {
            $gzipCompressor = new GzipCompressor();
            expect($gzipCompressor)->toBeAnInstanceOf(GzipCompressor::class);
        }
    });

    // Add more specific tests for GzipCompressor methods here
});

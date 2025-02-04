<?php

/**
 * This file is part of dimtrovich/db-dumper".
 *
 * (c) 2024 Dimitri Sitchet Tomkeu <devcode.dst@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

use Dimtrovich\DbDumper\Compressor\GzipstreamCompressor;

use function Kahlan\expect;

describe('Compressor\GzipstreamCompressor', function () {
    it('should be instantiable', function () {
        $gzipstreamCompressor = new GzipstreamCompressor();
        expect($gzipstreamCompressor)->toBeAnInstanceOf(GzipstreamCompressor::class);
    });

    // Add more specific tests for GzipstreamCompressor methods here
});

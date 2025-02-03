<?php

use Dimtrovich\DbDumper\Compressor\GzipCompressor;
use Dimtrovich\DbDumper\Exceptions\Exception;

use function Kahlan\expect;

describe('Compressor\GzipCompressor', function() {
	it('should be instantiable', function() {
		if (! function_exists('gzopen')) {
			expect(fn() => new GzipCompressor())
				->toThrow(Exception::compressionDriverMissing('gzip'));
		} else {
			$gzipCompressor = new GzipCompressor();
			expect($gzipCompressor)->toBeAnInstanceOf(GzipCompressor::class);
		}
	});

	// Add more specific tests for GzipCompressor methods here
});

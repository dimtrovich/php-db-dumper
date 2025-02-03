<?php

use Dimtrovich\DbDumper\Compressor\Bzip2Compressor;
use Dimtrovich\DbDumper\Exceptions\Exception;

use function Kahlan\expect;

describe('Compressor\Bzip2Compressor', function() {
	it('should be instantiable', function() {
		if (! function_exists('bzopen')) {
			expect(fn() => new BZip2Compressor())
				->toThrow(Exception::compressionDriverMissing('bzip2'));
		} else {
			$bzip2Compressor = new Bzip2Compressor();
			expect($bzip2Compressor)->toBeAnInstanceOf(Bzip2Compressor::class);
		}
	});

	// Add more specific tests for Bzip2Compressor methods here
});

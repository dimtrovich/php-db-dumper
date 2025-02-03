<?php

use Dimtrovich\DbDumper\Compressor\GzipstreamCompressor;

use function Kahlan\expect;

describe('Compressor\GzipstreamCompressor', function() {
	it('should be instantiable', function() {
		$gzipstreamCompressor = new GzipstreamCompressor();
		expect($gzipstreamCompressor)->toBeAnInstanceOf(GzipstreamCompressor::class);
	});

	// Add more specific tests for GzipstreamCompressor methods here
});

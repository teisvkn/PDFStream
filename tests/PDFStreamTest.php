<?php

class PDFStreamTest extends PHPUnit_Framework_TestCase
{
	public function testCanParseStream() {
		$pdfStreamsDirectory = __DIR__.'/data/output/pdfstreams';

		if (!is_dir($pdfStreamsDirectory )) {
			mkdir($pdfStreamsDirectory );
		}

		$pdfStream = new TeisVKN\PhpPdfStream\PDFStream($pdfStreamsDirectory);

		$pdfStream->extractStreams(
			fopen(__DIR__.'/data/test.pdf', 'r'),
			fopen(__DIR__.'/data/output/test.pdf.stripped', 'w')
		);
	}
}
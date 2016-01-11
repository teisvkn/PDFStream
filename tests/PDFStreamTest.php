<?php

class PDFStreamTest extends PHPUnit_Framework_TestCase
{
	public function testCanParseStream() {
		$pdfStream = new TeisVKN\PhpPdfStream\PDFStream(__DIR__.'/pdfstreams');

		$pdfStream->extractStreams(
			fopen(__DIR__.'/data/test.pdf', 'r'),
			fopen(__DIR__.'/data/output/test.pdf.stripped', 'w')
		);
	}
}
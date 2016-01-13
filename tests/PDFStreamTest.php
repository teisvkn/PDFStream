<?php

class PDFStreamTest extends PHPUnit_Framework_TestCase
{
	protected $pdfStream;
	protected $pdfStreamsDirectory;

	/**
	 * PDFStreamTest constructor.
	 */
	public function __construct() {
		$this->pdfStreamsDirectory = __DIR__.'/data/output/pdfstreams';

		if (!is_dir($this->pdfStreamsDirectory )) {
			mkdir($this->pdfStreamsDirectory, 0777, true);
		}

		$this->pdfStream = new TeisVKN\PhpPdfStream\PDFStream($this->pdfStreamsDirectory);;
	}


	public function testCanExtract() {
		// extract pdf-streams of data/test.pdf to data/output/pdfstreams/ directory,
		// and write the remaining parts of the pdf to data/output/test.pdf.stripped
		$this->pdfStream->extract(
			$srcResource = fopen(__DIR__.'/data/test.pdf', 'r'),
			$dstResource = fopen(__DIR__.'/data/output/test.pdf.stripped', 'w')
		);

		fclose($srcResource);
		fclose($dstResource);
	}

	/** @depends testCanExtract */
	public function testCanRestore() {
		// read the data/output/test.pdf.stripped, and restore it to data/output/test.restored.pdf
		// by gluing back in the extracted pdf-streams from data/output/pdfstreams/ directory.
		$this->pdfStream->restore(
			$srcResource = fopen(__DIR__.'/data/output/test.pdf.stripped', 'r'),
			$dstResource = fopen(__DIR__.'/data/output/test.restored.pdf', 'w+') // must be w+ to enable md5 validation
		);

		fclose($srcResource);
		fclose($dstResource);
	}

}
<?php

namespace TeisVKN\PhpPdfStream;

class PDFStream
{
	private $pdfStreamDirectory;
	private $pdfStreamResource;

	/**
	 * PdfStream constructor.
	 * @param $pdfStreamDirectory
	 */
	public function __construct($pdfStreamDirectory) {
		$this->pdfStreamDirectory = $pdfStreamDirectory;
	}


	/**
	 * @param resource $srcResource
	 * @param resource $dstResource
	 * @throws Exception
	 */
	public function extractStreams($srcResource, $dstResource) {
		if (!$this->isValidResource($srcResource)) {
			throw new Exception('$srcResource is not a valid stream resource');
		}

		if (!$this->isValidResource($dstResource)) {
			throw new Exception('$dstResource is not a valid stream resource');
		}

		while (($buffer = fgets($srcResource)) !== false) {
			$this->readPdfStreamBegin($dstResource, $buffer) ||
			$this->readPdfStreamEnd($dstResource, $buffer) ||
			$this->readPdfStream($buffer) ||
			$this->readPdfContent($dstResource, $buffer);
		}
	}


	/**
	 * @param $resource
	 * @return bool
	 */
	private function isValidResource($resource) {
		return is_resource($resource) && get_resource_type($resource) === 'stream';
	}


	/**
	 * @param $dstResource
	 * @param $buffer
	 * @return bool
	 */
	private function readPdfStreamBegin($dstResource, $buffer) {
		// no pdfStreamResource is currently open (being read),
		// and we have reached the beginning of a new pdf-stream
		if (!is_resource($this->pdfStreamResource) && preg_match('/stream\s$/', $buffer)) {

			// write the line to the dstResource
			fwrite($dstResource, $buffer);

			// open a new temp memory/temp-file-resource for the pdf-stream-content
			$this->pdfStreamResource = fopen('php://temp', 'w+');

			return true;
		}

		return false;
	}

	/**
	 * @param $dstResource
	 * @param $buffer
	 * @return bool
	 */
	private function readPdfStreamEnd($dstResource, $buffer) {
		// a pdfStreamResource is currently open (is being read),
		// and we have now reached the end of it
		if (is_resource($this->pdfStreamResource) && preg_match('/^endstream\s$/', $buffer)) {

			// write the extracted pdf-stream-content to a file, and get the filename
			$fileName = $this->writePdfStreamToStreamDirectory();
			fclose($this->pdfStreamResource);

			// write the fileName of the extracted pdf-stream to the dstResource
			fwrite($dstResource, $fileName.PHP_EOL);

			// write the endstream line
			fwrite($dstResource, $buffer);
			return true;
		}
		return false;
	}

	/**
	 * @param $buffer
	 * @return bool
	 */
	private function readPdfStream($buffer) {
		// a pdfStreamResource is open (we are reading content of a pdf-stream)
		if (is_resource($this->pdfStreamResource)) {
			// write the buffer to the pdfStreamResource
			fwrite($this->pdfStreamResource, $buffer);
			return true;
		}
		return false;
	}

	/**
	 * @param $dstResource
	 * @param $buffer
	 * @return bool
	 */
	private function readPdfContent($dstResource, $buffer) {
		fwrite($dstResource, $buffer);
		return true;
	}

	/**
	 * @return string filename, being the md5sum of the pdf-stream
	 * @throws Exception
	 */
	private function writePdfStreamToStreamDirectory() {
		rewind($this->pdfStreamResource);
		$pdfStreamMd5 = md5(stream_get_contents($this->pdfStreamResource));

		rewind($this->pdfStreamResource);
		$pdfStreamFileName = sprintf('%s/%s',$this->pdfStreamDirectory, $pdfStreamMd5);

		if (file_put_contents($pdfStreamFileName, $this->pdfStreamResource) === false) {
			throw new Exception(sprintf('Error occurred when writing pdfStream to %s', $pdfStreamFileName));
		}

		return $pdfStreamMd5;
	}


}
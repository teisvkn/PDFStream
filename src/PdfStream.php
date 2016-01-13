<?php

namespace TeisVKN\PhpPdfStream;

class PDFStream
{
	const MODE_EXTRACT = 'MODE_EXTRACT';
	const MODE_RESTORE = 'MODE_RESTORE';

	/**
	 * @var resource
	 */
	private $pdfStreamResource;

	/**
	 * @var string
	 */
	private $pdfStreamDirectory;

	/**
	 * PdfStream constructor.
	 * @param $pdfStreamDirectory
	 */
	public function __construct($pdfStreamDirectory) {
		$this->pdfStreamDirectory = $pdfStreamDirectory;
	}


	/**
	 * Extracts the pdf-streams from the srcResource, to the streamsDirectory,
	 * and writes the remaining PDF content to $dstResource
	 * @param resource $srcResource
	 * @param resource $dstResource
	 * @throws Exception
	 */
	public function extract($srcResource, $dstResource) {
		if (!$this->isValidResource($srcResource)) {
			throw new Exception('$srcResource is not a valid stream resource');
		}

		if (!$this->isValidResource($dstResource)) {
			throw new Exception('$dstResource is not a valid stream resource');
		}

		// write the md5sum of the original file, on first line
		$this->writeSrcResourceMd5Sum($srcResource, $dstResource);

		while (($buffer = fgets($srcResource)) !== false) {
			$this->readPdfStreamBegin($dstResource, $buffer) ||
			$this->readPdfStreamEnd($dstResource, $buffer) ||
			$this->readPdfStream($buffer) ||
			$this->readPdfContent($dstResource, $buffer);
		}
	}


	/**
	 * Restores the stripped pdf from srcResource, to dstResource by gluing back the extracted
	 * pdf-streams.
	 * @param $srcResource
	 * @param $dstResource
	 * @throws Exception
	 */
	public function restore($srcResource, $dstResource) {
		if (!$this->isValidResource($srcResource)) {
			throw new Exception('$srcResource is not a valid stream resource');
		}

		if (!$this->isValidResource($dstResource)) {
			throw new Exception('$dstResource is not a valid stream resource');
		}

		$originalMd5 = $this->readOriginalMd5Sum($srcResource);

		while (($buffer = fgets($srcResource)) !== false) {
			$this->readPdfStreamBegin($dstResource, $buffer) ||
			$this->readPdfStreamEnd($dstResource, $buffer, self::MODE_RESTORE) ||
			$this->readPdfStream($buffer) ||
			$this->readPdfContent($dstResource, $buffer);
		}

		$this->validateRestored($dstResource, $originalMd5);
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
	private function readPdfStreamEnd($dstResource, $buffer, $mode = self::MODE_EXTRACT) {
		// a pdfStreamResource is currently open (is being read),
		// and we have now reached the end of it
		if (is_resource($this->pdfStreamResource) && preg_match('/^endstream\s$/', $buffer)) {

			switch ($mode) {
				case self::MODE_EXTRACT:
					// $this->pdfStreamResource contains the extracted pdf-stream.
					// Write the extracted pdf-stream-content to a file, and get the filename
					$fileName = $this->writePdfStreamToStreamDirectory();
					// write the fileName of the extracted pdf-stream to the dstResource
					fwrite($dstResource, $fileName.PHP_EOL);
					break;

				case self::MODE_RESTORE:
					// $this->pdfStreamResource contains the file reference of extracted pdf-stream.
					// Write the content of extracted pdf-stream back to $dstResource
					$this->restorePdfStream($dstResource);
					break;
			}


			fclose($this->pdfStreamResource);


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
		$pdfStreamFilePath = sprintf('%s/%s', $this->pdfStreamDirectory, $pdfStreamMd5);
		$pdfStreamFileResource = fopen($pdfStreamFilePath, 'w');

		if ($pdfStreamFileResource === false) {
			throw new Exception(sprintf('Could not write pdf-stream to file %s', $pdfStreamFilePath));
		}

		stream_copy_to_stream($this->pdfStreamResource, $pdfStreamFileResource);

		return $pdfStreamMd5;
	}

	/**
	 * Calculate md5 of srcResource and write it to dstResource
	 * @param $srcResource
	 * @param $dstResource
	 */
	private function writeSrcResourceMd5Sum($srcResource, $dstResource) {
		fwrite(
			$dstResource,
			'originalMd5:' . md5(stream_get_contents($srcResource)) . PHP_EOL
		);
		rewind($srcResource);
	}

	/**
	 * @param $srcResource
	 * @return string
	 * @throws Exception
	 */
	private function readOriginalMd5Sum($srcResource) {
		$buffer = fgets($srcResource);

		if (!preg_match('/^originalMd5:(?<md5sum>[a-f0-9]{32})$/', $buffer, $match)) {
			throw new Exception('Can\'t read originalMd5 sum from $srcResource');
		}

		return $match['md5sum'];
	}

	/**
	 * @param $dstResource
	 * @throws Exception
	 */
	private function restorePdfStream($dstResource) {
		rewind($this->pdfStreamResource);
		$pdfStreamFilePath = sprintf('%s/%s', $this->pdfStreamDirectory, trim(stream_get_contents($this->pdfStreamResource)));
		$pdfStreamResource = fopen($pdfStreamFilePath, 'r');

		if ($pdfStreamResource === false) {
			throw new Exception(sprintf('Could not read extracted pdf-stream %s', $pdfStreamFilePath));
		}

		stream_copy_to_stream($pdfStreamResource, $dstResource);
		fclose($pdfStreamResource);
	}

	/**
	 * @param $dstResource
	 * @param $originalMd5
	 * @throws Exception
	 */
	private function validateRestored($dstResource, $originalMd5) {
		rewind($dstResource);

		if (md5(stream_get_contents($dstResource)) !== $originalMd5) {
			throw new Exception('MD5 sum of restored file does not match original');
		}
	}


}
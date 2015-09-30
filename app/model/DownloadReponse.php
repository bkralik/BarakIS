<?php

namespace App\Model;

use Nette;


/**
 * Download response.
 *
 * @property-read string $file
 * @property-read string $name
 * @property-read string $contentType
 */
class DownloadResponse extends Nette\Object implements Nette\Application\IResponse
{
	/** @var string */
	private $data;

	/** @var string */
	private $contentType;

	/** @var string */
	private $name;

	/** @var bool */
	public $resuming = FALSE;

	/** @var bool */
	private $forceDownload;


	/**
	 * @param  string  data
	 * @param  string  imposed file name
	 * @param  string  MIME content type
	 */
	public function __construct($data, $name = "file", $contentType = NULL, $forceDownload = TRUE)
	{
		/*if (!is_file($file)) {
			throw new Nette\Application\BadRequestException("File '$file' doesn't exist.");
		}*/

		$this->data = $data;
		$this->name = $name;;
		$this->contentType = $contentType ? $contentType : 'application/octet-stream';
		$this->forceDownload = $forceDownload;
	}


	/**
	 * Returns data to download.
	 * @return string
	 */
	public function getData()
	{
		return $this->data;
	}


	/**
	 * Returns the file name.
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}


	/**
	 * Returns the MIME content type of a downloaded file.
	 * @return string
	 */
	public function getContentType()
	{
		return $this->contentType;
	}


	/**
	 * Sends response to output.
	 * @return void
	 */
	public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse)
	{
		$httpResponse->setContentType($this->contentType);
		$httpResponse->setHeader('Content-Disposition',
			($this->forceDownload ? 'attachment' : 'inline')
				. '; filename="' . $this->name . '"'
				. '; filename*=utf-8\'\'' . rawurlencode($this->name));

		$length = strlen($this->data);

        /*
		if ($this->resuming) {
			$httpResponse->setHeader('Accept-Ranges', 'bytes');
			if (preg_match('#^bytes=(\d*)-(\d*)\z#', $httpRequest->getHeader('Range'), $matches)) {
				list(, $start, $end) = $matches;
				if ($start === '') {
					$start = max(0, $filesize - $end);
					$end = $filesize - 1;

				} elseif ($end === '' || $end > $filesize - 1) {
					$end = $filesize - 1;
				}
				if ($end < $start) {
					$httpResponse->setCode(416); // requested range not satisfiable
					return;
				}

				$httpResponse->setCode(206);
				$httpResponse->setHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $filesize);
				$length = $end - $start + 1;
				fseek($handle, $start);

			} else {
				$httpResponse->setHeader('Content-Range', 'bytes 0-' . ($filesize - 1) . '/' . $filesize);
			}
		}*/

		$httpResponse->setHeader('Content-Length', $length);
		echo($this->data);
	}

}

<?php

namespace Vojir\Responses\TempFileResponse;

use Nette;

/**
 * Class TempFileResponse
 * @package Vojir\Responses\TempFileResponse
 * @author Stanislav Vojíř
 */
final class TempFileResponse implements Nette\Application\IResponse{
  use Nette\SmartObject;

  /** @var bool */
  public $resuming = true;

  /** @var string */
  private $file;

  /** @var string */
  private $contentType;

  /** @var string */
  private $name;

  /** @var bool */
  private $forceDownload;

  /**
   * TempFileResponse constructor.
   * @param string $file
   * @param string|null $name = null
   * @param string|null $contentType = null
   * @param bool $forceDownload = true
   * @throws Nette\Application\BadRequestException
   */
  public function __construct(string $file, string $name = null, string $contentType = null, bool $forceDownload = true){
    if (!is_file($file)) {
      throw new Nette\Application\BadRequestException("File '$file' doesn't exist.");
    }

    $this->file = $file;
    $this->name = $name ?? basename($file);
    $this->contentType = $contentType ?: 'application/octet-stream';
    $this->forceDownload = $forceDownload;
  }


  /**
   * Returns the path to a downloaded file.
   */
  public function getFile(): string{
    return $this->file;
  }


  /**
   * Returns the file name.
   */
  public function getName(): string{
    return $this->name;
  }


  /**
   * Returns the MIME content type of a downloaded file.
   */
  public function getContentType(): string{
    return $this->contentType;
  }


  /**
   * Sends response to output.
   */
  public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse): void{
    $httpResponse->setContentType($this->contentType);
    $httpResponse->setHeader('Content-Disposition',
      ($this->forceDownload ? 'attachment' : 'inline')
      . '; filename="' . $this->name . '"'
      . '; filename*=utf-8\'\'' . rawurlencode($this->name));

    $filesize = $length = filesize($this->file);
    $handle = fopen($this->file, 'r');

    if ($this->resuming) {
      $httpResponse->setHeader('Accept-Ranges', 'bytes');
      if (preg_match('#^bytes=(\d*)-(\d*)\z#', (string) $httpRequest->getHeader('Range'), $matches)) {
        [, $start, $end] = $matches;
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
        fseek($handle, (int) $start);

      } else {
        $httpResponse->setHeader('Content-Range', 'bytes 0-' . ($filesize - 1) . '/' . $filesize);
      }
    }

    $httpResponse->setHeader('Content-Length', (string) $length);
    while (!feof($handle) && $length > 0) {
      echo $s = fread($handle, min(4000000, $length));
      $length -= strlen($s);
    }
    fclose($handle);

    //delete temp file
    unlink($this->file);
  }

}

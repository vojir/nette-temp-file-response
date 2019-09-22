<?php

namespace Vojir\Responses\CsvResponse;

use Nette;
use Nette\SmartObject;

/**
 * Class TempFileResponse
 * @package Vojir\Responses\CsvResponse
 * @author Stanislav Vojíř
 */
class TempFileResponse extends Nette\Application\Responses\FileResponse implements Nette\Application\IResponse{
  /** @var string $file */
  private $file;

  /**
   * TempFileResponse constructor.
   * @param string $file
   * @param string|null $name = null
   * @param string|null $contentType = null
   * @param bool $forceDownload = true
   * @throws Nette\Application\BadRequestException
   */
  public function __construct($file, $name = null, $contentType = null, $forceDownload = true){
    parent::__construct($file, $name, $contentType, $forceDownload);
    $this->file=$file;
  }

  public function send(Nette\Http\IRequest $httpRequest, Nette\Http\IResponse $httpResponse){
    parent::send($httpRequest, $httpResponse);
    unlink($this->file);
  }

}

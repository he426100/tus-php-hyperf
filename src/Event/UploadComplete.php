<?php

namespace Tus\Event;

use Tus\File;
use Tus\Request;
use Tus\Response;

class UploadComplete extends TusEvent
{
    /** @var string */
    public const NAME = 'tus-server.upload.complete';

    /**
     * UploadCompleteEvent constructor.
     *
     * @param File     $file
     * @param Request  $request
     * @param Response $response
     */
    public function __construct(File $file, Request $request, Response $response)
    {
        $this->file     = $file;
        $this->request  = $request;
        $this->response = $response;
    }
}
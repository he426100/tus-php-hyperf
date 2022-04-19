<?php

namespace Tus\Event;

use Tus\File;
use Tus\Request;
use Tus\Response;

class TusEvent
{
    /** @var File */
    protected $file;

    /** @var Request */
    protected $request;

    /** @var Response */
    protected $response;

    /**
     * Get file.
     *
     * @return File
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * Get request.
     *
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get response.
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}

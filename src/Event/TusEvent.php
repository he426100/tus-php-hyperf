<?php

declare(strict_types=1);
/**
 * This file is part of he426100/tus-php-hyperf.
 *
 * @link     https://github.com/he426100/tus-php-hyperf
 * @contact  mrpzx001@gmail.com
 * @license  https://github.com/he426100/tus-php-hyperf/blob/master/LICENSE
 */
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
     */
    public function getFile(): File
    {
        return $this->file;
    }

    /**
     * Get request.
     */
    public function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * Get response.
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}

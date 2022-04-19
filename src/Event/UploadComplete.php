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

class UploadComplete extends TusEvent
{
    /** @var string */
    public const NAME = 'tus-server.upload.complete';

    /**
     * UploadCompleteEvent constructor.
     */
    public function __construct(File $file, Request $request, Response $response)
    {
        $this->file = $file;
        $this->request = $request;
        $this->response = $response;
    }
}

<?php

declare(strict_types=1);
/**
 * This file is part of he426100/tus-php-hyperf.
 *
 * @link     https://github.com/he426100/tus-php-hyperf
 * @contact  mrpzx001@gmail.com
 * @license  https://github.com/he426100/tus-php-hyperf/blob/master/LICENSE
 */
namespace Tus\Middleware;

use Tus\Request;
use Tus\Response;

interface TusMiddlewareInterface
{
    /**
     * Handle request/response.
     *
     * @return mixed
     */
    public function handle(Request $request, Response $response);
}

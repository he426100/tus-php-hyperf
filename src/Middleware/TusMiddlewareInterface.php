<?php

namespace Tus\Middleware;

use Tus\Request;
use Tus\Response;

interface TusMiddlewareInterface
{
    /**
     * Handle request/response.
     *
     * @param Request  $request
     * @param Response $response
     *
     * @return mixed
     */
    public function handle(Request $request, Response $response);
}

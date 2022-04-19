<?php

namespace Tus\Middleware;

use Tus\Request;
use Tus\Response;
use Tus\Tus\Server;

class GlobalHeadersMiddleware implements TusMiddlewareInterface
{
    /**
     * {@inheritDoc}
     */
    public function handle(Request $request, Response $response)
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'Tus-Resumable' => Server::TUS_PROTOCOL_VERSION,
        ];

        $response->setHeaders($headers);
    }
}

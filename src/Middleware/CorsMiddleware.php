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

class CorsMiddleware implements TusMiddlewareInterface
{
    /** @const int 24 hours access control max age header */
    private const HEADER_ACCESS_CONTROL_MAX_AGE = 86400;

    /**
     * {@inheritDoc}
     */
    public function handle(Request $request, Response $response)
    {
        $response->setHeaders([
            'Access-Control-Allow-Origin' => $request->header('Origin'),
            'Access-Control-Allow-Methods' => implode(',', $request->allowedHttpVerbs()),
            'Access-Control-Allow-Headers' => 'Origin, X-Requested-With, Content-Type, Content-Length, Upload-Key, Upload-Checksum, Upload-Length, Upload-Offset, Tus-Version, Tus-Resumable, Upload-Metadata',
            'Access-Control-Expose-Headers' => 'Upload-Key, Upload-Checksum, Upload-Length, Upload-Offset, Upload-Metadata, Tus-Version, Tus-Resumable, Tus-Extension, Location',
            'Access-Control-Max-Age' => self::HEADER_ACCESS_CONTROL_MAX_AGE,
        ]);
    }
}

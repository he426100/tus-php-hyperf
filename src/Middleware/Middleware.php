<?php

namespace Tus\Middleware;

use Tus\Middleware\CorsMiddleware;
use Tus\Middleware\GlobalHeadersMiddleware;
use Tus\Middleware\TusMiddlewareInterface;

class Middleware
{
    /** @var array */
    protected $globalMiddleware = [];

    /**
     * Middleware constructor.
     */
    public function __construct()
    {
        $this->globalMiddleware = [
            GlobalHeadersMiddleware::class => new GlobalHeadersMiddleware(),
            CorsMiddleware::class => new CorsMiddleware(),
        ];
    }

    /**
     * Get registered middleware.
     *
     * @return array
     */
    public function list(): array
    {
        return $this->globalMiddleware;
    }

    /**
     * Set middleware.
     *
     * @param array $middleware
     *
     * @return Middleware
     */
    public function add(...$middleware): self
    {
        foreach ($middleware as $m) {
            if ($m instanceof TusMiddlewareInterface) {
                $this->globalMiddleware[\get_class($m)] = $m;
            } elseif (\is_string($m)) {
                $this->globalMiddleware[$m] = new $m();
            }
        }

        return $this;
    }

    /**
     * Skip middleware.
     *
     * @param array $middleware
     *
     * @return Middleware
     */
    public function skip(...$middleware): self
    {
        foreach ($middleware as $m) {
            unset($this->globalMiddleware[$m]);
        }

        return $this;
    }
}

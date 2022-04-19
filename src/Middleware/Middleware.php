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

<?php

declare(strict_types=1);
/**
 * This file is part of he426100/tus-php-hyperf.
 *
 * @link     https://github.com/he426100/tus-php-hyperf
 * @contact  mrpzx001@gmail.com
 * @license  https://github.com/he426100/tus-php-hyperf/blob/master/LICENSE
 */
namespace Tus\Tus;

use Tus\Cache\Cacheable;
use Tus\Cache\CacheFactory;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class AbstractTus
{
    /** @const string Tus protocol version. */
    public const TUS_PROTOCOL_VERSION = '1.0.0';

    /** @const string Upload type partial. */
    public const UPLOAD_TYPE_PARTIAL = 'partial';

    /** @const string Upload type final. */
    public const UPLOAD_TYPE_FINAL = 'final';

    /** @const string Name separator for partial upload. */
    protected const PARTIAL_UPLOAD_NAME_SEPARATOR = '_';

    /** @const string Upload type normal. */
    protected const UPLOAD_TYPE_NORMAL = 'normal';

    /** @const string Header Content Type */
    protected const HEADER_CONTENT_TYPE = 'application/offset+octet-stream';

    /** @var Cacheable */
    protected $cache;

    /** @var string */
    protected $apiPath = '/files';

    /** @var EventDispatcherInterface */
    protected $dispatcher;

    /**
     * Set cache.
     *
     * @param mixed $cache
     *
     * @throws \ReflectionException
     *
     * @return self
     */
    public function setCache($cache): self
    {
        if (\is_string($cache)) {
            $this->cache = make(CacheFactory::class)->make($cache);
        } elseif ($cache instanceof Cacheable) {
            $this->cache = $cache;
        }

        $prefix = 'tus:' . strtolower((new \ReflectionClass(static::class))->getShortName()) . ':';

        $this->cache->setPrefix($prefix);

        return $this;
    }

    /**
     * Get cache.
     * 
     * @return Cacheable
     */
    public function getCache(): Cacheable
    {
        return $this->cache;
    }

    /**
     * Set API path.
     * 
     * @param string $path
     * 
     * @return self
     */
    public function setApiPath(string $path): self
    {
        $this->apiPath = $path;

        return $this;
    }

    /**
     * Get API path.
     * 
     * @return string
     */
    public function getApiPath(): string
    {
        return $this->apiPath;
    }

    /**
     * Set and get event dispatcher.
     * 
     * @return EventDispatcherInterface
     */
    public function event(): EventDispatcherInterface
    {
        if ( ! $this->dispatcher) {
            $this->dispatcher = make(EventDispatcherInterface::class);
        }
        return $this->dispatcher;
    }

    /**
     * Set event dispatcher.
     *
     * @param EventDispatcherInterface $dispatcher
     *
     * @return self
     */
    public function setDispatcher(EventDispatcherInterface $dispatcher): self
    {
        $this->dispatcher = $dispatcher;

        return $this;
    }
}

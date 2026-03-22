<?php

declare(strict_types=1);
/**
 * This file is part of he426100/tus-php-hyperf.
 *
 * @link     https://github.com/he426100/tus-php-hyperf
 * @contact  mrpzx001@gmail.com
 * @license  https://github.com/he426100/tus-php-hyperf/blob/master/LICENSE
 */
namespace Tus\Cache;

use Carbon\Carbon;
use Hyperf\Cache\Cache;
use Psr\SimpleCache\CacheInterface;

class HyperfStore extends AbstractCache
{
    private const KEY_COLLECTOR_SUFFIX = 'keys';

    /** @var CacheInterface */
    protected $cache;

    /**
     * HyperfStore constructor.
     *
     * @param array $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Get cache.
     */
    public function getCache(): CacheInterface
    {
        return $this->cache;
    }

    /**
     * {@inheritDoc}
     */
    public function get(string $key, bool $withExpired = false)
    {
        $key = $this->formatKey($key);

        $contents = $this->cache->get($key);
        if ($contents !== null) {
            $contents = json_decode($contents, true);
        }

        if ($withExpired) {
            return $contents;
        }

        if (! $contents) {
            return null;
        }

        $isExpired = Carbon::parse($contents['expires_at'])->lt(Carbon::now());

        return $isExpired ? null : $contents;
    }

    /**
     * {@inheritDoc}
     */
    public function set(string $key, $value)
    {
        $key = $this->formatKey($key);
        $contents = $this->get($key) ?? [];

        if (\is_array($value)) {
            $contents = $value + $contents;
        } else {
            $contents[] = $value;
        }

        $isStored = $this->cache->set($key, json_encode($contents));

        if ($isStored) {
            $this->addKey($key);
        }

        return $isStored;
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $key = $this->formatKey($key);

        $isDeleted = $this->cache->delete($key);

        if ($isDeleted) {
            $this->delKey($key);
        }

        return $isDeleted;
    }

    /**
     * {@inheritDoc}
     */
    public function keys(): array
    {
        if (! $this->cache instanceof Cache) {
            return [];
        }

        return $this->cache->keys($this->getKeyCollector());
    }

    private function formatKey(string $key): string
    {
        $prefix = $this->getPrefix();

        if (! str_starts_with($key, $prefix)) {
            return $prefix . $key;
        }

        return $key;
    }

    private function getKeyCollector(): string
    {
        return $this->getPrefix() . self::KEY_COLLECTOR_SUFFIX;
    }

    private function addKey(string $key): void
    {
        if (! $this->cache instanceof Cache) {
            return;
        }

        $this->cache->addKey($this->getKeyCollector(), $key);
    }

    private function delKey(string $key): void
    {
        if (! $this->cache instanceof Cache) {
            return;
        }

        $this->cache->delKey($this->getKeyCollector(), $key);
    }
}

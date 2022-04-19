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
use Psr\SimpleCache\CacheInterface;

class HyperfStore extends AbstractCache
{
    /** @var CacheInterface */
    protected $cache;

    /**
     * HyperfStore constructor.
     *
     * @param array $options
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
        $prefix = $this->getPrefix();

        if (strpos($key, $prefix) === false) {
            $key = $prefix . $key;
        }

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
        $contents = $this->get($key) ?? [];

        if (\is_array($value)) {
            $contents = $value + $contents;
        } else {
            $contents[] = $value;
        }

        return $this->cache->set($this->getPrefix() . $key, json_encode($contents));
    }

    /**
     * {@inheritDoc}
     */
    public function delete(string $key): bool
    {
        $prefix = $this->getPrefix();

        if (strpos($key, $prefix) === false) {
            $key = $prefix . $key;
        }

        return $this->cache->delete([$key]);
    }

    /**
     * {@inheritDoc}
     */
    public function keys(): array
    {
        return $this->cache->keys($this->getPrefix() . '*');
    }
}

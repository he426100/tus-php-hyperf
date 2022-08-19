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

abstract class AbstractCache implements Cacheable
{
    /** @var int TTL in secs (default 1 day) */
    protected $ttl = 86400;

    /** @var string Prefix for cache keys */
    protected $prefix = 'tus:';

    /**
     * Set time to live.
     *
     * @param int $secs
     *
     * @return self
     */
    public function setTtl(int $secs): self
    {
        $this->ttl = $secs;

        return $this;
    }

    /**
     * @return int
     */
    public function getTtl(): int
    {
        return $this->ttl;
    }

    /**
     * Set cache prefix.
     *
     * @param string $prefix
     *
     * @return Cacheable
     */
    public function setPrefix(string $prefix): Cacheable
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Get cache prefix.
     * 
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Delete all keys.
     *
     * @param array $keys
     *
     * @return bool
     */
    public function deleteAll(array $keys): bool
    {
        if (empty($keys)) {
            return false;
        }

        $status = true;

        foreach ($keys as $key) {
            $status = $status && $this->delete($key);
        }

        return $status;
    }
}

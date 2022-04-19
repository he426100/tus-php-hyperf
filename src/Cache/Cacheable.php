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

interface Cacheable
{
    /** @see https://tools.ietf.org/html/rfc7231#section-7.1.1.1 */
    public const RFC_7231 = 'D, d M Y H:i:s \G\M\T';

    /**
     * Get data associated with the key.
     *
     * @return mixed
     */
    public function get(string $key, bool $withExpired = false);

    /**
     * Set data to the given key.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    public function set(string $key, $value);

    /**
     * Delete data associated with the key.
     */
    public function delete(string $key): bool;

    /**
     * Delete all data associated with the keys.
     */
    public function deleteAll(array $keys): bool;

    /**
     * Get time to live.
     */
    public function getTtl(): int;

    /**
     * Get cache keys.
     */
    public function keys(): array;

    /**
     * Set cache prefix.
     */
    public function setPrefix(string $prefix): self;

    /**
     * Get cache prefix.
     */
    public function getPrefix(): string;
}

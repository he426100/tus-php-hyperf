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

use function Hyperf\Support\make;

class CacheFactory
{
    /**
     * Make cache.
     *
     * @param string $type
     * 
     * @return Cacheable
     */
    public function make(string $type = 'file'): Cacheable
    {
        return make(HyperfStore::class);
    }
}

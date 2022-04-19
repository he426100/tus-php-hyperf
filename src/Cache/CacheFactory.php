<?php

namespace Tus\Cache;

use Tus\Cache\HyperfStore;

class CacheFactory
{
    /**
     * Make cache.
     *
     * @return Cacheable
     */
    public function make(): Cacheable
    {
        return make(HyperfStore::class);
    }
}

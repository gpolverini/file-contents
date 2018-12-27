<?php

namespace Psr\SimpleCache;

/**
 * Basic Implementation of CacheInterface.
 */
trait CacheAwareTrait
{
    /**
     * The cache instance.
     *
     * @var CacheInterface
     */
    protected $cache;

    /**
     * Sets a cache.
     *
     * @param CacheInterface $cache
     */
    public function setCache(CacheInterface $cache)
    {
        $this->cache = $cache;
    }
}

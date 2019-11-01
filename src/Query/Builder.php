<?php

namespace AXLMedia\Rememberable\Query;

use AXLMedia\Rememberable\Traits\CacheForGet;
use Illuminate\Database\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    use CacheForGet;

    /**
     * The key that should be used when caching the query.
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * The number of seconds to cache the query.
     *
     * @var int
     */
    protected $cacheTime;

    /**
     * The tags for the query cache.
     *
     * @var array
     */
    protected $cacheTags;

    /**
     * The cache driver to be used.
     *
     * @var string
     */
    protected $cacheDriver;

    /**
     * A cache prefix string.
     *
     * @var string
     */
    protected $cachePrefix = 'rememberable';

    /**
     * The key that should be used when caching the query.
     *
     * @var string
     */
    protected $usePlainKey = false;

    /**
     * Get a unique cache key for the complete query.
     *
     * @param mixed       $appends
     * @param string      $method
     * @param string|null $id
     *
     * @return string
     */
    public function getCacheKey($appends = null, $method = 'get', $id = null)
    {
        return $this->cachePrefix.':'.($this->cacheKey ?: $this->generateCacheKey($appends, $method, $id));
    }

    /**
     * Generate the unique cache key for the query.
     *
     * @param mixed  $appends
     * @param string $method
     *
     * @return string
     */
    public function generateCacheKey($appends = null, $method = 'get', $id = null)
    {
        if ($this->usePlainKey) {
            return  $this->generatePlainCacheKey($appends, $method, $id);
        }

        return hash(
            'sha256',
            $this->generatePlainCacheKey($appends, $method, $id)
        );
    }

    /**
     * Generate the plain unique cache key for the query.
     *
     * @param mixed  $appends
     * @param string $method
     *
     * @return string
     */
    public function generatePlainCacheKey($appends = null, $method = 'get', $id = null)
    {
        $name = $this->connection->getName();

        if ($method === 'count') {
            return $name.$method.$id.serialize($this->getBindings()).$appends;
        }

        return $name.$method.$id.$this->toSql().serialize($this->getBindings()).$appends;
    }

    /**
     * Flush the cache for the current model or a given tag name.
     *
     * @param mixed $cacheTags
     *
     * @return bool
     */
    public function flushCache($cacheTags = null)
    {
        $cache = $this->getCacheDriver();

        if (!method_exists($cache, 'tags')) {
            return false;
        }

        $cacheTags = $cacheTags ?: $this->cacheTags;

        $cache->tags($cacheTags ?: $this->cacheTags)->flush();

        return true;
    }

    /**
     * Get the cache driver.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCacheDriver()
    {
        return app('cache')->driver($this->cacheDriver);
    }

    /**
     * Get the cache object with tags assigned, if applicable.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCache()
    {
        $cache = $this->getCacheDriver();

        return $this->cacheTags ? $cache->tags($this->cacheTags) : $cache;
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param \DateTime|int $seconds
     * @param string        $key
     *
     * @return \AXLMedia\Rememberable\Query\Builder
     */
    public function remember($seconds, $key = null)
    {
        list($this->cacheTime, $this->cacheKey) = [$seconds, $key];

        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @param string $key
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function rememberForever($key = null)
    {
        return $this->remember(-1, $key);
    }

    /**
     * Indicate that the query should not be cached.
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function dontRemember()
    {
        $this->cacheTime = $this->cacheKey = $this->cacheTags = null;

        return $this;
    }

    /**
     * Indicate that the query should not be cached. Alias for dontRemember().
     *
     * @return \Illuminate\Database\Query\Builder|static
     */
    public function doNotRemember()
    {
        return $this->dontRemember();
    }

    /**
     * Set the cache prefix.
     *
     * @param string $prefix
     *
     * @return \AXLMedia\Rememberable\Query\Builder
     */
    public function prefix($prefix)
    {
        $this->cachePrefix = $prefix;

        return $this;
    }

    /**
     * Indicate that the results, if cached, should use the given cache tags.
     *
     * @param array|mixed $cacheTags
     *
     * @return \AXLMedia\Rememberable\Query\Builder
     */
    public function cacheTags($cacheTags)
    {
        $this->cacheTags = $cacheTags;

        return $this;
    }

    /**
     * Indicate that the results, if cached, should use the given cache driver.
     *
     * @param string $cacheDriver
     *
     * @return \AXLMedia\Rememberable\Query\Builder
     */
    public function cacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;

        return $this;
    }

    /**
     * Use a plain key instead of a hashed one in the cache driver.
     *
     * @return \AXLMedia\Rememberable\Query\Builder
     */
    public function plainKey()
    {
        $this->usePlainKey = true;

        return $this;
    }
}

<?php

namespace AXLMedia\Rememberable\Query;

use DateTime;
use Illuminate\Database\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
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
    protected $cacheSeconds;

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
     * A cache prefix.
     *
     * @var string
     */
    protected $cachePrefix = 'rememberable';

    /**
     * Execute the get query statement.
     *
     * @param  array  $columns
     * @return array|static[]
     */
    public function get($columns = ['*'])
    {
        if (! is_null($this->cacheSeconds)) {
            return $this->getCached($columns, 'get');
        }

        return parent::get($columns);
    }

    /**
     * Execute the query and get the first result.
     *
     * @param  array  $columns
     * @return \Illuminate\Database\Eloquent\Model|object|static|null
     */
    public function first($columns = ['*'])
    {
        if (! is_null($this->cacheSeconds)) {
            return $this->getCached($columns, 'first');
        }

        return parent::first($columns);
    }

    /**
     * Execute a query for a single record by ID.
     *
     * @param  int|string  $id
     * @param  array  $columns
     * @return mixed|static
     */
    public function find($id, $columns = ['*'])
    {
        if (! is_null($this->cacheSeconds)) {
            return $this->getCached($columns, 'find');
        }

        return parent::find($id, $columns);
    }

    /**
     * Retrieve the "count" result of the query.
     *
     * @param  string  $columns
     * @return int
     */
    public function count($columns = '*')
    {
        if (! is_null($this->cacheSeconds)) {
            return $this->getCached($columns, 'count');
        }

        return parent::count($columns);
    }

    /**
     * Execute the cached get query statement.
     *
     * @param  array  $columns
     * @param  string  $method
     * @param  string|null  $id
     * @return array
     */
    public function getCached($columns = ['*'], $method = 'get', $id = null)
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        // If the query is requested to be cached, we will cache it using a unique key
        // for this database connection and query statement, including the bindings
        // that are used on this query, providing great convenience when caching.
        $key = $this->getCacheKey(null, $method, $id);

        $seconds = $this->cacheSeconds;

        $cache = $this->getCache();

        $callback = $this->getCacheCallback($columns, $method, $id);

        // If we've been given a DateTime instance or a "seconds" value that is
        // greater than zero then we'll pass it on to the remember method.
        // Otherwise we'll cache it indefinitely.
        if ($seconds instanceof DateTime || $seconds > 0) {
            return $cache->remember($key, $seconds, $callback);
        }

        return $cache->rememberForever($key, $callback);
    }

    /**
     * Execute the pluck query statement.
     *
     * @param  string  $column
     * @param  mixed  $key
     * @return array|static[]
     */
    public function pluck($column, $key = null)
    {
        if (! is_null($this->cacheSeconds)) {
            return $this->pluckCached($column, $key, 'pluck');
        }

        return parent::pluck($column, $key);
    }

    /**
     * Execute the cached pluck query statement.
     *
     * @param  string  $column
     * @param  mixed  $key
     * @return array
     */
    public function pluckCached($column, $key = null, $method = 'get')
    {
        $cacheKey = $this->getCacheKey($column.$key, $method);

        $seconds = $this->cacheSeconds;

        $cache = $this->getCache();

        $callback = $this->pluckCacheCallback($column, $key);

        if ($seconds instanceof DateTime || $seconds > 0) {
            return $cache->remember($cacheKey, $seconds, $callback);
        }

        return $cache->rememberForever($cacheKey, $callback);
    }

    /**
     * Indicate that the query results should be cached.
     *
     * @param  \DateTime|int  $seconds
     * @param  string  $key
     * @return $this
     */
    public function remember($seconds, $key = null)
    {
        list($this->cacheSeconds, $this->cacheKey) = [$seconds, $key];

        return $this;
    }

    /**
     * Indicate that the query results should be cached forever.
     *
     * @param  string  $key
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
        $this->cacheSeconds = $this->cacheKey = $this->cacheTags = null;

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
     * @param  string  $prefix
     * @return $this
     */
    public function prefix($prefix)
    {
        $this->cachePrefix = $prefix;

        return $this;
    }

    /**
     * Indicate that the results, if cached, should use the given cache tags.
     *
     * @param  array|mixed  $cacheTags
     * @return $this
     */
    public function cacheTags($cacheTags)
    {
        $this->cacheTags = $cacheTags;

        return $this;
    }

    /**
     * Indicate that the results, if cached, should use the given cache driver.
     *
     * @param  string  $cacheDriver
     * @return $this
     */
    public function cacheDriver($cacheDriver)
    {
        $this->cacheDriver = $cacheDriver;

        return $this;
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
     * Get the cache driver.
     *
     * @return \Illuminate\Cache\CacheManager
     */
    protected function getCacheDriver()
    {
        return app('cache')->driver($this->cacheDriver);
    }

    /**
     * Get a unique cache key for the complete query.
     *
     * @param  mixed  $appends
     * @param  string  $method
     * @param  string|null  $id
     * @return string
     */
    public function getCacheKey($appends = null, $method = 'get', $id = null)
    {
        return $this->cachePrefix.':'.($this->cacheKey ?: $this->generateCacheKey($appends, $method, $id));
    }

    /**
     * Generate the unique cache key for the query.
     *
     * @param  mixed  $appends
     * @param  string  $method
     * @return string
     */
    public function generateCacheKey($appends = null, $method = 'get', $id = null)
    {
        $name = $this->connection->getName();

        if ($method === 'count') {
            return hash('sha256', $name.$method.$id.serialize($this->getBindings()).$appends);
        } else {
            return hash('sha256', $name.$method.$id.$this->toSql().serialize($this->getBindings()).$appends);
        }
    }

    /**
     * Flush the cache for the current model or a given tag name.
     *
     * @param  mixed  $cacheTags
     * @return bool
     */
    public function flushCache($cacheTags = null)
    {
        $cache = $this->getCacheDriver();

        if (! method_exists($cache, 'tags')) {
            return false;
        }

        $cacheTags = $cacheTags ?: $this->cacheTags;

        $cache->tags($cacheTags)->flush();

        return true;
    }

    /**
     * Get the callback for get queries.
     *
     * @param  array  $columns
     * @param  string  $method
     * @param  string|null  $id
     * @return \Closure
     */
    protected function getCacheCallback($columns, $method = 'get', $id = null)
    {
        return function () use ($columns, $method, $id) {
            $this->cacheSeconds = null;

            if ($id) {
                return $this->{$method}($id, $columns);
            } else {
                return $this->{$method}($columns);
            }
        };
    }

    /**
     * Get the callback for pluck queries.
     *
     * @param  string  $column
     * @param  mixed  $key
     * @return \Closure
     */
    protected function pluckCacheCallback($column, $key = null)
    {
        return function () use ($column, $key) {
            $this->cacheSeconds = null;

            return $this->pluck($column, $key);
        };
    }
}

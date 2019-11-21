<?php

namespace AXLMedia\Rememberable\Traits;

use DateTime;

class CacheUtils
{
    /**
     * Get the cache for get().
     *
     * @param array $columns
     *
     * @return array
     */
    protected function getCacheForGet($columns = ['*'])
    {
        if (is_null($this->columns)) {
            $this->columns = $columns;
        }

        $key = $this->getCacheKey(null, 'get');

        $seconds = $this->cacheTime;
        $cache = $this->getCache();
        $callback = $this->getCacheCallbackForGet($columns);

        if ($seconds instanceof DateTime || $seconds > 0) {
            return $cache->remember($key, $seconds, $callback);
        }

        return $cache->rememberForever($key, $callback);
    }

    /**
     * Get the callback for get() queries.
     *
     * @param array $columns
     *
     * @return \Closure
     */
    protected function getCacheCallbackForGet($columns = ['*'])
    {
        return function () use ($columns) {
            $this->cacheTime = null;

            return $this->get($columns);
        };
    }
}

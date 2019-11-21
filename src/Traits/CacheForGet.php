<?php

namespace AXLMedia\Rememberable\Traits;

trait CacheForGet
{
    use CacheUtils;

    /**
     * {@inheritdoc}
     */
    public function get($columns = ['*'])
    {
        if (! is_null($this->cacheTime)) {
            return $this->getCacheForGet($columns);
        }

        return parent::get($columns);
    }
}

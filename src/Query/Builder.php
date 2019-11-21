<?php

namespace AXLMedia\Rememberable\Query;

use AXLMedia\Rememberable\Traits\CacheForGet;
use Illuminate\Database\Query\Builder as BaseBuilder;

class Builder extends BaseBuilder
{
    use CacheForGet, BuilderUtils;
}

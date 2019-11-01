Laravel Rememberable
===================================

[![Total Downloads](https://poser.pugx.org/axl-media/rememberable/downloads.svg)](https://packagist.org/packages/axl-media/rememberable)
[![Latest Stable Version](https://poser.pugx.org/axl-media/rememberable/v/stable.svg)](https://packagist.org/packages/axl-media/rememberable)
[![Latest Unstable Version](https://poser.pugx.org/axl-media/rememberable/v/unstable.svg)](https://packagist.org/packages/axl-media/rememberable)
[![License](https://poser.pugx.org/axl-media/rememberable/license.svg)](https://packagist.org/packages/axl-media/rememberable)

Rememberable is a package useful for using the caching layer at the query builder level.

```php
// Cache the posts for 1 hour
Post::remember(now()->addHours(1))->get();

// Cache the posts for 24 hours.
Post::remember(24 * 60 * 60)->get();
```

```php
// Get a the first user's posts and remember them for a day.
User::first()->remember(now()->addDay())->posts()->get();

// You can also pass the number of seconds if you like (before Laravel 5.8 this will be interpreted as minutes).
User::first()->remember(60 * 60 * 24)->posts()->get();
```

## Available Methods

- `get()`
- `first()` and `firstOrFail()`
- `find()` and `findOrFail()`
- `count()`

## Installation

```sh
$ composer require axl-media/rememberable
```

You can simply apply the trait to each and every model you wish to use `remember()` on.

```php
<?php
namespace App;

use AXLMedia\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model as Eloquent;

class Post extends Eloquent
{
    use Rememberable;

    ...
}
```

## Usage

Using the remember method is super simple. Just pass the number of seconds you want to store the result of that query in the cache for, and whenever the same query is called within that time frame the result will be pulled from the cache, rather than from the database again.

```php
// Remember the number of users for an hour.
$users = User::remember(60 * 60)->count();
```

### Cache tags

If you want to tag certain queries you can add `cacheTags('tag_name')` to your query. Please notice that cache tags are not supported by all cache drivers.

```php
// Remember the number of users for an hour and tag it with 'user_queries'
User::remember(60 * 60)->cacheTags('user_queries')->count();
```

### Cache prefix

If you want a unique prefix added to the cache key for each of your queries (say, if your cache doesn't support tagging), you can add `prefix('prefix')` to your query.

```php
// Remember the number of users for an hour and prefix the key with 'users'
User::remember(60 * 60)->prefix('users')->count();
```

Alternatively, you can add the `$rememberCachePrefix` property to your model to always use that cache prefix.

### Cache driver

If you want to use a custom cache driver (defined in config/cache.php) you can add `cacheDriver('cacheDriver')` to your query.

```php
// Remember the number of users for an hour using redis as cache driver
User::remember(60 * 60)->cacheDriver('redis')->count();
```

Alternatively, you can add the `$rememberCacheDriver` property to your model to always use that cache driver.

#### Model wide cache tag

You can set a cache tag for all queries of a model by setting the `$rememberCacheTag` property with an unique string that should be used to tag the queries.

### Relationships

Validating works by caching queries on a query-by-query basis. This means that when you perform eager-loading those additional queries will not be cached as well unless explicitly specified. You can do that by using a callback with your eager-loads.

```php
$users = User::where('id', '>', '1')
    ->with([
        'posts' => function ($query) {
            $query->remember(60 * 60);
        }
    ])->remember(60 * 60)->take(5)->get();
```

### Always enable

You can opt-in to cache all queries of a model by setting the `$rememberFor` property with the number of seconds you want to cache results for. Use this feature with caution as it could lead to unexpected behaviour and stale data in your app if you're not familiar with how it works.

### Cache flushing

Based on the architecture of the package it's not possible to delete the cache for a single query. But if you tagged any queries using cache tags, you are able to flush the cache for the tag:

```php
User::flushCache('user_queries');
```

If you used the `$rememberCacheTag` property you can use the method without a parameter and the caches for the tag set by `$rememberCacheTag` are flushed:

```php
User::flushCache();
```
### Skipping cache

If you need to disable cache for a particular query, you can use the `dontRemember` method:

```php
User::latest()->dontRemember()->get();
```

<?php

namespace AXLMedia\Rememberable\Test;

use AXLMedia\Rememberable\Test\Models\Post;
use Cache;

class GetTest extends TestCase
{
    public function test_get()
    {
        $post = factory(Post::class)->create();
        $posts = Post::remember(now()->addHours(1))->get();
        $cache = Cache::get('rememberable:sqlitegetselect * from "posts"a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->id,
            $post->id
        );
    }

    public function test_get_with_columns()
    {
        $post = factory(Post::class)->create();
        $posts = Post::remember(now()->addHours(1))->get(['name']);
        $cache = Cache::get('rememberable:sqlitegetselect "name" from "posts"a:0:{}');

        $this->assertNotNull($cache);

        $this->assertEquals(
            $cache->first()->name,
            $post->name
        );
    }
}

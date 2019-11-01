<?php

namespace AXLMedia\Rememberable\Test\Models;

use Illuminate\Database\Eloquent\Model;
use AXLMedia\Rememberable\Rememberable;

class Post extends Model
{
    use Rememberable;

    protected $rememberUsePlainKey = true;

    protected $fillable = [
        'name',
    ];
}

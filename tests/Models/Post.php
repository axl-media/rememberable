<?php

namespace AXLMedia\Rememberable\Test\Models;

use AXLMedia\Rememberable\Rememberable;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use Rememberable;

    protected $rememberUsePlainKey = true;

    protected $fillable = [
        'name',
    ];
}

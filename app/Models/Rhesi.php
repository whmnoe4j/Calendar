<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rhesi extends Model
{
    protected $table = 'rhesis';

    protected $fillable = [
        'content',
        'author',
        'book',
        'tag_id'
    ];
}

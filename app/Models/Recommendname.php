<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recommendname extends Model
{
    protected $table = 'recommendnames';

    protected $fillable = [
        'value1',
        'value2',
        'num',
        'type'
    ];
}

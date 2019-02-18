<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Explain extends Model
{
    protected $table = 'explain';

    protected $fillable = [
    	'noun',
    	'explain'
    ];
}

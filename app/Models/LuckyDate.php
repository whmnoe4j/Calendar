<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LuckyDate extends Model
{
    protected $table = 'lucky_dates';

    protected $fillable = [
    	'date',
    	'name'
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KXZD extends Model
{
    protected $table = 'kxzd';

    protected $fillable = [
    	'word',
    	'jianti',
    	'fanti',
    	'pinyin',
    	'zhuyin',
    	'jianbu',
    	'jianbi',
    	'jianzong',
    	'fanbu',
    	'fanbi',
    	'fanzong',
    	'jiankxbihua',
    	'fankxbihua',
    	'kxbh',
    	'wb86',
    	'wb98',
    	'cj',
    	'sjhm',
    	'unicode',
    	'hanzibh',
    	'minsu',
    	'zixing',
    	'kxjieshi'
    ];
}

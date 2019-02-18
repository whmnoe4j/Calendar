<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XHZD extends Model
{
    protected $table = 'xhzd';

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
    	'bihua',
    	'wb86',
    	'wb98',
    	'cj',
    	'sjhm',
    	'unicode',
    	'hanzibh',
    	'minsu',
    	'zixing',
    	'xhjieshi',
    	'hzwx'
    ];
}

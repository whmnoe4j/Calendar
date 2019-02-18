<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SHULI extends Model
{
    protected $table = 'shuli';

    protected $fillable = [
    	'description',
    	'ji_xiong',
    	'ji_ye',
    	'jia_ting',
    	'jian_kang',
    	'han_yi',
    	'qian_tu',
    	'cai_yun',
    	'all_description'
    ];
}

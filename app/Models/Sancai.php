<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sancai extends Model
{
   	protected $table = 'sancai';

   	protected $fillable = [
   		'sancai',
   		'number',
   		'description',
   		'ji_xiong'
   	];
}

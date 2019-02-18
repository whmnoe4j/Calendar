<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Gname extends Model
{
    protected $fillable = [
        'from', 'type', 'name','description','num','loves','views'];
}

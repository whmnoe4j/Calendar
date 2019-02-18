<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sentence extends Model
{
    protected $fillable = [
        'verse', 'title', 'author_id','author','shici_id','type'];
}

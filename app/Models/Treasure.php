<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treasure extends Model
{
    public $table = 'treasure';
    protected $guarded = ['created_at', 'update_at'];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ganen extends Model
{
    public $table = 'ganen';
    protected $guarded = ['created_at', 'update_at'];
}

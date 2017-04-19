<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Privilege extends Model
{
    public $table = 'privileges';
    protected $guarded = ['created_at', 'update_at'];
    //
}
